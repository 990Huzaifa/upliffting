<?php

namespace App\Console\Commands;

use App\Models\GeneralAnnouncement;
use App\Models\User;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GAPost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:g-a-post';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send pending general announcements via FCM notifications';

    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        parent::__construct();
        $this->firebaseService = $firebaseService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting General Announcements sending process...');
        
        // Get current timestamp
        $now = Carbon::now()->format('Y-m-d H:i:s');
        
        // Fetch pending announcements
        $announcements = GeneralAnnouncement::where('is_sent', 0)
            ->where('scheduled_at', '<=', $now)
            ->where('status', 'approved')
            ->orderBy('priority', 'asc') // Send higher priority first (1 = critical, 5 = info)
            ->orderBy('id', 'desc')
            ->get();

        Log::info('Total Announcements to process: ' . $announcements->count());
        $this->info("📋 Found {$announcements->count()} announcements ready to send");

        if ($announcements->isEmpty()) {
            $this->info('✅ No pending announcements found.');
            return;
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($announcements as $announcement) {
            $this->info("📢 Processing Announcement ID: {$announcement->id} - '{$announcement->title}'");
            
            try {
                $result = $this->sendAnnouncement($announcement);
                
                if ($result['success']) {
                    // Mark as sent
                    $announcement->is_sent = 1;
                    $announcement->save();
                    
                    $successCount++;
                    $this->info("✅ Announcement ID {$announcement->id} sent successfully!");
                    
                    // Log detailed results
                    if (isset($result['total_success']) && isset($result['total_failure'])) {
                        $this->info("   📊 Total sent: {$result['total_success']}, Failed: {$result['total_failure']}");
                        Log::info("Announcement {$announcement->id} sent - Success: {$result['total_success']}, Failed: {$result['total_failure']}");
                    }
                } else {
                    $failureCount++;
                    $this->error("❌ Failed to send Announcement ID {$announcement->id}: {$result['error']}");
                    Log::error("Failed to send Announcement {$announcement->id}: {$result['error']}");
                }
                
            } catch (\Exception $e) {
                $failureCount++;
                $this->error("❌ Exception for Announcement ID {$announcement->id}: {$e->getMessage()}");
                Log::error("Exception sending Announcement {$announcement->id}: {$e->getMessage()}");
            }
        }

        // Final summary
        $this->info("🎯 Sending Summary:");
        $this->info("   ✅ Successful: {$successCount}");
        $this->info("   ❌ Failed: {$failureCount}");
        $this->info("   📊 Total processed: " . ($successCount + $failureCount));
        

        Log::info("GA Post Command completed - Success: {$successCount}, Failed: {$failureCount}");
        $this->info('🏁 General Announcements sending process completed!');
    }

    /**
     * Send announcement based on audience
     */
    protected function sendAnnouncement($announcement)
    {
        $title = $announcement->title;
        $body = $announcement->message ?? 'You have a new announcement';
        
        // Prepare notification data
        $notificationData = [
            'announcement_id' => $announcement->id,
            'priority' => $announcement->priority,
            'priority_text' => $this->getPriorityText($announcement->priority),
            'audience' => $announcement->audience,
            'notification_type' => 'general_announcement',
            'created_at' => $announcement->created_at->toISOString()
        ];

        // Add image and attachment URLs if available
        if ($announcement->image) {
            $notificationData['image_url'] = asset($announcement->image);
        }
        if ($announcement->attachment) {
            $notificationData['attachment_url'] = asset($announcement->attachment);
        }

        switch ($announcement->audience) {
            case 'riders':
                return $this->sendToRiders($title, $body, $notificationData);
                
            case 'customers':
                return $this->sendToCustomers($title, $body, $notificationData);
                
            case 'all':
                return $this->sendToAll($title, $body, $notificationData);
                
            default:
                return [
                    'success' => false,
                    'error' => 'Invalid audience type: ' . $announcement->audience
                ];
        }
    }

    /**
     * Send to all riders
     */
    protected function sendToRiders($title, $body, $data)
    {
        $riderTokens = User::where('role', 'rider')
            ->whereNotNull('fcm_id')
            ->pluck('fcm_id')
            ->filter()
            ->toArray();

        if (empty($riderTokens)) {
            return [
                'success' => false,
                'error' => 'No rider FCM tokens found'
            ];
        }

        $this->info("   🏍️ Sending to " . count($riderTokens) . " riders");
        
        return $this->firebaseService->sendToMultipleDevices(
            'rider',
            $riderTokens,
            $title,
            $body,
            $data
        );
    }

    /**
     * Send to all customers
     */
    protected function sendToCustomers($title, $body, $data)
    {
        $customerTokens = User::where('role', 'customer')
            ->whereNotNull('fcm_id')
            ->pluck('fcm_id')
            ->filter()
            ->toArray();

        if (empty($customerTokens)) {
            return [
                'success' => false,
                'error' => 'No customer FCM tokens found'
            ];
        }

        $this->info("   👥 Sending to " . count($customerTokens) . " customers");
        
        return $this->firebaseService->sendToMultipleDevices(
            'customer',
            $customerTokens,
            $title,
            $body,
            $data
        );
    }

    /**
     * Send to all users (both riders and customers)
     */
    protected function sendToAll($title, $body, $data)
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
            $this->info("   🏍️ Sending to " . count($riderTokens) . " riders");
            
            $riderResult = $this->firebaseService->sendToMultipleDevices(
                'rider',
                $riderTokens,
                $title,
                $body,
                $data
            );
            
            $results['rider_result'] = $riderResult;
            
            if ($riderResult['success']) {
                $results['total_success'] += $riderResult['success_count'];
                $results['total_failure'] += $riderResult['failure_count'];
                $this->info("   ✅ Riders: {$riderResult['success_count']} sent, {$riderResult['failure_count']} failed");
            } else {
                $results['success'] = false;
                $this->error("   ❌ Failed to send to riders: {$riderResult['error']}");
            }
        }

        // Send to customers
        $customerTokens = User::where('role', 'customer')
            ->whereNotNull('fcm_id')
            ->pluck('fcm_id')
            ->filter()
            ->toArray();

        if (!empty($customerTokens)) {
            $this->info("   👥 Sending to " . count($customerTokens) . " customers");
            
            $customerResult = $this->firebaseService->sendToMultipleDevices(
                'customer',
                $customerTokens,
                $title,
                $body,
                $data
            );
            
            $results['customer_result'] = $customerResult;
            
            if ($customerResult['success']) {
                $results['total_success'] += $customerResult['success_count'];
                $results['total_failure'] += $customerResult['failure_count'];
                $this->info("   ✅ Customers: {$customerResult['success_count']} sent, {$customerResult['failure_count']} failed");
            } else {
                $results['success'] = false;
                $this->error("   ❌ Failed to send to customers: {$customerResult['error']}");
            }
        }

        // Check if any tokens were found
        if (empty($riderTokens) && empty($customerTokens)) {
            return [
                'success' => false,
                'error' => 'No FCM tokens found for any users'
            ];
        }

        return $results;
    }

    /**
     * Get priority text from number
     */
    protected function getPriorityText($priority)
    {
        $priorities = [
            1 => 'Critical',
            2 => 'High',
            3 => 'Medium',
            4 => 'Low',
            5 => 'Info'
        ];
        
        return $priorities[$priority] ?? 'Info';
    }
}