<?php

use Illuminate\Support\Facades\Route;
use NexusPlugin\TpOpenConnect\Http\Controllers\Api\AssetController;
use NexusPlugin\TpOpenConnect\Http\Controllers\Api\SsoController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

// 配置对外 API 频率限制
RateLimiter::for('tpoc-api', function (\Illuminate\Http\Request $request) {
    $appId = $request->header('X-App-ID') ?: 'unknown';
    $ip = $request->ip() ?: 'unknown';
    
    // 按 App ID + IP 组合限制
    return [
        // 一般接口：每分钟 60 次
        Limit::perMinute(60)->by('tpoc:' . $appId . ':' . $ip),
        // 交易接口：每分钟 30 次（更严格）
        Limit::perMinute(30)->by('tpoc-trade:' . $appId . ':' . $ip),
    ];
});

// 对外接口：所有请求必须经过统一 HMAC 认证中间件。
Route::prefix('api/v1')->middleware(['api.auth', 'throttle:tpoc-api'])->group(function (): void {
    // SSO 验证接口
    Route::post('/sso/verify', [SsoController::class, 'verifySsoKey'])
        ->middleware('throttle:60,1'); // 每分钟 60 次
    
    // 用户信息查询接口
    Route::get('/user/info', [SsoController::class, 'getUserInfo'])
        ->middleware('throttle:60,1'); // 每分钟 60 次
    
    // 资产交易接口（更严格的限制）
    Route::post('/asset/trade', [AssetController::class, 'tradeAsset'])
        ->middleware('throttle:30,1'); // 每分钟 30 次
});
