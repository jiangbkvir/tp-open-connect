<?php

namespace NexusPlugin\TpOpenConnect\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/** 对外 API 统一的 HMAC-SHA256 签名工具。 */
class HmacService
{
    /** 时间戳容错窗口（60秒） */
    private const TIMESTAMP_WINDOW = 60;
    
    /** Nonce 有效期（秒） */
    private const NONCE_TTL = 300;

    /** 返回应用对象或 JSON 错误响应，所有对外 API 统一经过此方法。 */
    public function authenticate(Request $request): mixed
    {
        $appId = (string) $request->header('X-App-ID');
        $timestamp = (string) $request->header('X-Timestamp');
        $nonce = (string) $request->header('X-Nonce');
        $signature = (string) $request->header('X-Signature');

        if ($appId === '' || $timestamp === '' || $nonce === '' || $signature === '') {
            return response()->json(['code' => 401, 'message' => '缺少必要的认证请求头'], 401);
        }
        
        // 严格验证时间戳格式和范围
        if (!ctype_digit($timestamp)) {
            return response()->json(['code' => 401, 'message' => '时间戳格式不合法'], 401);
        }
        $ts = (int) $timestamp;
        if ($ts <= 0 || abs(time() - $ts) > self::TIMESTAMP_WINDOW) {
            return response()->json(['code' => 401, 'message' => '请求已过期'], 401);
        }
        
        // 严格验证 nonce 格式
        if (!preg_match('/^[A-Za-z0-9._:-]{16,128}$/', $nonce)) {
            return response()->json(['code' => 401, 'message' => 'Nonce 格式不合法'], 401);
        }

        // 验证 app_id 格式
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $appId) || strlen($appId) > 64) {
            return response()->json(['code' => 401, 'message' => 'App ID 格式不合法'], 401);
        }

        $app = DB::table('api_apps')->where('app_id', $appId)->first();
        if (!$app || !$app->status) {
            return response()->json(['code' => 403, 'message' => '第三方接入应用凭证无效或已被禁用'], 403);
        }
        
        // 签名验证
        $expected = $this->signRequest($request, $app->app_secret, $appId, $timestamp, $nonce);
        if (!hash_equals($expected, $signature)) {
            return response()->json(['code' => 401, 'message' => '签名校验失败'], 401);
        }

        $app->rules = json_decode($app->asset_rules ?? '{}', true) ?: [];
        $app->redirect_uris = json_decode($app->redirect_uris ?? '[]', true) ?: [];
        
        // 来源域名校验（根据 verify_origin 开关决定是否校验）
        // 默认关闭（0），方便开发环境测试；生产环境建议开启（1）
        if ($app->verify_origin && $app->redirect_uris) {
            $origin = $request->header('Origin') ?? $request->header('Referer');
            if ($origin) {
                $parsed = parse_url($origin);
                // 防止路径遍历攻击
                if (!$parsed || !isset($parsed['scheme'], $parsed['host'])) {
                    return response()->json(['code' => 403, 'message' => '请求来源不合法'], 403);
                }
                $source = ($parsed['scheme'] ?? '') . '://' . ($parsed['host'] ?? '') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
                $allowed = array_map(static fn ($uri) => rtrim(strtolower((string) $uri), '/'), $app->redirect_uris);
                if (!in_array(strtolower($source), $allowed, true)) {
                    return response()->json(['code' => 403, 'message' => '请求来源域名未授权'], 403);
                }
            }
        }

        // 仅在签名和来源校验都通过后消耗 Nonce。
        $nonceKey = "api:nonce:{$appId}:{$nonce}";
        if (!Redis::set($nonceKey, '1', 'EX', self::NONCE_TTL, 'NX')) {
            return response()->json(['code' => 401, 'message' => '重复的请求 (Nonce Replayed)'], 401);
        }

        return $app;
    }

    public function payload(string $appId, string|int $timestamp, string $nonce, string $method, string $path, array $query, string $body): string
    {
        ksort($query);
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        return implode("\n", [
            strtoupper($method),
            $path,
            $queryString,
            $appId,
            (string) $timestamp,
            $nonce,
            $body,
        ]);
    }

    public function sign(string $appId, string $secret, string|int $timestamp, string $nonce, string $method, string $path, array $query, string $body): string
    {
        return hash_hmac('sha256', $this->payload($appId, $timestamp, $nonce, $method, $path, $query, $body), $secret);
    }

    public function signRequest(Request $request, string $secret, string $appId, string|int $timestamp, string $nonce): string
    {
        // 获取规范化路径，防止路径遍历
        $requestUri = $request->getRequestUri();
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
        
        // 路径规范化：移除 .. 和多余斜杠
        $path = $this->normalizePath($path);

        return $this->sign($appId, $secret, $timestamp, $nonce, $request->getMethod(), $path, $request->query(), $request->getContent());
    }
    
    /**
     * 规范化路径，防止路径遍历攻击
     */
    private function normalizePath(string $path): string
    {
        // 解码 URL 编码字符
        $path = urldecode($path);
        
        // 移除 null 字节
        $path = str_replace("\0", '', $path);
        
        // 分割路径组件
        $parts = explode('/', $path);
        $result = [];
        
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                // 防止回退到根目录之上
                array_pop($result);
            } else {
                $result[] = $part;
            }
        }
        
        return '/' . implode('/', $result);
    }
}
