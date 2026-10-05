<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\OrderModel;

class Webhooks extends ResourceController
{
    public function midtrans()
    {
        $serverKey = env('MIDTRANS_SERVER_KEY', '');
        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$isProduction = false;

        try {
            $notif = new \Midtrans\Notification();
        } catch (\Exception $e) {
            log_message('error', 'Midtrans webhook parse error: ' . $e->getMessage());
            return $this->respond(['status' => 'ok', 'message' => 'Received'], 200);
        }

        $transaction = $notif->transaction_status;
        $type        = $notif->payment_type;
        $orderId     = $notif->order_id;
        $fraud       = $notif->fraud_status;

        $orderModel = new OrderModel();
        $order = $orderModel->where('order_code', $orderId)->first();

        if (!$order) {
            return $this->respond(['status' => 'ok', 'message' => 'Order not found, skipped'], 200);
        }

        if ($transaction == 'capture') {
            $status = ($type == 'credit_card' && $fraud == 'challenge') ? 'pending' : 'paid';
        } elseif ($transaction == 'settlement') {
            $status = 'paid';
        } elseif ($transaction == 'pending') {
            $status = 'pending';
        } elseif ($transaction == 'deny') {
            $status = 'failed';
        } elseif ($transaction == 'expire') {
            $status = 'failed';
        } elseif ($transaction == 'cancel') {
            $status = 'cancelled';
        } else {
            $status = $order['status'];
        }

        $orderModel->update($order['id'], [
            'status'         => $status,
            'payment_method' => $type,
            'payment_date'   => date('Y-m-d H:i:s'),
        ]);

        return $this->respond(['status' => 'success', 'message' => 'Notification processed']);
    }
}
