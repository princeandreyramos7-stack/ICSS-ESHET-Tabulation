<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected function casts(): array
    {
        return [
            'presentation_order' => 'integer',
        ];
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
