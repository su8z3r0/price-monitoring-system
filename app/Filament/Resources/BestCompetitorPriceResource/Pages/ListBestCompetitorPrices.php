<?php

namespace App\Filament\Resources\BestCompetitorPriceResource\Pages;

use App\Filament\Resources\BestCompetitorPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBestCompetitorPrices extends ListRecords
{
    protected static string $resource = BestCompetitorPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
