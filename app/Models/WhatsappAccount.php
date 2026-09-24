<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappAccount extends Model
{
    protected $fillable = [
        'name',
        'phone_number_id',
        'business_id',
        'access_token_encrypted',
        'api_version',
        'webhook_verify_token',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(CampaignTemplate::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
