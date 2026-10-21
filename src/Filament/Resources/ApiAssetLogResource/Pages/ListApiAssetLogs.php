<?php

namespace NexusPlugin\TpOpenConnect\Filament\Resources\ApiAssetLogResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use NexusPlugin\TpOpenConnect\Filament\Resources\ApiAssetLogResource;

class ListApiAssetLogs extends ListRecords
{
    protected static string $resource = ApiAssetLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_apps')
                ->label('返回接入应用管理')
                ->icon('heroicon-o-arrow-left')
                ->url(\NexusPlugin\TpOpenConnect\Filament\Resources\ApiAppResource::getUrl('index')),
        ];
    }
}
