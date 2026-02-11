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

                Forms\Components\Group::make()
                    ->schema([
                        // Local / FTP Path
                        Forms\Components\TextInput::make('path')
                            ->label('File Path')
                            ->required()
                            ->hidden(fn (Get $get) => !in_array($get('../source_type'), ['local', 'ftp'])),

                        // HTTP URL
                        Forms\Components\TextInput::make('url')
                            ->label('URL')
                            ->required()
                            ->url()
                            ->hidden(fn (Get $get) => $get('../source_type') !== 'http'),

                        // FTP Host
                        Forms\Components\TextInput::make('host')
                            ->label('FTP Host')
                            ->required()
                            ->hidden(fn (Get $get) => $get('../source_type') !== 'ftp'),

                        // FTP Credentials
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('username')
                                    ->label('FTP Username'),
                                Forms\Components\TextInput::make('password')
                                    ->label('FTP Password')
                                    ->password()
                                    ->revealable(),
                            ])
                            ->hidden(fn (Get $get) => $get('../source_type') !== 'ftp'),

                        // CSV Settings
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('delimiter')
                                    ->label('Delimiter')
                                    ->default(',')
                                    ->required()
                                    ->maxLength(1),
                                Forms\Components\TextInput::make('enclosure')
                                    ->label('Enclosure')
                                    ->default('"')
                                    ->required()
                                    ->maxLength(1),
                            ]),

                        // Column Mapping Section
                        Forms\Components\Section::make('Column Mapping')
                            ->description('Map internal fields to CSV headers')
                            ->schema([
                                Forms\Components\TextInput::make('columns.sku')
                                    ->label('SKU Column Header')
                                    ->required()
                                    ->default('sku'),
                                Forms\Components\TextInput::make('columns.title')
                                    ->label('Title Column Header')
                                    ->required()
                                    ->default('title'),
                                Forms\Components\TextInput::make('columns.price')
                                    ->label('Price Column Header')
                                    ->required()
                                    ->default('price'),
                            ])
                            ->columns(3),
                    ])
                    ->statePath('source_config'),
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
