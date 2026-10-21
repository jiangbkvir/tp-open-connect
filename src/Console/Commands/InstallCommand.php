<?php

namespace NexusPlugin\TpOpenConnect\Console\Commands;

use Illuminate\Console\Command;
use NexusPlugin\TpOpenConnect\Services\InstallService;

class InstallCommand extends Command
{
    protected $signature = 'tp-open-connect:install';
    protected $description = 'Install or repair the TP Open Connect plugin';

    public function handle(InstallService $installer): int
    {
        $this->info('正在安装 TP Open Connect 插件...');

        $result = $installer->install();

        $this->info('公开路径：' . $result['public_link']);
        $this->info('数据库迁移：' . ($result['migration_output'] ?: '已完成'));

        $this->info('Filament 页面和资源：插件运行时注册（未修改主站 Provider）');

        $this->newLine();
        $this->info('TP Open Connect 插件安装完成！');
        $this->newLine();

        $this->info('管理界面访问方式：');
        $this->info('  - 后台菜单：System → TP Open Connect');
        $this->info('  - 对接说明：/nexusphp/tp-open-connect-integration-guide');
        $this->newLine();

        $this->info('API 接口：');
        $this->info('  POST /api/v1/sso/verify - SSO 验证');
        $this->info('  GET  /api/v1/user/info - 用户信息查询');
        $this->info('  POST /api/v1/asset/trade - 资产交易');

        return self::SUCCESS;
    }
}
