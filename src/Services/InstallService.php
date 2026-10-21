<?php

namespace NexusPlugin\TpOpenConnect\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use NexusPlugin\TpOpenConnect\Models\ApiApp;

class InstallService
{
    public function install(): array
    {
        $migrationPath = $this->packageRoot() . '/database/migrations';
        Artisan::call('migrate', [
            '--path' => $migrationPath,
            '--realpath' => true,
            '--force' => true,
        ]);
        $migrationOutput = trim(Artisan::output());

        $link = $this->ensurePublicLink();

        Artisan::call('optimize:clear');

        return [
            'migration_output' => $migrationOutput,
            'public_link' => $link,
        ];
    }

    public function uninstall(bool $dropData = false): array
    {
        $link = public_path('plugins/tp-open-connect');
        $removedLink = false;

        if (is_link($link)) {
            $target = realpath($link);
            $expected = realpath($this->packageRoot() . '/public');
            if ($target === $expected) {
                unlink($link);
                $removedLink = true;
            }
        }

        if ($dropData) {
            Schema::dropIfExists('api_asset_logs');
            Schema::dropIfExists('api_operation_logs');
            Schema::dropIfExists('api_apps');
        }

        return [
            'removed_link' => $removedLink,
            'dropped_data' => $dropData,
            'filament_removed' => false,
        ];
    }

    public function ensurePublicLink(): string
    {
        $publicPlugins = public_path('plugins');
        if (!is_dir($publicPlugins) && !mkdir($publicPlugins, 0775, true) && !is_dir($publicPlugins)) {
            throw new \RuntimeException("无法创建公共插件目录：{$publicPlugins}");
        }

        $target = realpath($this->packageRoot() . '/public');
        if ($target === false) {
            throw new \RuntimeException('插件 public 目录不存在。');
        }

        $link = $publicPlugins . '/tp-open-connect';
        if (is_link($link)) {
            if (realpath($link) === $target) {
                return $link;
            }
            throw new \RuntimeException("公共链接已存在且指向其他位置：{$link}");
        }
        if (file_exists($link)) {
            throw new \RuntimeException("公共路径已存在且不是软链接，拒绝覆盖：{$link}");
        }
        if (!symlink($target, $link)) {
            throw new \RuntimeException("创建公共链接失败：{$link}");
        }

        return $link;
    }

    public function packageRoot(): string
    {
        $reflection = new \ReflectionClass(ApiApp::class);

        return dirname((string) $reflection->getFileName(), 3);
    }

}
