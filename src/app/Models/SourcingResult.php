<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourcingResult extends Model
{
    protected $fillable = [
        'inquiry_id',
        'status',
        'query',
        'llm_provider',
        'llm_model',
        'search_provider',
        'results',
        'raw_search',
        'input_tokens',
        'output_tokens',
        'error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'results'     => 'array',
            'raw_search'  => 'array',
            'started_at'  => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }
}
