<?php

namespace App\Models;

use CodeIgniter\Model;

class CourseModel extends Model
{
    protected $table            = 'courses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id', 'instructor_id', 'category_id', 'title', 'slug', 'description', 
        'thumbnail', 'trailer_video', 'price', 'discount_price', 'level', 
        'language', 'status', 'is_featured', 'total_students', 'total_lessons', 
        'total_duration', 'rating', 'requirements', 'target_audience', 'facilities'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Get detailed course information, joining with categories and mentors
     */
    public function getCourseDetails($slug = null)
    {
        $builder = $this->db->table($this->table . ' c');
        $builder->select('c.*, cat.name as category_name, m.name as instructor_name, m.photo as instructor_photo, m.bio as instructor_bio, m.expertise as instructor_role');
        $builder->select('(SELECT COUNT(id) FROM courses WHERE instructor_id = c.instructor_id AND deleted_at IS NULL) as instructor_courses');
        $builder->select('(SELECT COALESCE(SUM(total_students), 0) FROM courses WHERE instructor_id = c.instructor_id AND deleted_at IS NULL) as instructor_students');
        $builder->select('(SELECT COALESCE(AVG(rating), 0) FROM courses WHERE instructor_id = c.instructor_id AND rating > 0 AND deleted_at IS NULL) as instructor_rating');
        $builder->join('categories cat', 'cat.id = c.category_id', 'left');
        $builder->join('mentors m', 'm.id = c.instructor_id', 'left');
        $builder->where('c.deleted_at', null);

        if ($slug) {
            $builder->where('c.slug', $slug);
            return $builder->get()->getRowArray();
        }

        return $builder->get()->getResultArray();
    }

    protected $beforeInsert = ['generateId'];

    protected function generateId(array $data)
    {
        if (empty($data['data']['id'])) {
            $data['data']['id'] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        }
        return $data;
    }
}


