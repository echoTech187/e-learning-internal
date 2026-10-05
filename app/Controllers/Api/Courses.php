<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CourseModel;

class Courses extends ResourceController
{
    public function index()
    {
        $model = new CourseModel();
        $courses = $model->getCourseDetails();
        return $this->respond(['success' => true, 'data' => $courses]);
    }

    public function show($slug = null)
    {
        $model = new CourseModel();
        $course = $model->getCourseDetails($slug);
        if (!$course) {
            return $this->failNotFound('Course not found');
        }
        return $this->respond(['success' => true, 'data' => $course]);
    }
}
