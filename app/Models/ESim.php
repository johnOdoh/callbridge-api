<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ESim extends Model
{
    protected $guarded = [];
    protected $casts = [
        'is_activated' => 'boolean',
        'activated_on' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
