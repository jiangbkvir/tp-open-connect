<?php

namespace NexusPlugin\TpOpenConnect;

use Filament\Panel;
use Illuminate\Support\ServiceProvider;
use NexusPlugin\TpOpenConnect\Console\Commands\InstallCommand;
use NexusPlugin\TpOpenConnect\Console\Commands\UninstallCommand;
use NexusPlugin\TpOpenConnect\Filament\TpOpenConnectPlugin;
use NexusPlugin\TpOpenConnect\Http\Middleware\ApiAuthMiddleware;
use NexusPlugin\TpOpenConnect\Http\Middleware\TpOpenConnectAdminMiddleware;

class TpOpenConnectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 注册中间件
        $this->app['router']->aliasMiddleware('api.auth', ApiAuthMiddleware::class);
        $this->app['router']->aliasMiddleware('tpoc.admin', TpOpenConnectAdminMiddleware::class);

        // Filament 5 的面板在第一次被解析时才创建（AppPanelProvider 只注册了工厂闭包），
        // 因此在这里登记插件即可，无需修改主站 AppPanelProvider，也无需手动注册路由与 Livewire 组件。
        if (class_exists(Panel::class)) {
            Panel::configureUsing(function (Panel $panel): void {
                $panel->plugin(TpOpenConnectPlugin::make());
            });
        }
    }

    public function boot(): void
    {
        // 加载路由
        $this->loadRoutesFrom(__DIR__ . '/../routes/external.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/internal.php');

        // 加载数据库迁移
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 加载视图
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'tp-open-connect');

        // 注册控制台命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                UninstallCommand::class,
            ]);
        }
    }
}
