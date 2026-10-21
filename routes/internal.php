<?php

use Illuminate\Support\Facades\Route;
use NexusPlugin\TpOpenConnect\Http\Controllers\Admin\AdminAppController;
use NexusPlugin\TpOpenConnect\Http\Controllers\MainSite\SsoController;
use App\Models\User;

// 站内接口：使用主站登录态
// SSO Key 生成：传入 app_id，返回 sso_key
Route::post('/tp-open-connect/sso/generate', [SsoController::class, 'generateSsoKey'])
    ->middleware(['api', 'auth:nexus-web']);

// 管理后台 API：需要管理员权限
Route::prefix('admin/api/tp-open-connect')
    ->middleware(['web', 'auth.nexus:nexus-web', 'tpoc.admin'])
    ->group(function (): void {
        // apps 接口已移除
        Route::post('/apps/save', [AdminAppController::class, 'saveApp']);
        Route::post('/apps/delete', [AdminAppController::class, 'deleteApp']);
        Route::get('/trade-logs', [AdminAppController::class, 'tradeLogs']);
    });

// 对接方案页面：仅管理员可见
Route::get('/nexusphp/tp-open-connect/integration-guide', function () {
    if (!auth('nexus-web')->check()) {
        return redirect('/login')->with('error', '请先登录');
    }
    
    $user = auth('nexus-web')->user();
    if ((int) $user->class < (int) User::CLASS_SYSOP) {
        abort(403, '仅维护开发员及以上等级可访问');
    }
    
    return view('tp-open-connect::filament.pages.integration-guide');
})->middleware('web');
