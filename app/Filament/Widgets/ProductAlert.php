<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use App\Models\Product;
use Filament\Facades\Filament;

class ProductAlert extends BaseWidget
{
    protected static ?int $sort = 3;
    protected static ?string $heading = 'Produk hampir habis';

    public function table(Table $table): Table
    {
        // Ambil user yang sedang login
        $user = Filament::auth()->user();
        $isAdmin = $user && $user->role === 'admin';
        $storeId = $user->store_id ?? null;

        // Build query untuk produk dengan stok kurang dari atau sama dengan 10
        $query = Product::query()
            ->where('stock', '<=', 10)
            ->orderBy('stock', 'asc');

        // Jika user bukan admin, filter berdasarkan store_id
        if (!$isAdmin && $storeId) {
            $query->where('store_id', $storeId);
        }

        return $table
            ->query($query)
            ->columns([
                ImageColumn::make('image'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('stock')
                    ->label('Stok')
                    ->numeric()
                    ->badge() // Mengubah tampilan menjadi badge
                    ->colors([
                        'danger'  => fn ($state): bool => $state < 5,
                        'warning' => fn ($state): bool => $state >= 5 && $state <= 10,
                    ])
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(5);
    }
}
