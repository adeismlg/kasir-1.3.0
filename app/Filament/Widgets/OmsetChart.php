<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

class OmsetChart extends ChartWidget
{
    protected static ?string $heading = 'Omset';
    protected static ?int $sort = 1;
    public ?string $filter = 'today';
    protected static string $color = 'success';

    protected function getData(): array
    {
        // Ambil user yang sedang login dan periksa apakah dia admin
        $user    = Filament::auth()->user();
        $isAdmin = $user && $user->role === 'admin';
        $storeId = $user->store_id ?? null;

        $activeFilter = $this->filter;

        // Tentukan rentang dan periode berdasarkan filter aktif
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

        // Buat query dasar Order berdasarkan rentang waktu
        $query = Order::query()->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        if (!$isAdmin && $storeId) {
            $query->where('store_id', $storeId);
        }

        // Inisialisasi koleksi untuk menyimpan hasil data dan label
        $data = collect();
        $labels = collect();

        if ($dateRange['period'] === 'perHour') {
            // Group by jam untuk periode hari ini
            $results = $query->selectRaw('HOUR(created_at) as hour, SUM(total_price) as aggregate')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();
            // Buat interval 0-23 jam
            for ($i = 0; $i < 24; $i++) {
                $matching = $results->firstWhere('hour', $i);
                $data->push($matching ? (float) $matching->aggregate : 0);
                $labels->push(sprintf("%02d:00", $i));
            }
        } elseif ($dateRange['period'] === 'perDay') {
            // Group by tanggal untuk periode harian
            $results = $query->selectRaw('DATE(created_at) as day, SUM(total_price) as aggregate')
                ->groupBy('day')
                ->orderBy('day')
                ->get();
            $period = CarbonPeriod::create($dateRange['start'], $dateRange['end']);
            foreach ($period as $date) {
                $day = $date->toDateString();
                $matching = $results->firstWhere('day', $day);
                $data->push($matching ? (float) $matching->aggregate : 0);
                $labels->push($date->format('d M'));
            }
        } else { // 'perMonth'
            // Group by bulan untuk periode tahunan
            $results = $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total_price) as aggregate')
                ->groupBy('month')
                ->orderBy('month')
                ->get();
            $start = Carbon::parse($dateRange['start'])->startOfMonth();
            $end = Carbon::parse($dateRange['end'])->startOfMonth();
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
                    'label' => 'Omset ' . $this->getFilters()[$activeFilter],
                    'data'  => $data->toArray(),
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
