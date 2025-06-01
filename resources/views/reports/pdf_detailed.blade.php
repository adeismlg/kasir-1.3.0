<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan Detail</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Keuangan Detail</h2>
        <p>Periode: {{ $start }} s/d {{ $end }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Deskripsi</th>
                <th>Debit</th>
                <th>Kredit</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $tx)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($tx->trans_date)->format('Y-m-d') }}</td>
                    <td>{{ $tx->description }}</td>
                    <td class="right">{{ $tx->debit ? number_format($tx->debit) : '-' }}</td>
                    <td class="right">{{ $tx->credit ? number_format($tx->credit) : '-' }}</td>
                    <td class="right">{{ number_format($tx->balance) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
