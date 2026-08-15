# Feature: Fix CMS Image Upload and Display (nginx + APP_URL)

## Summary

The CMS admin panel cannot upload photos and existing CMS images do not render on the frontend. Two independent server-configuration bugs cause this: (1) the `upci.b8.co.nz` nginx vhost has no `client_max_body_size` directive, so nginx rejects any upload >1 MB with HTTP 413 before PHP ever sees it, and (2) `APP_URL=http://localhost:8000` in `.env` makes Laravel's `public` filesystem disk return URLs like `http://localhost:8000/storage/...` — unreachable from any real browser. This plan fixes both, verifies the `storage` symlink and permissions, and validates end-to-end upload/display for the Filament forms that use `FileUpload` (Departments, Pages/CMS builder, Gallery Items).

## User Story

As a **CMS administrator**
I want to **upload photos in the Filament admin and see them render on the public site**
So that **I can manage Department hero images, CMS page blocks (hero/image/cards), and Gallery Items without asking a developer**

## Problem Statement

- Uploading any image >1 MB via `/admin` forms (Departments, Pages, Gallery Items) fails silently or with a 413 error from nginx.
- Images already referenced by the CMS (and newly uploaded ones, if any slip through) do not load in browsers because their URLs resolve to `http://localhost:8000/storage/...`.
- `storage/app/public/` is empty (only `.gitignore`) — confirming that no upload has ever successfully persisted on this host.

Testable success signal: Admin user uploads a 2 MB JPEG via `/admin/departments/create`, the file appears under `storage/app/public/department-images/`, and the image renders on `https://upci.b8.co.nz/storage/department-images/{file}` and in the Filament preview.

## Solution Statement

Two pinpoint config edits, one verification, one regression check:

