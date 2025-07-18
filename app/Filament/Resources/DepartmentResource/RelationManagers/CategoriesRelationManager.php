<?php

namespace App\Filament\Resources\DepartmentResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Category;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use App\Enums\Roles\AdminPermissionEnum;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class CategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'categories';

    public function form(Form $form): Form
    {
        $department = $this->getOwnerRecord();

        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        TextInput::make('name')
                            ->live(onBlur: true)
                            ->required()
                            ->maxLength(255)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                $set('slug', Str::slug($state));
                            }),
                        TextInput::make('slug')
                            ->required(),
                        Select::make('parent_id')
                            ->label('Parent Category')
                            ->options(function () use($department) {
                                return Category::query()
                                        ->where('department_id', $department->id)
                                        ->pluck('name', 'id')
                                        ->toArray();
                            })
                            ->preload()
                            ->searchable(),
                    ]),
                Checkbox::make('is_active')
                    ->label('Active')
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('parent.name')
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean()
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        true => 'Active',
                        false => 'Inactive',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn(): bool => Filament::auth()->user()?->hasPermissionTo(AdminPermissionEnum::ADD_CATEGORY->value) ?? false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn(): bool => Filament::auth()->user()?->hasPermissionTo(AdminPermissionEnum::EDIT_CATEGORY->value) ?? false),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(): bool => Filament::auth()->user()?->hasPermissionTo(AdminPermissionEnum::DELETE_CATEGORY->value) ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(): bool => Filament::auth()->user()?->hasPermissionTo(AdminPermissionEnum::DELETE_CATEGORY->value) ?? false),
                ]),
            ]);
    }
}
