<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\SupportTicketModel;

class SupportTickets extends BaseController
{
    use ResponseTrait;

    public function escalate()
    {
        $data = $this->request->getJSON();

        if (!isset($data->category) || !isset($data->priority) || !isset($data->role)) {
            return $this->failValidationErrors('Category, priority, and role are required.');
        }

        $ticketModel = new SupportTicketModel();
        
        $insertData = [
            'user_id'       => $data->user_id ?? null,
            'category'      => $data->category,
            'priority'      => $data->priority,
            'assigned_role' => $data->role,
            'description'   => $data->description ?? 'Eskalasi otomatis dari EduBot',
            'status'        => 'OPEN'
        ];

        if ($ticketModel->insert($insertData)) {
            return $this->respondCreated([
                'status'    => 'success',
                'message'   => 'Ticket created successfully',
                'ticket_id' => $ticketModel->getInsertID()
            ]);
        }

        return $this->failServerError('Gagal membuat tiket bantuan.');
    }
}
