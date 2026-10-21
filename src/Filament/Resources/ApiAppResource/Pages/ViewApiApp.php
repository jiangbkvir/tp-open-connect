<?php

namespace NexusPlugin\TpOpenConnect\Filament\Resources\ApiAppResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use NexusPlugin\TpOpenConnect\Filament\Resources\ApiAppResource;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class ViewApiApp extends ViewRecord
{
    protected static string $resource = ApiAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerate_secret')
                ->label('重新生成 Secret')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('重新生成 App Secret')
                ->modalDescription('这将使旧的 App Secret 失效，确定要继续吗？')
                ->action(function () {
                    $secret = 'sec_' . Str::random(32);
                    
                    $this->record->update([
                        'app_secret' => $secret,
                        'updated_at' => now(),
                    ]);
                    
                    // 在右上角显示通知
                    Notification::make()
                        ->success()
                        ->title('App Secret 已重新生成')
                        ->body("新的 Secret：\n\n{$secret}\n\n⚠️ 旧的 Secret 已失效，新的只显示这一次！")
                        ->persistent()
                        ->send();
                }),
            Action::make('edit')
                ->label('编辑')
                ->url(fn () => static::getResource()::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
