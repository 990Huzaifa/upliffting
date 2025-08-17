<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $customerMessaging;
    protected $riderMessaging;

    public function __construct()
    {
        // Initialize Customer Firebase
        $customerFactory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/customer-firebase-credentials.json'));
        $this->customerMessaging = $customerFactory->createMessaging();

        // Initialize Rider Firebase
        $riderFactory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/rider-firebase-credentials.json'));
        $this->riderMessaging = $riderFactory->createMessaging();
    }

    /**
     * Get messaging instance based on app type
     */
    private function getMessagingInstance($appType)
    {
        switch (strtolower($appType)) {
            case 'customer':
                return $this->customerMessaging;
            case 'rider':
                return $this->riderMessaging;
            default:
                throw new \InvalidArgumentException("Invalid app type: {$appType}. Use 'customer' or 'rider'.");
        }
    }

    /**
     * Send notification to single device
     */
    public function sendToDevice($appType, $fcmToken, $title, $body, $data = [])
    {
        try {
            $messaging = $this->getMessagingInstance($appType);
            
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $result = $messaging->send($message);
            
            Log::info('FCM notification sent successfully', [
                'app_type' => $appType,
                'token' => $fcmToken,
                'result' => $result
            ]);
            
            return [
                'success' => true,
                'result' => $result,
                'app_type' => $appType
            ];
            
        } catch (\Exception $e) {
            Log::error('FCM notification failed', [
                'app_type' => $appType,
                'token' => $fcmToken,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'app_type' => $appType
            ];
        }
    }

    /**
     * Send notification to multiple devices of same app type
     */
    public function sendToMultipleDevices($appType, $fcmTokens, $title, $body, $data = [])
    {
        try {
            $messaging = $this->getMessagingInstance($appType);
            
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $result = $messaging->sendMulticast($message, $fcmTokens);
            
            Log::info('FCM multicast notification sent', [
                'app_type' => $appType,
                'tokens_count' => count($fcmTokens),
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count()
            ]);
            
            return [
                'success' => true,
                'result' => $result,
                'app_type' => $appType,
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count()
            ];
            
        } catch (\Exception $e) {
            Log::error('FCM multicast notification failed', [
                'app_type' => $appType,
                'tokens_count' => count($fcmTokens),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'app_type' => $appType
            ];
        }
    }

    /**
     * Send notification to topic of specific app
     */
    public function sendToTopic($appType, $topic, $title, $body, $data = [])
    {
        try {
            $messaging = $this->getMessagingInstance($appType);
            
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $result = $messaging->send($message);
            
            Log::info('FCM topic notification sent successfully', [
                'app_type' => $appType,
                'topic' => $topic,
                'result' => $result
            ]);
            
            return [
                'success' => true,
                'result' => $result,
                'app_type' => $appType
            ];
            
        } catch (\Exception $e) {
            Log::error('FCM topic notification failed', [
                'app_type' => $appType,
                'topic' => $topic,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'app_type' => $appType
            ];
        }
    }

    /**
     * Send to all customers
     */
    public function sendToAllCustomers($title, $body, $data = [])
    {
        // Get all customer FCM tokens from database
        $customerTokens = \App\Models\User::where('user_type', 'customer')
            ->whereNotNull('fcm_id')
            ->pluck('fcm_id')
            ->toArray();

        if (empty($customerTokens)) {
            return ['success' => false, 'error' => 'No customer tokens found'];
        }

        return $this->sendToMultipleDevices('customer', $customerTokens, $title, $body, $data);
    }

    /**
     * Send to all riders
     */
    public function sendToAllRiders($title, $body, $data = [])
    {
        // Get all rider FCM tokens from database
        $riderTokens = \App\Models\User::where('user_type', 'rider')
            ->whereNotNull('fcm_id')
            ->pluck('fcm_id')
            ->toArray();

        if (empty($riderTokens)) {
            return ['success' => false, 'error' => 'No rider tokens found'];
        }

        return $this->sendToMultipleDevices('rider', $riderTokens, $title, $body, $data);
    }

    /**
     * Subscribe token to topic
     */
    public function subscribeToTopic($appType, $fcmTokens, $topic)
    {
        try {
            $messaging = $this->getMessagingInstance($appType);
            $result = $messaging->subscribeToTopic($topic, $fcmTokens);
            return ['success' => true, 'result' => $result, 'app_type' => $appType];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'app_type' => $appType];
        }
    }

    /**
     * Unsubscribe token from topic
     */
    public function unsubscribeFromTopic($appType, $fcmTokens, $topic)
    {
        try {
            $messaging = $this->getMessagingInstance($appType);
            $result = $messaging->unsubscribeFromTopic($topic, $fcmTokens);
            return ['success' => true, 'result' => $result, 'app_type' => $appType];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'app_type' => $appType];
        }
    }

    /**
     * Send order notification to both customer and rider
     */
    public function sendOrderNotification($customerId, $riderId, $orderData)
    {
        $results = [];

        // Send to customer
        $customer = \App\Models\User::find($customerId);
        if ($customer && $customer->fcm_id) {
            $results['customer'] = $this->sendToDevice(
                'customer',
                $customer->fcm_id,
                'Order Update',
                "Your order #{$orderData['order_id']} has been updated.",
                array_merge($orderData, ['notification_type' => 'order_update'])
            );
        }

        // Send to rider
        $rider = \App\Models\User::find($riderId);
        if ($rider && $rider->fcm_id) {
            $results['rider'] = $this->sendToDevice(
                'rider',
                $rider->fcm_id,
                'New Order Assignment',
                "You have been assigned order #{$orderData['order_id']}.",
                array_merge($orderData, ['notification_type' => 'new_order'])
            );
        }

        return $results;
    }
}