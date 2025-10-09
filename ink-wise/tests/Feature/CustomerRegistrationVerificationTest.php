<?php

namespace Tests\Feature;

use App\Mail\CustomerEmailVerificationCode;
use App\Models\UserVerification;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerRegistrationVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_must_verify_email_before_registration(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        Mail::fake();

        $email = 'new-user@example.com';

    config(['app.url' => 'http://localhost']);
    URL::forceRootUrl('http://localhost');

        $sendResponse = $this->postJson('/customer/register/send-code', [
            'email' => $email,
        ]);

        $sendResponse->assertStatus(200);

        $code = null;
        Mail::assertSent(CustomerEmailVerificationCode::class, function (CustomerEmailVerificationCode $mail) use (&$code) {
            $code = $mail->code;
            return true;
        });

        $this->assertNotNull($code);
        $this->assertDatabaseHas('user_verifications', [
            'email' => $email,
        ]);

        $registrationData = [
            'first_name' => 'New',
            'middle_name' => null,
            'last_name' => 'User',
            'birthdate' => '1990-01-01',
            'contact_number' => '09123456789',
            'email' => $email,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'verification_code' => '000000', // wrong code first
        ];

        $this->from(route('customer.register.form'))
            ->post(route('customer.register'), $registrationData)
            ->assertSessionHasErrors('verification_code');

        $registrationData['verification_code'] = $code;

        $response = $this->post(route('customer.register'), $registrationData);

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        $this->assertDatabaseHas('customers', [
            'first_name' => 'New',
            'last_name' => 'User',
        ]);

        $token = UserVerification::where('email', $email)->first();
        $this->assertNotNull($token?->consumed_at);
    }
}
