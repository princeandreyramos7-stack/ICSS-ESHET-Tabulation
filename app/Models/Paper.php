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
     * Check if paper has a manuscript uploaded
     */
    public function hasManuscript(): bool
    {
        return !empty($this->manuscript_path) && Storage::exists($this->manuscript_path);
    }

    /**
     * Get the full URL to download/view the manuscript
     */
    public function getManuscriptUrlAttribute(): ?string
    {
        return $this->hasManuscript() ? route('manuscripts.show', $this->id) : null;
    }
}