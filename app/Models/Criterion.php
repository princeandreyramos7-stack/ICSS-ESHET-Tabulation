<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Criterion extends Model
{
    use HasFactory;

    protected $table = 'criteria';

    protected $fillable = [
        'name',
        'description',
        'weight',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
