<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop',
        'address',
        'phone',
        'store_id',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}

