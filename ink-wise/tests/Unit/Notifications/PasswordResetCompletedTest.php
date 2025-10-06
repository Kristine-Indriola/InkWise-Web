<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\PasswordResetCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetCompletedTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_uses_mail_and_database_channels(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $notification = new PasswordResetCompleted($user);

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function test_database_payload_includes_expected_message_and_url(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $notification = new PasswordResetCompleted($user);

        $data = $notification->toDatabase($user);

        $this->assertSame('🔐 '.$user->email.' just updated their password.', $data['message']);
        $this->assertSame(route('admin.users.index'), $data['url']);
        $this->assertSame('fa-solid fa-lock', $data['icon']);
    }
}
