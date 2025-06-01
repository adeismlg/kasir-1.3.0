<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #007bff;
            margin-bottom: 20px;
        }
        .header img {
            max-height: 60px;
        }
        .header h1 {
            margin: 5px 0 0;
            font-size: 24px;
            color: #007bff;
        }
        .header h2 {
            margin: 0;
            font-size: 18px;
        }
        .report-info {
            margin: 20px 30px;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        .report-info h3 {
            margin: 0 0 5px;
            font-size: 18px;
        }
        .summary-table, .detail-table {
            width: 90%;
            margin: 10px auto 20px;
            border-collapse: collapse;
        }
        .summary-table th,
        .summary-table td,
        .detail-table th,
        .detail-table td {
            border: 1px solid #ccc;
            padding: 8px;
        }
        .summary-table th {
            background-color: #e9ecef;
        }
        .detail-table th {
            background-color: #f1f1f1;
        }
        .right {
            text-align: right;
        }
        .section-title {
            text-align: center;
            font-size: 16px;
            margin-top: 30px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Header aplikasi -->
    <div class="header">
        <!-- Ganti 'logo.png' dengan path logo Anda -->
        <img src="{{ public_path('images/umkm.png') }}" alt="Logo Aplikasi">
        <h1>UMKM Corner</h1>
        <h2>Laporan Keuangan</h2>
    </div>
    
    @foreach($detailedData as $data)
        @php
            $report = $data['report'];
            // Atur keterangan periode
            if ($report->period_type === 'hari') {
                $periodText = "Harian (" . \Carbon\Carbon::parse($report->report_date)->format('d M Y') . ")";
            } elseif ($report->period_type === 'bulan') {
                $periodText = "Bulanan (" . \Carbon\Carbon::parse($report->report_date)->format('F Y') . ")";
            } else { // 'tahun'
                $periodText = "Tahunan (" . \Carbon\Carbon::parse($report->report_date)->format('Y') . ")";
            }
        @endphp
        
        <!-- Bagian Info Laporan -->
        <div class="report-info">
            <h3>
                {{ $report->store->name ?? $report->store_id }} | {{ $periodText }}
            </h3>
            <p>Tanggal Laporan: {{ $report->report_date }}</p>
        </div>
        
        <!-- Tabel Ringkasan Laporan (sesuai header aplikasi) -->
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Total Pendapatan</th>
                    <th>Total Pengeluaran</th>
                    <th>Laba Bersih</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="right">{{ number_format($report->total_revenue) }}</td>
                    <td class="right">{{ number_format($report->total_expense) }}</td>
                    <td class="right">{{ number_format($report->net_profit) }}</td>
                </tr>
            </tbody>
        </table>
        
        <!-- Detail Transaksi -->
        <!-- Detail Transaksi -->
<div class="section-title">Detail Transaksi</div>
@php
    // Parse report_date menggunakan namespace penuh untuk Carbon
    $reportDate = \Carbon\Carbon::parse($report->report_date);
    
    // Ambil data order dari model Order sesuai tipe laporan dan store_id report
    if ($report->period_type === 'hari') {
        $orders = \App\Models\Order::where('store_id', $report->store_id)
            ->whereDate('created_at', $reportDate->toDateString())
            ->orderBy('created_at')
            ->get();
    } elseif ($report->period_type === 'bulan') {
        $orders = \App\Models\Order::where('store_id', $report->store_id)
            ->whereYear('created_at', $reportDate->year)
            ->whereMonth('created_at', $reportDate->month)
            ->orderBy('created_at')
            ->get();
    } elseif ($report->period_type === 'tahun') {
        $orders = \App\Models\Order::where('store_id', $report->store_id)
            ->whereYear('created_at', $reportDate->year)
            ->orderBy('created_at')
            ->get();
    } else {
        $orders = collect();
    }
@endphp

<table class="detail-table">
    <thead>
        <tr>
            <th>ID Pesanan</th>
            <th>Tanggal Transaksi</th>
            <th>Metode Pembayaran</th>
            <th>Harga Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($orders as $order)
            <tr>
                <td>{{ $order->id }}</td>
                <td>{{ \Carbon\Carbon::parse($order->created_at)->format('Y-m-d') }}</td>
                <td>{{ $order->payment_method ?? $order->payment_method_id }}</td>
                <td class="right">{{ number_format($order->total_price) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align: center;">Data detail transaksi tidak tersedia.</td>
            </tr>
        @endforelse
    </tbody>
</table>




        
    @endforeach
</body>
</html>
