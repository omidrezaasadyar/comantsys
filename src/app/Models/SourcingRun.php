<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourcingRun extends Model
{
    protected $fillable = [
        'sourcing_request_id',
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

    /**
     * Casts live ONLY here — never inside $fillable (silent-bug pattern in this repo).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'results'     => 'array',
            'raw_search'  => 'array',
            'started_at'  => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function sourcingRequest(): BelongsTo
    {
        return $this->belongsTo(SourcingRequest::class);
    }
}
