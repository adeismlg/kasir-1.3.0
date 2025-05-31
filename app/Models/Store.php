<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Kolom yang dapat diisi (mass assignable)
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($store) {
            Setting::create([
                'shop' => $store->name,
                'address' => $store->address ?? 'Belum diatur',
                'phone' => 'Belum diatur',
                'store_id' => $store->id,
            ]);
        });
    }

    /**
     * Relasi: Store memiliki banyak produk
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Relasi: Store memiliki banyak kategori
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Relasi: Store memiliki banyak metode pembayaran
     */
    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Relasi: Store memiliki satu owner (User)
     */
    public function owner()
    {
        return $this->hasOne(User::class);
    }

    public function setting()
    {
        return $this->hasOne(Setting::class);
    }
    
}
