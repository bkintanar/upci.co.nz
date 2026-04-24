# Investigation: Mixed-Content Block on Filament Asset URLs

**Issue**: Free-form (no GH issue number)
**Type**: BUG
**Investigated**: 2026-04-24

### Assessment

| Metric     | Value  | Reasoning                                                                                                                                                                                    |
| ---------- | ------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Severity   | HIGH   | The Filament admin panel's Livewire+Alpine runtime fails to boot on any HTTPS-served page that loads a component whose JS is delivered by the `/js/filament/...` asset route — every edit page with a textarea (Gallery Items, Pages CMS, AGS Updates, etc.) is broken for every admin user. No client-side workaround. |
| Complexity | LOW    | One-line addition to `bootstrap/app.php`. No migrations, no tests affected, no breaking changes to app code.                                                                                 |
| Confidence | HIGH   | Confirmed both sides: `APP_URL=https://upci.b8.co.nz` is set correctly, but neither `trustProxies()` is registered in `bootstrap/app.php` nor is `URL::forceScheme('https')` called in `AppServiceProvider::boot()` — so Laravel reads the scheme from the internal HTTP request from the proxy and generates HTTP asset URLs. Textbook Laravel-behind-reverse-proxy setup bug. |

---

## Problem Statement

Loading any Filament admin edit page over HTTPS (e.g. `https://upci.b8.co.nz/admin/gallery-items/1/edit`) triggers a browser block: Filament requests `http://upci.b8.co.nz/js/filament/forms/components/textarea.js` via `Alpine.download()`, the browser refuses mixed content, the Livewire `initTree` chain crashes, and the page becomes non-interactive. `APP_URL` is already `https://upci.b8.co.nz`, so the question is why internal URL generation still produces HTTP URLs.

---

## Analysis

### Evidence Chain

**WHY the browser blocked the script**
↓ BECAUSE it was requested over `http://` on a page delivered over `https://`
Evidence: browser console — `Mixed Content: ... requested an insecure script 'http://upci.b8.co.nz/js/filament/forms/components/textarea.js'`

**WHY Filament generated an `http://` URL for the asset**
↓ BECAUSE Laravel's `url()` / `asset()` / route helpers (which Filament uses to build asset URLs) ask `$request->getScheme()`, and that returns `http` inside the Laravel app even though the browser used `https://` to reach the proxy
Evidence: `vendor/laravel/framework/src/Illuminate/Http/Request.php` — `getScheme()` defers to Symfony, which checks `X-Forwarded-Proto` **only if the remote IP is in the trusted-proxy list**.

**WHY Symfony doesn't trust the `X-Forwarded-Proto: https` header the proxy is sending**
↓ BECAUSE no `TrustProxies` middleware is registered. In Laravel 11 this is configured via `bootstrap/app.php` — specifically `$middleware->trustProxies(at: ...)`. Neither this nor the equivalent `URL::forceScheme('https')` in a service provider is present in this app.
Evidence (the gap):
```php
// bootstrap/app.php — current state
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'api/*',
    ]);
})
```
```php
// app/Providers/AppServiceProvider.php — current state
public function boot(): void
{
    //
}
```
```bash
$ grep -rn "TrustProxies\|trustProxies\|forceScheme\|X-Forwarded" app/ bootstrap/
(no matches)
```

