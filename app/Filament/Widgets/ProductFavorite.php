<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Product;
use Filament\Facades\Filament;

class ProductFavorite extends BaseWidget
{
    protected static ?int $sort = 4;
    protected static ?string $heading = 'Produk Favorit';

    public function table(Table $table): Table
    {
        // Ambil user yang sedang login menggunakan Filament
        $user = Filament::auth()->user();
        // Cek apakah user tersebut admin; jika role disimpan sebagai string 'admin'
        $isAdmin = $user && $user->role === 'admin';
        // Ambil store_id dari user jika ada
        $storeId = $user->store_id ?? null;

        // Query untuk produk favorit; 
        // jika user bukan admin, filter produk berdasarkan store_id
        $productQuery = Product::query()
            ->withCount('orderProducts')
            ->when(!$isAdmin, function ($query) use ($storeId) {
                $query->where('store_id', $storeId);
            })
            ->orderByDesc('order_products_count')
            ->take(10);

        return $table
            ->query($productQuery)
            ->columns([
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_products_count')
                    ->label('Dipesan')
                    ->searchable(),
            ])
            ->defaultPaginationPageOption(5);
    }
}
