<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CourseContentSeeder extends Seeder
{
    private function genUuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    public function run()
    {
        $db = \Config\Database::connect();
        $builderSections = $db->table('course_sections');
        $builderLessons = $db->table('lessons');

        // Course 1: Mastering React & Next.js
        $course1Id = 'c6c6f868-bb30-11f1-be91-6afdfb41063e';
        
        $c1Section1Id = $this->genUuid();
        $builderSections->insert([
            'id' => $c1Section1Id,
            'course_id' => $course1Id,
            'title' => 'Pengenalan React & Next.js',
            'order' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $builderLessons->insertBatch([
            [
                'id' => $this->genUuid(),
                'section_id' => $c1Section1Id,
                'title' => 'Apa itu React dan Next.js?',
                'type' => 'youtube',
                'content' => 'https://www.youtube.com/watch?v=SqcY0GlETPk',
                'description' => 'Mengenal fundamental React dan keunggulan Next.js untuk aplikasi web.',
                'duration' => 15,
                'is_free' => 1,
                'is_published' => 1,
                'order' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => $this->genUuid(),
                'section_id' => $c1Section1Id,
                'title' => 'Setup Environment & Instalasi',
                'type' => 'article',
                'content' => '<h3>Persiapan Lingkungan Kerja</h3><p>Pastikan Anda telah menginstal Node.js versi 18+. Anda bisa menggunakan npm, yarn, atau pnpm.</p>',
                'description' => 'Cara menginstal Next.js menggunakan create-next-app.',
                'duration' => 10,
                'is_free' => 1,
                'is_published' => 1,
                'order' => 2,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);

        $c1Section2Id = $this->genUuid();
        $builderSections->insert([
            'id' => $c1Section2Id,
            'course_id' => $course1Id,
            'title' => 'Fundamental React Components',
            'order' => 2,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $builderLessons->insertBatch([
            [
                'id' => $this->genUuid(),
                'section_id' => $c1Section2Id,
                'title' => 'State & Props',
                'type' => 'youtube',
                'content' => 'https://www.youtube.com/watch?v=bMknfKXIFA8',
                'description' => 'Memahami manajemen data internal dan eksternal komponen.',
                'duration' => 25,
                'is_free' => 0,
                'is_published' => 1,
                'order' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);

        // Course 2: Full-Stack Web Developer dengan Laravel & Vue
        $course2Id = 'b0def314-cf50-4d76-a6a1-792624d81888';
        
        $c2Section1Id = $this->genUuid();
        $builderSections->insert([
            'id' => $c2Section1Id,
            'course_id' => $course2Id,
            'title' => 'Membangun API dengan Laravel',
            'order' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $builderLessons->insertBatch([
            [
                'id' => $this->genUuid(),
                'section_id' => $c2Section1Id,
                'title' => 'Membuat RESTful API',
                'type' => 'youtube',
                'content' => 'https://www.youtube.com/watch?v=ImtZ5yENzgE',
                'description' => 'Pembuatan resource controller di Laravel.',
                'duration' => 30,
                'is_free' => 1,
                'is_published' => 1,
                'order' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
        
        // Update total_lessons on course
        $db->query("UPDATE courses SET total_lessons = 3 WHERE id = '{$course1Id}'");
        $db->query("UPDATE courses SET total_lessons = 1 WHERE id = '{$course2Id}'");
    }
}
