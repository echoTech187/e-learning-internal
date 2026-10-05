<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CourseObjectivesSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        
        // Get all courses
        $courses = $db->table('courses')->get()->getResultArray();
        
        $objectives = [];
        foreach ($courses as $course) {
            $generateId = function() {
                return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            };

            $objectives[] = [
                'id' => $generateId(),
                'course_id' => $course['id'],
                'objective' => 'Memahami konsep dasar secara mendalam tentang ' . $course['title'],
                'sort_order' => 1
            ];
            $objectives[] = [
                'id' => $generateId(),
                'course_id' => $course['id'],
                'objective' => 'Mempraktikkan implementasi ' . $course['title'] . ' dalam studi kasus nyata',
                'sort_order' => 2
            ];
            $objectives[] = [
                'id' => $generateId(),
                'course_id' => $course['id'],
                'objective' => 'Menguasai best practice, arsitektur, dan optimasi standar industri',
                'sort_order' => 3
            ];
            $objectives[] = [
                'id' => $generateId(),
                'course_id' => $course['id'],
                'objective' => 'Siap berkarir sebagai profesional atau membangun portofolio solid',
                'sort_order' => 4
            ];
        }
        
        if (!empty($objectives)) {
            // clear existing to avoid duplicates if re-run
            $db->table('course_objectives')->truncate();
            $db->table('course_objectives')->insertBatch($objectives);
        }
    }
}
