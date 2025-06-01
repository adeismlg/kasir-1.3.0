<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Keuangan</h2>
        <p>Periode: {{ $periode }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Tanggal Laporan</th>
                <th>Tipe Periode</th>
                <th>Pendapatan</th>
                <th>Pengeluaran</th>
                <th>Laba Bersih</th>
            </tr>
        </thead>
        <tbody>
        @foreach($reports as $report)
            <tr>
                <td>{{ $report->report_date }}</td>
                <td>{{ $report->period_type }}</td>
                <td>{{ number_format($report->total_revenue) }}</td>
                <td>{{ number_format($report->total_expense) }}</td>
                <td>{{ number_format($report->net_profit) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
