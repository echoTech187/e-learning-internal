<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDetailsToCourses extends Migration
{
    public function up()
    {
        $this->forge->addColumn('courses', [
            'requirements' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'target_audience' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'facilities' => [
                'type' => 'JSON',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('courses', ['requirements', 'target_audience', 'facilities']);
    }
}