**WHY `APP_URL=https://upci.b8.co.nz` in `.env` doesn't rescue this**
↓ BECAUSE `APP_URL` is used only when Laravel is generating URLs without an active HTTP request (artisan, queues, mail). For a live request, the scheme comes from `Request::getScheme()`, which Symfony picks from `$_SERVER['HTTPS']` (set by PHP-FPM's SAPI). Under a reverse-proxy setup, `HTTPS` is unset on the internal hop so Symfony reports `http`. Setting `APP_URL` does nothing on a live request — confirmed by the fact that the user already set it and the bug still reproduces.
Evidence: `vendor/symfony/http-foundation/Request.php::getScheme()` returns `$this->isSecure() ? 'https' : 'http'`; `isSecure()` reads `HTTPS` from `$_SERVER` OR, if `X-Forwarded-Proto` is set AND the proxy is trusted, from that header. Neither condition is met here.

**ROOT CAUSE**: `bootstrap/app.php` does not call `trustProxies()`, so Symfony ignores the proxy's `X-Forwarded-Proto: https` header and Laravel's URL helpers generate `http://` asset URLs on every request.

### Why this shows up now

This likely wasn't always broken — it was either masked (a dev accessing via HTTP directly, or via a non-proxied port) or introduced when the deployment moved behind an HTTPS terminator. No git history in the repo points at a removed TrustProxies config, because Laravel 11's minimal skeleton simply omits it by default; it's opt-in since 11.0. A project created with `laravel new` before ~Laravel 11 would have had a `app/Http/Middleware/TrustProxies.php` stub; Laravel 11's skeleton does not.

### Affected Files

| File                                  | Lines | Action | Description                                                                 |
| ------------------------------------- | ----- | ------ | --------------------------------------------------------------------------- |
| `bootstrap/app.php`                   | 11-16 | UPDATE | Add `$middleware->trustProxies(at: '*')` inside the `withMiddleware` closure |
| `app/Providers/AppServiceProvider.php` | (optional) | UPDATE | Safety net — `URL::forceScheme('https')` when `APP_URL` starts with `https://`. Not strictly needed if trustProxies is added; serves as belt-and-suspenders for environments where the proxy chain changes. |

### Integration Points

- Every URL-generating call site in the app: `url()`, `asset()`, `route()`, `Storage::url()`, Filament's internal asset router (`vendor/filament/support/routes/web.php`), Livewire's `@livewireScripts` directive. All of them flow through the same `Request::getScheme()` path, so fixing the proxy trust fixes all of them at once.
- Filament's `/js/filament/...` asset URLs specifically — `vendor/filament/support/src/Facades/FilamentAsset.php::getScriptSrc()` calls `route('filament.asset', ...)` which uses the request scheme.

### Git History

No prior TrustProxies config existed in this repo; Laravel 11 default skeleton. Not a regression — an original gap.

---

## Implementation Plan

### Step 1: Register trusted proxies in `bootstrap/app.php`

**File**: `bootstrap/app.php`
**Lines**: 11-16
**Action**: UPDATE

**Current code:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'api/*',
    ]);
})
```

**Required change:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*');

    $middleware->validateCsrfTokens(except: [
        'api/*',
    ]);
})
```

**Why**: Tells Symfony to honour `X-Forwarded-Proto`, `X-Forwarded-For`, and `X-Forwarded-Host` from any proxy. Safe when the app is only reachable through the proxy (which is the case for upci.b8.co.nz). `at: '*'` is the Laravel-docs-recommended value when the app sits behind a known trusted proxy — anything more specific (e.g. `['127.0.0.1', '::1']`) requires the operator to know the proxy's internal IP, which varies by container setup.

**Why not use `URL::forceScheme` as the primary fix**: It's a blunter instrument — forces HTTPS unconditionally regardless of the actual request scheme. Works, but loses the request-driven correctness that `trustProxies` gives (e.g. if someone curls the backend directly over HTTP for debugging, they'd get HTTPS URLs back and confusion). Trust proxies is the principled answer; it also fixes `$request->ip()` returning the real client IP (useful for logs, rate-limiting) and correct `$request->getHost()` — bonus wins.

---

### Step 2 (optional safety net): Force HTTPS when `APP_URL` is https

**File**: `app/Providers/AppServiceProvider.php`
**Action**: UPDATE

**Current code:**
```php
public function boot(): void
{
    //
}
```

**Required change:**
```php
public function boot(): void
{
    if (str_starts_with(config('app.url'), 'https://')) {
        \Illuminate\Support\Facades\URL::forceScheme('https');
    }
}
```

**Why**: Belt-and-suspenders. If someone later deploys behind a proxy that doesn't set `X-Forwarded-Proto`, or a misconfigured one, this catches it. Harmless if Step 1 is already doing its job. Keyed on `APP_URL` scheme so local HTTP dev is unaffected.

**Skip this step if** you want to keep the change minimal. Step 1 alone is sufficient against the reported symptom.

---

### Step 3: Verify

After implementing Step 1 (and optionally Step 2), clear caches and reload:

```bash
cd /var/www/personal/upci.co.nz
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
```

Then visit `https://upci.b8.co.nz/admin/gallery-items/1/edit` in a browser.

---

## Patterns to Follow

**From Laravel 11 docs — exact trustProxies placement:**
```php
// Laravel 11+ official pattern (trusted-proxy section of docs)
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*');
})
```

**From existing project patterns — CSRF exclusion shape mirrored exactly:**
```php
// SOURCE: bootstrap/app.php:11-15
$middleware->validateCsrfTokens(except: [
    'api/*',
]);
```

---

## Edge Cases & Risks

