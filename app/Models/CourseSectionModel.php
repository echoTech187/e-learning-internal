<?php

namespace App\Models;

use CodeIgniter\Model;

class CourseSectionModel extends Model
{
    protected $table      = 'course_sections';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';

    protected $allowedFields = [
        'id', 'course_id', 'title', 'description', 'order', 'created_at', 'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}