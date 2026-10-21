<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 第三方接入应用凭证与细粒度权限配置表
        Schema::create('api_apps', function (Blueprint $table) {
            $table->increments('id');
            $table->string('app_id', 64)->unique()->comment('第三方接入应用 App ID');
            $table->string('app_secret', 128)->comment('第三方接入应用 App Secret');
            $table->string('app_name', 100)->comment('接入应用名称');
            $table->tinyInteger('status')->default(1)->comment('状态: 1启用, 0禁用');
            $table->json('redirect_uris')->nullable()->comment('SSO 允许的回调域名白名单');
            $table->json('asset_rules')->nullable()->comment('资产与接口细粒度开关规则');
            $table->timestamps();

            $table->index('app_id');
        });

        // 2. 多资产交易流水审计表
        Schema::create('api_asset_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('trade_id', 64)->unique()->comment('全局唯一交易流水号');
            $table->string('out_trade_no', 64)->comment('接入应用外部唯一交易单号');
            $table->string('app_id', 64)->comment('调用的接入应用 App ID');
            $table->unsignedInteger('user_id')->comment('关联主站用户 ID');
            $table->enum('asset_type', ['upload', 'download', 'bonus', 'signin_card'])->comment('资产类型');
            $table->enum('action', ['deduct', 'increase'])->comment('交易行为: deduct扣减, increase增加');
            $table->unsignedBigInteger('amount')->default(0)->comment('交易数量/字节数');
            $table->unsignedBigInteger('before_amount')->default(0)->comment('交易前资产余额');
            $table->unsignedBigInteger('after_amount')->default(0)->comment('交易后资产余额');
            $table->string('remark', 255)->default('')->comment('交易备注说明');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['app_id', 'out_trade_no'], 'uk_app_out_trade');
            $table->index(['user_id', 'asset_type'], 'idx_user_asset');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_asset_logs');
        Schema::dropIfExists('api_apps');
    }
};
