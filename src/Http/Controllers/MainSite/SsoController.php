<?php

namespace NexusPlugin\TpOpenConnect\Http\Controllers\MainSite;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use NexusPlugin\TpOpenConnect\Models\ApiApp;
use Illuminate\Support\Facades\Redis;

class SsoController
{
    /**
     * 主站生成 SSO Key
     * POST /tp-open-connect/sso/generate
     *
     * 传入 app_id，返回 sso_key
     * 主站前端拿到 sso_key 后自行拼接到第三方应用URL进行跳转
     */
    public function generateSsoKey(Request $request)
    {
        // 1. 验证用户登录
        $user = Auth::guard('nexus-web')->user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '用户未登录'
            ], 401);
        }
        if ($user->status !== \App\Models\User::STATUS_CONFIRMED || $user->enabled !== \App\Models\User::ENABLED_YES) {
            return response()->json(['code' => 403, 'message' => '用户状态异常或已封禁'], 403);
        }

        // 2. 验证参数
        $appId = $request->input('app_id');
        if (!$appId) {
            return response()->json([
                'code' => 400,
                'message' => '缺少 app_id 参数'
            ], 400);
        }

        // 3. 查询第三方接入应用配置
        $app = ApiApp::where('app_id', $appId)->first();
        if (!$app) {
            return response()->json([
                'code' => 404,
                'message' => '第三方接入应用不存在'
            ], 404);
        }

        if (!$app->status) {
            return response()->json([
                'code' => 403,
                'message' => '第三方接入应用已被禁用'
            ], 403);
        }

        // 4. 检查是否启用 SSO
        if (!$app->hasSsoVerify()) {
            return response()->json([
                'code' => 403,
                'message' => '第三方接入应用未启用 SSO 功能'
            ], 403);
        }

        // 5. 生成 sso_key 并保存到 Redis（30秒有效期）
        $ssoKey = bin2hex(random_bytes(16));
        Redis::setex("sso:key:{$ssoKey}", 30, json_encode([
            'user_id' => $user->id,
            'app_id' => $app->app_id,
        ]));

        return response()->json([
            'code' => 200,
            'message' => '生成成功',
            'data' => [
                'sso_key' => $ssoKey,
                'expires_in' => 30,
            ]
        ]);
    }
}
