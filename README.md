# Conference Tabulation System

Online tabulation for research presentations at the **2nd International Conference on Sustainable Solutions in Engineering, Science, Health, Education, and Technology (ICSS-ESHET 2026)**. Evaluators score each paper on the official five-criterion rubric from any device; the administrator manages papers and evaluators, locks tracks when presentations finish, and prints result sheets.

Built with Laravel 12, Inertia.js and React.

## How it works

**Roles**

| Role | Can do |
|------|--------|
| Administrator | Add/edit papers and evaluator accounts, lock/unlock tracks, view and print per-track result sheets and per-paper breakdowns. |
| Evaluator | Assigned to **one** track by the administrator. Signs in, rates every paper in that track on the five criteria, adds comments, and can revise ratings until the track is locked. |

**Tracks** (parallel sessions, Day 02 - each with a venue, session chair, co-session chair and its own panel of evaluators)

1. Sustainable Engineering Solutions / Renewable Energy and Environmental Technologies
2. Bridging Technology and Public Health / Cross-Disciplinary Approaches to Global Health Challenges
3. Digital Innovations in Education and Social Sciences
4. Computing Technology
5. Cross-Disciplinary in Legal Justice, Human Arts and Architecture
6. Extension Track - Human Development, Sustainable Agriculture, Health and Environmental Resilience
7. Extension Track - Engineering, Smart Analytics, ICT and Digital Innovations

**Criteria** (identical for every track, total 100)

| Criterion | Max rating |
|-----------|-----------|
| Originality, novelty, creativity, or innovativeness | 25 |
| Significance, impact, or contribution | 25 |
| Clarity and coherence of the oral presentation including materials and delivery | 15 |
| Mastery of the subject | 20 |
| Quality of presentation materials | 15 |

An evaluator's total for a paper is the sum of the five ratings. The paper's **average** is the mean of all submitted evaluator totals. **Rank** within a track uses competition ranking (tied papers share a rank; the next rank is skipped).

**Flow**

1. Admin creates evaluator accounts and assigns each one to a track (Evaluators page), then enters papers with their track and paper number (Papers page).
2. Evaluators sign in and see only their own track. They pick a paper and fill in the rubric. Every rating is validated against its maximum both in the browser and on the server.
3. Evaluators can revise a submitted evaluation while the track is open.
4. When a track's presentations are done, the admin locks it from **Tracks & Locks**. Locked tracks reject any further changes.
5. Admin prints the track result sheet (papers x evaluators, average, rank, signature block) or a per-paper breakdown with comments.

## Local setup

```bash
composer install
npm install
cp .env.example .env          # then edit DB_* and ADMIN_* values
php artisan key:generate
php artisan migrate --seed    # roles, admin account, 7 tracks, 5 criteria
npm run dev                   # in one terminal
php artisan serve             # in another
```

## Production deployment

Run the built-in checklist on the server after configuring `.env`; it exits with an error while anything blocking remains:

```bash
php artisan app:preflight
```

It verifies debug mode, HTTPS, secure cookies, session lifetime, database and migrations, that an administrator exists, that the default and demo accounts are gone, and that the frontend build is present.

1. Set these in `.env` on the server:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`
   - `DB_*` for MySQL (SQLite is fine only for small single-server setups)
   - `ADMIN_EMAIL` and a strong `ADMIN_PASSWORD` **before** seeding
   - `SESSION_SECURE_COOKIE=true` when served over HTTPS
   - `CONFERENCE_*` to change the branding without touching code
2. Run:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan key:generate
   php artisan migrate --force --seed
   php artisan optimize
   ```
3. Point the web server document root to `public/`.
4. Sign in as the admin, add evaluators and papers, then share evaluator credentials.

The app trusts the reverse proxy headers so links are generated with `https` behind hosting panels or Cloudflare.

### Changing the admin password

Sign in as the admin and use **My account** in the sidebar footer. Evaluator passwords are reset by the admin from the Evaluators page (Edit, then type a new password).

## Security notes

- No public registration and no email password reset; the administrator creates accounts and resets passwords.
- Login is rate-limited (10 attempts per minute per IP, plus Breeze's per-account lockout). Evaluation submits are limited to 60 per minute per user.
- Every rating is validated server-side against its criterion maximum; totals are computed server-side and never trusted from the client.
- One evaluation per evaluator per paper is enforced by a unique index, and concurrent duplicate submits are retried safely.
- Track locks are checked inside the write transaction, so a lock applied a moment earlier always wins.
- Evaluators only ever receive their own ratings, and only for the track they are assigned to; scoring a paper from another track is refused (403). Results, averages and other evaluators' scores are admin-only.
- Browser security headers (nosniff, frame-ancestors same-origin, referrer policy, HSTS over HTTPS) are sent on every response.
- Laravel Pulse is disabled by default (`PULSE_ENABLED=false`); when enabled it is admin-only at `/pulse`.
- Seeders refuse to create the default administrator or demo accounts in production.

## Tests

```bash
php artisan test
```

Covers rubric validation (ranges, missing criteria, decimals), lock enforcement, role separation, ranking with ties, paper/evaluator management, and that every page renders with the expected props.
