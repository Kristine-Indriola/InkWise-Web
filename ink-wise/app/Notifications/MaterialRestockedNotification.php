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
        $restockedBy = $this->restockedBy ? $this->restockedBy->name : 'System';

        return [
            'message' => "[Restock] {$materialName} restocked (+{$this->quantityAdded}). Current stock: {$currentStock}.",
            'material_id' => $this->material->material_id,
            'quantity_added' => $this->quantityAdded,
            'current_stock' => $currentStock,
            'restocked_by' => $restockedBy,
        ];
    }
}
