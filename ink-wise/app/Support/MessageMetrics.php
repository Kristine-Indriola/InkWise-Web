<?php

namespace App\Support;

use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MessageMetrics
{
    /**
     * Count admin-facing unread messages.
     */
    public static function adminUnreadCount(): int
    {
        if (! Schema::hasTable('messages')) {
            return 0;
        }

        try {
            $query = Message::query()
                ->where(function ($q) {
                    $q->whereRaw('LOWER(sender_type) = ?', ['customer'])
                      ->orWhereRaw('LOWER(sender_type) = ?', ['guest']);
                })
                ->where(function ($q) {
                    $q->whereNull('receiver_type')
                      ->orWhereRaw('LOWER(receiver_type) = ?', ['user'])
                      ->orWhereRaw('LOWER(receiver_type) = ?', ['admin'])
                      ->orWhereRaw('LOWER(receiver_type) = ?', ['staff']);
                });

            if (Schema::hasColumn('messages', 'seen_at')) {
                $query->whereNull('seen_at');
            } elseif (Schema::hasColumn('messages', 'is_read')) {
                $query->where('is_read', 0);
            } else {
                $query->where('created_at', '>=', now()->subDays(7));
            }

            return (int) $query->count();
        } catch (\Throwable $e) {
            Log::debug('Failed to compute admin unread count: '.$e->getMessage());
            return 0;
        }
    }

    /**
     * Mark a customer or guest thread as seen by admin.
     */
    public static function markThreadSeenForAdmin(Message $original): void
    {
        if (! Schema::hasTable('messages')) {
            return;
        }

        $useSeenAt = Schema::hasColumn('messages', 'seen_at');
        $useIsRead = ! $useSeenAt && Schema::hasColumn('messages', 'is_read');

        if (! $useSeenAt && ! $useIsRead) {
            return; // Nothing to update (fallback mode is time-based)
        }

        try {
            $lowerSender = strtolower($original->sender_type ?? '');
            $lowerReceiver = strtolower($original->receiver_type ?? '');

            $query = Message::query()
                ->where(function ($q) {
                    $q->whereRaw('LOWER(sender_type) = ?', ['customer'])
                      ->orWhereRaw('LOWER(sender_type) = ?', ['guest']);
                });

            if ($lowerSender === 'guest' || $lowerReceiver === 'guest') {
                $email = $original->email;
                if (! $email) {
                    return;
                }
                $query->where(function ($q) use ($email) {
                    $q->where('email', $email)
                      ->orWhere(function ($nested) use ($email) {
                          $nested->whereRaw('LOWER(receiver_type) = ?', ['guest'])
                                 ->where('email', $email);
                      });
                });
            } else {
                $customerId = null;
                if ($lowerSender === 'customer') {
                    $customerId = $original->sender_id;
                } elseif ($lowerReceiver === 'customer') {
                    $customerId = $original->receiver_id;
                }

                if (! $customerId) {
                    return;
                }

                $query->where(function ($q) use ($customerId) {
                    $q->where(function ($nested) use ($customerId) {
                        $nested->whereRaw('LOWER(sender_type) = ?', ['customer'])
                               ->where('sender_id', $customerId);
                    })->orWhere(function ($nested) use ($customerId) {
                        $nested->whereRaw('LOWER(receiver_type) = ?', ['customer'])
                               ->where('receiver_id', $customerId);
                    });
                });
            }

            if ($useSeenAt) {
                $query->whereNull('seen_at')->update(['seen_at' => now()]);
            } else {
                $query->where('is_read', 0)->update(['is_read' => 1]);
            }
        } catch (\Throwable $e) {
            Log::debug('Failed to mark admin thread as seen: '.$e->getMessage());
        }
    }
}