1. **nginx**: Add `client_max_body_size 25M;` to `/etc/nginx/sites-available/upci.b8.co.nz` (matching PHP's `post_max_size = 25M`). Reload nginx.
2. **APP_URL**: Set `APP_URL=https://upci.b8.co.nz` (or the correct canonical host) in `/var/www/personal/upci.co.nz/.env`. Clear Laravel config cache.
3. **Storage symlink**: Confirm `public/storage → storage/app/public` exists (it does) and that `storage/app/public` is writable by `www-data` (it is — `775`, owned by `www-data:www-data`).
4. **Regression**: Upload a test image, delete it via Filament, and confirm the public frontend still renders other images.

No application code changes are required. No schema changes. No new Filament resources. The CMS already has working `FileUpload` fields on Departments, Gallery Items, and the Pages builder — they're blocked purely by infrastructure/config.

## Metadata

| Field            | Value                                                              |
| ---------------- | ------------------------------------------------------------------ |
| Type             | BUG_FIX                                                            |
| Complexity       | LOW                                                                |
| Systems Affected | nginx vhost, Laravel `.env`, Filament admin, public storage symlink |
| Dependencies     | Laravel 11, Filament v4, PHP 8.4-FPM, nginx (existing versions)    |
| Estimated Tasks  | 5                                                                  |

---

## UX Design

### Before State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                              BEFORE STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   ┌──────────────┐      ┌──────────────┐      ┌──────────────────────┐       ║
║   │   Admin      │      │   Click      │      │  413 Payload Too    │       ║
║   │ /admin/      │ ───► │  "Upload"    │ ───► │  Large (nginx)       │       ║
║   │ departments  │      │  (2MB JPEG)  │      │  OR silent failure   │       ║
║   └──────────────┘      └──────────────┘      └──────────────────────┘       ║
║                                                                               ║
║   ┌──────────────┐      ┌──────────────┐      ┌──────────────────────┐       ║
║   │  Frontend    │      │  Image src=  │      │  Broken image       │       ║
║   │ /departments │ ───► │  http://     │ ───► │  (localhost:8000     │       ║
║   │              │      │  localhost:  │      │   unreachable)       │       ║
║   │              │      │  8000/...    │      │                      │       ║
║   └──────────────┘      └──────────────┘      └──────────────────────┘       ║
║                                                                               ║
║   PAIN_POINT: Admin cannot persist any image >1 MB; even if persisted, URLs  ║
║               point at localhost:8000 and don't render for end users.         ║
║   DATA_FLOW:  Browser → nginx (REJECTS >1MB) → PHP-FPM → Filament never runs ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                               AFTER STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   ┌──────────────┐      ┌──────────────┐      ┌──────────────────────┐       ║
║   │   Admin      │      │  Livewire    │      │ storage/app/public/ │       ║
║   │ /admin/      │ ───► │  temp upload │ ───► │ department-images/  │       ║
║   │ departments  │      │  → move      │      │  hero-abc123.jpg    │       ║
║   └──────────────┘      └──────────────┘      └──────────────────────┘       ║
║                                                                               ║
║   ┌──────────────┐      ┌──────────────┐      ┌──────────────────────┐       ║
║   │  Frontend    │      │  Image src=  │      │  Image renders ✅    │       ║
║   │ /departments │ ───► │ https://     │ ───► │  (served via         │       ║
║   │              │      │ upci.b8.co.  │      │  public/storage      │       ║
║   │              │      │ nz/storage/ │      │  symlink)            │       ║
║   └──────────────┘      └──────────────┘      └──────────────────────┘       ║
║                                                                               ║
║   VALUE_ADD: Admin can manage all CMS media; frontend renders CMS images.    ║
║   DATA_FLOW: Browser → nginx (25M allowed) → PHP-FPM → Filament → `public`   ║
║              disk → storage/app/public/ → public/storage symlink → browser.  ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes
| Location | Before | After | User Impact |
|----------|--------|-------|-------------|
| `/admin/departments/create` (Hero Image) | Upload fails at nginx (413) | Upload succeeds up to 5 MB (FileUpload cap) | Can manage Department hero images |
| `/admin/pages/*` (CMS builder hero/image/cards) | Same nginx 413 | Uploads succeed | Can build CMS pages with images |
| `/admin/gallery-items/create` (Image Path) | Same nginx 413 | Uploads succeed | Can populate the Gallery |
| `/departments/{slug}` frontend | `<img>` resolves to `localhost:8000` → broken | Resolves to `upci.b8.co.nz` → renders | End users see department hero images |
| Filament image preview in admin | Broken preview (localhost URL) | Working preview | Admin sees what they uploaded |

---

## Mandatory Reading

**Files the implementer must read before editing anything:**

