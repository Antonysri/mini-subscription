<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    /**
     * RefreshDatabase TRAIT:
     * Express-la test mudinjathum DB clear panra mathiri,
     * ovvoru test mudiyum pothum test database-ah clean pannidum!
     */
    use RefreshDatabase;

    /**
     * TEST 1: User register aaguratha check pannuvom (POST /api/register)
     * Expected: HTTP 201 Created, access_token, user details
     */
    public function test_user_can_register_successfully(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Antony S',
            'email'                 => 'antony@test.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user' => ['id', 'name', 'email'],
                ],
            ]);

        // Database-la user create aagittaara nu verify panrom:
        $this->assertDatabaseHas('users', [
            'email' => 'antony@test.com',
        ]);
    }

    /**
     * TEST 2: Validation fail aaguratha check pannuvom (Short password / Missing fields)
     * Expected: HTTP 422 Unprocessable Entity
     */
    public function test_registration_validation_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => '',
            'email'                 => 'not-an-email',
            'password'              => '123', // less than 6 chars
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * TEST 3: Login successfully with correct credentials (POST /api/login)
     * Expected: HTTP 200 OK, new token
     */
    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'login_test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'login_test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'data' => ['access_token', 'token_type', 'expires_in', 'user'],
            ]);
    }

    /**
     * TEST 4: Login fails with wrong password (POST /api/login)
     * Expected: HTTP 401 Unauthorized
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => 'correct_password',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'user@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid email or password',
            ]);
    }

    /**
     * TEST 5: Authenticated user can fetch their own profile (GET /api/me)
     * Expected: HTTP 200 OK
     */
    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $user = User::factory()->create([
            'email'    => 'me_test@example.com',
            'password' => 'password123',
        ]);

        // Token create panrom:
        $token = auth('api')->login($user);

        // Header-la Bearer Token anupi /api/me call panrom:
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id'    => $user->id,
                    'email' => 'me_test@example.com',
                ],
            ]);
    }

    /**
     * TEST 6: Requesting /api/me without token is rejected
     * Expected: HTTP 401 Unauthorized
     */
    public function test_unauthenticated_user_cannot_access_me_endpoint(): void
    {
        // Token illama direct-ah /api/me access panna try panrom:
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    /**
     * TEST 7: User logout invalidates/blacklists token (POST /api/logout)
     * Expected: HTTP 200 OK, subsequent call with SAME token returns 401!
     */
    public function test_user_can_logout_and_token_is_blacklisted(): void
    {
        $user = User::factory()->create([
            'email'    => 'logout_test@example.com',
            'password' => 'password123',
        ]);

        $token = auth('api')->login($user);

        // 1. First Logout panrom:
        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully logged out',
            ]);

        // 2. Ipo ATHE TOKEN-ah vachu marubadiyum /api/me access panna try panrom:
        // Token blacklist aagiruntha 401 vara vendum!
        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $meResponse->assertStatus(401);
    }
}