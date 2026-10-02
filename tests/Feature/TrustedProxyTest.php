<?php

use Illuminate\Support\Facades\Route;

/*
 * Regression for trust-all-proxies-bypasses-throttle: trustProxies('*') let
 * any client pick its own IP through X-Forwarded-For, so rotating the header
 * dodged the IP-keyed throttles. Only loopback and Cloudflare are trusted now.
 */

beforeEach(function () {
    // Under /api/ because the SPA catch-all swallows every other late-registered path.
    Route::get('/api/_test/client-ip', fn () => response()->json(['ip' => request()->ip()]));
});

test('X-Forwarded-For from an untrusted client is ignored', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->withHeader('X-Forwarded-For', '1.2.3.4')
        ->getJson('/api/_test/client-ip')
        ->assertJson(['ip' => '203.0.113.9']);
});

test('X-Forwarded-For from a Cloudflare edge is honoured', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '172.64.10.20'])
        ->withHeader('X-Forwarded-For', '1.2.3.4')
        ->getJson('/api/_test/client-ip')
        ->assertJson(['ip' => '1.2.3.4']);
});

test('rotating X-Forwarded-For no longer escapes the contact throttle', function () {
    $payload = ['name' => 'A', 'email' => 'a@example.com', 'subject' => 'Hi', 'message' => 'Hello there'];

    foreach (range(1, 5) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->withHeader('X-Forwarded-For', "10.0.0.{$i}")
            ->postJson('/api/contact', $payload);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->withHeader('X-Forwarded-For', '10.0.0.99')
        ->postJson('/api/contact', $payload)
        ->assertStatus(429);
});
