<?php

namespace App\Filament\Resources\PriceComparisonResource\Pages;

use App\Filament\Resources\PriceComparisonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPriceComparisons extends ListRecords
{
    protected static string $resource = PriceComparisonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
