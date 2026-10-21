<?php

namespace NexusPlugin\TpOpenConnect;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Livewire\Mechanisms\ComponentRegistry;
use NexusPlugin\TpOpenConnect\Console\Commands\InstallCommand;
use NexusPlugin\TpOpenConnect\Console\Commands\UninstallCommand;
use NexusPlugin\TpOpenConnect\Http\Middleware\ApiAuthMiddleware;
use NexusPlugin\TpOpenConnect\Http\Middleware\TpOpenConnectAdminMiddleware;

class TpOpenConnectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 注册中间件
        $this->app['router']->aliasMiddleware('api.auth', ApiAuthMiddleware::class);
        $this->app['router']->aliasMiddleware('tpoc.admin', TpOpenConnectAdminMiddleware::class);
    }

    public function boot(): void
    {
        // Filament 面板由主站 Provider 延迟创建，应用完全启动后再注入插件组件并注册路由。
        // 这样安装插件不需要修改主站 AppPanelProvider。
        $this->app->booted(function (): void {
            if (! class_exists(Filament::class)) {
                return;
            }

            $panel = Filament::getDefaultPanel();
            $resources = [
                \NexusPlugin\TpOpenConnect\Filament\Resources\ApiAppResource::class,
                \NexusPlugin\TpOpenConnect\Filament\Resources\ApiAssetLogResource::class,
            ];
            $pages = [\NexusPlugin\TpOpenConnect\Filament\Pages\TpOpenConnectManagement::class];
            $panel->resources($resources)->pages($pages);

            // 面板注册发生在应用 booted 回调之前，资源页面不会自动进入 Livewire 注册表。
            // 显式注册这些组件，保证分页、筛选等后续 Livewire 请求可以解析页面别名。
            $livewireComponents = $pages;
            foreach ($resources as $resource) {
                foreach ($resource::getPages() as $pageRegistration) {
                    $livewireComponents[] = $pageRegistration->getPage();
                }
            }

            $componentRegistry = app(ComponentRegistry::class);
            foreach (array_unique($livewireComponents) as $component) {
                Livewire::component($componentRegistry->getName($component), $component);
            }

            Route::name('filament.')->group(function () use ($panel, $pages, $resources): void {
                Route::middleware($panel->getMiddleware())
                    ->prefix($panel->getPath())
                    ->name($panel->getId() . '.')
                    ->middleware($panel->getAuthMiddleware())
                    ->group(function () use ($panel, $pages, $resources): void {
                        foreach ($pages as $page) {
                            $page::registerRoutes($panel);
                        }
                        foreach ($resources as $resource) {
                            $resource::registerRoutes($panel);
                        }
                    });
            });
        });
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
