<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE api_apps MODIFY app_id VARCHAR(64) NOT NULL COMMENT '第三方接入应用 App ID'");
        DB::statement("ALTER TABLE api_apps MODIFY app_secret VARCHAR(128) NOT NULL COMMENT '第三方接入应用 App Secret'");
        DB::statement("ALTER TABLE api_apps MODIFY app_name VARCHAR(100) NOT NULL COMMENT '接入应用名称'");
        DB::statement("ALTER TABLE api_asset_logs MODIFY out_trade_no VARCHAR(64) NOT NULL COMMENT '接入应用外部唯一交易单号'");
        DB::statement("ALTER TABLE api_asset_logs MODIFY app_id VARCHAR(64) NOT NULL COMMENT '调用的接入应用 App ID'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE api_apps MODIFY app_id VARCHAR(64) NOT NULL COMMENT '应用 App ID'");
        DB::statement("ALTER TABLE api_apps MODIFY app_secret VARCHAR(128) NOT NULL COMMENT '应用 App Secret'");
        DB::statement("ALTER TABLE api_apps MODIFY app_name VARCHAR(100) NOT NULL COMMENT '应用名称'");
        DB::statement("ALTER TABLE api_asset_logs MODIFY out_trade_no VARCHAR(64) NOT NULL COMMENT '应用外部唯一交易单号'");
        DB::statement("ALTER TABLE api_asset_logs MODIFY app_id VARCHAR(64) NOT NULL COMMENT '调用的应用 App ID'");
    }
};
