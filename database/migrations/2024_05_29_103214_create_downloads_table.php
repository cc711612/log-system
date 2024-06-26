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
        Schema::create('downloads', function (Blueprint $table) {
            $table->id('id')->comment('唯一辨識代號'); // 唯一辨識代號，主鍵，唯一且非空
            $table->unsignedBigInteger('user_id')->comment('User Index')->index(); // 外鍵 User Index，必須且索引
            $table->unsignedBigInteger('execute_schedule_id')->comment('execute_schedules index')->index();
            $table->text('url')->comment('Download URL'); // 下載 URL，必須
            $table->string('domain_name')->nullable()->comment('Domain'); // 域名
            $table->string('service_type')->nullable()->comment('Service Type'); // 服務類型
            $table->text('control_group_name')->nullable()->comment('GC Name'); // GC 名稱
            $table->text('control_group_code')->nullable()->comment('GC ID'); // GC 代號
            $table->timestamp('log_time_start')->nullable()->comment('Log Time Start'); // 日誌時間開始，必須
            $table->timestamp('log_time_end')->nullable()->comment('Log Time End'); // 日誌時間結束，必須
            $table->string('type', 10)->comment('Type'); // 類型，必須
            $table->string('status', 11)->comment('Status'); // 狀態，必須
            $table->timestamp('created_at')->useCurrent()->comment('Created At'); // 創建時間，默認當前時間
            $table->timestamp('updated_at')->useCurrent()->comment('Updated At'); // 更新時間，默認當前時間
            $table->softDeletes()->comment('Deleted At'); // 軟刪除時間

            // 建立索引鍵
            $table->index(['user_id', 'domain_name','log_time_start','log_time_end'])->comment('驗證碼索引');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('downloads');
    }
};