| Risk/Edge Case                                                            | Mitigation                                                                                                                     |
| ------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `trustProxies(at: '*')` over-trusts in a direct-access deployment         | In this setup the app is only exposed through the proxy; direct access isn't a threat vector. If that changes, tighten to specific CIDRs. |
| Opcache / config cache still returns the old bootstrap                    | Run `config:clear`, `route:clear`, `view:clear` after the edit; restart PHP-FPM if opcache.validate_timestamps=0               |
| `X-Forwarded-For` now drives `$request->ip()` — dependent rate-limits     | Audit is quick — grep for `$request->ip()` / `->ip()` usage. In this repo, no custom use; Laravel's built-in throttle will now see the real client IP (which is what you want for rate limiting anyway). |
| Some asset URLs cached in DB / content (e.g. hardcoded in CMS records)     | `Storage::disk('public')->url(...)` paths in CMS are generated at render-time, not stored; safe. Check `pages` / `gallery_items` / `menu_items` tables for literal `http://upci...` strings with `sqlite3 upci "SELECT id, slug FROM pages WHERE content LIKE '%http://upci.b8%';"` if paranoid. |
| Mix of HTTP/HTTPS in dev (someone accessing on plain HTTP)                | With Step 1 only: HTTP requests still work and get HTTP asset URLs — no mixed content. With Step 2 added: HTTP requests get HTTPS asset URLs — could be confusing but typically harmless (browser just follows the URL). |
| Filament-generated signed URLs (password reset links from the CLI) already use the correct scheme | Confirmed — `Filament::getResetPasswordUrl()` uses `URL::signedRoute()` which picks up `URL::forceScheme` / trust-proxy state. Current reset URLs in `storage/logs/laravel.log` are already `http://` — after this fix, newly-generated links will be `https://`. Existing old tokens remain valid but point to http URLs; operators should regenerate. |

---

## Validation

### Automated Checks

```bash
# Confirm no syntax error in the edited file
php -l bootstrap/app.php
php -l app/Providers/AppServiceProvider.php

# Confirm existing tests still pass
sudo -u www-data php artisan test --filter "AccessLevelScoping|PanelAccessGate|EventAccessPolicy|ChurchPolicyLocalEdit"
# EXPECT: 27/27 pass

# Quick scheme test via curl (use the host + --resolve pattern from earlier plans)
curl -sI --resolve upci.b8.co.nz:443:127.0.0.1 https://upci.b8.co.nz/admin/login | head -5
# EXPECT: 200 OK; cookies set with Secure flag (if session cookie secure is on)

# Probe the scheme as seen by the app — tinker-equivalent one-liner:
sudo -u www-data HOME=/tmp php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\$req = Illuminate\Http\Request::create('https://upci.b8.co.nz/admin/gallery-items/1/edit');
\$req->headers->set('X-Forwarded-Proto', 'https');
\$req->server->set('REMOTE_ADDR', '10.0.0.1');
echo 'isSecure: ' . var_export(\$req->isSecure(), true) . PHP_EOL;
echo 'scheme: ' . \$req->getScheme() . PHP_EOL;
"
# EXPECT: isSecure: true, scheme: https  (after Step 1; without Step 1 these report http)
```

### Manual Verification

1. Clear caches (see Step 3).
2. Open `https://upci.b8.co.nz/admin/login` in an incognito window with DevTools open.
3. **EXPECT in Network tab**: every JS/CSS asset URL starts with `https://upci.b8.co.nz/...` — zero HTTP requests, zero mixed-content warnings in Console.
4. Log in as `admin@upci.co.nz`.
5. Go to `/admin/gallery-items` → click a row → Edit.
6. **EXPECT**: page fully interactive, textarea renders, no red errors in Console. Without the fix, Livewire fails to initialise and the form is frozen.
7. Repeat for one Pages edit form (it also uses textarea / rich-text).

---

## Scope Boundaries

**IN SCOPE:**

- `bootstrap/app.php` — add `trustProxies(at: '*')`
- Optionally `app/Providers/AppServiceProvider.php` — add `URL::forceScheme('https')` guarded by APP_URL check
- Cache clears after the edit

**OUT OF SCOPE (do not touch):**

- `.env` — `APP_URL` is already correct
- Web-server / reverse-proxy config — don't touch the proxy; fix the app's trust of it
- Session cookie `secure` / `same_site` flags — a separate hardening step; see `config/session.php`. Can be followed up after this is confirmed working
- Any routes, controllers, resources, middlewares, tests other than the two files above
- Regenerating existing signed URLs (password-reset tokens in the log) — they still work, they just look like `http://` strings; natural churn
- Moving to full HSTS headers, CSP, etc. — separate hardening plan

---

## Metadata

- **Investigated by**: Claude
- **Timestamp**: 2026-04-24
- **Artifact**: `.claude/PRPs/issues/investigation-mixed-content-filament-assets.md`
- **Next step**: run `/prp-issue-fix .claude/PRPs/issues/investigation-mixed-content-filament-assets.md` OR simply apply the one-line Step 1 edit and clear caches.
