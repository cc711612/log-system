<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('execute_schedules', function (Blueprint $table) {
            $table->id('id')->comment('唯一辨識代號'); // 唯一辨識代號，主鍵，唯一且非空
            $table->unsignedBigInteger('user_id')->comment('User Index')->index(); // 外鍵 User Index，必須且索引
            $table->timestamp('log_time_start')->nullable()->comment('Log Time Start'); // 日誌時間開始，必須
            $table->timestamp('log_time_end')->nullable()->comment('Log Time End'); // 日誌時間結束，必須
            $table->string('type', 10)->comment('Type'); // 類型，必須
            $table->string('status', 10)->comment('Status'); // 狀態，必須
            $table->timestamp('process_time_start')->useCurrent()->comment('Process Time Start'); // 處理時間開始，必須，默認當前時間
            $table->timestamp('process_time_end')->useCurrent()->comment('Process Time End'); // 處理時間結束，必須，默認當前時間
            $table->timestamp('created_at')->useCurrent()->comment('Created At'); // 創建時間，必須，默認當前時間
            $table->timestamp('updated_at')->useCurrent()->comment('Updated At'); // 更新時間，必須，默認當前時間
            $table->softDeletes()->comment('Deleted At'); // 軟刪除時間
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('execute_schedules');
    }
};
