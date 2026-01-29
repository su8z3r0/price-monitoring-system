<?php

namespace App\Filament\Resources\CompetitorPriceResource\Pages;

use App\Filament\Resources\CompetitorPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompetitorPrices extends ListRecords
{
    protected static string $resource = CompetitorPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
