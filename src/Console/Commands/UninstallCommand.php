<?php

namespace NexusPlugin\TpOpenConnect\Console\Commands;

use Illuminate\Console\Command;
use NexusPlugin\TpOpenConnect\Services\InstallService;

class UninstallCommand extends Command
{
    protected $signature = 'tp-open-connect:uninstall {--drop-data : Drop all plugin data tables}';
    protected $description = 'Uninstall the TP Open Connect plugin';

    public function handle(InstallService $installer): int
    {
        $this->info('正在卸载 TP Open Connect 插件...');

        $dropData = $this->option('drop-data');

        if ($dropData) {
            if (!$this->confirm('这将删除所有插件数据表，确定要继续吗？')) {
                $this->info('卸载已取消。');
                return self::SUCCESS;
            }
        }

        $result = $installer->uninstall($dropData);

        if ($result['removed_link']) {
            $this->info('公共目录软链接已移除。');
        }

        if ($result['dropped_data']) {
            $this->info('数据表已删除。');
        }

        if (isset($result['filament_removed']) && $result['filament_removed']) {
            $this->info('Filament 页面注册已移除。');
        }

        $this->info('TP Open Connect 插件卸载完成！');
        $this->newLine();
        $this->info('要从 Composer 完全移除插件：');
        $this->info('  composer remove nexusphp/tp-open-connect');

        return self::SUCCESS;
    }
}