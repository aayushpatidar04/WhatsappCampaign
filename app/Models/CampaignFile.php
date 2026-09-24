<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignFile extends Model
{
    protected $fillable = [
        'campaign_id',
        'campaign_message_id',
        'phone_number',
        'original_filename',
        'stored_path',
        'mime_type',
        'size_bytes',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(CampaignMessage::class, 'campaign_message_id');
    }
}
