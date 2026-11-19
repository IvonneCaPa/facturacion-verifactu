<?php

namespace App\Filament\Resources\SerieFacturacionResource\Pages;

use App\Filament\Resources\SerieFacturacionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSerieFacturacions extends ManageRecords
{
    protected static string $resource = SerieFacturacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
