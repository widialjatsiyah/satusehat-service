<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Encryption\DecryptException;

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

    // protected $casts = [
    //     'satusehat_client_secret' => 'encrypted',    // This is sensitive and needs encryption
    //     'satusehat_access_token'  => 'encrypted',    // This also contains sensitive data
    //     'satusehat_token_expires_at' => 'datetime',
    //     'organization_id' => 'string', // This is not encrypted
    //     'active' => 'boolean',
    // ];

    /**
     * Safely get the SATUSEHAT client secret, handling potential decryption errors
     */
    // public function getSatusehatClientSecretAttribute($value)
    // {
    //     if ($value === null) {
    //         return null;
    //     }

    //     try {
    //         return $this->getAttributeValue('satusehat_client_secret');
    //     } catch (DecryptException $e) {
    //         \Log::error('Failed to decrypt satusehat_client_secret: ' . $e->getMessage());
    //         return null;
    //     }
    // }

    /**
     * Safely get the SATUSEHAT access token, handling potential decryption errors
     */
    public function getSatusehatAccessTokenAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        try {
            return $this->getAttributeValue('satusehat_access_token');
        } catch (DecryptException $e) {
            \Log::warning('Failed to decrypt satusehat_access_token: ' . $e->getMessage());
            return null;
        }
    }
}
