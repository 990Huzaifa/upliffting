<?php

namespace App\Services;

use App\Models\GeneralAnnouncement;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AnnouncementNotificationService
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Send announcement notification
     */
    public function sendAnnouncement(GeneralAnnouncement $announcement)
    {
        try {
            if (!$announcement->can_send) {
                return [
                    'success' => false,
                    'error' => 'Announcement is not ready to send'
                ];
            }

            Log::info("Starting to send announcement: {$announcement->id}");

            $results = [];

            switch ($announcement->audience) {
                case 'riders':
                    $results = $this->sendToRiders($announcement);
                    break;
                case 'customers':
                    $results = $this->sendToCustomers($announcement);
                    break;
                case 'all':
                    $results = $this->sendToAll($announcement);
                    break;
            }

            // Mark as sent if successful
            if ($results['success']) {
                $announcement->markAsSent();
            }

            return $results;

        } catch (\Exception $e) {
            Log::error("Failed to send announcement {$announcement->id}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send to all riders
     */
    protected function sendToRiders(GeneralAnnouncement $announcement)
    {
        $riderTokens = User::where('role', 'rider')
            ->whereNotNull('fcm_id')
            ->pluck('fcm_id')
            ->filter()
            ->toArray();

        if (empty($riderTokens)) {
            return [
                'success' => false,
                'error' => 'No rider tokens found'
            ];
        }

        return $this->firebaseService->sendToMultipleDevices(
            'rider',
            $riderTokens,
            $announcement->title,
            $announcement->message ?? 'New announcement available',
            $announcement->getNotificationData()
        );
    }

    /**
     * Send to all customers
     */
    protected function sendToCustomers(GeneralAnnouncement $announcement)
    {
        $customerTokens = User::where('role', 'customer')
            ->whereNotNull('fcm_id')
            ->pluck('fcm_id')
            ->filter()
            ->toArray();

        if (empty($customerTokens)) {
            return [
                'success' => false,
                'error' => 'No customer tokens found'
            ];
        }

        return $this->firebaseService->sendToMultipleDevices(
            'customer',
            $customerTokens,
            $announcement->title,
            $announcement->message ?? 'New announcement available',
            $announcement->getNotificationData()
        );
    }

    /**
     * Send to all users (both riders and customers)
     */
    protected function sendToAll(GeneralAnnouncement $announcement)
    {
        $results = [
            'success' => true,
            'rider_result' => null,
            'customer_result' => null,
            'total_success' => 0,
            'total_failure' => 0
        ];

        // Send to riders
        $riderTokens = User::where('role', 'rider')
            ->whereNotNull('fcm_id')
            ->pluck('fcm_id')
            ->filter()
            ->toArray();

        if (!empty($riderTokens)) {
            $riderResult = $this->firebaseService->sendToMultipleDevices(
                'rider',
                $riderTokens,
                $announcement->title,
                $announcement->message ?? 'New announcement available',
                $announcement->getNotificationData()
            );
            $results['rider_result'] = $riderResult;
            
            if ($riderResult['success']) {
                $results['total_success'] += $riderResult['success_count'];
                $results['total_failure'] += $riderResult['failure_count'];
            } else {
                $results['success'] = false;
            }
        }

        // Send to customers
        $customerTokens = User::where('role', 'customer')
            ->whereNotNull('fcm_id')
            ->pluck('fcm_id')
            ->filter()
            ->toArray();

        if (!empty($customerTokens)) {
            $customerResult = $this->firebaseService->sendToMultipleDevices(
                'customer',
                $customerTokens,
                $announcement->title,
                $announcement->message ?? 'New announcement available',
                $announcement->getNotificationData()
            );
            $results['customer_result'] = $customerResult;
            
            if ($customerResult['success']) {
                $results['total_success'] += $customerResult['success_count'];
                $results['total_failure'] += $customerResult['failure_count'];
            } else {
                $results['success'] = false;
            }
        }

        // If no tokens found at all
        if (empty($riderTokens) && empty($customerTokens)) {
            return [
                'success' => false,
                'error' => 'No FCM tokens found for any users'
            ];
        }

        return $results;
    }

    /**
     * Send multiple announcements
     */
    public function sendMultipleAnnouncements($announcementIds)
    {
        $results = [];
        
        foreach ($announcementIds as $id) {
            $announcement = GeneralAnnouncement::find($id);
            if ($announcement) {
                $results[$id] = $this->sendAnnouncement($announcement);
            }
        }

        return $results;
    }

    /**
     * Send all ready announcements
     */
    public function sendReadyAnnouncements()
    {
        $readyAnnouncements = GeneralAnnouncement::readyToSend()->get();

        if ($readyAnnouncements->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No announcements ready to send',
                'count' => 0
            ];
        }

        $results = [];
        $successCount = 0;

        foreach ($readyAnnouncements as $announcement) {
            $result = $this->sendAnnouncement($announcement);
            $results[$announcement->id] = $result;
            
            if ($result['success']) {
                $successCount++;
            }
        }

        return [
            'success' => true,
            'results' => $results,
            'total_processed' => $readyAnnouncements->count(),
            'successful_sends' => $successCount,
            'failed_sends' => $readyAnnouncements->count() - $successCount
        ];
    }
}