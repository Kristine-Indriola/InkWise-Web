<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockNotification extends Notification
{
    use Queueable;

    protected $material;
    protected $status; // low / out

    public function __construct($material, $status)
    {
        $this->material = $material;
        $this->status   = $status;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "⚠️ {$this->material->material_name} is {$this->status} (Stock: {$this->material->inventory->stock_level})",
        ];
    }
}
