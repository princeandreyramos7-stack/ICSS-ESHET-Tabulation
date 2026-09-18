<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Track extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'name',
        'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'is_locked' => 'boolean',
        ];
    }

    public function papers(): HasMany
    {
        return $this->hasMany(Paper::class)->orderBy('presentation_order')->orderBy('paper_no');
    }

    public function getLabelAttribute(): string
    {
        return "Track {$this->number}: {$this->name}";
    }
}
