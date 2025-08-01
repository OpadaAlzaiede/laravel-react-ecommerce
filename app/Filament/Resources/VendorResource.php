<?php

namespace App\Filament\Resources;

use App\Models\Vendor;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\Roles\RoleEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use App\Enums\Users\VendorStatusEnum;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\VendorResource\Pages;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.email'),
                TextColumn::make('store_name'),
                TextColumn::make('store_address'),
                TextColumn::make('status')
                    ->badge()
                    ->colors(VendorStatusEnum::colors()),
                TextColumn::make('created_at'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn (Vendor $record): bool => $record->status === VendorStatusEnum::PENDING->value)
                ->action(function (Vendor $record) {
                    $record->update(['status' => VendorStatusEnum::APPROVED->value]);
                }),

            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->visible(fn (Vendor $record): bool => $record->status === VendorStatusEnum::PENDING->value)
                ->action(function (Vendor $record) {
                    $record->update(['status' => VendorStatusEnum::REJECTED->value]);
                }),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendors::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user && $user->hasRole(RoleEnum::ADMIN->value);
    }
}
