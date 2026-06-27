<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        "name",
        "country",
        "city",
        "address",
        "phone",
        "email",
        "website",
        "tags",
        "notes",
        "is_active",
    ];

    protected $casts = [
        "is_active" => "boolean",
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupplierAttachment::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(SupplierPart::class);
    }
}
