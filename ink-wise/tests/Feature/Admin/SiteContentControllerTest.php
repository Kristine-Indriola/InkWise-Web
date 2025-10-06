<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SiteContentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_site_content_form(): void
    {
        Cache::flush();

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        SiteSetting::create(SiteSetting::defaults());

    $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.settings.site-content.edit'));

        $response = $this->actingAs($admin)->get('/admin/settings/site-content');
        if ($response->status() !== 200) {
            fwrite(STDOUT, $response->getContent());
        }

        $response->assertOk();
        $response->assertSee('Site Content');
        $response->assertViewHas('settings');
    }

    public function test_admin_can_update_site_content(): void
    {
    Cache::flush();

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $settings = SiteSetting::create(SiteSetting::defaults());

        $payload = [
            'contact_heading' => 'Connect With Us',
            'contact_company' => 'InkWise HQ',
            'contact_subheading' => 'We would love to hear from you.',
            'contact_address' => '456 Sample Road, Makati City',
            'contact_phone' => '+63 912 345 6789',
            'contact_email' => 'hello@inkwise.test',
            'contact_hours' => "Monday – Friday: 8:00 AM – 6:00 PM\nSaturday: 10:00 AM – 2:00 PM",
            'about_heading' => 'Our Story',
            'about_body' => 'We craft unforgettable, personalised stationery for every milestone.',
        ];

    $response = $this->actingAs($admin)->put('/admin/settings/site-content', $payload);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Site content updated successfully.');

        $this->assertDatabaseHas('site_settings', [
            'id' => $settings->id,
            'contact_heading' => 'Connect With Us',
            'contact_company' => 'InkWise HQ',
            'contact_email' => 'hello@inkwise.test',
            'about_heading' => 'Our Story',
        ]);

        $this->assertDatabaseHas('site_settings', [
            'id' => $settings->id,
            'contact_hours' => "Monday – Friday: 8:00 AM – 6:00 PM\nSaturday: 10:00 AM – 2:00 PM",
        ]);
    }
}
