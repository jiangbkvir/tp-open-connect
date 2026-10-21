<?php

namespace NexusPlugin\TpOpenConnect\Services;

use Illuminate\Http\Request;
use NexusPlugin\TpOpenConnect\Models\ApiOperationLog;

class OperationLogService
{
    /**
     * 敏感字段列表，记录日志时会脱敏处理
     */
    private const SENSITIVE_FIELDS = ['password', 'secret', 'token', 'key', 'credential', 'auth'];
    
    /**
     * 记录外部接口失败日志
     */
    public function externalFailure(Request $request, int $statusCode, string $result, ?string $appId = null): void
    {
        $this->write($request, 'external', 'api.failure', $statusCode, $result, $appId);
    }

    /**
     * 记录管理员操作日志
     */
    public function adminChange(Request $request, string $action, string $result, ?string $appId = null): void
    {
        $this->write($request, 'internal', $action, 200, $result, $appId, auth('nexus-web')->id());
    }

    /**
     * 脱敏处理敏感信息
     */
    private function sanitizeResult(string $result): string
    {
        $sanitized = $result;
        
        // 对常见敏感模式进行脱敏
        // 1. App Secret 格式: sec_xxxxxxxx
        $sanitized = preg_replace('/sec_[a-zA-Z0-9]{20,}/', '***SECRET***', $sanitized);
        
        // 2. 可能包含的数据库错误详情（脱敏内部信息）
        $patterns = [
            '/SQLSTATE\[\w+\][^\s]*/i' => '[DB_ERROR]',
            '/Table [\'"`]?\w+[\'"`]? doesn\'t exist/i' => 'Table not found',
            '/Column not found[^,]*/i' => 'Column error',
            '/Duplicate entry [\'"`]?[^\'"`]+[\'"`]?/i' => 'Duplicate entry',
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $sanitized = preg_replace($pattern, $replacement, $sanitized);
        }
        
        // 3. 限制长度
        if (strlen($sanitized) > 250) {
            $sanitized = substr($sanitized, 0, 250) . '...';
        }
        
        return $sanitized;
    }

    private function write(Request $request, string $type, string $action, int $statusCode, string $result, ?string $appId, ?int $operatorId = null): void
    {
        try {
            // 脱敏处理结果
            $sanitizedResult = $this->sanitizeResult($result);
            
            // 获取请求路径，限制长度
            $path = (string) $request->getRequestUri();
            $path = substr($path, 0, 255);
            
            // 获取客户端 IP，脱敏处理（IPv6 可能包含敏感信息）
            $ip = $request->ip();
            if ($ip && strlen($ip) > 45) {
                $ip = substr($ip, 0, 45);
            }
            
            // 处理 request_id
            $requestId = $request->header('X-Request-ID');
            if ($requestId) {
                $requestId = substr((string) $requestId, 0, 128);
            }
            
            // 处理 user_id，防止过大数值
            $userId = $request->input('user_id');
            if ($userId !== null) {
                $userId = filter_var($userId, FILTER_VALIDATE_INT);
                if ($userId === false || $userId <= 0 || $userId > 2147483647) {
                    $userId = null;
                }
            }
            
            ApiOperationLog::query()->create([
                'interface_type' => $type,
                'action' => substr($action, 0, 64),
                'method' => substr($request->getMethod(), 0, 10),
                'path' => $path,
                'app_id' => $appId ? substr($appId, 0, 64) : null,
                'operator_id' => $operatorId,
                'user_id' => $userId,
                'status_code' => $statusCode,
                'result' => $sanitizedResult,
                'ip' => $ip,
                'request_id' => $requestId,
            ]);
        } catch (\Throwable $e) {
            // 静默失败，不影响主流程，但记录到系统日志
            report($e);
        }
    }
}
