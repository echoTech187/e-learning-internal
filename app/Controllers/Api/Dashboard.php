<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class Dashboard extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        // Mock data for Phase 3 (will be replaced by real DB queries in Phase 4 & 7)
        $data = [
            'platform' => [
                'established' => 'May 13, 2022',
                'last_update' => date('M d, Y'),
                'data_center' => 'GCP Asia-Southeast2, Jakarta, Indonesia',
                'sla' => '99.9% Uptime SLA'
            ],
            'recent_activities' => [
                [
                    'icon' => 'fab fa-discord',
                    'icon_color' => '#000000',
                    'title' => 'Community forum',
                    'description' => 'Today, 11:00 AM'
                ],
                [
                    'icon' => 'fab fa-instagram',
                    'icon_color' => '#f97316',
                    'title' => 'Instagram comment',
                    'description' => 'Today, 10:40 AM'
                ],
                [
                    'icon' => 'fas fa-user-plus',
                    'icon_color' => '#2563eb',
                    'title' => 'New Mentor Reg',
                    'description' => 'Yesterday, 04:30 PM'
                ]
            ],
            'latest_revenue' => [
                'course_name' => 'Full-Stack Laravel',
                'amount' => 'Rp 550,000',
                'trx_id' => 'TRX-458905840958490',
                'payment_method' => 'Bank Transfer (BCA)',
                'last_update' => 'today, 01:45 PM',
                'timeline' => [
                    'created' => 'Sep 27',
                    'paid' => 'Sep 27',
                    'enrolled' => 'Sep 27',
                    'completed' => '-'
                ]
            ]
        ];

        return $this->respond($data);
    }
}
