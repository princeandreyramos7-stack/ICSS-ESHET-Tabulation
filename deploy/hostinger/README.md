# Deploying to Hostinger (LiteSpeed shared hosting)

Two directories matter. Everything else named `Icss-folder` on the account is an old copy and must not be served.

| Role | Path |
|------|------|
| **Application** (Laravel project, NOT web-accessible) | `/home/u988863428/domains/icss-eshet-tabulation.pitonmain.com/Icss-folder` |
| **Web root** (hPanel document root of the subdomain) | `/home/u988863428/domains/pitonmain.com/public_html/domains/icss-eshet-tabulation.pitonmain.com/Icss-folder/public` |

Below, `APP` and `WEB` stand for those two paths:

```bash
APP=/home/u988863428/domains/icss-eshet-tabulation.pitonmain.com/Icss-folder
WEB=/home/u988863428/domains/pitonmain.com/public_html/domains/icss-eshet-tabulation.pitonmain.com/Icss-folder/public
```

## 1. Web root contents

The web root must contain **only** these items, all taken from this repository:

```
WEB/
├── index.php     <- deploy/hostinger/public_html/index.php  (pins APP by absolute path)
├── .htaccess     <- deploy/hostinger/public_html/.htaccess
├── .user.ini     <- deploy/hostinger/public_html/.user.ini  (PHP upload limits for manuscripts)
├── build/        <- APP/public/build   (committed Vite build)
├── img/          <- APP/public/img
├── favicon.ico   <- APP/public/favicon.ico
├── robots.txt    <- APP/public/robots.txt
└── storage       -> APP/storage/app/public   (symlink)
```

```bash
cp  "$APP/deploy/hostinger/public_html/index.php"  "$WEB/index.php"
cp  "$APP/deploy/hostinger/public_html/.htaccess"  "$WEB/.htaccess"
cp  "$APP/deploy/hostinger/public_html/.user.ini"  "$WEB/.user.ini"
rsync -a --delete "$APP/public/build/" "$WEB/build/"
rsync -a          "$APP/public/img/"   "$WEB/img/"
cp  "$APP/public/favicon.ico" "$APP/public/robots.txt" "$WEB/"
ln -sfn "$APP/storage/app/public" "$WEB/storage"
```

Do **not** use `APP/public/index.php` in the web root: it loads `../vendor` relative to itself, which is not the application.

### Verify which application the web root boots

```bash
grep -n "ICSS_PROJECT_PATH\|require" "$WEB/index.php"   # must show the APP path above
readlink -f "$WEB/storage"                              # must be APP/storage/app/public
ls -la "$WEB"                                           # nothing else: no .env, vendor/, app/ ...
```

If `ls -la "$WEB/.."` shows `app/`, `vendor/`, `.env` next to `public/`, the document root is sitting inside a
full copy of the project. Either point hPanel's document root at the real `WEB` directory or replace the copy
with the layout above. A symptom of this misconfiguration is `https://<domain>/storage/` redirecting to
`/public/storage`.

## 2. Application setup

```bash
cd "$APP"
git status && git branch --show-current            # main, clean
composer install --no-dev --optimize-autoloader
php artisan key:generate --force                    # only if APP_KEY is empty
php artisan migrate --force
php artisan db:seed --force                         # roles, admin, tracks, criteria; safe to re-run
php artisan optimize:clear && php artisan optimize
php artisan app:preflight
```

`.env` must contain `APP_ENV=production`, `APP_DEBUG=false`,
`APP_URL=https://icss-eshet-tabulation.pitonmain.com`, `APP_TIMEZONE=Asia/Manila`,
`SESSION_SECURE_COOKIE=true`, the MySQL `DB_*` values, and `ADMIN_EMAIL` / `ADMIN_PASSWORD`.
Keep **only** `.env` in `$APP`; never upload `.env.production` alongside it (see section 3, item 1).

### Releasing an update (e.g. the one-track-per-evaluator workflow)

No new `.env` keys are required; the server's existing `.env` stays as it is (it is not in Git and must not be).

