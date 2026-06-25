<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignMessage extends Model
{
    protected $table = 'campaign_messages';

    protected $fillable = [
        'campaign_id',
        'phone_number',
        'whatsapp_message_id',
        'status',
        'error_message',
        'error_code',
        'error_subcode',
        'failure_reason',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByFailureReason($query, string $reason)
    {
        return $query->where('failure_reason', $reason);
    }
}