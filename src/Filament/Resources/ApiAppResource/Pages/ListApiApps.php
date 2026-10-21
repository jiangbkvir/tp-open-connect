<?php

namespace NexusPlugin\TpOpenConnect\Filament\Resources\ApiAppResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use NexusPlugin\TpOpenConnect\Filament\Resources\ApiAppResource;
use NexusPlugin\TpOpenConnect\Models\ApiApp;
use NexusPlugin\TpOpenConnect\Models\ApiAssetLog;

class ListApiApps extends ListRecords
{
    protected static string $resource = ApiAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('integration_guide')
                ->label('对接方案')
                ->icon('heroicon-o-book-open')
                ->url(route('tpoc.integration-guide'))
                ->openUrlInNewTab(),
            Actions\Action::make('trade_logs')
                ->label('查看交易流水')
                ->icon('heroicon-o-document-text')
                ->url(\NexusPlugin\TpOpenConnect\Filament\Resources\ApiAssetLogResource::getUrl('index')),
            Actions\Action::make('stats')
                ->label('统计概览')
                ->icon('heroicon-o-chart-bar')
                ->modalHeading('统计概览')
                ->modalDescription(function () {
                    $totalApps = ApiApp::count();
                    $activeApps = ApiApp::where('status', 1)->count();
                    $totalTrades = ApiAssetLog::count();

                    return "第三方接入应用总数: {$totalApps} | 启用中: {$activeApps} | 交易笔数: " . number_format($totalTrades);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('关闭'),
            Actions\CreateAction::make(),
        ];
    }
}
