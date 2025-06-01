<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (!$user) {
            return $query->whereNull('id');
        }

        if ($user->role === 'owner') {
            return $query->where('store_id', '=', $user->store_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Select::make('store_id')
                    ->relationship('store', 'name')
                    ->default(fn() => Auth::user()?->store_id)
                    ->disabled(fn() => Auth::user()?->role === 'owner')
                    ->required(),

                \Filament\Forms\Components\Select::make('period_type')
                    ->options([
                        'hari'  => 'Harian',
                        'bulan' => 'Bulanan',
                        'tahun' => 'Tahunan',
                    ])
                    // ->disabled(fn() => Auth::user()?->role === 'owner')
                    ->required(),

                \Filament\Forms\Components\DatePicker::make('report_date')
                    ->required(),

                \Filament\Forms\Components\TextInput::make('total_revenue')
                    ->numeric()
                    ->disabled()
                    ->required(),

                \Filament\Forms\Components\TextInput::make('total_expense')
                    ->numeric()
                    ->disabled()
                    ->required(),

                \Filament\Forms\Components\TextInput::make('net_profit')
                    ->numeric()
                    ->disabled()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_type'),
                Tables\Columns\TextColumn::make('report_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_revenue')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_expense')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_profit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                // Tambahkan tombol Generate Report di header
Tables\Actions\Action::make('generate_report')
    ->form([
        \Filament\Forms\Components\Select::make('period_type')
            ->label('Tipe Periode')
            ->options([
                'hari'  => 'Harian',
                'bulan' => 'Bulanan',
                'tahun' => 'Tahunan',
            ])
            ->required(),
        \Filament\Forms\Components\DatePicker::make('report_date')
            ->label('Tanggal Laporan')
            ->required(),
    ])
    ->action(function (array $data) {
        $user = Auth::user();
        $storeId = ($user && $user->role === 'owner') ? $user->store_id : 1;
        
        // Pastikan timezone sesuai, jika perlu gunakan config('app.timezone')
        $reportDate = Carbon::parse($data['report_date']);
        
        if ($data['period_type'] === 'hari') {
            $start = (clone $reportDate)->startOfDay();
            $end   = (clone $reportDate)->endOfDay();
        } elseif ($data['period_type'] === 'bulan') {
            $start = (clone $reportDate)->startOfMonth();
            $end   = (clone $reportDate)->endOfMonth();
        } else { // 'tahun'
            $start = (clone $reportDate)->startOfYear();
            $end   = (clone $reportDate)->endOfYear();
        }
        
        // Debug: pastikan rentang tanggal benar
        // dd($start->toDateTimeString(), $end->toDateTimeString());

        $totalRevenue = \App\Models\Order::where('store_id', $storeId)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_price');

        $totalExpense = \App\Models\Expense::where('store_id', $storeId)
            ->whereBetween('date_expense', [$start, $end])
            ->sum('amount');

        $netProfit = $totalRevenue - $totalExpense;

        \App\Models\Report::create([
            'store_id'      => $storeId,
            'period_type'   => $data['period_type'],
            'report_date'   => $reportDate->toDateString(),
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_profit'    => $netProfit,
        ]);

        \Filament\Notifications\Notification::make()
            ->title('Report berhasil dibuat!')
            ->success()
            ->send();

        return redirect(static::getUrl('index'));
    })
    ->requiresConfirmation()
    ->icon('heroicon-o-document-text'),

            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('exportDetailed')
                    ->label('Export Detailed Report')
                    ->action(function (\Illuminate\Support\Collection $records) {
                        $detailedData = [];
                        foreach ($records as $report) {
                            if ($report->period_type === 'hari') {
                                $start = Carbon::parse($report->report_date)->startOfDay();
                                $end   = Carbon::parse($report->report_date)->endOfDay();
                            } elseif ($report->period_type === 'bulan') {
                                $start = Carbon::parse($report->report_date)->startOfMonth();
                                $end   = Carbon::parse($report->report_date)->endOfMonth();
                            } else {
                                $start = Carbon::parse($report->report_date)->startOfYear();
                                $end   = Carbon::parse($report->report_date)->endOfYear();
                            }

                            $orders = \App\Models\Order::selectRaw("created_at as trans_date, total_price as amount, name as description, 'credit' as type")
                                ->where('store_id', $report->store_id)
                                ->whereBetween('created_at', [$start, $end])
                                ->get();

                            $expenses = \App\Models\Expense::selectRaw("date_expense as trans_date, amount, name as description, 'debit' as type")
                                ->where('store_id', $report->store_id)
                                ->whereBetween('date_expense', [$start, $end])
                                ->get();

                            $transactions = $orders->merge($expenses)->sortBy('trans_date');

                            $runningBalance = 0;
                            foreach ($transactions as $tx) {
                                if ($tx->type === 'credit') {
                                    $runningBalance += $tx->amount;
                                    $tx->credit = $tx->amount;
                                    $tx->debit = null;
                                } else {
                                    $runningBalance -= $tx->amount;
                                    $tx->debit = $tx->amount;
                                    $tx->credit = null;
                                }
                                $tx->balance = $runningBalance;
                            }

                            $detailedData[] = [
                                'report' => $report,
                                'transactions' => $transactions,
                            ];
                        }

                        $html = view('reports.pdf_detailed_bulk', compact('detailedData'))->render();
                        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'detailed_report.pdf');
                    })
                    ->icon('heroicon-o-arrow-down-tray'),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit'   => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
