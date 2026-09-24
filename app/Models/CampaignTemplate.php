<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignTemplate extends Model
{
    protected $fillable = [
        'whatsapp_account_id',
        'name',
        'language_code',
        'header_type',
        'header_text',
        'body_variables',
        'has_document_header',
        'button_variables',
    ];

    protected $casts = [
        'body_variables'    => 'array',
        'button_variables'  => 'array',
        'has_document_header' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
