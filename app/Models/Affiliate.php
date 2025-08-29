<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    protected $guarded = [];
    protected $casts = [
        'is_completed' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }
}