```bash
cd "$APP"
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force          # adds users.track_id and tracks.venue/session_chair/co_session_chair
php artisan db:seed --force --class=TrackSeeder   # renames tracks 3/5/6 and adds track 7 with venues and chairs
php artisan optimize:clear && php artisan optimize
rsync -a --delete "$APP/public/build/" "$WEB/build/"
php artisan app:preflight
```

Then, as admin, open **Evaluators** and set the track of every existing evaluator (they cannot score until assigned).

The frontend build is committed (`public/build/`), so `npm` is not needed on the server. Rebuild locally with
`npm run build`, commit, push, then `git pull` on the server and re-sync `WEB/build/`.

### Releasing the manuscript / PDF fixes (2026-09-19)

```bash
cd "$APP"
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force                        # adds papers.manuscript_path / manuscript_original_name
php artisan optimize:clear && php artisan optimize
rsync -a --delete "$APP/public/build/" "$WEB/build/"   # REQUIRED: the browser loads JS from WEB, not APP
php artisan app:preflight
```

To confirm the deploy actually landed, compare the bundle the site serves with the one in Git:

```bash
curl -s https://icss-eshet-tabulation.pitonmain.com/login | grep -o 'build/assets/app-[A-Za-z0-9_-]*\.js'
grep -o '"file": *"assets/app-[^"]*"' "$APP/public/build/manifest.json"
```

They must match. If the site still shows the branded "Error 500" page afterwards, the real exception is in
`tail -n 80 "$APP/storage/logs/laravel.log"` - paste that, not the error page.

## 3. Every-page HTTP 500 while `php artisan` works

`/up` answering 200 while `/` and `/login` answer 500 means Laravel boots but the **web** request fails before
the session starts, i.e. the web process cannot use the database. Check, in this order:

1. **`ls -la "$APP"/.env*`** - there must be exactly one file, `.env`. If `.env.production` also exists,
   `php artisan optimize` caches **that** file's `DB_*` values instead of `.env`'s (Laravel re-reads
   `.env.<APP_ENV>` during `config:cache`). Un-cached CLI commands keep using `.env`, which is why
   `php artisan about` and `db:seed` work while the website returns 500. Fix:
   ```bash
   cd "$APP" && rm .env.production && php artisan optimize:clear && php artisan optimize
   ```
   `php artisan app:preflight` fails with "No .env.production file beside .env" while the file is present.
2. `grep require "$WEB/index.php"` - is the web root booting `$APP`, or an old copy with its own `.env`?
3. `php artisan config:clear` in whichever project the web root boots; a stale `bootstrap/cache/config.php`
   keeps old `DB_*` values even after `.env` was corrected.
4. `php artisan tinker --execute="DB::table('sessions')->count();"` from that project.
5. `tail -n 50 "$APP/storage/logs/laravel-$(date +%F).log"`.
6. Directory permissions: `chmod -R ug+rwX "$APP/storage" "$APP/bootstrap/cache"`.

## 4. "This password does not use the Bcrypt algorithm"

The stored password of that user is not a bcrypt hash (typically a row edited by hand in phpMyAdmin).
Running `php artisan db:seed --force` will reset the admin account (email: `Piton@gmail.com`) to use a 
properly bcrypt-hashed password. Never paste passwords into the `users` table directly; create or reset 
accounts through the application or through the seeder.

## 5. Manuscript uploads: "file too large" or 405 on Edit

The papers form shows the limit that is really in force: the smaller of `MANUSCRIPT_MAX_MB` in `.env`
(default 25) and PHP's `upload_max_filesize` / `post_max_size`. When PHP's limits are lower than the
file, PHP discards the whole request body before Laravel runs; the app now turns that into a toast with
the limit instead of a 405/413 page.

To raise the PHP limits, either copy `deploy/hostinger/public_html/.user.ini` to `$WEB` (section 1) or set
**upload_max_filesize**, **post_max_size** and **max_input_time** under hPanel → Advanced → PHP Configuration
→ PHP options. hPanel wins if both are set. Verify with:

```bash
cd "$APP" && php -r 'foreach (["upload_max_filesize","post_max_size"] as $k) echo "$k=", ini_get($k), PHP_EOL;'
```

(the CLI may report different values than the web server; the number on the papers form is the one that counts).
