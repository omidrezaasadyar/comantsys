<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourcingRequest extends Model
{
    protected $fillable = [
        'part_name',
        'part_number',
        'description',
        'search_instructions',
        'status',
    ];

    /**
     * Casts live ONLY here — never inside $fillable (silent-bug pattern in this repo).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(SourcingRun::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SourcingRequestAttachment::class);
    }
}
