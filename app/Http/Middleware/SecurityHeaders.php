<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * HANDLE AN INCOMING REQUEST & ATTACH OWASP SECURITY HEADERS:
     * 
     * ❓ INTHA MIDDLEWARE ENNA PANNUTHU?
     * 1. Controller process panni thara Response-ah intercept pannum.
     * 2. Browser-ku response porathukku munnadi 5 Critical OWASP Security Headers-ah add pannum.
     * 3. Clickjacking, MIME sniffing, XSS attacks-ah 100% block pannum!
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Controller kitta irunthu response-ah vangarom (Express next() mathiri):
        $response = $next($request);

        // 2. OWASP 2026 Recommended Security Headers:
        
        // A. MIME Sniffing Defense (Forces browser to respect declared content-type):
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // B. Clickjacking Defense (Blocks embedding inside iframes):
        $response->headers->set('X-Frame-Options', 'DENY');

        // C. XSS Filter (Blocks pages if reflected XSS is detected):
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // D. Referrer Leakage Defense (Keeps URLs safe across domains):
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // E. HSTS (Enforces HTTPS strictly for 1 full year):
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        return $response;
    }
}