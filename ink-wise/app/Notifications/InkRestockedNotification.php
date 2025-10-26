<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InkRestockedNotification extends Notification
{
    use Queueable;

    protected $ink;
    protected int $quantityAdded;
    protected $restockedBy;

    public function __construct($ink, int $quantityAdded, $restockedBy = null)
    {
        $this->ink = $ink;
        $this->quantityAdded = $quantityAdded;
        $this->restockedBy = $restockedBy;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $inkName = $this->ink->material_name;
        $currentStock = optional($this->ink->inventory)->stock_level ?? $this->ink->stock_qty ?? 0;
        $restockedBy = $this->restockedBy ? ($this->restockedBy->name ?? $this->restockedBy->email) : 'System';

        return [
            'message' => "[Ink Restock] {$inkName} restocked (+{$this->quantityAdded}). Current stock: {$currentStock}.",
            'ink_id' => $this->ink->id,
            'quantity_added' => $this->quantityAdded,
            'current_stock' => $currentStock,
            'restocked_by' => $restockedBy,
        ];
    }
}
