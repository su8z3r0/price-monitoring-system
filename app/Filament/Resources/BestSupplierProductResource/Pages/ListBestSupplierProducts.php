<?php

namespace App\Filament\Resources\BestSupplierProductResource\Pages;

use App\Filament\Resources\BestSupplierProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBestSupplierProducts extends ListRecords
{
    protected static string $resource = BestSupplierProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
