<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'sender_type',
        'receiver_id',
        'receiver_type',
        'name',
        'email',
        'message',
        'attachment_path',
    ];

    protected $appends = [
        'attachment_url',
    ];

    public function sender()
    {
        return $this->morphTo(__FUNCTION__, 'sender_type', 'sender_id');
    }

    public function receiver()
    {
        return $this->morphTo(__FUNCTION__, 'receiver_type', 'receiver_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        $path = $this->attachment_path;

        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        $diskUrl = config('filesystems.disks.public.url');
        if (is_string($diskUrl) && $diskUrl !== '') {
            return rtrim($diskUrl, '/') . '/' . $normalized;
        }

        return asset('storage/' . $normalized);
    }
}
