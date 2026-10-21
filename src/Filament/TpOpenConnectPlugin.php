<?php

namespace NexusPlugin\TpOpenConnect\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use NexusPlugin\TpOpenConnect\Filament\Pages\IntegrationGuide;
use NexusPlugin\TpOpenConnect\Filament\Pages\TpOpenConnectManagement;
use NexusPlugin\TpOpenConnect\Filament\Resources\ApiAppResource;
use NexusPlugin\TpOpenConnect\Filament\Resources\ApiAssetLogResource;

class TpOpenConnectPlugin implements Plugin
{
    public function getId(): string
    {
        return 'tp-open-connect';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                ApiAppResource::class,
                ApiAssetLogResource::class,
            ])
            ->pages([
                TpOpenConnectManagement::class,
                IntegrationGuide::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
