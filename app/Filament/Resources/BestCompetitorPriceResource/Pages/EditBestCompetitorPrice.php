<?php

namespace App\Filament\Resources\BestCompetitorPriceResource\Pages;

use App\Filament\Resources\BestCompetitorPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBestCompetitorPrice extends EditRecord
{
    protected static string $resource = BestCompetitorPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
