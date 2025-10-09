<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class UserVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'token',
        'verified_at',
        'expires_at',
        'consumed_at',
        'attempts',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    // Relation to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('consumed_at')->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof CarbonInterface
            ? $this->expires_at->isPast()
            : false;
    }

    public function consume(): void
    {
        $this->consumed_at = now();
        $this->save();
    }

    public function markVerified(): void
    {
        $this->verified_at = now();
        $this->attempts = 0;
        $this->consume();
    }

    public function checkCode(string $plainCode): bool
    {
        return Hash::check($plainCode, $this->token);
    }
}
