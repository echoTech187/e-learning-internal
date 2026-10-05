<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\OrderModel;
use App\Models\CourseModel;
use App\Models\UserModel;

class Transactions extends ResourceController
{
    public function create()
    {
        $json = $this->request->getJSON();
        
        $userId = $json->user_id ?? null;
        $courseId = $json->course_id ?? null;
        $couponCode = $json->coupon_code ?? null;
        
        if (!$userId || !$courseId) {
            return $this->failValidationErrors('user_id and course_id are required');
        }
        
        $courseModel = new CourseModel();
        $course = $courseModel->find($courseId);
        if (!$course) {
            return $this->failNotFound('Course not found');
        }
        
        $price = $course['price'];
        
        $db = \Config\Database::connect();
        
        // Settings
        $settings = $db->table('platform_settings')->limit(1)->get()->getRowArray();
        $serviceFee = $settings ? (float)($settings['service_fee'] ?? 0) : 0;
        
        $orderModel = new OrderModel();
        
        // Cek order pending sebelumnya
        $existingOrder = $orderModel->where('user_id', $userId)
                                    ->where('course_id', $courseId)
                                    ->whereIn('status', ['pending', 'draft'])
                                    ->first();
                                    
        $discountAmount = 0;
        if (!empty($couponCode)) {
            $coupon = $db->table('coupons')->where('code', $couponCode)->where('status', 'active')->get()->getRowArray();
            if (!$coupon) {
                return $this->failValidationErrors('Kupon tidak valid atau sudah tidak aktif');
            }
            
            // Prevent reuse
            $usedQuery = $db->table('orders')
                            ->where('user_id', $userId)
                            ->where('coupon_code', $couponCode)
                            ->whereIn('status', ['paid', 'pending', 'draft']); // Draft included so they don't abuse it in other pending carts
            if ($existingOrder) {
                $usedQuery->where('id !=', $existingOrder['id']);
            }
            $alreadyUsed = $usedQuery->countAllResults();
            if ($alreadyUsed > 0) {
                return $this->failValidationErrors('Kupon ini sudah pernah Anda gunakan sebelumnya');
            }
            
            // Calculate discount (based on frontend logic: base is price * 1.5, actual is price)
            // But from backend perspective, price is $course['price']
            $inflatedPrice = $price * 1.5;
            $frontendPotongan = $price * 0.5;
            
            if ($coupon['discount_type'] === 'percentage') {
                $couponDisc = ($inflatedPrice * $coupon['discount_amount']) / 100;
            } else {
                $couponDisc = $coupon['discount_amount'];
            }
            
            if ($coupon['max_discount'] > 0 && $couponDisc > $coupon['max_discount']) {
                $couponDisc = $coupon['max_discount'];
            }
            
            $discountAmount = $couponDisc;
        }

        $totalBasePrice = $price * 1.5;
        $totalDiscount = ($price * 0.5) + $discountAmount;
        $finalTotal = $totalBasePrice - $totalDiscount + $serviceFee;
        
        // Prevent negative total
        if ($finalTotal < 0) {
            $finalTotal = 0;
        }

        $orderCode = 'TRX-' . time() . '-' . rand(1000, 9999);
        
        $orderData = [
            'order_code' => $orderCode,
            'user_id' => $userId,
            'course_id' => $courseId,
            'amount' => $price,
            'discount' => $discountAmount,
            'coupon_code' => $couponCode,
            'total' => $finalTotal,
            'status' => 'draft',
            'payment_method' => null,
            'payment_proof' => null,
            'payment_date' => null,
            'snap_token' => null
        ];
        if ($existingOrder) {
            // Update created_at so the frontend shows the correct new transaction time
            $orderData['created_at'] = date('Y-m-d H:i:s');
            $orderModel->update($existingOrder['id'], $orderData);
            $orderData['id'] = $existingOrder['id'];
        } else {
            $orderData['id'] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $orderModel->insert($orderData);
        }
        
        \Midtrans\Config::$serverKey = getenv('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = false;
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
        
        $userModel = new UserModel();
        $user = $userModel->find($userId);
        
        $params = [
            'transaction_details' => [
                'order_id' => $orderCode,
                'gross_amount' => $finalTotal,
            ],
            'customer_details' => [
                'first_name' => $user['name'] ?? 'User',
                'email' => $user['email'] ?? '',
                'phone' => $user['phone'] ?? '',
            ],
            'expiry' => [
                'start_time' => date("Y-m-d H:i:s O"),
                'unit' => 'minute',
                'duration' => 60
            ]
        ];
        
        try {
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            $orderModel->update($orderData['id'], ['snap_token' => $snapToken, 'midtrans_request' => json_encode($params)]);
            
            return $this->respond([
                'status' => 'success',
                'data' => [
                    'id' => $orderData['id'],
                    'order_code' => $orderCode,
                    'snap_token' => $snapToken
                ]
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    public function status($orderCode)
    {
        $orderModel = new OrderModel();
        $order = $orderModel->where('order_code', $orderCode)->first();
        
        if (!$order) {
            return $this->failNotFound('Order not found');
        }

        if ($order['status'] === 'paid') {
            return $this->respond(['status' => 'paid']);
        }

        if ($order['status'] === 'expired') {
            return $this->respond(['status' => 'expired']);
        }

        // Cek jika transaksi pending sudah lebih dari 1 jam (3600 detik)
        $createdAt = !empty($order['created_at']) ? strtotime($order['created_at']) : 0;
        if ($createdAt > 0 && (time() - $createdAt) > 3600 && $order['status'] === 'pending') {
            $orderModel->update($order['id'], ['status' => 'expired']);
            try {
                \Midtrans\Config::$serverKey = getenv('MIDTRANS_SERVER_KEY');
                \Midtrans\Config::$isProduction = false;
                \Midtrans\Transaction::cancel($orderCode);
            } catch (\Exception $ex) {}
            return $this->respond(['status' => 'expired']);
        }

        \Midtrans\Config::$serverKey = getenv('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = false;
        
        try {
            $statusResponse = \Midtrans\Transaction::status($orderCode);
            $transactionStatus = $statusResponse->transaction_status;
            $orderModel->update($order['id'], ['midtrans_response' => json_encode($statusResponse)]);
            $type = $statusResponse->payment_type;
            
            if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
                $orderModel->update($order['id'], [
                    'status' => 'paid',
                    'payment_method' => $type,
                    'payment_date' => date('Y-m-d H:i:s')
                ]);
                
                // Auto-Enrollment
                $db = \Config\Database::connect();
                $enrollmentId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
                
                $db->table('enrollments')->insert([
                    'id' => $enrollmentId,
                    'user_id' => $order['user_id'],
                    'course_id' => $order['course_id'],
                    'order_id' => $order['id'],
                    'progress' => 0.00,
                    'enrolled_at' => date('Y-m-d H:i:s')
                ]);

                \CodeIgniter\Events\Events::trigger('student_enrolled', $order['user_id'], $order['course_id']);

                return $this->respond(['status' => 'paid']);
            } else if ($transactionStatus == 'expire') {
                $orderModel->update($order['id'], ['status' => 'expired']);
                return $this->respond(['status' => 'expired']);
            } else if ($transactionStatus == 'cancel') {
                $orderModel->update($order['id'], ['status' => 'cancelled']);
                return $this->respond(['status' => 'cancelled']);
            }
            
            if ($transactionStatus == 'pending' && !empty($type) && (empty($order['payment_method']) || $order['status'] == 'draft')) {
                $orderModel->update($order['id'], ['status' => 'pending', 'payment_method' => $type]);
            }
            
            return $this->respond([
                'status' => $order['status'],
                'midtrans_data' => $statusResponse
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Midtrans Status Error: ' . $e->getMessage() . ' for order: ' . $orderCode);
            // Midtrans will throw 404 if transaction is not found or not paid yet in some cases
            return $this->respond(['status' => $order['status'], 'message' => $e->getMessage()]);
        }
    }
    public function enrollments($userId)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('enrollments');
        $builder->select('enrollments.*, courses.title as course_title, courses.slug as course_slug, courses.thumbnail as course_thumbnail');
        $builder->join('courses', 'courses.id = enrollments.course_id', 'left');
        $builder->where('enrollments.user_id', $userId);
        $enrollments = $builder->get()->getResultArray();
        return $this->respond(['success' => true, 'data' => $enrollments]);
    }

    public function userOrders($userId)
    {
        $start = microtime(true);
        $db = \Config\Database::connect();

        // Only check and update if user actually has pending orders
        $pendingCount = $db->table('orders')->where('user_id', $userId)->whereIn('status', ['pending', 'draft'])->countAllResults(false);
        if ($pendingCount > 0) {
            $db->table('orders')
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'draft'])
                ->groupStart()
                    ->where('created_at IS NULL')
                    ->orWhere('created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)')
                ->groupEnd()
                ->update(['status' => 'expired']);
        }

        $limit = (int) ($this->request->getGet('limit') ?? 50);
        if ($limit <= 0 || $limit > 100) {
            $limit = 50;
        }

        $builder = $db->table('orders');
        $builder->select('orders.*, courses.title as course_title, courses.slug as course_slug, courses.thumbnail as course_thumbnail, mentors.name as instructor_name');
        $builder->join('courses', 'courses.id = orders.course_id', 'left');
        $builder->join('mentors', 'mentors.id = courses.instructor_id', 'left');
        $builder->where('orders.user_id', $userId);
        $builder->orderBy('orders.created_at', 'DESC');
        $builder->limit($limit);
        $orders = $builder->get()->getResultArray();
        $time = microtime(true) - $start;
        return $this->respond(['success' => true, 'data' => $orders, 'debug_time' => $time]);
    }

    public function autoExpire()
    {
        $db = \Config\Database::connect();

        // Step 1: Cancel incomplete pending orders (created_at IS NULL)
        $cancelBuilder = $db->table('orders');
        $cancelBuilder->whereIn('status', ['pending', 'draft']);
        $cancelBuilder->where('created_at IS NULL');
        $cancelledCount = $cancelBuilder->countAllResults(false);
        $cancelBuilder->update(['status' => 'cancelled']);

        // Step 2: Expire pending orders older than 1 hour
        $builder = $db->table('orders');
        $builder->whereIn('status', ['pending', 'draft']);
        $builder->where('created_at IS NOT NULL');
        $builder->where('created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)');
        $affected = $builder->countAllResults(false);
        $builder->update(['status' => 'expired']);

        return $this->respond([
            'success' => true,
            'message' => "Auto-expire service executed. $affected order(s) expired, $cancelledCount order(s) cancelled (incomplete data).",
            'expired_count' => $affected,
            'cancelled_count' => $cancelledCount
        ]);
    }

    public function webhook()
    {
        $json = $this->request->getJSON();
        if (!$json || empty($json->order_id)) {
            return $this->fail('Invalid payload');
        }
        
        $orderCode = $json->order_id;
        
        // We simply reuse the status() logic which securely fetches the real status from Midtrans
        // This makes it immune to spoofed webhook payloads because we don't trust the payload,
        // we only use the order_id to trigger a secure server-to-server check.
        
        return $this->status($orderCode);
    }

}











