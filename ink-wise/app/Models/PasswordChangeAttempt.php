<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordChangeAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'email',
        'attempt_details',
        'expires_at',
        'confirmed_at',
        'used'
    ];

    protected $casts = [
        'attempt_details' => 'array',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'used' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function markAsUsed(): void
    {
        $this->update([
            'used' => true,
            'confirmed_at' => now()
        ]);
    }
}
