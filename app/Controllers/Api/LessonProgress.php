<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\LessonProgressModel;
use App\Models\EnrollmentModel;
use App\Models\LessonModel;
use App\Models\CourseSectionModel;

/**
 * LessonProgress Controller (Internal)
 * POST /api/lesson-progress  -> Mark lesson as completed / update watch_time
 */
class LessonProgress extends ResourceController
{
    public function create()
    {
        $json      = $this->request->getJSON();
        $userId    = $json->user_id    ?? null;
        $lessonId  = $json->lesson_id  ?? null;
        $completed = $json->is_completed ?? false;
        $watchTime = $json->watch_time ?? 0;

        if (!$userId || !$lessonId) {
            return $this->failValidationErrors('user_id and lesson_id are required');
        }

        $progressModel = new LessonProgressModel();
        $existing = $progressModel
            ->where('user_id', $userId)
            ->where('lesson_id', $lessonId)
            ->first();

        $now = date('Y-m-d H:i:s');

        if ($existing) {
            // Update only if not already completed (prevent regression)
            $updateData = ['watch_time' => max($existing['watch_time'], (int) $watchTime)];
            if (!$existing['is_completed'] && $completed) {
                $updateData['is_completed']  = 1;
                $updateData['completed_at']  = $now;
            }
            $progressModel->update($existing['id'], $updateData);
            $record = $progressModel->find($existing['id']);
        } else {
            $newId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $progressModel->insert([
                'id'           => $newId,
                'user_id'      => $userId,
                'lesson_id'    => $lessonId,
                'is_completed' => $completed ? 1 : 0,
                'watch_time'   => (int) $watchTime,
                'completed_at' => $completed ? $now : null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
            $record = $progressModel->find($newId);
        }

        // Recalculate enrollment progress
        $this->recalculateEnrollmentProgress($userId, $lessonId);

        return $this->respondCreated([
            'status' => 'success',
            'data'   => $record,
        ]);
    }

    private function recalculateEnrollmentProgress(string $userId, string $lessonId): void
    {
        // Find which course this lesson belongs to
        $lessonModel  = new LessonModel();
        $sectionModel = new CourseSectionModel();

        $lesson  = $lessonModel->find($lessonId);
        if (!$lesson) return;

        $section = $sectionModel->find($lesson['section_id']);
        if (!$section) return;

        $courseId = $section['course_id'];

        // Count all published lessons in course
        $allSectionIds = array_column(
            $sectionModel->where('course_id', $courseId)->findAll(),
            'id'
        );

        if (empty($allSectionIds)) return;

        $lessonModel2   = new LessonModel();
        $totalLessons   = $lessonModel2
            ->whereIn('section_id', $allSectionIds)
            ->where('is_published', 1)
            ->countAllResults();

        if ($totalLessons === 0) return;

        // Count completed lessons by this user
        $allLessonIds = array_column(
            $lessonModel2->whereIn('section_id', $allSectionIds)->where('is_published', 1)->findAll(),
            'id'
        );

        $progressModel = new LessonProgressModel();
        $completed     = $progressModel
            ->where('user_id', $userId)
            ->whereIn('lesson_id', $allLessonIds)
            ->where('is_completed', 1)
            ->countAllResults();

        $percent = round(($completed / $totalLessons) * 100, 2);

        $enrollmentModel = new EnrollmentModel();
        $enrollment      = $enrollmentModel
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        if ($enrollment) {
            $updateData = ['progress' => $percent];
            if ($percent >= 100) {
                $updateData['completed_at'] = date('Y-m-d H:i:s');
            }
            $enrollmentModel->update($enrollment['id'], $updateData);
        }
    }
}