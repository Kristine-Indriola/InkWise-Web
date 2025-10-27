<?php

namespace Tests\Unit\Notifications;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use Tests\TestCase;

class OrderStatusUpdatedTest extends TestCase
{
    public function test_notification_uses_mail_and_database_channels(): void
    {
        $user = new User([
            'user_id' => 1,
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
        ]);

        $order = new Order([
            'id' => 1,
            'order_number' => 'ORD-001',
            'status' => 'pending',
        ]);

        $statusLabels = [
            'pending' => 'Order Received',
            'processing' => 'Processing',
            'completed' => 'Completed',
        ];

        $notification = new OrderStatusUpdated($order, 'pending', 'processing', $statusLabels);

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function test_database_payload_includes_expected_data(): void
    {
        $user = new User([
            'user_id' => 1,
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
        ]);

        $order = new Order([
            'id' => 1,
            'order_number' => 'ORD-001',
            'status' => 'pending',
        ]);

        $statusLabels = [
            'pending' => 'Order Received',
            'processing' => 'Processing',
            'completed' => 'Completed',
        ];

        $notification = new OrderStatusUpdated($order, 'pending', 'processing', $statusLabels);

        $data = $notification->toArray($user);

        $this->assertSame('Your order ORD-001 status has been updated to: Processing', $data['message']);
        $this->assertSame(1, $data['order_id']);
        $this->assertSame('ORD-001', $data['order_number']);
        $this->assertSame('pending', $data['old_status']);
        $this->assertSame('processing', $data['new_status']);
        $this->assertSame('Processing', $data['status_label']);
        $this->assertSame(route('customer.my_purchase'), $data['url']);
    }

    public function test_notification_handles_order_without_order_number(): void
    {
        $user = new User([
            'user_id' => 1,
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
        ]);

        $order = new Order([
            'id' => 123,
            'order_number' => null,
            'status' => 'pending',
        ]);

        $statusLabels = [
            'pending' => 'Order Received',
            'processing' => 'Processing',
        ];

        $notification = new OrderStatusUpdated($order, 'pending', 'processing', $statusLabels);

        $data = $notification->toArray($user);

        $this->assertSame('Your order #123 status has been updated to: Processing', $data['message']);
    }
}