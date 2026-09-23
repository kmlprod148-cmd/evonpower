<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Sms\PhoneOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = $this->createRoleUser('admin');

        $response = $this->post('/login', [
            'login_method' => 'admin',
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = $this->createRoleUser('admin');

        $this->post('/login', [
            'login_method' => 'admin',
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_customer_role_users_can_not_use_admin_email_login(): void
    {
        $user = $this->createRoleUser('user');

        $response = $this->post('/login', [
            'login_method' => 'admin',
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_role_users_can_not_use_customer_phone_login(): void
    {
        $this->createRoleUser('admin', [
            'phone' => '+212612345678',
        ]);

        $response = $this->post('/login', [
            'login_method' => 'customer',
            'phone' => '0612345678',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_customers_can_start_and_complete_phone_otp_login(): void
    {
        $otp = new class extends PhoneOtpService {
            public function __construct() {}

            public function issue(string $phoneE164, string $purpose = 'registration', ?string $ip = null): array
            {
                return ['issued' => true, 'retry_after' => null, 'code' => '123456'];
            }

            public function verify(string $phoneE164, string $candidate, string $purpose = 'registration'): string
            {
                return $candidate === '123456' ? 'ok' : 'invalid';
            }
        };

        $this->app->instance(PhoneOtpService::class, $otp);

        $user = $this->createRoleUser('user', [
            'phone' => '+212612345678',
            'phone_verified_at' => now(),
            'email' => null,
            'password' => null,
        ]);

        $this->post('/login', [
            'login_method' => 'customer',
            'phone' => '0612345678',
        ])->assertRedirect(route('login.customer.verify.show'));

        $response = $this->post(route('login.customer.verify'), [
            'code' => '123456',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('reservations.index', absolute: false));
    }

    public function test_users_can_logout(): void
    {
        $user = $this->createRoleUser('admin');

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
    }

    private function createRoleUser(string $roleName, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }
}
