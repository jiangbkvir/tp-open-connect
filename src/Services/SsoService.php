<?php

namespace NexusPlugin\TpOpenConnect\Services;

use Illuminate\Support\Facades\Redis;
use NexusPlugin\TpOpenConnect\Models\ApiApp;

class SsoService
{
    /**
     * 主站生成带有 sso_key 的 URL (带回调域名白名单校验)
     *
     * @param int $userId 用户 ID
     * @param string $targetAppUrl 目标接入应用 URL
     * @param ApiApp|string $app 接入应用配置对象或 app_id
     * @return string 带有 sso_key 的完整 URL
     * @throws \InvalidArgumentException 如果回调域名不在白名单中
     */
    public function generateSsoUrl(int $userId, string $targetAppUrl, $app): string
    {
        // 如果传入的是 app_id 字符串，则查询对应的 App
        if (is_string($app)) {
            $app = ApiApp::query()->where('app_id', $app)->first();
            if (!$app) {
                throw new \InvalidArgumentException("接入应用不存在: {$app}");
            }
        }

        if (!$app->status) {
            throw new \InvalidArgumentException('接入应用已禁用');
        }

        if (!$app->isAllowedRedirectUri($targetAppUrl)) {
            $parseUrl = parse_url($targetAppUrl);
            $targetHost = ($parseUrl['scheme'] ?? 'https') . '://' . ($parseUrl['host'] ?? '');
            throw new \InvalidArgumentException("非法回调域名: {$targetHost}");
        }

        $ssoKey = bin2hex(random_bytes(16));
        Redis::setex("sso:key:{$ssoKey}", 30, json_encode([
            'user_id' => $userId,
            'app_id' => $app->app_id,
        ]));

        $joiner = (strpos($targetAppUrl, '?') === false) ? '?' : '&';
        return $targetAppUrl . $joiner . 'sso_key=' . $ssoKey;
    }

    /**
     * 验证 sso_key 并返回用户信息
     *
     * @param string $ssoKey SSO 密钥
     * @return array|null 用户信息数组，如果无效则返回 null
     */
    public function verifyAndConsumeKey(string $ssoKey): ?array
    {
        // PhpRedis eval 参数顺序为 (脚本, key 数量, keys/args...)。
        $rawJson = Redis::eval("return redis.call('GETDEL', KEYS[1])", 1, "sso:key:{$ssoKey}");
        if (!$rawJson) {
            return null;
        }

        return json_decode($rawJson, true);
    }

    /**
     * 仅当 Key 属于指定应用时才核销，避免错误应用请求消耗有效 Key。
     */
    public function verifyAndConsumeKeyForApp(string $ssoKey, string $appId): ?array
    {
        $script = <<<'LUA'
local value = redis.call('GET', KEYS[1])
if not value then return false end
local object = cjson.decode(value)
if object.app_id ~= ARGV[1] then return false end
redis.call('DEL', KEYS[1])
return value
LUA;

        $rawJson = Redis::eval($script, 1, "sso:key:{$ssoKey}", $appId);
        return $rawJson ? json_decode($rawJson, true) : null;
    }
}
