<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceComparisonResource\Pages;
use App\Filament\Resources\PriceComparisonResource\RelationManagers;
use App\Models\PriceComparison;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PriceComparisonResource extends Resource
{
    protected static ?string $model = PriceComparison::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Analysis';

    protected static ?string $navigationLabel = 'Price Comparisons';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->required()
                    ->maxLength(255)
                    ->label('Sku'),

                Forms\Components\TextInput::make('product_title')
                    ->required()
                    ->maxLength(255)
                    ->label('Product Title'),

                Forms\Components\TextInput::make('our_price')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->prefix('€')
                    ->label('Our Price'),

                Forms\Components\TextInput::make('competitor_price')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->prefix('€')
                    ->label('Competitor Price'),

                Forms\Components\TextInput::make('price_difference')
                    ->disabled()
                    ->numeric()
                    ->step(0.01)
                    ->prefix('€')
                    ->helperText('Calculated automatically')
                    ->label('Price Difference'),

                Forms\Components\Toggle::make('is_competitive')
                    ->default(true)
                    ->label('Is Competitive'),

                Forms\Components\TextInput::make('competitiveness_percentage')
                    ->disabled()
                    ->numeric()
                    ->step(0.01)
                    ->suffix('%')
                    ->label('Competitiveness Percentage'),
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

                Tables\Columns\TextColumn::make('product_title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->product_title)
                    ->label('Product Title'),

                Tables\Columns\TextColumn::make('our_price')
                    ->money('EUR')
                    ->sortable()
                    ->label('Our Price'),

                Tables\Columns\TextColumn::make('competitor_price')
                    ->money('EUR')
                    ->sortable()
                    ->label('Competitor Price'),

                Tables\Columns\TextColumn::make('price_difference')
                    ->money('EUR')
                    ->sortable()
                    ->color(fn ($state) => $state < 0 ? 'success' : 'danger')
                    ->label('Price Difference'),

                Tables\Columns\IconColumn::make('is_competitive')
                    ->boolean(),

                Tables\Columns\TextColumn::make('competitiveness_percentage')
                    ->suffix('%')
                    ->sortable()
                    ->label('Competitiveness %'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_competitive')
                    ->label('Competitive Status')
                    ->placeholder('All')
                    ->trueLabel('Competitive')
                    ->falseLabel('Not Competitive'),
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPriceComparisons::route('/'),
            'create' => Pages\CreatePriceComparison::route('/create'),
            'edit' => Pages\EditPriceComparison::route('/{record}/edit'),
        ];
    }
}
