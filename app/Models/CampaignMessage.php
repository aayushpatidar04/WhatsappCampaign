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
        'variables',
        'has_attachment',
    ];

    protected $casts = [
        'sent_at'        => 'datetime',
        'delivered_at'   => 'datetime',
        'read_at'        => 'datetime',
        'failed_at'      => 'datetime',
        'variables'      => 'array',
        'has_attachment' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(CampaignFile::class, 'campaign_message_id');
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