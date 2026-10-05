<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CategoryModel;

class Categories extends ResourceController
{
    protected $modelName = CategoryModel::class;
    protected $format    = 'json';

    public function index()
    {
        $db = \Config\Database::connect();
        
        // Join with courses to get real count
        $builder = $db->table('categories c');
        $builder->select('c.id, c.name, c.slug, c.icon, c.description, c.is_active, COUNT(co.id) as course_count');
        $builder->join('courses co', 'co.category_id = c.id', 'left');
        $builder->where('c.is_active', 1);
        $builder->groupBy('c.id');
        
        $categories = $builder->get()->getResultArray();
        
        return $this->respond($categories);
    }
}
