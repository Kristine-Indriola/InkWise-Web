<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\ReviewUpload;

class ReviewUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_image_and_create_audit()
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user);

        $file = UploadedFile::fake()->image('photo.png');

        $response = $this->post(route('order.review.upload-image'), [
            'image' => $file,
            'side' => 'front',
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('url', $data);

        // Ensure file stored
        Storage::disk('public')->assertExists(str_replace('/storage/', '', parse_url($data['url'], PHP_URL_PATH)));

        // Ensure db record created
        $this->assertDatabaseHas('review_uploads', [
            'user_id' => $user->id,
            'side' => 'front',
        ]);
    }
}