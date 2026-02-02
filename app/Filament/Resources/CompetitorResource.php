<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompetitorResource\Pages;
use App\Filament\Resources\CompetitorResource\RelationManagers;
use App\Models\Competitor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompetitorResource extends Resource
{
    protected static ?string $model = Competitor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Competitors';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('website')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('crawler_config')
                    ->rows(10)
                    ->helperText('Enter JSON with: base_url, product_urls (optional), selectors (sku, title, price)'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('website')
                    ->url(fn ($record) => $record->website)
                    ->openUrlInNewTab(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompetitors::route('/'),
            'create' => Pages\CreateCompetitor::route('/create'),
            'edit' => Pages\EditCompetitor::route('/{record}/edit'),
        ];
    }
}
