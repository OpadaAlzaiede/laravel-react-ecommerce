<?php

namespace App\Filament\Vendor\Resources;

use App\Models\Order;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\Roles\RoleEnum;
use Filament\Facades\Filament;
use App\Enums\Orders\StatusEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Vendor\Resources\OrderResource\Pages;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();

        return parent::getEloquentQuery()->forVendor();
    }

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
