<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Enums\Roles\RoleEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Enums\Products\ProductStatusEnum;
use Filament\Forms\Components\RichEditor;
use Filament\Pages\SubNavigationPosition;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductResource\Pages;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ProductImages;
use App\Filament\Resources\ProductResource\Pages\ProductVariationTypes;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-s-queue-list';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        TextInput::make('title')
                        ->live(onBlur: true)
                        ->required()
                        ->afterStateUpdated(function (string $operation, $state, callable $set) {
                            $set('slug', Str::slug($state));
                        }),
                        TextInput::make('slug'),
                        Select::make('department_id')
                            ->label('Department')
                            ->relationship('department', 'name')
                            ->preload()
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function(callable $set) {
                                $set('category_id', null);
                            }),
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship(
                                name: 'category',
                                titleAttribute: 'name',
                                modifyQueryUsing: function(Builder $query, callable $get) {
                                    $departmentId = $get('department_id');
                                    if ($departmentId) {
                                        $query->where('department_id', $departmentId);
                                    }
                                }
                            )
                            ->preload()
                            ->searchable()
                            ->required(),
                        ]),
                RichEditor::make('description')
                    ->required()
                    ->columnSpan(2)
                    ->toolbarButtons([
                        'blockquote',
                        'bold',
                        'bulletList',
                        'h2',
                        'h3',
                        'italic',
                        'link',
                        'orderedList',
                        'redo',
                        'strike',
                        'underline',
                        'undo',
                        'table'
                    ]),
                TextInput::make('price')
                    ->required()
                    ->numeric(),
                TextInput::make('quantity')
                    ->integer(),
                Select::make('status')
                    ->options(ProductStatusEnum::labels())
                    ->default(ProductStatusEnum::DRAFT->value)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('images')
                    ->label('Image')
                    ->collection('images')
                    ->limit(1)
                    ->conversion('thumb'),
                TextColumn::make('title')
                    ->sortable()
                    ->words(10)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors(ProductStatusEnum::colors()),
                TextColumn::make('department.name')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('price')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ProductStatusEnum::labels()),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name'),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'images' => Pages\ProductImages::route('/{record}/images'),
            'variation-types' => Pages\ProductVariationTypes::route('/{record}/variation-types'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
                EditProduct::class,
                ProductImages::class,
                ProductVariationTypes::class
            ]);
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user && $user->hasRole(RoleEnum::VENDOR->value);
    }
}
