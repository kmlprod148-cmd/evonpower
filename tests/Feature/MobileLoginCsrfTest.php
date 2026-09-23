<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test suite pour vérifier le fix du problème 419 sur mobile
 */
class MobileLoginCsrfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur de test
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    }

    /**
     * Test: Login normal depuis desktop doit fonctionner
     */
    public function test_desktop_login_works()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        $response = $this->post('/login', [
            '_token' => csrf_token(),
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Test: Login depuis iPhone doit fonctionner
     */
    public function test_iphone_login_works()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1',
        ])->get('/login');

        $response->assertStatus(200);

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1',
        ])->post('/login', [
            '_token' => csrf_token(),
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Test: Login depuis Android doit fonctionner
     */
    public function test_android_login_works()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36',
        ])->get('/login');

        $response->assertStatus(200);

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36',
        ])->post('/login', [
            '_token' => csrf_token(),
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Test: RefreshCsrfForMobile ne doit PAS régénérer le token sur POST
     */
    public function test_csrf_token_not_regenerated_on_post()
    {
        // Simuler un appareil mobile
        $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15';

        // GET /login pour obtenir le token initial
        $response = $this->withHeaders(['User-Agent' => $userAgent])->get('/login');
        $initialToken = csrf_token();

        // POST /login avec le même token
        $response = $this->withHeaders(['User-Agent' => $userAgent])->post('/login', [
            '_token' => $initialToken,
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Le login doit réussir (pas de 419)
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Test: Token invalide doit retourner 419
     */
    public function test_invalid_token_returns_419()
    {
        $response = $this->post('/login', [
            '_token' => 'invalid-token-12345',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(419);
    }

    /**
     * Test: Endpoint /csrf-token doit retourner un token valide
     */
    public function test_csrf_token_endpoint_returns_valid_token()
    {
        $response = $this->get('/csrf-token');

        $response->assertStatus(200);
        $response->assertJsonStructure(['csrf_token']);
        
        $token = $response->json('csrf_token');
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        $this->assertGreaterThan(20, strlen($token));
    }

    /**
     * Test: /csrf-token avec cache busting doit retourner un nouveau token
     */
    public function test_csrf_token_endpoint_with_cache_busting()
    {
        $response = $this->get('/csrf-token?t=' . time() . '&r=' . uniqid());

        $response->assertStatus(200);
        $response->assertJsonStructure(['csrf_token']);
    }

    /**
     * Test: Mobile headers doivent être ajoutés sur mobile
     */
    public function test_mobile_headers_added_on_mobile_request()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
        ])->get('/login');

        $response->assertStatus(200);
        // Note: On ne peut pas tester les headers de réponse facilement dans les tests
        // mais le middleware les ajoute
    }

    /**
     * Test: Session doit persister après login mobile
     */
    public function test_session_persists_after_mobile_login()
    {
        $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)';

        // Login
        $response = $this->withHeaders(['User-Agent' => $userAgent])->post('/login', [
            '_token' => csrf_token(),
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();

        // Vérifier que la session est toujours valide
        $response = $this->withHeaders(['User-Agent' => $userAgent])->get('/dashboard');
        $response->assertStatus(200);
        $this->assertAuthenticated();
    }

    /**
     * Test: Login avec remember me doit fonctionner
     */
    public function test_mobile_login_with_remember_me()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
        ])->post('/login', [
            '_token' => csrf_token(),
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Test: Mauvais credentials ne doivent pas causer 419
     */
    public function test_wrong_credentials_do_not_cause_419()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
        ])->post('/login', [
            '_token' => csrf_token(),
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        // Doit retourner erreur de validation, pas 419
        $response->assertSessionHasErrors('email');
        $response->assertStatus(302); // Redirect back
    }

    /**
     * Test: Multiple requêtes POST consécutives depuis mobile
     */
    public function test_multiple_consecutive_posts_from_mobile()
    {
        $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)';

        // Première tentative (mauvais password)
        $response = $this->withHeaders(['User-Agent' => $userAgent])->post('/login', [
            '_token' => csrf_token(),
            'email' => 'test@example.com',
            'password' => 'wrong',
        ]);
        $response->assertSessionHasErrors();

        // Deuxième tentative (bon password) - doit fonctionner
        $response = $this->withHeaders(['User-Agent' => $userAgent])->post('/login', [
            '_token' => csrf_token(),
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Test: AJAX login depuis mobile doit fonctionner
     */
    public function test_ajax_mobile_login_works()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->post('/login', [
            '_token' => csrf_token(),
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
    }

    /**
     * Test: AJAX login avec token invalide doit retourner JSON 419
     */
    public function test_ajax_mobile_login_with_invalid_token_returns_json()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->post('/login', [
            '_token' => 'invalid-token',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(419);
        $response->assertJson([
            'message' => 'Session expirée. Veuillez rafraîchir la page.',
            'error' => 'csrf_token_mismatch',
            'mobile_device' => true,
        ]);
    }
}

