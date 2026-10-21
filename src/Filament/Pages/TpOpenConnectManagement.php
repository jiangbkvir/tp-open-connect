<?php

namespace NexusPlugin\TpOpenConnect\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;

class TpOpenConnectManagement extends Page
{
    public static function canAccess(): bool
    {
        return auth('nexus-web')->check() && (int) auth('nexus-web')->user()->class >= User::CLASS_SYSOP;
    }

    protected static ?string $slug = 'system/tp-open-connect-management';
    protected static ?string $navigationLabel = 'TP Open Connect';
    protected static ?string $title = 'TP Open Connect';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected string $view = 'tp-open-connect::filament.pages.management';
}
