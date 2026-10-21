<?php

namespace NexusPlugin\TpOpenConnect\Filament\Pages;

use Filament\Pages\Page;

class IntegrationGuide extends Page
{
    protected static ?string $slug = 'tp-open-connect-integration-guide';
    protected static ?string $navigationLabel = '对接方案';
    protected static ?string $title = 'TP Open Connect 对接方案';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'tp-open-connect::filament.pages.integration-guide';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}