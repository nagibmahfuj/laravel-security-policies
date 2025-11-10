<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class MfaChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'code', 'expires_at', 'consumed_at', 'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return ! is_null($this->consumed_at);
    }
}
