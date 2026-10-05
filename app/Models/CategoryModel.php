<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table            = 'categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id', 'parent_id', 'name', 'slug', 'icon', 'description', 'is_active'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getCategoriesWithChildren()
    {
        // Simple logic for fetching root categories and attaching children if needed
        $all = $this->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
        
        $nested = [];
        // Extract root categories
        foreach($all as $cat) {
            if (empty($cat['parent_id'])) {
                $cat['children'] = [];
                $nested[$cat['id']] = $cat;
            }
        }
        
        // Attach children to roots
        foreach($all as $cat) {
            if (!empty($cat['parent_id']) && isset($nested[$cat['parent_id']])) {
                $nested[$cat['parent_id']]['children'][] = $cat;
            }
        }
        
        return $nested;
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