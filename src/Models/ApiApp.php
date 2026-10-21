<?php

namespace NexusPlugin\TpOpenConnect\Models;

use Illuminate\Database\Eloquent\Model;
use NexusPlugin\TpOpenConnect\Services\OperationLogService;

class ApiApp extends Model
{
    protected static function booted(): void
    {
        static::updated(function (self $app): void {
            if (app()->runningInConsole() || !request()) return;
            $action = $app->wasChanged('app_secret') ? 'admin.app.regenerate_secret' : 'admin.app.update';
            $result = $app->wasChanged('app_secret') ? 'App Secret 已重新生成' : '接入应用配置修改成功';
            app(OperationLogService::class)->adminChange(request(), $action, $result, $app->app_id);
        });
        static::created(function (self $app): void {
            if (app()->runningInConsole() || !request()) return;
            app(OperationLogService::class)->adminChange(request(), 'admin.app.create', '接入应用配置创建成功', $app->app_id);
        });
        static::deleted(function (self $app): void {
            if (app()->runningInConsole() || !request()) return;
            app(OperationLogService::class)->adminChange(request(), 'admin.app.delete', '接入应用配置删除成功', $app->app_id);
        });
    }
    protected $connection = 'mysql';
    protected $table = 'api_apps';

    protected $fillable = [
        'app_id',
        'app_secret',
        'app_name',
        'status',
        'redirect_uris',
        'asset_rules',
        'verify_origin',
    ];

    protected $casts = [
        'redirect_uris' => 'array',
        'asset_rules' => 'array',
        'status' => 'boolean',
        'verify_origin' => 'boolean',
    ];

    /**
     * 检查是否启用 SSO 验证
     */
    public function hasSsoVerify(): bool
    {
        return (bool) ($this->asset_rules['sso_verify'] ?? false);
    }

    /**
     * 获取资产权限规则
     */
    public function getAssetRules(): array
    {
        return $this->asset_rules['asset_rules'] ?? [];
    }

    /**
     * 检查是否有某个资产类型的某个操作权限
     */
    public function hasPermission(string $assetType, string $action): bool
    {
        $rules = $this->getAssetRules();
        return !empty($rules[$assetType][$action]);
    }

    /**
     * 是否校验请求来源
     * 默认关闭（false），方便开发环境测试
     */
    public function shouldVerifyOrigin(): bool
    {
        return (bool) $this->verify_origin;
    }

    /**
     * 验证回调域名是否在白名单中
     */
    public function isAllowedRedirectUri(string $url): bool
    {
        if (empty($this->redirect_uris)) {
            return false;
        }

        $parseUrl = parse_url($url);
        if (!$parseUrl || empty($parseUrl['scheme']) || empty($parseUrl['host']) || isset($parseUrl['user']) || isset($parseUrl['pass'])) {
            return false;
        }
        $targetHost = strtolower($parseUrl['scheme']) . '://' . strtolower($parseUrl['host']);
        if (isset($parseUrl['port'])) {
            $targetHost .= ':' . $parseUrl['port'];
        }

        return in_array($targetHost, array_map(static fn ($uri) => rtrim(strtolower((string) $uri), '/'), $this->redirect_uris), true);
    }
}
