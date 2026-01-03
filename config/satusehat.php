<?php

return [
    // Default timeout for SATUSEHAT API requests
    'timeout' => env('SATUSEHAT_TIMEOUT', 60),
    
    // SATUSEHAT environment - DEV, STG, or PROD
    'env' => env('SATUSEHAT_ENV', 'DEV'),
    
    // Base URLs for different environments
    'base_url_dev' => env('SATUSEHAT_BASE_URL_DEV', 'https://api-satusehat-dev.dto.kemkes.go.id'),
    'base_url_stg' => env('SATUSEHAT_BASE_URL_STG', 'https://api-satusehat-stg.dto.kemkes.go.id'),
    'base_url_prod' => env('SATUSEHAT_BASE_URL_PROD', 'https://api-satusehat.kemkes.go.id'),
    
    // For local development override
    'ss_parameter_override' => env('SS_PARAMETER_OVERRIDE', false),
];