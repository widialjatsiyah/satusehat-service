<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatusehatLog extends Model
{
    protected $fillable = [
        'clinic_id','resource_type','request_payload','response_payload','status','retry_count'
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}

