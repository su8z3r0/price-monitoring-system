<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BestCompetitorPriceResource\Pages;
use App\Filament\Resources\BestCompetitorPriceResource\RelationManagers;
use App\Models\BestCompetitorPrice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BestCompetitorPriceResource extends Resource
{
    protected static ?string $model = BestCompetitorPrice::class;

    protected static ?string $navigationGroup = 'Competitors';
    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Best Competitor Prices';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('product_title')
                    ->required()
                    ->maxLength(255)
                    ->label('Product Title'),
                Forms\Components\TextInput::make('sku')
                    ->required()
                    ->maxLength(255)
                    ->label('SKU'),
                Forms\Components\TextInput::make('sale_price')
                    ->required()
                    ->numeric()
                    ->prefix('€')
                    ->step(0.01)
                    ->label('Sale Price'),
                Forms\Components\TextInput::make('winner_competitor')
                    ->required()
                    ->maxLength(255)
                    ->label('Winner Competitor')
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_title')
                    ->searchable()
                    ->sortable()
                    ->label('Product Title'),

                Tables\Columns\TextColumn::make('sku')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->label('SKU'),

                Tables\Columns\TextColumn::make('sale_price')
                    ->money('EUR')
                    ->sortable()
                    ->label('Sale Price'),

                Tables\Columns\TextColumn::make('winner_competitor')
                    ->searchable()
                    ->sortable()
                    ->label('Winner Competitor')
            ])
            ->filters([
                //
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
            ->defaultSort('sale_price', 'asc');
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
            'index' => Pages\ListBestCompetitorPrices::route('/'),
            'create' => Pages\CreateBestCompetitorPrice::route('/create'),
            'edit' => Pages\EditBestCompetitorPrice::route('/{record}/edit'),
        ];
    }
}
