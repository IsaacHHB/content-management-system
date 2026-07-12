<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        $script = "'self' 'nonce-{$nonce}'";
        $style = "'self' 'nonce-{$nonce}'";
        $connect = "'self'";

        if (app()->isLocal()) {
            $script .= " 'unsafe-eval' http://localhost:5173 http://127.0.0.1:5173";
            $connect .= ' http://localhost:5173 http://127.0.0.1:5173 ws://localhost:5173 ws://127.0.0.1:5173';
        }

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "connect-src {$connect}",
            "font-src 'self' data:",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "frame-src 'self' https://www.youtube-nocookie.com https://player.vimeo.com",
            "img-src 'self' data: blob:",
            "object-src 'none'",
            "script-src {$script}",
            "style-src {$style}",
            // React (Radix, dnd-kit, the sidebar's --sidebar-width) ships styles as
            // `style="..."` attributes, which SSR emits into the server HTML. A nonce in
            // style-src makes browsers ignore 'unsafe-inline' there, so style attributes
            // need their own directive or every SSR'd inline style is silently dropped.
            "style-src-attr 'unsafe-inline'",
        ]);
    }
}
