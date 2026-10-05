<?php

namespace App\Models;

use CodeIgniter\Model;

class QuizAnswerModel extends Model
{
    protected $table      = 'quiz_answers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';

    protected $allowedFields = [
        'id', 'attempt_id', 'question_id', 'answer', 'is_correct'
    ];

    protected $useTimestamps = false;
}