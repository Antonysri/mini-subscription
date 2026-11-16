<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAndRateLimitTest extends TestCase
{
    /**
     * RefreshDatabase TRAIT:
     * Ovvoru test mudiyum pothum database reset pannum.
     */
    use RefreshDatabase;

    /**
     * TEST 1: OWASP Security Headers Present Check
     * 
     * Enna check panrom?
     * SecurityHeaders middleware ellam API response-layum 5 critical security headers-ah
     * inject pannuthanu verify panrom.
     */
    public function test_security_headers_are_present_on_api_responses(): void
    {
        // 1. GET /api/plans endpoint-ku call panrom:
        $response = $this->getJson('/api/plans');

        $response->assertStatus(200);

        // 2. OWASP Headers irukanu ovvonna verify panrom:
        // A. MIME Sniffing Defense:
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        // B. Clickjacking Defense (iframe embedding block):
        $response->assertHeader('X-Frame-Options', 'DENY');

        // C. Reflected XSS Defense:
        $response->assertHeader('X-XSS-Protection', '1; mode=block');

        // D. Referrer Leakage Defense:
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // E. HSTS (Strict Transport Security for HTTPS):
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    /**
     * TEST 2: Auth Rate Limiter (Brute-Force Protection)
     * 
     * Enna check panrom?
     * /api/login endpoint-ku 1 nimishathula 5 attempts mattum allow aaganum.
     * 6-avathu thadava call pannum pothu HTTP 429 Too Many Requests vara vendum!
     */
    public function test_auth_rate_limiting_blocks_brute_force_after_five_attempts(): void
    {
        $payload = [
            'email'    => 'attacker@test.com',
            'password' => 'wrongpass',
        ];

        // 1. First 5 attempts: Rate limit block aagakoodathu (401 invalid credentials vara vendum):
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/login', $payload);
            $this->assertNotEquals(429, $response->status(), "Attempt {$i} should not be throttled yet");
        }

        // 2. 6th attempt: Kandippa 429 Too Many Requests vara vendum!
        $blockedResponse = $this->postJson('/api/login', $payload);
        $blockedResponse->assertStatus(429);
    }

    /**
     * TEST 3: Centralized 401 Unauthorized JSON Envelope
     * 
     * Enna check panrom?
     * Token illama protected route (/api/me) call panna,
     * bootstrap/app.php-la namma ezhuthina standardized custom JSON envelope varuthanu paakrom.
     */
    public function test_unauthenticated_request_returns_custom_json_envelope(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated or token expired.',
            ]);
    }

    /**
     * TEST 4: Centralized 404 Not Found JSON Envelope
     * 
     * Enna check panrom?
     * Thevai illatha non-existent API endpoint call panna,
     * HTML error page vara koodathu, clean 404 JSON response vara vendum.
     */
    public function test_not_found_endpoint_returns_custom_json_envelope(): void
    {
        $response = $this->getJson('/api/non-existent-endpoint-xyz');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Endpoint or resource not found.',
            ]);
    }

    /**
     * TEST 5: Protected Routes Have 60 req/min Rate Limit Headers
     * 
     * Enna check panrom?
     * Authenticated user-ku 'throttle:api' middleware trigger aagi,
     * X-RateLimit-Limit: 60 matrum X-RateLimit-Remaining headers varuthanu check panrom.
     */
    public function test_authenticated_route_has_api_rate_limit_headers(): void
    {
        $user = User::factory()->create([
            'email' => 'ratelimit_user@example.com',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $response->assertStatus(200);

        // Rate limit headers verify panrom:
        $response->assertHeader('X-RateLimit-Limit', '60');
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
    }
}
