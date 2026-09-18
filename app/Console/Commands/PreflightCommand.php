<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deployment checklist. Run on the server after configuring .env:
 *   php artisan app:preflight
 * Exits non-zero when a blocking problem is found.
 */
class PreflightCommand extends Command
{
    protected $signature = 'app:preflight';

    protected $description = 'Check that the environment is safe for production use';

    protected int $failures = 0;

    public function handle(): int
    {
        $this->info('Production preflight for ' . config('conference.short_name'));
        $this->newLine();

        $this->check(config('app.env') === 'production', 'APP_ENV is production', 'APP_ENV=' . config('app.env'));
        $this->check(config('app.debug') === false, 'APP_DEBUG is off', 'Debug pages expose code and secrets to visitors');
        $this->check((bool) config('app.key'), 'APP_KEY is set', 'Run php artisan key:generate');
        $this->check(str_starts_with((string) config('app.url'), 'https://'), 'APP_URL uses https', 'APP_URL=' . config('app.url'));
        $this->check(config('session.secure') === true, 'SESSION_SECURE_COOKIE is true', 'Cookies would be sent over plain HTTP');
        $this->check((int) config('session.lifetime') >= 240, 'Session lifetime covers a scoring session', 'SESSION_LIFETIME=' . config('session.lifetime') . ' minutes; 720 recommended');
        $this->check(config('pulse.enabled') === false, 'Pulse profiling disabled', 'PULSE_ENABLED=true adds DB writes to every request');

        $driver = config('database.default');
        $this->check($driver !== 'sqlite', 'Database is not SQLite', "DB_CONNECTION={$driver}: fine for small events, MySQL recommended for many simultaneous evaluators", warnOnly: true);

        try {
            DB::connection()->getPdo();
            $this->check(true, 'Database connection works');
            $this->check(Schema::hasTable('evaluations'), 'Migrations have run', 'Run php artisan migrate --force');
            $admins = User::role(User::ROLE_ADMIN)->count();
            $this->check($admins > 0, 'At least one administrator exists', 'Run php artisan db:seed --force');
            $default = User::where('email', 'admin@conference.local')->exists();
            $this->check(! $default, 'Default admin@conference.local account removed', 'This account uses a guessable password');
            $demo = User::where('email', 'like', 'evaluator%@conference.local')->exists();
            $this->check(! $demo, 'Demo evaluator accounts removed', 'DemoSeeder accounts use the password "password"');
        } catch (\Throwable $e) {
            $this->check(false, 'Database connection works', $e->getMessage());
        }

        $this->check(file_exists(public_path('build/manifest.json')), 'Frontend build exists', 'Run npm ci && npm run build');
        $this->check(! file_exists(public_path('hot')), 'No Vite "hot" file in public/', 'Delete public/hot or assets will point at a dev server');
        $this->check(is_writable(storage_path()), 'storage/ is writable', 'chmod -R ug+rw storage bootstrap/cache');
        $this->check(is_writable(base_path('bootstrap/cache')), 'bootstrap/cache is writable');

        $this->newLine();
        if ($this->failures > 0) {
            $this->error("{$this->failures} blocking issue(s). Do not go live until they are fixed.");

            return self::FAILURE;
        }

        $this->info('All checks passed.');

        return self::SUCCESS;
    }

    protected function check(bool $ok, string $label, string $hint = '', bool $warnOnly = false): void
    {
        if ($ok) {
            $this->line("  <fg=green>PASS</> {$label}");

            return;
        }

        if ($warnOnly) {
            $this->line("  <fg=yellow>WARN</> {$label}" . ($hint ? " - {$hint}" : ''));

            return;
        }

        $this->failures++;
        $this->line("  <fg=red>FAIL</> {$label}" . ($hint ? " - {$hint}" : ''));
    }
}
