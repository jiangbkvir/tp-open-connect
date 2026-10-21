<?php

namespace NexusPlugin\TpOpenConnect\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\QueryException;
use App\Models\User;

class AssetController
{

    /**
     * 幂等、高并发与高精度交易接口
     * POST /api/v1/asset/trade
     */
    public function tradeAsset(Request $request)
    {
        $app        = $request->get('current_app');
        $assetRules = $app->rules['asset_rules'] ?? [];

        $userId     = (int) $request->input('user_id');
        $outTradeNo = trim((string) $request->input('out_trade_no'));
        $assetType  = $request->input('asset_type');
        $action     = $request->input('action');
        $amountStr  = (string)$request->input('amount');
        $remark     = trim((string) $request->input('remark', ''));

        if (!in_array($assetType, ['upload', 'download', 'bonus', 'signin_card'], true)
            || !in_array($action, ['deduct', 'increase'], true)) {
            return response()->json(['code' => 400, 'message' => '资产类型或交易操作不合法'], 400);
        }

        if ($userId <= 0 || $outTradeNo === '' || strlen($outTradeNo) > 64 || strlen($remark) > 255
            || !preg_match('/^[0-9]+$/', $amountStr)
            || bccomp($amountStr, '0', 0) <= 0
            || bccomp($amountStr, '18446744073709551615', 0) > 0) {
            return response()->json(['code' => 400, 'message' => '参数不合法或交易数量必须大于0'], 400);
        }

        // 权限必须先于幂等返回，避免无权限请求复用旧订单得到 200。
        $rule = $assetRules[$assetType] ?? [];
        if (($action === 'deduct' && empty($rule['deduct'])) || ($action === 'increase' && empty($rule['increase']))) {
            return response()->json(['code' => 403, 'message' => "无权对 [{$assetType}] 执行 [{$action}] 操作"], 403);
        }

        // 1. 幂等性检测：若已处理直接返回历史日志
        $existLog = DB::table('api_asset_logs')
            ->where('app_id', $app->app_id)
            ->where('out_trade_no', $outTradeNo)
            ->first();

        if ($existLog) {
            if ((int) $existLog->user_id !== $userId || $existLog->asset_type !== $assetType
                || $existLog->action !== $action || (string) $existLog->amount !== $amountStr) {
                return response()->json(['code' => 409, 'message' => '外部订单号已使用且交易参数不一致'], 409);
            }
            return response()->json([
                'code' => 200,
                'message' => '重复请求，返回历史交易结果',
                'data' => [
                    'trade_id'      => $existLog->trade_id,
                    'out_trade_no'  => $existLog->out_trade_no,
                    'asset_type'    => $existLog->asset_type,
                    'action'        => $existLog->action,
                    'amount'        => (string)$existLog->amount,
                    'before_amount' => (string)$existLog->before_amount,
                    'after_amount'  => (string)$existLog->after_amount,
                ]
            ]);
        }

        // 前置 Redis 分布式并发锁（60秒超时，支持续期）
        $lockKey = "sso:trade:lock:{$userId}";
        $lockToken = bin2hex(random_bytes(16));
        if (!Redis::set($lockKey, $lockToken, "EX", 60, "NX")) {
            return response()->json(['code' => 429, 'message' => '并发交易请求频繁，请稍后重试'], 429);
        }

        $fieldMap = [
            'upload'      => 'uploaded',
            'download'    => 'downloaded',
            'bonus'       => 'seedbonus',
            // API 对外统一使用 signin_card，核心 users 表实际字段为 attendance_card。
            'signin_card' => 'attendance_card',
        ];
        $dbField = $fieldMap[$assetType];
        $lockRefreshed = false;

        try {
            DB::beginTransaction();

            // 续期锁（防止长事务超时）
            Redis::expire($lockKey, 60);
            $lockRefreshed = true;

            // 在锁和事务内再次检查，覆盖首次查询后的并发窗口。
            $existLog = DB::table('api_asset_logs')
                ->where('app_id', $app->app_id)
                ->where('out_trade_no', $outTradeNo)
                ->lockForUpdate()
                ->first();
            if ($existLog) {
                DB::rollBack();
                if ((int) $existLog->user_id !== $userId || $existLog->asset_type !== $assetType
                    || $existLog->action !== $action || (string) $existLog->amount !== $amountStr) {
                    return response()->json(['code' => 409, 'message' => '外部订单号已使用且交易参数不一致'], 409);
                }
                return response()->json([
                    'code' => 200,
                    'message' => '重复请求，返回历史交易结果',
                    'data' => [
                        'trade_id' => $existLog->trade_id,
                        'out_trade_no' => $existLog->out_trade_no,
                        'asset_type' => $existLog->asset_type,
                        'action' => $existLog->action,
                        'amount' => (string) $existLog->amount,
                        'before_amount' => (string) $existLog->before_amount,
                        'after_amount' => (string) $existLog->after_amount,
                    ],
                ]);
            }

            $user = DB::table('users')->where('id', $userId)->lockForUpdate()->first();
            // 统一错误响应，防止用户枚举
            if (!$user || $user->status !== User::STATUS_CONFIRMED || $user->enabled !== User::ENABLED_YES) {
                DB::rollBack();
                return response()->json(['code' => 400, 'message' => '交易请求参数错误'], 400);
            }

            $beforeAmount = (string)$user->$dbField;

            // 4. BCMath 高精度计算，防止大字节数浮点溢出
            if ($action === 'deduct') {
                if (bccomp($beforeAmount, $amountStr, 0) < 0) {
                    DB::rollBack();
                    return response()->json(['code' => 400, 'message' => '用户资产余额不足'], 400);
                }
                $afterAmount = bcsub($beforeAmount, $amountStr, 0);
            } else {
                $afterAmount = bcadd($beforeAmount, $amountStr, 0);
            }

            // 更新资产
            DB::table('users')->where('id', $userId)->update([$dbField => $afterAmount]);

            // 写入审计日志
            $tradeId = 'TX' . strtoupper(bin2hex(random_bytes(16)));
            DB::table('api_asset_logs')->insert([
                'trade_id'      => $tradeId,
                'out_trade_no'  => $outTradeNo,
                'app_id'        => $app->app_id,
                'user_id'       => $userId,
                'asset_type'    => $assetType,
                'action'        => $action,
                'amount'        => $amountStr,
                'before_amount' => $beforeAmount,
                'after_amount'  => $afterAmount,
                'remark'        => $remark,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            DB::commit();

            // 魔力变动写入 bonus_logs 日志表
            if ($assetType === 'bonus') {
                DB::table('bonus_logs')->insert([
                    'business_type' => 20000,
                    'uid' => $userId,
                    'old_total_value' => (float) $beforeAmount,
                    'value' => (float) ($action === 'increase' ? $amountStr : '-' . $amountStr),
                    'new_total_value' => (float) $afterAmount,
                    'comment' => sprintf('[第三方接入应用: %s]%s', $app->app_name, $remark ? ' ' . $remark : ''),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            return response()->json([
                'code' => 200,
                'data' => [
                    'trade_id'      => $tradeId,
                    'out_trade_no'  => $outTradeNo,
                    'asset_type'    => $assetType,
                    'action'        => $action,
                    'amount'        => $amountStr,
                    'before_amount' => $beforeAmount,
                    'after_amount'  => $afterAmount
                ]
            ]);

        } catch (QueryException $e) {
            DB::rollBack();
            // 唯一索引兜底处理不同用户锁之间的同订单并发。
            if (str_contains(strtolower($e->getMessage()), 'duplicate') || (string) $e->getCode() === '23000') {
                $existLog = DB::table('api_asset_logs')
                    ->where('app_id', $app->app_id)
                    ->where('out_trade_no', $outTradeNo)
                    ->first();
                if ($existLog) {
                    if ((int) $existLog->user_id !== $userId || $existLog->asset_type !== $assetType
                        || $existLog->action !== $action || (string) $existLog->amount !== $amountStr) {
                        return response()->json(['code' => 409, 'message' => '外部订单号已使用且交易参数不一致'], 409);
                    }
                    return response()->json([
                        'code' => 200,
                        'message' => '重复请求，返回历史交易结果',
                        'data' => [
                            'trade_id' => $existLog->trade_id,
                            'out_trade_no' => $existLog->out_trade_no,
                            'asset_type' => $existLog->asset_type,
                            'action' => $existLog->action,
                            'amount' => (string) $existLog->amount,
                            'before_amount' => (string) $existLog->before_amount,
                            'after_amount' => (string) $existLog->after_amount,
                        ],
                    ]);
                }
            }
            report($e);
            return response()->json(['code' => 500, 'message' => '交易失败，请稍后重试'], 500);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['code' => 500, 'message' => '交易失败，请稍后重试'], 500);
        } finally {
            Redis::eval(
                "if redis.call('GET', KEYS[1]) == ARGV[1] then return redis.call('DEL', KEYS[1]) else return 0 end",
                1,
                $lockKey,
                $lockToken
            );
        }
    }
}
