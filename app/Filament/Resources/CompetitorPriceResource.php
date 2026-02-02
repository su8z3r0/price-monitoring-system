<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompetitorPriceResource\Pages;
use App\Filament\Resources\CompetitorPriceResource\RelationManagers;
use App\Models\CompetitorPrice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompetitorPriceResource extends Resource
{
    protected static ?string $model = CompetitorPrice::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Competitors';

    protected static ?string $navigationLabel = 'Competitor Prices';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('competitor_id')
                    ->relationship('competitor', 'name')
                    ->required()
                    ->searchable()
                    ->label('Competitor'),

                Forms\Components\TextInput::make('sku')
                    ->required()
                    ->maxLength(255)
                    ->label('SKU'),

                Forms\Components\TextInput::make('product_title')
                    ->required()
                    ->maxLength(255)
                    ->label('Product Title'),

                Forms\Components\TextInput::make('sale_price')
                    ->required()
                    ->numeric()
                    ->prefix('€')
                    ->step(0.01)
                    ->label('Sale Price'),

                Forms\Components\TextInput::make('product_url')
                    ->url()
                    ->nullable()
                    ->placeholder('https://example.com')
                    ->label('Product URL'),

                Forms\Components\DateTimePicker::make('scraped_at')
                    ->required()
                    ->default(now())
                    ->label('Scraped At'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('competitor.name')
                    ->sortable()
                    ->searchable()
                    ->label('Competitor'),

                Tables\Columns\TextColumn::make('sku')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->label('SKU'),

                Tables\Columns\TextColumn::make('product_title')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->product_title)
                    ->label('Product'),

                Tables\Columns\TextColumn::make('sale_price')
                    ->money('EUR')
                    ->sortable()
                    ->label('Price'),

                Tables\Columns\TextColumn::make('product_url')
                    ->limit(30)
                    ->url(fn ($record) => $record->product_url)
                    ->openUrlInNewTab()
                    ->label('URL'),

                Tables\Columns\TextColumn::make('scraped_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->label('Scraped'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('competitor_id')
                    ->relationship('competitor', 'name')
                    ->label('Competitor')
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
            ->defaultSort('scraped_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompetitorPrices::route('/'),
            'create' => Pages\CreateCompetitorPrice::route('/create'),
            'edit' => Pages\EditCompetitorPrice::route('/{record}/edit'),
        ];
    }
}
