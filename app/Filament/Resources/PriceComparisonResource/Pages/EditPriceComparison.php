<?php

namespace App\Filament\Resources\PriceComparisonResource\Pages;

use App\Filament\Resources\PriceComparisonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPriceComparison extends EditRecord
{
    protected static string $resource = PriceComparisonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
