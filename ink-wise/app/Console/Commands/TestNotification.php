<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Template;
use App\Notifications\TemplateUploadedNotification;

class TestNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the template upload notification system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing template upload notification system...');

        // Find an admin user
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $this->error('No admin user found!');
            return;
        }

        // Find a staff user
        $staff = User::where('role', 'staff')->first();
        if (!$staff) {
            $this->warn('No staff user found, using admin as staff for test');
            $staff = $admin;
        }

        // Find or create a test template
        $template = Template::first();
        if (!$template) {
            $this->warn('No template found, creating a test template');
            $template = Template::create([
                'name' => 'Test Template',
                'product_type' => 'invitation',
                'status' => 'uploaded',
                'user_id' => $staff->id,
            ]);
        }

        $this->info("Sending notification to admin: {$admin->name}");
        $this->info("From staff: {$staff->name}");
        $this->info("About template: {$template->name}");

        // Send the notification
        $admin->notify(new TemplateUploadedNotification($template, $staff));

        $this->info('Notification sent successfully!');
        $this->info("Admin now has {$admin->notifications()->count()} notifications");
    }
}
