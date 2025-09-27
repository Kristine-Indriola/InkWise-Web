<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserApproval extends Model
{
    protected $fillable = [
        'user_id',
        'approved_by',
        'status',
        'approved_at',
        'remarks',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

