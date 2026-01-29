<?php

namespace App\Filament\Resources\CompetitorPriceResource\Pages;

use App\Filament\Resources\CompetitorPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompetitorPrice extends EditRecord
{
    protected static string $resource = CompetitorPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
