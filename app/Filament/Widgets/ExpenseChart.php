<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Expense;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Facades\Filament;

class ExpenseChart extends ChartWidget
{
    protected static ?string $heading = 'Expense';
    protected static ?int $sort = 2;
    protected static string $color = 'danger';
    public ?string $filter = 'today';

    protected function getData(): array
    {
        // Ambil user yang sedang login dan cek apakah user adalah admin.
        $user    = Filament::auth()->user();
        $isAdmin = $user && $user->role === 'admin';
        $storeId = $user->store_id ?? null;

        $activeFilter = $this->filter;

        // Tentukan rentang waktu dan metode pengelompokan data
        $dateRange = match ($activeFilter) {
            'today' => [
                'start'  => now()->startOfDay(),
                'end'    => now()->endOfDay(),
                'period' => 'perHour',
            ],
            'week' => [
                'start'  => now()->startOfWeek(),
                'end'    => now()->endOfWeek(),
                'period' => 'perDay',
            ],
            'month' => [
                'start'  => now()->startOfMonth(),
                'end'    => now()->endOfMonth(),
                'period' => 'perDay',
            ],
            'year' => [
                'start'  => now()->startOfYear(),
                'end'    => now()->endOfYear(),
                'period' => 'perMonth',
            ],
        };

        // Buat query dasar Expense dalam rentang waktu
        $query = Expense::query()->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        if (!$isAdmin && $storeId) {
            $query->where('store_id', $storeId);
        }

        $data = collect();
        $labels = collect();

        if ($dateRange['period'] === 'perHour') {
            // Kelompokkan berdasarkan jam (0-23)
            $results = $query->selectRaw('HOUR(created_at) as hour, SUM(amount) as aggregate')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            // Buat data untuk setiap jam dalam 24 jam
            for ($i = 0; $i < 24; $i++) {
                $matching = $results->firstWhere('hour', $i);
                $data->push($matching ? (float) $matching->aggregate : 0);
                $labels->push(sprintf("%02d:00", $i));
            }
        } elseif ($dateRange['period'] === 'perDay') {
            // Kelompokkan berdasarkan tanggal
            $results = $query->selectRaw('DATE(created_at) as day, SUM(amount) as aggregate')
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            // Buat interval tanggal antara start dan end
            $period = CarbonPeriod::create($dateRange['start'], $dateRange['end']);
            foreach ($period as $date) {
                $day = $date->toDateString();
                $matching = $results->firstWhere('day', $day);
                $data->push($matching ? (float) $matching->aggregate : 0);
                $labels->push($date->format('d M'));
            }
        } else { // perMonth
            // Kelompokkan berdasarkan bulan (format "Y-m")
            $results = $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as aggregate')
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $start = Carbon::parse($dateRange['start'])->startOfMonth();
            $end   = Carbon::parse($dateRange['end'])->startOfMonth();
            $period = CarbonPeriod::create($start, '1 month', $end);
            foreach ($period as $date) {
                $month = $date->format('Y-m');
                $matching = $results->firstWhere('month', $month);
                $data->push($matching ? (float) $matching->aggregate : 0);
                $labels->push($date->format('M Y'));
            }
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Expense ' . $this->getFilters()[$activeFilter],
                    'data'            => $data->toArray(),
                    'backgroundColor' => static::$color,
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week'  => 'Last week',
            'month' => 'Last month',
            'year'  => 'This year',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
