<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Sms\PhoneOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_submitting_name_and_phone_issues_an_otp_and_redirects_to_verify_screen(): void
    {
        // Force the log driver so no real Infobip call happens during tests.
        config(['services.infobip.driver' => 'log']);

        $response = $this->post('/register', [
            'name'  => 'Test User',
            'phone' => '+212612345678',
        ]);

        $response->assertRedirect(route('register.verify.show'));
        $this->assertGuest();

        // A code row should now exist for this phone.
        $this->assertDatabaseHas('phone_verification_codes', [
            'phone'   => '+212612345678',
            'purpose' => 'registration',
        ]);

        // No user is created until the OTP is verified.
        $this->assertDatabaseMissing('users', ['phone' => '+212612345678']);
    }

    public function test_verifying_correct_otp_creates_user_and_logs_them_in(): void
    {
        config(['services.infobip.driver' => 'log']);

        $issue = $this->post('/register', [
            'name'  => 'Test User',
            'phone' => '+212611112222',
        ]);
        $issue->assertRedirect(route('register.verify.show'));

        // Read back the cleartext code from the dev session flash.
        $code = $issue->getSession()->get('dev_otp_code');
        $this->assertNotNull($code, 'Dev code should be flashed when driver=log');

        $verify = $this->post('/register/verify', ['code' => $code]);
        $verify->assertRedirect();

        $this->assertAuthenticated();
        $user = User::where('phone', '+212611112222')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNull($user->email);
        $this->assertNull($user->password);
    }

    public function test_wrong_otp_does_not_create_user(): void
    {
        config(['services.infobip.driver' => 'log']);

        $this->post('/register', [
            'name'  => 'Test User',
            'phone' => '+212600000001',
        ]);

        $response = $this->post('/register/verify', ['code' => '000000']);
        $response->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['phone' => '+212600000001']);
    }

    public function test_invalid_phone_format_is_rejected(): void
    {
        $response = $this->post('/register', [
            'name'  => 'Test User',
            'phone' => '0612345678',
        ]);

        $response->assertSessionHasErrors('phone');
    }
}
