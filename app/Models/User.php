<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_verified' => 'boolean'
        ];
    }

    private function isUnique($field, $value)
    {
        return ! static::where($field, $value)->exists();
    }

    public function fullname()
    {
        return "$this->firstname $this->lastname";
    }

    public function generateUsernameAndNumber()
    {
        do {
            $username = $this->lastname . random_int(100, 999);
        }
        while(! $this->isUnique('username', $username));
        do {
            $number = random_int(1000000000, 9999999999);
        }
        while(! $this->isUnique('number', $number));
        return ['username' => $username, 'number' => $number];
    }

    public function preference()
    {
        return $this->hasOne(Preference::class);
    }

    public function esim()
    {
        return $this->hasOne(ESim::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function outgoingCalls()
    {
        return $this->hasMany(CallLog::class, 'from');
    }

    public function incomingCalls()
    {
        return $this->hasMany(CallLog::class, 'to');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
