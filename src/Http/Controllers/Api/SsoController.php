<?php

namespace NexusPlugin\TpOpenConnect\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use NexusPlugin\TpOpenConnect\Services\SsoService;

class SsoController
{
    /**
     * SSO Key 单次核销接口
     * POST /api/v1/sso/verify
     */
    public function verifySsoKey(Request $request)
    {
        $app = $request->get('current_app');
        if (empty($app->rules['sso_verify'])) {
            return response()->json(['code' => 403, 'message' => '无 SSO 验证权限'], 403);
        }

        $ssoKey = $request->input('sso_key');
        if (!$ssoKey) {
            return response()->json(['code' => 400, 'message' => '缺少 sso_key'], 400);
        }

        $data = (new SsoService())->verifyAndConsumeKeyForApp($ssoKey, (string) $app->app_id);
        if (!$data) {
            return response()->json(['code' => 400, 'message' => 'sso_key 无效或已过期'], 400);
        }
        $user = DB::table('users')->where('id', $data['user_id'])->first();

        // 统一错误响应，防止用户枚举攻击
        if (!$user || $user->status !== User::STATUS_CONFIRMED || $user->enabled !== User::ENABLED_YES) {
            return response()->json(['code' => 403, 'message' => '用户状态异常或无访问权限'], 403);
        }

        return response()->json([
            'code' => 200,
            'data' => [
                'user_id'   => $user->id,
                'username'  => $user->username,
                'email'     => $user->email,
                'class'     => (int)$user->class,
                'class_name'=> User::getClassText($user->class),
                'avatar_url'=> self::buildAvatarUrl($user->avatar),
            ]
        ]);
    }

    /**
     * 动态用户信息查询接口
     * GET /api/v1/user/info
     */
    public function getUserInfo(Request $request)
    {
        $app = $request->get('current_app');
        $assetRules = $app->rules['asset_rules'] ?? [];

        $userId = $request->input('user_id');
        // 验证 user_id 格式
        if (!is_numeric($userId) || (int)$userId <= 0) {
            return response()->json(['code' => 400, 'message' => '参数格式错误'], 400);
        }
        $userId = (int) $userId;
        
        $user = DB::table('users')->where('id', $userId)->first();

        // 统一错误响应，防止用户枚举攻击
        if (!$user || $user->status !== User::STATUS_CONFIRMED || $user->enabled !== User::ENABLED_YES) {
            return response()->json(['code' => 400, 'message' => '请求参数错误'], 400);
        }

        $className = User::getClassText($user->class);
        $data = [
            'user_id'   => $user->id,
            'username'  => $user->username,
            'class'     => (int)$user->class,
            'class_name'=> $className,
        ];

        // 按权限控制开关输出大整数/数值字符串
        if (!empty($assetRules['upload']['query']))     $data['uploaded_bytes']   = (string)$user->uploaded;
        if (!empty($assetRules['download']['query']))   $data['downloaded_bytes'] = (string)$user->downloaded;
        if (!empty($assetRules['bonus']['query']))      $data['bonus_points']     = (string)$user->seedbonus;
        // API 字段保持 signin_cards，数据库字段映射到核心 users.attendance_card。
        if (!empty($assetRules['signin_card']['query'])) $data['signin_cards']     = (int)$user->attendance_card;

        return response()->json(['code' => 200, 'data' => $data]);
    }

    /**
     * 构建完整的头像 URL
     */
    private static function buildAvatarUrl(?string $avatar): string
    {
        if (empty($avatar)) {
            return '';
        }
        
        // 如果已经是完整 URL，直接返回
        if (strpos($avatar, 'http://') === 0 || strpos($avatar, 'https://') === 0) {
            return $avatar;
        }
        
        // 拼接主站域名
        $baseUrl = rtrim(config('app.url'), '/');
        return $baseUrl . '/' . ltrim($avatar, '/');
    }

}
