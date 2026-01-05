<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InkStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'ink_id',
        'movement_type',
        'quantity',
        'user_id',
        'notes',
    ];

    public function ink(): BelongsTo
    {
        return $this->belongsTo(Ink::class, 'ink_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
