<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_operation_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->enum('interface_type', ['external', 'internal']);
            $table->string('action', 64);
            $table->string('method', 10)->nullable();
            $table->string('path', 255)->nullable();
            $table->string('app_id', 64)->nullable();
            $table->unsignedInteger('operator_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('result', 255)->default('');
            $table->string('ip', 45)->nullable();
            $table->string('request_id', 128)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['interface_type', 'created_at']);
            $table->index(['app_id', 'created_at']);
            $table->index(['operator_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_operation_logs');
    }
};
