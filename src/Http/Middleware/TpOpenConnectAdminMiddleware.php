<?php

namespace NexusPlugin\TpOpenConnect\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class TpOpenConnectAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['code' => 401, 'message' => '请先登录'], 401);
        }

        if ((int) $user->class < (int) User::CLASS_SYSOP) {
            return response()->json(['code' => 403, 'message' => '仅维护开发员及以上等级可访问'], 403);
        }

        return $next($request);
    }
}
