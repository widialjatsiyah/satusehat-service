<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = [
        'code','name',
        'satusehat_client_id','satusehat_client_secret',
        'satusehat_access_token','satusehat_token_expires_at',
        'organization_id', // Added for SATUSEHAT integration
        'api_shared_secret','active'
    ];

    protected $casts = [
        'satusehat_client_secret' => 'encrypted',
        'satusehat_access_token'  => 'encrypted',
        'satusehat_token_expires_at' => 'datetime',
        'organization_id' => 'string', // SATUSEHAT organization ID
        'active' => 'boolean',
    ];
}