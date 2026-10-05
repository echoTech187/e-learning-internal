<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'code'            => 'EDUNUSA10',
                'description'     => 'Diskon 10% untuk semua kursus (Maks Rp 50.000)',
                'discount_type'   => 'percentage',
                'discount_amount' => 10,
                'min_purchase'    => 100000,
                'max_discount'    => 50000,
                'valid_from'      => date('Y-m-d H:i:s'),
                'valid_until'     => date('Y-m-d H:i:s', strtotime('+30 days')),
                'usage_limit'     => 1000,
                'used_count'      => 0,
                'status'          => 'active',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'code'            => 'DISKON50K',
                'description'     => 'Potongan harga langsung Rp 50.000',
                'discount_type'   => 'fixed',
                'discount_amount' => 50000,
                'min_purchase'    => 150000,
                'max_discount'    => 50000,
                'valid_from'      => date('Y-m-d H:i:s'),
                'valid_until'     => date('Y-m-d H:i:s', strtotime('+7 days')),
                'usage_limit'     => 500,
                'used_count'      => 0,
                'status'          => 'active',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'code'            => 'KILAT20',
                'description'     => 'Diskon kilat 20% (Tanpa maksimum diskon)',
                'discount_type'   => 'percentage',
                'discount_amount' => 20,
                'min_purchase'    => 200000,
                'max_discount'    => null,
                'valid_from'      => date('Y-m-d H:i:s'),
                'valid_until'     => date('Y-m-d H:i:s', strtotime('+1 days')),
                'usage_limit'     => 100,
                'used_count'      => 0,
                'status'          => 'active',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]
        ];

        // Insert data if table is empty
        if ($this->db->table('coupons')->countAllResults() == 0) {
            $this->db->table('coupons')->insertBatch($data);
        }
    }
}
