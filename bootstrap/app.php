<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Production sits behind Cloudflare. Trusting every proxy ('*') let any
        // client set X-Forwarded-For and dodge the IP-keyed throttles on
        // /api/contact and /api/address-search, so only loopback (the local
        // nginx) and Cloudflare's published ranges are trusted. Override with a
        // comma-separated TRUSTED_PROXIES if the edge changes.
        // Ranges: https://www.cloudflare.com/ips-v4 and /ips-v6 (checked 2026-09-30).
        $middleware->trustProxies(at: array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', implode(',', [
            '127.0.0.1', '::1',
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
            '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
            '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
            '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
        ])))))));

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // The SPA's fetch() calls send no Accept header, so a validation
        // failure came back as a 302 to the previous page, a throttle as an
        // HTML 429 and a missing route as an HTML 404. Everything under
        // /api/ answers in JSON regardless of what the client asked for.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
    })->create();
