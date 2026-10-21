<?php

namespace NexusPlugin\TpOpenConnect\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use NexusPlugin\TpOpenConnect\Filament\Pages\TpOpenConnectManagement;

class TpOpenConnectPlugin implements Plugin
{
    public function getId(): string
    {
        return 'tp-open-connect';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            TpOpenConnectManagement::class,
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