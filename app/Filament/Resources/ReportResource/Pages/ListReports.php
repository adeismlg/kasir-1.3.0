<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Order;
use App\Models\Expense;
use Carbon\Carbon;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;
    
    // Hapus getHeaderActions jika sebelumnya ada, dan gunakan getActions() untuk action per baris
    // protected function getActions(): array
    // {
    //     return [
    //         Action::make('exportDetailed')
    //             ->label('Export Detailed Report')
    //             ->action(function ($record) {
    //                 // $record merupakan instance Report pada baris yang dipilih

    //                 // Tentukan rentang waktu berdasarkan period_type laporan
    //                 if ($record->period_type === 'hari') {
    //                     $start = Carbon::parse($record->report_date)->startOfDay();
    //                     $end   = Carbon::parse($record->report_date)->endOfDay();
    //                 } elseif ($record->period_type === 'bulan') {
    //                     $start = Carbon::parse($record->report_date)->startOfMonth();
    //                     $end   = Carbon::parse($record->report_date)->endOfMonth();
    //                 } else { // periode 'tahun'
    //                     $start = Carbon::parse($record->report_date)->startOfYear();
    //                     $end   = Carbon::parse($record->report_date)->endOfYear();
    //                 }

    //                 // Ambil transaksi dari orders (sebagai kredit)
    //                 $orders = Order::selectRaw("created_at as trans_date, total_price as amount, name as description, 'credit' as type")
    //                     ->where('store_id', $record->store_id)
    //                     ->whereBetween('created_at', [$start, $end])
    //                     ->get();

    //                 // Ambil transaksi dari expenses (sebagai debit)
    //                 $expenses = Expense::selectRaw("date_expense as trans_date, amount, name as description, 'debit' as type")
    //                     ->where('store_id', $record->store_id)
    //                     ->whereBetween('date_expense', [$start, $end])
    //                     ->get();

    //                 // Gabungkan dan urutkan transaksi berdasarkan tanggal
    //                 $transactions = $orders->merge($expenses)->sortBy('trans_date');

    //                 // Hitung saldo berjalan (running balance)
    //                 $runningBalance = 0;
    //                 foreach ($transactions as $tx) {
    //                     if ($tx->type === 'credit') {
    //                         $runningBalance += $tx->amount;
    //                         $tx->credit = $tx->amount;
    //                         $tx->debit  = null;
    //                     } else {
    //                         $runningBalance -= $tx->amount;
    //                         $tx->debit  = $tx->amount;
    //                         $tx->credit = null;
    //                     }
    //                     $tx->balance = $runningBalance;
    //                 }

    //                 // Render view PDF dengan data transaksi
    //                 $html = view('reports.pdf_detailed', [
    //                     'transactions' => $transactions,
    //                     'start' => $start->toDateString(),
    //                     'end'   => $end->toDateString(),
    //                 ])->render();

    //                 // Generate PDF menggunakan DomPDF
    //                 $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

    //                 // Kembalikan file PDF sebagai stream download
    //                 return response()->streamDownload(function () use ($pdf) {
    //                     echo $pdf->output();
    //                 }, "detailed_report_{$record->id}.pdf");
    //             })
    //             ->icon('heroicon-o-arrow-down-tray'),
    //     ];
    // }
}
