<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'name',
        'description',
        'template_name',
        'language_code',
        'status',
        'total_numbers',
        'sent_count',
        'delivered_count',
        'read_count',
        'failed_count',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(CampaignMessage::class);
    }

    public function pendingMessages(): HasMany
    {
        return $this->messages()->where('status', 'pending');
    }

    public function sentMessages(): HasMany
    {
        return $this->messages()->where('status', 'sent');
    }

    public function deliveredMessages(): HasMany
    {
        return $this->messages()->where('status', 'delivered');
    }

    public function readMessages(): HasMany
    {
        return $this->messages()->where('status', 'read');
    }

    public function failedMessages(): HasMany
    {
        return $this->messages()->where('status', 'failed');
    }

    public function notRegisteredMessages(): HasMany
    {
        return $this->messages()->where('failure_reason', 'NOT_REGISTERED');
    }

    public function healthErrorMessages(): HasMany
    {
        return $this->messages()->where('failure_reason', 'HEALTH_ERROR');
    }

    public function blockedMessages(): HasMany
    {
        return $this->messages()->where('failure_reason', 'BLOCKED');
    }
}