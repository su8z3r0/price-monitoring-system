<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;


class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Suppliers';

    protected static ?string $navigationLabel = 'Suppliers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Supplier Name'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->inline(false),

                Forms\Components\Select::make('source_type')
                    ->options([
                        'local' => 'Local File',
                        'ftp' => 'FTP Server',
                        'http' => 'HTTP/HTTPS URL',
                    ])
                    ->required()
                    ->live()
                    ->label('Source Type'),

                Forms\Components\Textarea::make('source_config')
                    ->label('Source Config (JSON)')
                    ->rows(10)
                    ->required()
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $state)
                    ->dehydrateStateUsing(fn ($state) => json_decode($state, true))
                    ->helperText(fn (Get $get) => match ($get('source_type')) {
                        'ftp' => 'Example: {"host": "ftp.site.com", "username": "user", "password": "pass", "path": "/file.csv", "delimiter": ";", "enclosure": "\"", "columns": {"sku": "sku", "title": "name", "price": "price"}}',
                        'http' => 'Example: {"url": "https://site.com/feed.csv", "delimiter": ";", "enclosure": "\"", "columns": {"sku": "sku", "title": "name", "price": "price"}}',
                        default => 'Example: {"path": "/path/to/file.csv", "delimiter": ";", "enclosure": "\"", "columns": {"sku": "sku", "title": "name", "price": "price"}}',
                    }),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Name'),

                Tables\Columns\TextColumn::make('source_type')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'local' => 'success',
                        'ftp' => 'warning',
                        'http' => 'info',
                    })
                    ->label('Source Type'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source_type')
                    ->options([
                        'local' => 'Local',
                        'ftp' => 'FTP',
                        'http' => 'HTTP',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status'),
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
            ->defaultSort('name', 'asc');
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }


}
