<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'paper_id',
        'user_id',
        'total',
        'comments',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }
}
