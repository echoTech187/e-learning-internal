<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CourseDetailsSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('courses');

        $courses = $builder->get()->getResultArray();

        foreach ($courses as $course) {
            $builder->where('id', $course['id'])->update([
                'requirements' => json_encode([
                    "Komputer atau laptop (Windows, macOS, atau Linux) dengan spesifikasi standar.",
                    "Koneksi internet yang stabil untuk mengakses video materi dan mengunduh berkas aset.",
                    "Pemahaman dasar logika pemrograman atau ketertarikan tinggi untuk belajar dari dasar.",
                    "Semangat belajar mandiri dan komitmen untuk menyelesaikan seluruh proyek latihan terpadu."
                ]),
                'target_audience' => json_encode([
                    "Mahasiswa, Fresh Graduate, dan Pelajar yang ingin membangun portofolio keahlian siap kerja.",
                    "Junior Developer yang ingin meningkatkan level keahlian menuju Full-Stack Engineer.",
                    "Profesional yang ingin melakukan perpindahan karir (career switch) ke industri teknologi.",
                    "Praktisi yang ingin mendalami arsitektur modern berstandar enterprise B2B."
                ]),
                'facilities' => json_encode([
                    "Akses Selamanya & Seumur Hidup",
                    "Akses di Ponsel, Tablet & PC Desktop",
                    "Sertifikat Kelulusan Resmi EduNusa",
                    "Kuis Evaluasi & Proyek Latihan Mandiri",
                    "Forum Diskusi Tanya Jawab Bersama Mentor",
                    "Source Code Lengkap & Aset Template"
                ]),
                'rating' => 0,
                'total_students' => 0,
            ]);
        }
    }
}
