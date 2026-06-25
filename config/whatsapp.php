<?php

return [
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'api_version' => env('WHATSAPP_API_VERSION', 'v25.0'),
    'template_name' => env('WHATSAPP_TEMPLATE_NAME', 'banaras_saree_stores'),
    'language_code' => env('WHATSAPP_LANGUAGE_CODE', 'en_IN'),
    'header_image_url' => env('WHATSAPP_HEADER_IMAGE_URL'),
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    'rate_limit_per_minute' => env('RATE_LIMIT_PER_MINUTE', 30),
];