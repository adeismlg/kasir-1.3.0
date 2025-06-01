<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'period_type',
        'report_date',
        'total_revenue',
        'total_expense',
        'net_profit',
    ];

    // Jika perlu, tambahkan relasi ke model Store
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
