<x-filament-panels::page>
    <div class="space-y-6">
        <!-- 说明 -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">
                📊 交易流水说明
            </h3>
            <div class="text-sm text-blue-700 dark:text-blue-300 space-y-2">
                <p>此页面显示所有第三方接入应用的资产交易记录，包括上传量、下载量、魔力值和签到卡的操作。</p>
                <p>每笔交易都有唯一的交易ID，可用于对账和追溯。</p>
            </div>
        </div>

        <!-- 交易流水列表 -->
        {{ $this->table }}
    </div>
</x-filament-panels::page>
