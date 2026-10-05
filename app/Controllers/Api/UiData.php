<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class UiData extends ResourceController
{
    public function testimonials()
    {
        $db = \Config\Database::connect();
        $query = $db->table('testimonials')->get();
        return $this->respond(['success' => true, 'data' => $query->getResultArray()]);
    }

    public function platformSettings()
    {
        $db = \Config\Database::connect();
        $query = $db->table('platform_settings')->limit(1)->get()->getRowArray();
        return $this->respond(['success' => true, 'data' => $query]);
    }

    public function coupons()
    {
        $db = \Config\Database::connect();
        $query = $db->table('coupons')->where('status', 'active')->get()->getResultArray();
        return $this->respond(['success' => true, 'data' => $query]);
    }

    public function stats()
    {
        $db = \Config\Database::connect();

        $studentsCount    = $db->table('users')->where('role', 'student')->countAllResults();
        $instructorsCount = $db->table('users')->where('role', 'instructor')->countAllResults();
        $coursesCount     = $db->table('courses')->countAllResults();

        $ratingQuery   = $db->table('course_reviews')->selectAvg('rating')->get()->getRowArray();
        $averageRating = (isset($ratingQuery['rating']) && $ratingQuery['rating'] > 0)
            ? round($ratingQuery['rating'], 1) . '/5'
            : '0/5';

        $formatNumber = function ($num) {
            if ($num >= 1000000) return round($num / 1000000, 1) . 'M+';
            if ($num >= 1000)    return round($num / 1000, 1) . 'K+';
            return $num > 0 ? $num . '+' : '0';
        };

        // Load all site_metrics as flat key=>value map
        $metricsRaw = $db->table('site_metrics')->get()->getResultArray();
        $data = [];
        foreach ($metricsRaw as $m) {
            $data[$m['key']] = $m['value'];
        }

        // Merge with auto-calculated counts
        $data['students_count']    = $formatNumber($studentsCount);
        $data['courses_count']     = $formatNumber($coursesCount);
        $data['instructors_count'] = $formatNumber($instructorsCount);
        $data['average_rating']    = $averageRating;

        return $this->respond(['success' => true, 'data' => $data]);
    }

    public function courseDetail($courseId = null)
    {
        if (!$courseId) return $this->failNotFound('course_id required');
        $db = \Config\Database::connect();

        $objectives = $db->table('course_objectives')
            ->where('course_id', $courseId)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();

        $sections = $db->table('course_curricula')
            ->where('course_id', $courseId)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();

        foreach ($sections as &$section) {
            $section['items'] = $db->table('course_curriculum_items')
                ->where('curricula_id', $section['id'])
                ->orderBy('sort_order', 'ASC')
                ->get()->getResultArray();
        }

        $reviews = $db->table('course_reviews')
            ->select('course_reviews.id, course_reviews.rating, course_reviews.review as comment, course_reviews.created_at as date, users.name')
            ->join('users', 'users.id = course_reviews.user_id')
            ->where('course_reviews.course_id', $courseId)
            ->orderBy('course_reviews.created_at', 'DESC')
            ->get()->getResultArray();

        return $this->respond([
            'success' => true,
            'data'    => [
                'objectives' => $objectives,
                'curricula'  => $sections,
                'reviews'    => $reviews,
            ]
        ]);
    }
}
