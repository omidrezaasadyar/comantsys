<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPart extends Model
{
    protected $fillable = [
        "supplier_id",
        "part_name",
        "part_number",
        "price",
        "currency",
        "notes",
    ];

    protected $casts = [
        "price" => "decimal:2",
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
