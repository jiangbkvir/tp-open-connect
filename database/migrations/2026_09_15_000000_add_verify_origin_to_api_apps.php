<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('api_apps', function (Blueprint $table) {
            // 是否校验请求来源，默认关闭（0），方便开发环境测试
            $table->boolean('verify_origin')
                ->default(0)
                ->after('asset_rules')
                ->comment('是否校验请求来源: 1启用, 0禁用(默认,方便开发测试)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_apps', function (Blueprint $table) {
            $table->dropColumn('verify_origin');
        });
    }
};
