<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Paper extends Model
{
    use HasFactory;

    protected $fillable = [
        'track_id',
        'paper_no',
        'title',
        'researcher',
        'affiliation',
        'presentation_order',
        'manuscript_path',
        'manuscript_original_name',
    ];

    protected function casts(): array
    {
        return [
            'presentation_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Paper $paper) {
            // Delete manuscript file when paper is deleted
            if ($paper->manuscript_path) {
                Storage::delete($paper->manuscript_path);
            }
        });
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * Absolute filesystem path of the uploaded manuscript, or null when there is
     * no upload or the file is not readable. Resolved from the disk root directly
     * so serving never depends on Flysystem metadata calls.
     */
    public function manuscriptAbsolutePath(): ?string
    {
        if (empty($this->manuscript_path)) {
            return null;
        }

        try {
            $path = Storage::path($this->manuscript_path);
        } catch (\Throwable) {
            return null;
        }

        return is_file($path) && is_readable($path) ? $path : null;
    }

    public function hasManuscript(): bool
    {
        return $this->manuscriptAbsolutePath() !== null;
    }

    /** Name shown to users and used for downloads; always ends in .pdf. */
    public function manuscriptDisplayName(): string
    {
        $name = trim((string) $this->manuscript_original_name);
        if ($name === '') {
            $name = 'Paper-' . $this->paper_no . '.pdf';
        }

        return str_ends_with(strtolower($name), '.pdf') ? $name : $name . '.pdf';
    }

    /** Inline view URL (iframe preview / full tab), null when nothing is uploaded. */
    public function getManuscriptUrlAttribute(): ?string
    {
        return $this->hasManuscript() ? route('manuscripts.show', $this->id) : null;
    }

    /** Forced-download URL, null when nothing is uploaded. */
    public function getManuscriptDownloadUrlAttribute(): ?string
    {
        return $this->hasManuscript() ? route('manuscripts.download', $this->id) : null;
    }
}
