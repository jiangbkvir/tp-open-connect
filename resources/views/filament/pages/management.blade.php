<x-filament-panels::page>
    <div class="space-y-6">
        <!-- 统计概览 -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ \NexusPlugin\TpOpenConnect\Models\ApiApp::query()->count() }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">第三方接入应用总数</div>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl">
                            📱
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ \NexusPlugin\TpOpenConnect\Models\ApiApp::query()->where('status', 1)->count() }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">启用中</div>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl">
                            ✅
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format(\NexusPlugin\TpOpenConnect\Models\ApiAssetLog::query()->count()) }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">交易总笔数</div>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl">
                            💰
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 使用说明 -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 mt-6">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">
                📖 使用说明
            </h3>
            <div class="text-sm text-blue-700 dark:text-blue-300 space-y-2">
                <p><strong>1. 创建接入应用：</strong>填写表单后点击“创建接入应用”按钮</p>
                <p><strong>2. 获取凭证：</strong>创建成功后，App ID 和 App Secret 会自动生成</p>
                <p><strong>3. 配置权限：</strong>在表单中勾选接入应用需要的资产操作权限</p>
                <p><strong>4. 设置域名：</strong>添加接入应用的回调域名白名单</p>
                <p><strong>5. 集成接口：</strong>查看 <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">INTEGRATION.md</code> 了解如何调用 API</p>
            </div>
        </div>

        <!-- 快速链接 -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                🔗 快速链接
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <a href="/plugins/tp-open-connect/index.php" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    → API 信息页面
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>
