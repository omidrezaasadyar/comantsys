<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourcingRequestAttachment extends Model
{
    protected $fillable = [
        'sourcing_request_id',
        'title',
        'file_path',
        'file_type',
    ];

    public function sourcingRequest(): BelongsTo
    {
        return $this->belongsTo(SourcingRequest::class);
    }
}
