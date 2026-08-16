<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalRequestMessage extends Model
{
    protected $fillable = [
        'portal_request_id',
        'sender',
        'body',
    ];

    // ── رابطه‌ها ──

    public function portalRequest(): BelongsTo
    {
        return $this->belongsTo(PortalRequest::class);
    }

    // ── کمک‌متدها ──

    /**
     * تشخیص فرستنده — تا منطق مقایسهٔ رشته‌ای از ویو بیرون بماند.
     */
    public function isFromAdmin(): bool
    {
        return $this->sender === 'admin';
    }

    public function isFromCustomer(): bool
    {
        return $this->sender === 'customer';
    }
}
