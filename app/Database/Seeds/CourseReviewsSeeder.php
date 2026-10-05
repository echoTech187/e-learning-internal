<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CourseReviewsSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        
        $courses = $db->table('courses')->get()->getResultArray();
        $users = $db->table('users')->where('role', 'student')->get()->getResultArray();
        
        if (empty($users)) {
            echo "No student users found, skipping course reviews seeder.\n";
            return;
        }

        $reviews = [];
        $generateId = function() {
            return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        };

        foreach ($courses as $course) {
            // Pick a random user for each review
            for ($i = 0; $i < 3; $i++) {
                $user = $users[array_rand($users)];
                $reviews[] = [
                    'id' => $generateId(),
                    'user_id' => $user['id'],
                    'course_id' => $course['id'],
                    'rating' => rand(4, 5),
                    'review' => 'Materi tentang ' . $course['title'] . ' ini sangat daging! Mentor juga sangat responsif. Sangat direkomendasikan.',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        if (!empty($reviews)) {
            // Try truncating but if there are foreign key constraints it might fail, let's just delete
            $db->table('course_reviews')->emptyTable();
            $db->table('course_reviews')->insertBatch($reviews);
        }
    }
}
