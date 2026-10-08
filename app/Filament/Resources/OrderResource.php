<?php

namespace App\Filament\Resources;

use App\Enums\Orders\StatusEnum;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->columns([
                TextColumn::make('total_price'),
                TextColumn::make('online_payment_commission'),
                TextColumn::make('website_commission'),
                TextColumn::make('vendor.store_name')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('user email'),
                TextColumn::make('user.name')
                    ->label('user name')
                    ->searchable(),
                TextColumn::make('vendor_subtotal'),
                TextColumn::make('status')
                    ->searchable()
                    ->badge()
                    ->colors(StatusEnum::colors()),
                TextColumn::make('created_at'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusEnum::labels()),
                DateRangeFilter::make('created_at'),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
        ];
    }
}
