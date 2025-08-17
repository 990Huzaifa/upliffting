<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class GeneralAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'image',
        'attachment',
        'priority',
        'audience',
        'status',
        'scheduled_at',
        'is_sent'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'is_sent' => 'boolean',
        'priority' => 'integer'
    ];

    


    // Methods
    public function markAsSent()
    {
        $this->update([
            'is_sent' => true,
            'updated_at' => now()
        ]);
    }


    public function getNotificationData()
    {
        return [
            'announcement_id' => $this->id,
            'priority' => $this->priority,
            'priority_text' => $this->priority_text,
            'audience' => $this->audience,
            'image_url' => $this->image_url,
            'attachment_url' => $this->attachment_url,
            'notification_type' => 'general_announcement',
            'created_at' => $this->created_at->toISOString()
        ];
    }
}