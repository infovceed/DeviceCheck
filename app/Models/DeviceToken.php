<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'expires_at',
        'revoked',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked' => 'boolean',
    ];

    public static function issue(int $ttlHours = 24): self
    {
        $token = Str::random(64);
        $expiresAt = Carbon::now()->addHours($ttlHours);

        return static::create([
            'token' => $token,
            'expires_at' => $expiresAt,
            'revoked' => false,
        ]);
    }

    public function isValid(): bool
    {
        if ($this->revoked) {
            return false;
        }

        return is_null($this->expires_at) || $this->expires_at->isFuture();
    }
}
