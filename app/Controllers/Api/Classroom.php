<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\EnrollmentModel;
use App\Models\CourseSectionModel;
use App\Models\LessonModel;
use App\Models\LessonProgressModel;
use App\Models\CourseModel;

/**
 * Classroom Controller (Internal)
 * Endpoint: GET /api/classroom/{enrollmentId}?user_id={userId}
 * Returns full course curriculum with per-user lesson progress.
 */
class Classroom extends ResourceController
{
    public function show($enrollmentId = null)
    {
        $userId = $this->request->getGet('user_id');

        if (!$enrollmentId || !$userId) {
            return $this->failValidationErrors('enrollment_id and user_id are required');
        }

        $enrollmentModel = new EnrollmentModel();
        $enrollment = $enrollmentModel
            ->where('id', $enrollmentId)
            ->where('user_id', $userId)
            ->first();

        if (!$enrollment) {
            return $this->failNotFound('Enrollment not found or access denied');
        }

        $courseModel     = new CourseModel();
        $sectionModel    = new CourseSectionModel();
        $lessonModel     = new LessonModel();
        $progressModel   = new LessonProgressModel();

        $course   = $courseModel->find($enrollment['course_id']);
        $sections = $sectionModel
            ->where('course_id', $enrollment['course_id'])
            ->orderBy('order', 'ASC')
            ->findAll();

        // Collect all lesson IDs for this course
        $allLessonIds  = [];
        $enrichedSections = [];

        foreach ($sections as $section) {
            $lessons = $lessonModel
                ->where('section_id', $section['id'])
                ->where('is_published', 1)
                ->orderBy('order', 'ASC')
                ->findAll();

            foreach ($lessons as $lesson) {
                $allLessonIds[] = $lesson['id'];
            }

            $section['lessons'] = $lessons;
            $enrichedSections[] = $section;
        }

        // Fetch user progress for all lessons at once
        $progressMap = [];
        if (!empty($allLessonIds)) {
            $progressRecords = $progressModel
                ->where('user_id', $userId)
                ->whereIn('lesson_id', $allLessonIds)
                ->findAll();

            foreach ($progressRecords as $p) {
                $progressMap[$p['lesson_id']] = $p;
            }
        }

        $totalLessons     = count($allLessonIds);
        $completedLessons = count(array_filter($progressMap, fn($p) => $p['is_completed']));
        $progressPercent  = $totalLessons > 0
            ? round(($completedLessons / $totalLessons) * 100, 2)
            : 0;

        // Attach progress to each lesson
        foreach ($enrichedSections as &$section) {
            foreach ($section['lessons'] as &$lesson) {
                $lesson['progress'] = $progressMap[$lesson['id']] ?? null;
                $lesson['is_completed'] = isset($progressMap[$lesson['id']])
                    ? (bool) $progressMap[$lesson['id']]['is_completed']
                    : false;
            }
        }

        return $this->respond([
            'status'  => 'success',
            'data'    => [
                'enrollment'       => $enrollment,
                'course'           => $course,
                'sections'         => $enrichedSections,
                'progress_summary' => [
                    'total_lessons'     => $totalLessons,
                    'completed_lessons' => $completedLessons,
                    'percent'           => $progressPercent,
                ],
            ],
        ]);
    }
}