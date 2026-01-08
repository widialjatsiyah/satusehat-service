<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatusehatLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id', 'resource_type', 'request_payload', 'response_payload', 'status', 'retry_count'
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Create a log entry for SATUSEHAT API requests
     */
    public static function log($clinicId, $resourceType, $method, $statusCode, $response, $requestPayload = null)
    {
        return self::create([
            'clinic_id' => $clinicId,
            'resource_type' => $resourceType,
            'request_payload' => json_encode($requestPayload),
            'response_payload' => json_encode($response),
            'status' => $statusCode >= 200 && $statusCode < 300 ? 'SUCCESS' : 'FAILED',
        ]);
    }
}