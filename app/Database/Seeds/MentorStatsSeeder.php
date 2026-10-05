<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\MentorModel;

class MentorStatsSeeder extends Seeder
{
    public function run()
    {
        $mentorModel = new MentorModel();
        
        $mentors = $mentorModel->findAll();
        foreach ($mentors as $mentor) {
            $mentorModel->update($mentor['id'], [
                'rating' => 4.9,
                'total_students' => 1250,
                'total_courses' => 8
            ]);
        }
        
        echo "Mentor stats seeded successfully!\n";
    }
}
