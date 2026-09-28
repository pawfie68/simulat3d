# Production preparation — do not publish until reviewed

This patch does not update OVH, Cloudflare, GitHub secrets or mailbox settings. Existing public ZIP URLs remain public until the server is configured. No claim of complete legal compliance is made from a source-code review alone.

## 1. Confirm hosting and backups

In OVH Manager, inspect the domain's actual document root, PHP version and Apache `.htaccess` support. Use PHP 8.2 or newer. The parent copy contains legacy `SetEnv PHP_VER 5_3`; do not copy that directive into the new deployment. Ask OVH support if it conflicts with the current `.ovhconfig`. Keep a recoverable backup before changing server configuration.

Verify HTTPS for both apex and www, and enforce HTTPS at the hosting layer. The beta endpoint refuses non-HTTPS production requests. Do not spoof HTTPS with a request header. Test both canonical host behaviour and the cookie after redirects.

## 2. Set up private beta storage on OVH

Create `~/simulat3d-private/` **outside** `~/www/` and any other web-served document root. Place `config.php` there, based on `server/config.example.php`. The endpoint defaults to that location when installed in `~/www/api/beta.php`. `SIMULAT3D_PRIVATE_DIR` can override it where hosting environment variables are supported.

Use a NEW strong beta password; the old password was embedded in public JavaScript and must be considered disclosed. Generate `password_hash` using PHP's `password_hash(..., PASSWORD_DEFAULT)`, without placing the plaintext password in shell history or Git. Generate `rate_secret` with `bin2hex(random_bytes(32))`. Keep this config out of Git, FTP public directories and CI logs. The deployment does not upload secrets.

Set `version` and upload the corresponding real ZIP into the private `downloads/` subdirectory. Nothing in this patch supplies or modifies the MSFS binary. Allow PHP to read config/ZIPs and write private `sessions/` and `rate/` directories. Avoid world-readable permissions where hosting permits.

Copy `server/cleanup.php` OUTSIDE www and configure a daily OVH scheduled PHP task. Set its private path explicitly if different from the script's default. This removes expired rate-limit and session files. Session validity expires independently of cleanup. A shared password provides shared access, not named users; rotate it to revoke all sessions.

Move/remove the previous `/www/downloads/*.zip` packages after verifying the private copy. The new `public/downloads/.htaccess` denies ALL direct access under `/downloads/`, including files left by FTP deployments. Verify a known old ZIP URL returns 403/404 (not 200) on the actual server. Do not rely only on hiding links. Apache rules are not executed by Astro's dev server.

## 3. Verify data practices

- Controller provided: Simulat3D Sp. z o.o., Al. Jana Pawła II 37/3A (Building F), 31-864 Kraków, Poland. Privacy contact: office@simulat3d.eu.
- User-approved ordinary enquiry retention: one month after the conversation ends. Implement a recurring mailbox deletion process; preserve only records covered by a concrete contractual/legal/dispute need. Check sent copies, archives and provider backups.
- Confirm the mailbox provider (the data controller and email hosting provider are different roles).
- Check OVH, Cloudflare and Resend processor agreements, processing locations, sub-processors, logs/retention and any non-EEA transfer mechanism actually applicable to this account. Do not invent region or SCC/adequacy claims. Enter verified facts in `src/data/privacy.json`; set `providerReviewConfirmed` only after review.
- The current Cloudflare contact worker lives outside this Git repository in the sibling `simulat3d-contact` directory. Its production configuration and error logs must be reviewed separately. Source presently logs Resend error responses; avoid personal data in provider error logs. This patch does not deploy that worker.
- Review the editor-only Decap/GitHub OAuth integration and revoke stale tokens. It is separate from beta access. OAuth code in the sibling directory still contains an old GitHub Pages origin. Do not treat the public-site policy as a complete audit of editorial account security.

## 4. Verify in a staging document root first

Use the built static site together with PHP. Check missing config returns 503; anonymous download returns 401; wrong password fails; correct password gives HttpOnly/Secure/SameSite=Strict cookie; refresh retains access; logout/expiry/password rotation revoke it; ZIP cannot be accessed directly. The integration test suite covers application logic, but hosting rewrites, HTTPS, filesystem permissions and Apache rules require a real-server check.

Ensure browser network requests on `/` and `/msfs-beta/` use only same-origin resources before any contact submission. Review cookies and storage with a fresh browser profile. The CMS is a separate external-service workflow. Confirm form notices, PL/EN policy and keyboard access. Do not send real test messages without permission.

After these checks, set `deploymentVerified=true` and complete the remaining privacy configuration. `npm run check:release` must pass; draft warnings must disappear.

## 5. Publish only with explicit approval

The existing FTP workflow remains bound to main and manual dispatch. It now uses `npm ci`, tests, build and privacy readiness checks. The branch/PR workflow validates without publishing. FTP_PASSWORD remains in GitHub Secrets; no credential changes are required by this patch. The public `.htaccess` and PHP endpoint are included in `dist/` by Astro.

Request approval for the concrete reviewed deployment. A push to main can publish. Never merge/push simply to test a production change. After upload, repeat the direct-ZIP and session checks. A failed deployment should not restore publicly accessible ZIPs as a rollback shortcut.
