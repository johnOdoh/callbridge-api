<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = [];
    protected $casts = [
        'features' => 'array',
        'unlimited_data' => 'boolean',
        'unlimited_calls' => 'boolean',
        'esim_enabled' => 'boolean'
    ];
}
