<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    protected $guarded = [];
    protected $casts = [
        'call_alerts' => 'boolean',
        'new_messages' => 'boolean',
        'subscription_reminders' => 'boolean',
        'call_from_unknown' => 'boolean',
        'show_online' => 'boolean',
        'share_profile' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function ($preference) {
            $preference->display_name = $preference->user->fullname();
        });
    }
}
