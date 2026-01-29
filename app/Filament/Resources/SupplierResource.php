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
use Illuminate\Support\HtmlString;

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

                Forms\Components\Select::make('source_type')
                    ->options([
                        'local' => 'Local File',
                        'ftp' => 'FTP Server',
                        'http' => 'HTTP/HTTPS URL',
                    ])
                    ->required()
                    ->live()
                    ->label('Source Type'),

                // Box informativo che cambia in base al source_type
                Forms\Components\Placeholder::make('config_help')
                    ->label('Configuration Guide')
                    ->content(fn (Get $get) => new HtmlString(self::getConfigGuideHtml($get('source_type'))))
                    ->hidden(fn (Get $get) => !$get('source_type')),

                Forms\Components\Textarea::make('source_config')
                    ->required()
                    ->rows(12)
                    ->label('Source Configuration (JSON)')
                    ->placeholder('{}'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->inline(false),
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

    private static function getConfigGuideHtml(?string $sourceType): string
    {
        $configs = [
            'local' => [
                'icon' => '📁',
                'title' => 'Local File',
                'fields' => [
                    'path' => 'Path to CSV file in storage (e.g., /storage/suppliers/file.csv)',
                    'columns' => 'Column mapping: sku, title, price',
                ],
                'example' => [
                    'path' => '/storage/suppliers/supplier1.csv',
                    'columns' => ['sku' => 'sku', 'title' => 'product_name', 'price' => 'price'],
                ],
            ],
            'ftp' => [
                'icon' => '🌐',
                'title' => 'FTP Server',
                'fields' => [
                    'host' => 'FTP hostname (e.g., ftp.supplier.com)',
                    'username' => 'FTP username',
                    'password' => 'FTP password',
                    'path' => 'Remote file path',
                    'columns' => 'Column mapping: sku, title, price',
                ],
                'example' => [
                    'host' => 'ftp.supplier.com',
                    'username' => 'user',
                    'password' => '••••••••',
                    'path' => '/exports/products.csv',
                    'columns' => ['sku' => 'code', 'title' => 'name', 'price' => 'price'],
                ],
            ],
            'http' => [
                'icon' => '🔗',
                'title' => 'HTTP/HTTPS URL',
                'fields' => [
                    'url' => 'Direct URL to CSV file',
                    'columns' => 'Column mapping: sku, title, price',
                ],
                'example' => [
                    'url' => 'https://supplier.com/api/products.csv',
                    'columns' => ['sku' => 'product_code', 'title' => 'name', 'price' => 'sale_price'],
                ],
            ],
        ];

        if (!$sourceType || !isset($configs[$sourceType])) {
            return '<div class="text-gray-500 text-sm">Select a source type to see configuration guide</div>';
        }

        $config = $configs[$sourceType];

        $html = '<div class="space-y-3 text-sm">';

        // Title
        $html .= '<div class="flex items-center gap-2 font-semibold text-lg">';
        $html .= '<span>' . $config['icon'] . '</span>';
        $html .= '<span>' . $config['title'] . '</span>';
        $html .= '</div>';

        // Fields
        $html .= '<div class="space-y-2">';
        foreach ($config['fields'] as $field => $description) {
            $html .= '<div>';
            $html .= '<span class="font-medium text-primary-600">' . $field . ':</span> ';
            $html .= '<span class="text-gray-600">' . $description . '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';

        // Example
        $html .= '<div class="mt-4">';
        $html .= '<div class="font-medium mb-2">Example Configuration:</div>';
        $html .= '<pre class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg overflow-x-auto text-xs">';
        $html .= htmlspecialchars(json_encode($config['example'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $html .= '</pre>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }
}
