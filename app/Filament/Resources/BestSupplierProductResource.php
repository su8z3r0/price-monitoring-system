<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BestSupplierProductResource\Pages;
use App\Filament\Resources\BestSupplierProductResource\RelationManagers;
use App\Models\BestSupplierProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BestSupplierProductResource extends Resource
{
    protected static ?string $model = BestSupplierProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Suppliers';

    protected static ?string $navigationLabel = 'Best Supplier Products';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->required()
                    ->maxLength(255)
                    ->label('Sku'),

                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->label('Title'),

                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->prefix('€')
                    ->label('Price'),

                Forms\Components\Select::make('winner_supplier_id')
                    ->relationship('winnerSupplier', 'name')
                    ->required()
                    ->searchable()
                    ->label('Supplier'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->label('SKU'),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->title)
                    ->label('Title'),

                Tables\Columns\TextColumn::make('price')
                    ->money('EUR')
                    ->sortable()
                    ->label('Price'),

                Tables\Columns\TextColumn::make('winnerSupplier.name')
                    ->sortable()
                    ->searchable()
                    ->label('Supplier'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('winner_supplier_id')
                    ->relationship('winnerSupplier', 'name')
                    ->label('Supplier')
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('price', 'asc');
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
            'index' => Pages\ListBestSupplierProducts::route('/'),
            'create' => Pages\CreateBestSupplierProduct::route('/create'),
            'edit' => Pages\EditBestSupplierProduct::route('/{record}/edit'),
        ];
    }
}
