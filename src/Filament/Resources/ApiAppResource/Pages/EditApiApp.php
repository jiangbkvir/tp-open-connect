<?php

namespace NexusPlugin\TpOpenConnect\Filament\Resources\ApiAppResource\Pages;

use Filament\Resources\Pages\EditRecord;
use NexusPlugin\TpOpenConnect\Filament\Resources\ApiAppResource;

class EditApiApp extends EditRecord
{
    protected static string $resource = ApiAppResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
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
}
