<?php

namespace App\Support;

/**
 * The manuscript upload ceiling actually in force on this server.
 *
 * The app's own limit (config manuscripts.max_upload_mb) is capped by PHP's
 * upload_max_filesize and post_max_size, so the form can promise only what
 * the host will accept and validation messages can quote the real number.
 */
class UploadLimit
{
    /** Room left in post_max_size for the other form fields and multipart framing. */
    private const FORM_OVERHEAD_BYTES = 256 * 1024;

    public static function manuscriptBytes(): int
    {
        $limits = [
            (int) config('manuscripts.max_upload_mb', 25) * 1024 * 1024,
            self::iniBytes('upload_max_filesize'),
            self::iniBytes('post_max_size') - self::FORM_OVERHEAD_BYTES,
        ];

        // 0 / unlimited ini values impose no cap.
        $limits = array_filter($limits, fn ($b) => $b > 0);

        return max(1024 * 1024, min($limits));
    }

    public static function manuscriptKilobytes(): int
    {
        return intdiv(self::manuscriptBytes(), 1024);
    }

    /** Whole megabytes, rounded down, for display ("max 25 MB"). */
    public static function manuscriptMegabytes(): int
    {
        return max(1, intdiv(self::manuscriptBytes(), 1024 * 1024));
    }

    /** Parse a php.ini shorthand size ("8M", "512K", "1G", "0") into bytes; 0 means unlimited. */
    public static function iniBytes(string $key): int
    {
        $raw = trim((string) ini_get($key));
        if ($raw === '' || $raw === '-1') {
            return 0;
        }

        $unit = strtolower(substr($raw, -1));
        $value = (float) $raw;

        return (int) match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
