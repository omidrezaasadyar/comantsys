<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalRequestAttachment extends Model
{
    protected $fillable = [
        'portal_request_id',
        'title',
        'file_path',
        'file_type',
        'source',
    ];

    public function portalRequest(): BelongsTo
    {
        return $this->belongsTo(PortalRequest::class);
    }
}
