<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappIncomingMessage extends Model
{
    protected $fillable = [
        'wa_message_id',
        'from_phone',
        'to_phone',
        'from_name',
        'type',
        'body',
        'media_url',
        'media_mime_type',
        'raw_payload',
        'wa_timestamp',
    ];

    protected $casts = [
        'raw_payload'  => 'array',
        'wa_timestamp' => 'datetime',
    ];
}