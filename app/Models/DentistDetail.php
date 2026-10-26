<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentistDetail extends Model
{
    protected $fillable = ['user_id', 'specialization', 'available_schedule', 'appointment_limit'];

    protected $casts = [
        'specialization' => 'array',
        'available_schedule' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}