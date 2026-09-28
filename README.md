# Simulat3D

Astro 4 static site + Decap CMS. Git origin remains `https://github.com/pawfie68/simulat3d.git`.
GitHub Actions builds `dist/` and uploads it to OVH `/www/`. PHP 8.2+ handles beta sessions and downloads on OVH; Node is required only for building/development.

## Local preview

Use Node 22 (as in CI). Run commands in this repository, not its parent:

```powershell
npm ci
npm run dev -- --host 127.0.0.1
```

Open http://127.0.0.1:4321/. The Astro proxy sends `/api/` to PHP on `127.0.0.1:4322`.
Set up a local **non-production** private folder using `server/config.example.php` and a unique test password hash. Set `SIMULAT3D_PRIVATE_DIR` to its absolute path, set `SIMULAT3D_LOCAL=1`, then:

```powershell
php -S 127.0.0.1:4322 server/router.php
```

Private packages go in `<private-folder>/downloads/SKALARKIe_MSFSInterface_v<version>.zip`.
The private config selects the version. A missing package returns 404. No public downloads are served by the PHP development router. Never expose this development server to the network.

`npm run preview` previews static build output only; it does not execute PHP. Use the paired Astro/PHP development servers to test beta authentication.

## Checks and deployment

```powershell
npm test
npm run build
npm run check:release
```

Tests require PHP 8.2+ in PATH, or set `PHP_BIN` to the PHP executable. Security tests use temporary test credentials and packages, not OVH.
`check:release` intentionally fails while privacy/provider/hosting facts are unconfirmed. Do not bypass it. See [deployment instructions](docs/DEPLOYMENT.md).

Pushes to non-main branches and PRs run CI without deployment. Pushes to main or a manual run of `deploy.yml` can publish to OVH after all checks pass. Do not run `push.bat` or push main without approval to publish.

## Privacy

Fonts and their OFL licences are in `public/fonts/`. The site has no analytics or marketing tracker. The previous consent banner has been removed. Language selection uses sessionStorage only on explicit choice. Beta uses a seven-day server-validated session and an HttpOnly/Secure/SameSite cookie in production. The contact form still uses the existing Cloudflare Worker and Resend; it does not subscribe users to marketing. The policy is at `/privacy/` (PL/EN).

`src/data/privacy.json` contains controller details and facts that must match operational practice. A one-month correspondence retention rule requires mailbox deletion, including applicable sent copies/backups; publishing text alone does not perform that deletion.

Decap CMS and GitHub integration are retained. The CMS is for editors, not beta testers. Its external services and browser token storage are described separately in the policy.
