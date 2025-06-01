<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Product;
use App\Models\Order;
use App\Models\Expense;
use Filament\Facades\Filament;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        // Ganti pengecekan dengan memeriksa properti role
        $isAdmin = $user && $user->role === 'admin';
        $storeId = $user->store_id ?? null;

        $product_count = $isAdmin 
            ? Product::count() 
            : Product::where('store_id', $storeId)->count();

        $order_count = $isAdmin 
            ? Order::count() 
            : Order::where('store_id', $storeId)->count();

        $omset = $isAdmin 
            ? Order::sum('total_price') 
            : Order::where('store_id', $storeId)->sum('total_price');

        $expense = $isAdmin 
            ? Expense::sum('amount') 
            : Expense::where('store_id', $storeId)->sum('amount');

        return [
            Stat::make('Produk', $product_count),
            Stat::make('Order', $order_count),
            Stat::make('Omset', number_format($omset, 0, ",", ".")),
            Stat::make('Expense', number_format($expense, 0, ",", ".")),
        ];
    }
}
