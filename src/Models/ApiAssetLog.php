<?php

namespace NexusPlugin\TpOpenConnect\Models;

use Illuminate\Database\Eloquent\Model;

class ApiAssetLog extends Model
{
    protected $connection = 'mysql';
    protected $table = 'api_asset_logs';

    public $timestamps = false;

    protected $fillable = [
        'trade_id',
        'out_trade_no',
        'app_id',
        'user_id',
        'asset_type',
        'action',
        'amount',
        'before_amount',
        'after_amount',
        'remark',
        'created_at',
    ];

    /**
     * 获取关联的应用
     */
    public function app()
    {
        return $this->belongsTo(ApiApp::class, 'app_id', 'app_id');
    }

    /**
     * 格式化金额显示
     */
    public function getFormattedAmount(): string
    {
        return number_format((int) $this->amount);
    }

    /**
     * 格式化变动前金额显示
     */
    public function getFormattedBeforeAmount(): string
    {
        return number_format((int) $this->before_amount);
    }

    /**
     * 格式化变动后金额显示
     */
    public function getFormattedAfterAmount(): string
    {
        return number_format((int) $this->after_amount);
    }

    /**
     * 获取资产类型的中文名称
     */
    public function getAssetTypeName(): string
    {
        $names = [
            'upload' => '上传量',
            'download' => '下载量',
            'bonus' => '魔力值',
            'signin_card' => '签到卡',
        ];

        return $names[$this->asset_type] ?? $this->asset_type;
    }

    /**
     * 获取操作类型的中文名称
     */
    public function getActionName(): string
    {
        return $this->action === 'deduct' ? '扣减' : '增加';
    }

    /**
     * 检查是否为扣减操作
     */
    public function isDeduct(): bool
    {
        return $this->action === 'deduct';
    }

    /**
     * 检查是否为增加操作
     */
    public function isIncrease(): bool
    {
        return $this->action === 'increase';
    }
}