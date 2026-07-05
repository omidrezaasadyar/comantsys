<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'person_type',
        'name',
        'national_id',
        'economic_code',
        'postal_code',
        'address',
        'phone',
        'notes',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
