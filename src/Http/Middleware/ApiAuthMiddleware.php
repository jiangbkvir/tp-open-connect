<?php

namespace NexusPlugin\TpOpenConnect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use NexusPlugin\TpOpenConnect\Services\HmacService;
use NexusPlugin\TpOpenConnect\Services\OperationLogService;

class ApiAuthMiddleware
{
    public function __construct(private readonly HmacService $hmac, private readonly OperationLogService $logs)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $app = $this->hmac->authenticate($request);
        if ($app instanceof Response) {
            $this->logs->externalFailure($request, $app->getStatusCode(), (string) ($app->getData(true)['message'] ?? '认证失败'), $request->header('X-App-ID'));
            return $app;
        }
        $request->attributes->set('current_app', $app);
        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $this->logs->externalFailure($request, 500, '服务器内部错误', $app->app_id);
            throw $e;
        }
        if ($response->getStatusCode() >= 400) {
            $this->logs->externalFailure($request, $response->getStatusCode(), (string) ($response->getData(true)['message'] ?? '请求失败'), $app->app_id);
        }
        return $response;
    }
}
