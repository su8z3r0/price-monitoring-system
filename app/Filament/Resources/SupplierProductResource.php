<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierProductResource\Pages;
use App\Filament\Resources\SupplierProductResource\RelationManagers;
use App\Models\SupplierProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierProductResource extends Resource
{
    protected static ?string $model = SupplierProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Suppliers';

    protected static ?string $navigationLabel = 'Supplier Products';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->required()
                    ->searchable()
                    ->label('Supplier'),

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

                Forms\Components\DateTimePicker::make('imported_at')
                    ->required()
                    ->default(now())
                    ->label('Imported At'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.name')
                    ->sortable()
                    ->searchable()
                    ->label('Supplier'),

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

                Tables\Columns\TextColumn::make('imported_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->label('Imported At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
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
            ->defaultSort('imported_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierProducts::route('/'),
            'create' => Pages\CreateSupplierProduct::route('/create'),
            'edit' => Pages\EditSupplierProduct::route('/{record}/edit'),
        ];
    }
}
