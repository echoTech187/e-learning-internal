<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatsToMentors extends Migration
{
    public function up()
    {
        $fields = [
            'rating' => [
                'type' => 'DECIMAL',
                'constraint' => '3,2',
                'default' => 4.9,
                'null' => true
            ],
            'total_students' => [
                'type' => 'INT',
                'default' => 0,
                'null' => true
            ],
            'total_courses' => [
                'type' => 'INT',
                'default' => 0,
                'null' => true
            ]
        ];

        $this->forge->addColumn('mentors', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('mentors', 'rating');
        $this->forge->dropColumn('mentors', 'total_students');
        $this->forge->dropColumn('mentors', 'total_courses');
    }
}
