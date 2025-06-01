<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderProduct;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-m-shopping-cart';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (!$user) {
            return $query->whereNull('id'); // Jika tidak ada user, jangan tampilkan data
        }

        if ($user->role === 'owner') {
            // Pastikan $user->store_id sudah terisi dan sesuai dengan tipe yang di tabel Payment Methods
            return $query->where('store_id', '=', $user->store_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Info Utama')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('gender')
                                    ->options([
                                        'male' => 'Laki-laki',
                                        'female' => 'Perempuan'
                                    ])
                                    ->required(),
                                Forms\Components\Hidden::make('store_id') // Store ID otomatis sesuai toko owner
                                    ->default(fn () => \Illuminate\Support\Facades\Auth::user()?->store_id)
                                    ->required(),
                            ])
                ]),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Info Tambahan')
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\DatePicker::make('birthday'),
                            ])
                ]),
                Forms\Components\Section::make('Produk dipesan')->schema([
                    self::getItemsRepeater(),
                ]),
                
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('total_price')
                                    ->required()
                                    ->prefix('Rp ')
                                    ->readOnly()
                                    ->numeric(),
                                Forms\Components\Textarea::make('note')
                                    ->columnSpanFull(),
                            ])
                    ]),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Pembayaran')
                            ->schema([
                                Forms\Components\Select::make('payment_method_id')
                                    ->relationship('paymentMethod', 'name', function (Builder $query) {
                                        $user = Auth::user();
                                        // Pastikan hanya payment method dengan store_id user yang muncul
                                        return $query->where('store_id', $user->store_id);
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $paymentMethod = PaymentMethod::find($state);
                                        $set('is_cash', $paymentMethod?->is_cash ?? false);

                                        if (!$paymentMethod->is_cash) {
                                            $set('change_amount', 0);
                                            $set('paid_amount', $get('total_price'));
                                        }
                                    })
                                    ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        $paymentMethod = PaymentMethod::find($state);

                                        if (!$paymentMethod?->is_cash) {
                                            $set('paid_amount', $get('total_price'));
                                            $set('change_amount', 0);
                                        }

                                        $set('is_cash', $paymentMethod?->is_cash ?? false);
                                    }),
                                Forms\Components\Hidden::make('is_cash')
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('paid_amount')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->reactive()
                                    ->label('Nominal Bayar')
                                    ->readOnly(fn (Forms\Get $get) => $get('is_cash') == false)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        // function untuk menghitung uang kembalian

                                        self::updateExcangePaid($get, $set);
                                    }),
                                Forms\Components\TextInput::make('change_amount')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->label('Kembalian')
                                    ->readOnly(),
                            ])
                        ]),
                        
               
                
                
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender'),
                Tables\Columns\TextColumn::make('total_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('change_amount')
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
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('orderProducts')
            ->relationship()
            ->live()
            ->columns([
                'md' => 10,
            ])
            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                self::updateTotalPrice($get, $set);
            })
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Produk')
                    ->required()
                    // Menggunakan closure untuk opsi, sehingga query akan dieksekusi pada runtime
                    ->options(function () {
                        $user = Auth::user();
                        if (!$user || !$user->store_id) {
                            return [];
                        }
                        return Product::query()
                            ->where('stock', '>', 1)
                            ->where('store_id', $user->store_id)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->columnSpan([
                        'md' => 5,
                    ])
                    ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get, $state) {
                        $product = Product::find($state);
                        $set('unit_price', $product->price ?? 0);
                        $set('stock', $product->stock ?? 0);
                    })
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $product = Product::find($state);
                        $set('unit_price', $product->price ?? 0);
                        $set('stock', $product->stock ?? 0);
                        self::updateTotalPrice($get, $set);
                    })
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                // Tambahkan komponen lain untuk order item jika diperlukan
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->columnSpan([
                        'md' => 1,
                    ])
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $stock = $get('stock');
                        if ($state > $stock) {
                            $set('quantity', $stock);
                            \Filament\Notifications\Notification::make()
                                ->title('Stok tidak mencukupi')
                                ->warning()
                                ->send();
                        }
                        self::updateTotalPrice($get, $set);
                    }),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->readOnly()
                    ->columnSpan([
                        'md' => 1,
                    ]),
                Forms\Components\TextInput::make('unit_price')
                    ->label('Harga saat ini')
                    ->required()
                    ->prefix('Rp ')
                    ->numeric()
                    ->readOnly()
                    ->columnSpan([
                        'md' => 3,
                    ]),
            ]);
    }


    protected static function updateTotalPrice(Forms\Get $get, Forms\Set $set): void
    {
        $selectedProducts = collect($get('orderProducts'))->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity']));

        $prices = Product::find($selectedProducts->pluck('product_id'))->pluck('price', 'id');
        $total = $selectedProducts->reduce(function ($total, $product) use ($prices) {
            return $total + ($prices[$product['product_id']] * $product['quantity']);
        }, 0);

        $set('total_price', $total);
    }

    protected static function updateExcangePaid(Forms\Get $get, Forms\Set $set): void
    {
        $paidAmount = (int) $get('paid_amount') ?? 0;
        $totalPrice = (int) $get('total_price') ?? 0;
        $exchangePaid = $paidAmount - $totalPrice;
        $set('change_amount', $exchangePaid);
    }
}
