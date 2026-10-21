<?php

namespace NexusPlugin\TpOpenConnect\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NexusPlugin\TpOpenConnect\Services\OperationLogService;

class AdminAppController
{
    public function __construct(private readonly OperationLogService $logs)
    {
    }

    /**
     * 获取第三方接入应用列表 (分页)
     */
    public function listApps(Request $request)
    {
        $pageSize = $request->input('page_size', 15);
        // 验证 page_size 范围
        $pageSize = filter_var($pageSize, FILTER_VALIDATE_INT);
        if ($pageSize === false || $pageSize < 1 || $pageSize > 100) {
            $pageSize = 15;
        }
        
        $apps = DB::table('api_apps')
            ->select(['id', 'app_id', 'app_name', 'status', 'verify_origin', 'redirect_uris', 'asset_rules', 'created_at', 'updated_at'])
            ->orderBy('id', 'DESC')
            ->paginate($pageSize);

        foreach ($apps as $app) {
            $app->asset_rules   = json_decode($app->asset_rules ?? '{}', true);
            $app->redirect_uris = json_decode($app->redirect_uris ?? '[]', true);
            $app->verify_origin = (bool) $app->verify_origin;
        }

        return response()->json(['code' => 200, 'data' => $apps]);
    }

    /**
     * 创建或更新第三方接入应用配置 (含域名白名单与 4 项资产开关配置)
     */
    public function saveApp(Request $request)
    {
        $id           = $request->input('id');
        $appName      = $request->input('app_name');
        $status       = $request->input('status', 1);
        $ssoVerify    = $request->boolean('sso_verify', true);
        $verifyOrigin = $request->boolean('verify_origin', false); // 默认关闭，方便开发测试
        $redirectUris = $request->input('redirect_uris', []);
        $assetsInput  = $request->input('assets', []);

        // 严格的输入验证
        if (!is_string($appName) || trim($appName) === '' || mb_strlen($appName) > 100) {
            return response()->json(['code' => 400, 'message' => '应用名称参数不合法'], 400);
        }
        
        if (!is_array($redirectUris) || count($redirectUris) > 20) {
            return response()->json(['code' => 400, 'message' => '回调地址参数不合法'], 400);
        }
        
        // 验证每个 URI
        foreach ($redirectUris as $uri) {
            if (!is_string($uri) || mb_strlen($uri) > 255) {
                return response()->json(['code' => 400, 'message' => '回调地址格式不合法'], 400);
            }
        }
        
        // 验证 id 格式
        if ($id !== null && (!is_numeric($id) || (int)$id <= 0)) {
            return response()->json(['code' => 400, 'message' => 'ID 参数不合法'], 400);
        }
        $id = $id ? (int)$id : null;
        
        // 验证 status 格式
        if (!in_array((int)$status, [0, 1], true)) {
            return response()->json(['code' => 400, 'message' => '状态参数不合法'], 400);
        }
        $status = (int)$status;

        $redirectUris = array_values(array_unique(array_map('trim', $redirectUris)));
        foreach ($redirectUris as $uri) {
            $parsed = parse_url($uri);
            if (!$parsed || !in_array(strtolower($parsed['scheme'] ?? ''), ['https', 'http'], true)
                || empty($parsed['host']) || isset($parsed['user']) || isset($parsed['pass'])) {
                return response()->json(['code' => 400, 'message' => '回调地址必须是合法的 HTTP(S) 地址'], 400);
            }
        }

        $assetTypes = ['upload', 'download', 'bonus', 'signin_card'];
        $formattedRules = [
            'sso_verify'  => $ssoVerify,
            'asset_rules' => []
        ];

        foreach ($assetTypes as $type) {
            $formattedRules['asset_rules'][$type] = [
                'query'    => !empty($assetsInput[$type]['query']),
                'deduct'   => !empty($assetsInput[$type]['deduct']),
                'increase' => !empty($assetsInput[$type]['increase']),
            ];
        }

        $data = [
            'app_name'      => $appName,
            'status'        => $status,
            'verify_origin' => $verifyOrigin ? 1 : 0,
            'redirect_uris' => json_encode(array_values($redirectUris)),
            'asset_rules'   => json_encode($formattedRules),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        if ($id) {
            // 检查记录是否存在
            $exists = DB::table('api_apps')->where('id', $id)->exists();
            if (!$exists) {
                return response()->json(['code' => 404, 'message' => '接入应用不存在'], 404);
            }
            DB::table('api_apps')->where('id', $id)->update($data);
            $action = 'admin.app.update';
            $appId = DB::table('api_apps')->where('id', $id)->value('app_id');
        } else {
            $data['app_id']     = 'app_' . Str::random(12);
            $data['app_secret'] = 'sec_' . Str::random(32);
            $data['created_at'] = date('Y-m-d H:i:s');
            $id = DB::table('api_apps')->insertGetId($data);
            $action = 'admin.app.create';
            $appId = $data['app_id'];
        }

        $this->logs->adminChange($request, $action, '接入应用配置保存成功', $appId);

        return response()->json(['code' => 200, 'message' => '保存成功', 'data' => ['id' => $id]]);
    }

    /**
     * 删除第三方接入应用
     */
    public function deleteApp(Request $request)
    {
        $id = $request->input('id');
        
        // 严格验证 id
        if (!is_numeric($id) || (int)$id <= 0) {
            return response()->json(['code' => 400, 'message' => 'ID 参数不合法'], 400);
        }
        $id = (int)$id;
        
        $appId = DB::table('api_apps')->where('id', $id)->value('app_id');
        if (!$appId) {
            return response()->json(['code' => 404, 'message' => '接入应用不存在'], 404);
        }
        
        DB::table('api_apps')->where('id', $id)->delete();
        $this->logs->adminChange($request, 'admin.app.delete', '接入应用配置删除成功', $appId);
        return response()->json(['code' => 200, 'message' => '删除成功']);
    }

    /**
     * 查询交易流水审计日志
     */
    public function tradeLogs(Request $request)
    {
        $query = DB::table('api_asset_logs');

        if ($appId = $request->input('app_id')) {
            // 严格验证 app_id 格式
            if (!is_string($appId) || strlen($appId) > 64 || !preg_match('/^[a-z0-9_]+$/', $appId)) {
                return response()->json(['code' => 400, 'message' => '应用 ID 格式不合法'], 400);
            }
            $query->where('app_id', $appId);
        }
        if ($userId = $request->input('user_id')) {
            // 严格验证 user_id
            if (!is_numeric($userId) || (int)$userId <= 0) {
                return response()->json(['code' => 400, 'message' => '用户 ID 格式不合法'], 400);
            }
            $query->where('user_id', (int)$userId);
        }
        if ($assetType = $request->input('asset_type')) {
            // 严格验证 asset_type
            if (!in_array($assetType, ['upload', 'download', 'bonus', 'signin_card'], true)) {
                return response()->json(['code' => 400, 'message' => '资产类型不合法'], 400);
            }
            $query->where('asset_type', $assetType);
        }

        $pageSize = $request->input('page_size', 20);
        $pageSize = filter_var($pageSize, FILTER_VALIDATE_INT);
        if ($pageSize === false || $pageSize < 1 || $pageSize > 100) {
            $pageSize = 20;
        }
        
        $logs = $query->orderBy('id', 'DESC')->paginate($pageSize);
        return response()->json(['code' => 200, 'data' => $logs]);
    }
}
