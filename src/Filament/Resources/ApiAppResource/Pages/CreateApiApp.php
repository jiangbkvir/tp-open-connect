<?php

namespace NexusPlugin\TpOpenConnect\Filament\Resources\ApiAppResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use NexusPlugin\TpOpenConnect\Filament\Resources\ApiAppResource;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

class CreateApiApp extends CreateRecord
{
    protected static string $resource = ApiAppResource::class;

    /**
     * 创建成功后存储 Secret 用于显示
     */
    public ?string $newAppSecret = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['app_id'] = 'app_' . Str::random(12);
        $data['app_secret'] = 'sec_' . Str::random(32);

        // 临时存储 Secret，用于创建成功后显示
        $this->newAppSecret = $data['app_secret'];

        // 构建资产权限规则
        $assetTypes = ['upload', 'download', 'bonus', 'signin_card'];
        $assetRules = [];

        foreach ($assetTypes as $type) {
            $assetRules[$type] = [
                'query' => !empty($data[$type . '_query']),
                'deduct' => !empty($data[$type . '_deduct']),
                'increase' => !empty($data[$type . '_increase']),
            ];
            unset($data[$type . '_query'], $data[$type . '_deduct'], $data[$type . '_increase']);
        }

        // ApiApp 模型已声明 array cast，交给 Eloquent 统一序列化，避免二次 JSON 编码。
        $data['redirect_uris'] = array_values($data['redirect_uris'] ?? []);
        $data['asset_rules'] = [
            'sso_verify' => $data['sso_verify'] ?? false,
            'asset_rules' => $assetRules,
        ];

        return $data;
    }

    /**
     * 创建成功后显示 Secret 弹窗
     */
    protected function afterCreate(): void
    {
        if ($this->newAppSecret) {
            // 挂载显示 Secret 的 Action
            $this->mountAction('create_result');
        }
    }

    /**
     * 创建成功后显示 Secret（只显示一次）
     */
    protected function createResultAction(): Action
    {
        $record = $this->record;

        return Action::make('create_result')
            ->modalHeading('接入应用创建成功')
            ->modalDescription('请立即复制并妥善保存以下信息。关闭此窗口后将无法再次查看 App Secret。')
            ->modalContent(fn (): HtmlString => new HtmlString(
                '<div class="space-y-4">'
                . '<div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">'
                . '<div class="mb-3">'
                . '<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">App ID</label>'
                . '<div class="flex gap-2">'
                . '<input id="tp-app-id" type="text" readonly value="' . e($record?->app_id ?? '') . '" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono">'
                . '<button type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'tp-app-id\').value); this.innerText=\'已复制\'; setTimeout(() => this.innerText=\'复制\', 2000);" class="fi-btn fi-btn-color-gray rounded-lg px-3 py-2 text-sm">复制</button>'
                . '</div>'
                . '</div>'
                . '<div>'
                . '<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">App Secret <span class="text-red-500">*</span></label>'
                . '<div class="flex gap-2">'
                . '<input id="tp-app-secret" type="text" readonly value="' . e($this->newAppSecret ?? '') . '" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono bg-yellow-50 dark:bg-yellow-900/20">'
                . '<button type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'tp-app-secret\').value); this.innerText=\'已复制\'; setTimeout(() => this.innerText=\'复制\', 2000);" class="fi-btn fi-btn-color-primary rounded-lg px-3 py-2 text-sm">复制</button>'
                . '</div>'
                . '<p class="text-xs text-red-500 mt-1">⚠️ Secret 只显示这一次，请务必复制保存！</p>'
                . '</div>'
                . '</div>'
                . '</div>'
            ))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('我已保存')
            ->after(function (): void {
                $this->newAppSecret = null;
            });
    }
}