| Priority | File | Lines | Why Read This |
|----------|------|-------|---------------|
| P0 | `/etc/nginx/sites-available/upci.b8.co.nz` | 1-31 | The vhost to patch — currently lacks `client_max_body_size` |
| P0 | `/var/www/personal/upci.co.nz/.env` | 1-10 | Contains wrong `APP_URL=http://localhost:8000` at line 5 |
| P1 | `/var/www/personal/upci.co.nz/config/filesystems.php` | 41-48 | `public` disk URL derives from `APP_URL` — confirms the symptom |
| P1 | `/etc/nginx/sites-available/dms-staging.b8.co.nz` | — | Sibling vhost on same host that **already** uses `client_max_body_size 25M` — the pattern to mirror |
| P2 | `/var/www/personal/upci.co.nz/app/Filament/Resources/Departments/Schemas/DepartmentForm.php` | 45-49 | Example `FileUpload` with `->directory('department-images')->maxSize(5120)` |
| P2 | `/var/www/personal/upci.co.nz/app/Filament/Resources/Pages/Schemas/PageForm.php` | 90-94, 149-154, 237-241 | All CMS `FileUpload` fields — regression surface |
| P2 | `/var/www/personal/upci.co.nz/app/Filament/Resources/GalleryItems/Schemas/GalleryItemForm.php` | 21-24 | Gallery `FileUpload` — regression surface |
| P3 | `/etc/php/8.4/fpm/php.ini` | — | Confirms `post_max_size = 25M`, `upload_max_filesize = 20M` (don't need to change) |

**External Documentation:**
| Source | Section | Why Needed |
|--------|---------|------------|
| [nginx `client_max_body_size`](https://nginx.org/en/docs/http/ngx_http_core_module.html#client_max_body_size) | Directive ref | Default is 1M; scope rules (http/server/location) |
| [Laravel 11 filesystem — public disk](https://laravel.com/docs/11.x/filesystem#the-public-disk) | "Public Disk" + "The Local Driver" | Confirms `APP_URL` drives `public` disk URL and the `storage:link` requirement |
| [Filament v4 FileUpload](https://filamentphp.com/docs/4.x/forms/file-upload) | Disks / Directories | Defaults to the panel's default filesystem disk (`public`); `->directory()` sets subfolder |
| [Livewire v3 file uploads](https://livewire.laravel.com/docs/uploads#configuration) | Temporary upload config | Livewire chunks uploads; final POST still subject to nginx `client_max_body_size` |

---

## Patterns to Mirror

**NGINX_UPLOAD_LIMIT:**
```nginx
# SOURCE: /etc/nginx/sites-available/dms-staging.b8.co.nz
# (existing sibling vhost on this host — mirror this directive placement)
server {
    listen 80;
    server_name dms-staging.b8.co.nz;
    root /var/www/.../public;
    index index.php;

    client_max_body_size 25M;   # <-- ADD THIS (matches PHP post_max_size)

    # ... rest unchanged ...
}
```

**ENV_APP_URL:**
```env
# SOURCE: standard Laravel 11 .env pattern
# BEFORE:
APP_URL=http://localhost:8000
# AFTER (use the real canonical host the site is reached at):
APP_URL=https://upci.b8.co.nz
```

**FILAMENT_FILEUPLOAD (reference only — not being modified):**
```php
// SOURCE: app/Filament/Resources/Departments/Schemas/DepartmentForm.php:45-49
FileUpload::make('hero_image')
    ->label('Hero Image')
    ->image()
    ->directory('department-images')
    ->maxSize(5120), // KB — i.e. 5 MB
```

---

## Files to Change

| File | Action | Justification |
|------|--------|---------------|
| `/etc/nginx/sites-available/upci.b8.co.nz` | UPDATE | Add `client_max_body_size 25M;` inside `server {}` block |
| `/var/www/personal/upci.co.nz/.env` | UPDATE | Change `APP_URL` from `http://localhost:8000` to the canonical production URL |

No application source files change. No migrations. No new routes.

---

## NOT Building (Scope Limits)

Explicit exclusions to prevent scope creep:

- **Not building a Leadership CMS resource.** `resources/js/views/about/Leadership.vue` is hardcoded and there is no Filament resource for it. If the long-term goal is to manage leadership (with photos) through the CMS, that's a separate feature (new `Leader` model + migration + Filament resource + Vue integration) and out of scope here. This plan only fixes the existing upload/display pipeline used by Departments, Pages builder, and Gallery Items.
- **Not migrating to S3 / remote storage.** `config/filesystems.php` has an `s3` disk stub but no credentials. Keeping local `public` disk.
- **Not changing PHP-FPM limits.** `upload_max_filesize=20M`, `post_max_size=25M` are already sufficient for the 5 MB FileUpload caps.
- **Not enabling HTTPS / TLS on the vhost.** The current vhost only listens on `:80`. If the site is actually served over TLS by a separate terminator (Cloudflare, load balancer, reverse proxy), use `https://` in `APP_URL`; otherwise `http://`. Operator decides based on actual deployment. (See Task 2 gotcha.)
- **Not changing `APP_ENV=local` / `APP_DEBUG=true`.** Flag separately; out of scope for this upload fix.
- **Not adding a test suite** for infrastructure config. Validation is manual via the steps below.

---

## Step-by-Step Tasks

Execute in order. Each task is atomic and independently verifiable.

### Task 1: UPDATE `/etc/nginx/sites-available/upci.b8.co.nz`

- **ACTION**: Add `client_max_body_size 25M;` directive inside the `server {}` block, above the `location /` block
- **MIRROR**: `/etc/nginx/sites-available/dms-staging.b8.co.nz` — places the directive near the top of `server {}`
- **IMPLEMENT**: Insert one line after `charset utf-8;`:
  ```nginx
  client_max_body_size 25M;
  ```
- **WHY 25M**: Matches PHP-FPM `post_max_size = 25M` (ceiling of what PHP will accept); keeps nginx from being the narrower pipe
- **GOTCHA**: Requires `root` / `sudo` (the implementer must have privileges to edit `/etc/nginx/`). This edit is on the host, not inside the Laravel repo.
- **GOTCHA**: Do not put the directive inside a `location` block — keep it at `server` scope so it applies to both `/` and `\.php$`
- **VALIDATE**:
  ```bash
  sudo nginx -t
  sudo systemctl reload nginx
  curl -sI -X POST -H "Content-Length: 2000000" http://upci.b8.co.nz/admin/ | head -1
  # Should NOT be "413 Request Entity Too Large"
  ```

### Task 2: UPDATE `/var/www/personal/upci.co.nz/.env`

- **ACTION**: Change `APP_URL` from `http://localhost:8000` to the actual canonical URL
- **IMPLEMENT**: Replace line 5. **Confirm with the operator which URL is canonical** before editing:
  - If the site is currently reached at `http://upci.b8.co.nz` → `APP_URL=http://upci.b8.co.nz`
  - If served via HTTPS (Cloudflare/LB terminating TLS) → `APP_URL=https://upci.b8.co.nz`
  - If the production domain is `upci.co.nz` (the repo name suggests so) → use that instead
- **GOTCHA**: The `public` disk URL is built as `APP_URL . '/storage'` (see `config/filesystems.php:45`). Any trailing slash on `APP_URL` will produce `//storage` — don't add one.
- **GOTCHA**: If the site is reached via multiple hostnames, pick the canonical one. Image `src` will be absolute URLs containing this value.
- **POST-EDIT**: Clear Laravel caches so the new env takes effect:
  ```bash
  cd /var/www/personal/upci.co.nz
  sudo -u www-data php artisan config:clear
  sudo -u www-data php artisan cache:clear
  sudo -u www-data php artisan view:clear
  ```
- **VALIDATE**:
  ```bash
  cd /var/www/personal/upci.co.nz
  sudo -u www-data php artisan tinker --execute="echo Storage::disk('public')->url('test.jpg');"
  # Should output: https://upci.b8.co.nz/storage/test.jpg  (not localhost:8000)
  ```

### Task 3: VERIFY storage symlink and writable directory

- **ACTION**: Confirm the symlink and permissions are correct (they appear to be; this is a defensive check)
- **CHECKS**:
  ```bash
  ls -la /var/www/personal/upci.co.nz/public/storage
  # Expect: symlink → /var/www/personal/upci.co.nz/storage/app/public

  stat -c '%U:%G %a' /var/www/personal/upci.co.nz/storage/app/public
  # Expect: www-data:www-data 775 (or similar — must be writable by php-fpm user)
  ```
- **IF BROKEN**: Recreate with:
  ```bash
  cd /var/www/personal/upci.co.nz
  sudo -u www-data php artisan storage:link
  sudo chown -R www-data:www-data storage/ bootstrap/cache/
  sudo chmod -R 775 storage/ bootstrap/cache/
  ```
- **VALIDATE**: File written via the `public` disk must be readable by nginx. Quick round-trip:
  ```bash
  echo "ok" | sudo -u www-data tee /var/www/personal/upci.co.nz/storage/app/public/ping.txt
  curl -s http://upci.b8.co.nz/storage/ping.txt
  # Expect: "ok"
  sudo rm /var/www/personal/upci.co.nz/storage/app/public/ping.txt
  ```

### Task 4: End-to-end admin upload test (Departments)

- **ACTION**: Manually verify the fix in the Filament admin
- **STEPS**:
  1. Log in at `/admin`
  2. Go to **Departments → New Department**
  3. Fill `name` and `slug`
  4. Upload a ~2 MB JPEG to **Hero Image** (this would have failed before the fix)
  5. Save
  6. Confirm the file exists on disk:
     ```bash
     ls -la /var/www/personal/upci.co.nz/storage/app/public/department-images/
     ```
  7. Reopen the record in admin — the image preview must render (not a broken icon)
  8. Hit the public URL the admin shows for the preview — it must load in an incognito browser

### Task 5: End-to-end frontend display test

- **ACTION**: Confirm CMS pages and gallery render images for end users
- **STEPS**:
  1. Using the Department created in Task 4, visit the public-facing department page (whatever route Vue uses for `/departments/{slug}` — see `resources/js/router/routes.js`) and confirm the hero image renders
  2. In admin, create a CMS Page with a **Hero** block containing a background image and an **Image** block; publish it
  3. Visit `/cms/{slug}` and confirm both images render
  4. In admin, create a Gallery Item with an image; visit the public gallery route and confirm it renders
  5. Inspect the DOM: `<img src="...">` URLs must start with the `APP_URL` set in Task 2, not `localhost:8000`

---

## Testing Strategy

### Manual Tests (no automated suite for config changes)

| Test | Validates |
|------|-----------|
| `nginx -t` passes | Syntactically valid nginx config |
| `curl -I` with 2 MB payload does not return 413 | Task 1 effective |
| `Storage::disk('public')->url('x')` returns `https://upci.b8.co.nz/storage/x` | Task 2 effective |
| `curl http://upci.b8.co.nz/storage/{known-file}` returns the file | Symlink + web server integration OK |
| Admin upload of a 2 MB image to Departments completes | Full path works |
| Frontend image tag renders in a browser not on localhost | Display works for real users |

### Edge Cases Checklist

- [ ] Upload exactly at the FileUpload `maxSize` cap (5120 KB for most fields, 2048 KB for CMS card icons)
- [ ] Upload just over 5 MB — Filament should reject with a user-facing message (not a nginx 413)
- [ ] Upload a non-image extension — `->image()` on FileUpload should reject it
- [ ] Upload a file named with spaces / non-ASCII chars — Laravel should hash-rename
- [ ] Admin deletes an image — file removed from `storage/app/public/` and no orphan URL remains in DB
- [ ] Visit site over HTTPS (if TLS terminator present) — no mixed-content warnings on image URLs
- [ ] `APP_URL` value matches the scheme the user is actually on (http vs https)

---

## Validation Commands

### Level 1: Nginx syntax

```bash
sudo nginx -t
```
**EXPECT**: `syntax is ok` and `test is successful`

### Level 2: Nginx reload

```bash
sudo systemctl reload nginx
systemctl status nginx --no-pager | head -5
```
**EXPECT**: `active (running)`

### Level 3: Laravel config reload

```bash
cd /var/www/personal/upci.co.nz
sudo -u www-data php artisan config:clear && sudo -u www-data php artisan cache:clear
```
**EXPECT**: `Configuration cache cleared successfully.`

### Level 4: Live URL check

```bash
sudo -u www-data php /var/www/personal/upci.co.nz/artisan tinker --execute="echo Storage::disk('public')->url('test.jpg');"
```
**EXPECT**: `https://upci.b8.co.nz/storage/test.jpg` (or whichever URL was chosen in Task 2)

### Level 5: End-to-end upload + display (manual, browser)

See Tasks 4 and 5.

### Level 6: Laravel log review

```bash
tail -n 200 /var/www/personal/upci.co.nz/storage/logs/laravel.log
```
**EXPECT**: No new errors during/after the upload test. No `PostTooLargeException`, no `FileNotFoundException`.

---

## Acceptance Criteria

- [ ] `nginx -t` passes and nginx reloads cleanly
- [ ] `/etc/nginx/sites-available/upci.b8.co.nz` contains `client_max_body_size 25M;` inside the `server {}` block
- [ ] `.env` `APP_URL` is set to the canonical, publicly-reachable URL (not `localhost`)
- [ ] `php artisan tinker` shows `Storage::disk('public')->url('x')` returning the canonical URL
- [ ] A 2 MB JPEG uploaded via `/admin/departments/create` persists under `storage/app/public/department-images/`
- [ ] Filament admin preview of the uploaded image renders
- [ ] The same image renders when requested from `{APP_URL}/storage/department-images/{file}` in a fresh browser
- [ ] CMS Page builder hero/image/cards uploads round-trip successfully
- [ ] Gallery Items upload round-trips successfully
- [ ] No regressions in existing routes (home, /about/*, /find-church)

---

## Completion Checklist

- [ ] Task 1 complete, nginx reloaded
- [ ] Task 2 complete, Laravel config cleared
- [ ] Task 3 verified (symlink + perms)
- [ ] Task 4 end-to-end admin upload successful
- [ ] Task 5 end-to-end frontend display successful
- [ ] Edge cases spot-checked
- [ ] No new errors in `laravel.log`

---

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Operator picks wrong `APP_URL` scheme (http vs https) | MED | MED | Confirm in advance which scheme users actually hit; mixed content is the visible symptom — easy to catch in Task 5 |
| Other nginx contexts (default server, http block) override the per-vhost limit | LOW | LOW | Default nginx ships no `client_max_body_size` at http scope; the per-vhost value wins. Verify with `nginx -T | grep client_max_body_size` after reload |
| `APP_URL` change breaks hard-coded `localhost:8000` references elsewhere (e.g., old seeds, external webhook URLs) | LOW | LOW | Grep the repo for `localhost:8000` before changing (`rg "localhost:8000"`); the original intent of that value was a dev placeholder — nothing in app code should depend on it |
| Cloudflare/upstream proxy has its own body-size limit | LOW | MED | If uploads still 413 after Task 1, check whatever sits in front of nginx; this is a known gotcha (Cloudflare free = 100 MB, fine) |
| Old records in DB reference image paths that no longer exist (since `storage/app/public` is empty) | MED | LOW | These were never successfully uploaded in the first place; admin can re-upload. If there's meaningful orphan data, surface it — but it's out of scope of this fix |
| FileUpload's `->image()` MIME check rejects a valid JPEG with unusual extension | LOW | LOW | Task 5 edge-case list covers this — reject is expected behaviour, user just renames |

---

## Notes

- **Why this looks like nginx + env, not Laravel code:** `storage/app/public/` is completely empty (only `.gitignore`). If the Filament code path were broken, you'd expect partial writes or at least errors in `laravel.log`. The fact that nothing has ever landed on disk is consistent with nginx rejecting the request before PHP starts. The user's instinct ("might be an nginx problem") is correct.
- **Why `APP_URL` matters even with the symlink working:** Laravel's `public` disk builds asset URLs from `config('app.url')` (see `config/filesystems.php:45`). Filament's `FileUpload` uses `Storage::disk('public')->url()` for previews, and the Vue frontend receives absolute URLs from the JSON API. A wrong `APP_URL` = wrong hostname in every `<img src>`.
- **Sibling vhost precedent:** `dms-staging.b8.co.nz` already sets `client_max_body_size 25M` on the same host. Using the same value here is consistent.
- **Follow-up (separate plan, not this one):** consider adding a Leadership Filament resource + Vue binding if managing leadership photos through the CMS is actually desired. The hardcoded `Leadership.vue` currently has no admin entry point. Flag with the user.
- **Security housekeeping (also out of scope):** `APP_ENV=local` and `APP_DEBUG=true` in a publicly-reachable deployment should be changed to `production` / `false` once this fix is verified.
