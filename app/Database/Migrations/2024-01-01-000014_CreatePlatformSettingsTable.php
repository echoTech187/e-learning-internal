<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlatformSettingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'platform_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
                'default'    => 'EduNusa Platform',
            ],
            'phone_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'default'    => '+62 812-3456-7890',
            ],
            'website_url' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
                'default'    => 'edunusa.edu.id',
            ],
            'established_date' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'default'    => 'May 13, 2022',
            ],
            'data_center' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
                'default'    => 'GCP Asia-Southeast2, Jakarta, Indonesia',
            ],
            'is_suspended' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'is_muted' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'btn_suspend' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'btn_mute_alert' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'btn_stop_monitoring' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'btn_modules' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'btn_add_admin' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'btn_shut_down' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('platform_settings', true);
    }

    public function down()
    {
        $this->forge->dropTable('platform_settings');
    }
}
