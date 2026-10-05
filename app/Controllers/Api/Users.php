<?php
namespace App\Controllers\Api;
use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;

class Users extends ResourceController
{
    protected $modelName = 'App\Models\UserModel';
    protected $format    = 'json';

    public function find_by_email()
    {
        $email = $this->request->getGet('email');
        if (!$email) return $this->failValidationErrors('Mohon sertakan alamat email.');
        $user = $this->model->where('email', $email)->first();
        if (!$user) return $this->failNotFound('Mohon maaf, pengguna tidak ditemukan.');
        return $this->respond($user);
    }
    
    public function create()
    {
        $data = $this->request->getJSON(true);
        if ($this->model->insert($data)) {
            // UserModel uses UUID now, getInsertID() might not work for non-auto_increment
            // If beforeInsert generates it, we can get it from $data if it was passed, but it isn't.
            // Let's just find the user by email to return the id, or just return success
            $user = $this->model->where('email', $data['email'])->first();
            return $this->respondCreated(['id' => $user['id'] ?? null, 'message' => 'Akun berhasil didaftarkan.']);
        }
        return $this->failValidationErrors($this->model->errors());
    }

    public function onboarding()
    {
        $data = $this->request->getJSON(true);
        $userId = $data['user_id'] ?? null;
        $role = $data['role'] ?? null;
        $profileData = $data['profile'] ?? []; 

        if (!$userId || !$role) return $this->failValidationErrors('Mohon lengkapi data profil Anda.');

        $updateData = ['role' => $role];
        if (isset($profileData['phone_number'])) {
            $updateData['phone'] = $profileData['phone_number'];
        } elseif (isset($profileData['phone'])) {
            $updateData['phone'] = $profileData['phone'];
        }
        
        $this->model->update($userId, $updateData);

        $user = $this->model->find($userId);
        return $this->respond($user);
    }
}
