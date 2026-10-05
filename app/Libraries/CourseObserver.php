<?php

namespace App\Libraries;

class CourseObserver
{
    /**
     * Triggered when a student completes a course.
     * Usage in controller: \CodeIgniter\Events\Events::trigger('student_course_completed', $studentId, $courseId);
     */
    public static function onStudentCompletedCourse(string $studentId, string $courseId)
    {
        // 1. Generate certificate logic here
        log_message('info', "Student {$studentId} completed course {$courseId}. Generating certificate...");
        
        // 2. Update student progress / status logic here
        
        // 3. Send congratulatory email
    }

    /**
     * Triggered when a student enrolls in a course.
     * Usage in controller: \CodeIgniter\Events\Events::trigger('student_enrolled', $studentId, $courseId);
     */
    public static function onStudentEnrolled(string $studentId, string $courseId)
    {
        $db = \Config\Database::connect();
        
        // Increment the total_students count in the courses table dynamically
        $db->table('courses')
           ->where('id', $courseId)
           ->increment('total_students', 1);
           
        log_message('info', "Student {$studentId} enrolled in course {$courseId}. total_students incremented.");
    }
}
