<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MaterialRestockedNotification extends Notification
{
    use Queueable;

    protected $material;
    protected int $quantityAdded;
    protected $restockedBy;

    public function __construct($material, int $quantityAdded, $restockedBy = null)
    {
        $this->material = $material;
        $this->quantityAdded = $quantityAdded;
        $this->restockedBy = $restockedBy;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $materialName = $this->material->material_name;
        $currentStock = optional($this->material->inventory)->stock_level ?? $this->material->stock_qty ?? 0;
        $restockedBy = $this->resolveRestockerName();

        return [
            'message' => "[Restock] {$materialName} restocked (+{$this->quantityAdded}) by {$restockedBy}. Current stock: {$currentStock}.",
            'material_id' => $this->material->material_id,
            'quantity_added' => $this->quantityAdded,
            'current_stock' => $currentStock,
            'restocked_by' => $restockedBy,
        ];
    }

    protected function resolveRestockerName(): string
    {
        if (!$this->restockedBy) {
            return 'System';
        }

        $candidate = trim((string) ($this->restockedBy->name ?? ''));
        if ($candidate !== '') {
            return $candidate;
        }

        if (method_exists($this->restockedBy, 'staff')) {
            $staff = $this->restockedBy->staff;
            if ($staff) {
                $parts = array_filter([
                    $staff->first_name ?? null,
                    $staff->middle_name ?? null,
                    $staff->last_name ?? null,
                ], fn ($value) => is_string($value) && trim($value) !== '');

                $candidate = trim(implode(' ', $parts));
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        if (property_exists($this->restockedBy, 'email') && is_string($this->restockedBy->email)) {
            return $this->restockedBy->email;
        }

        return 'Staff';
    }
}
