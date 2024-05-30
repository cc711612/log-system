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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('delay_minutes')->comment('抓取log的延遲時間'); // 項目名稱，必須
            $table->unsignedSmallInteger('schedule_check_interval_minutes')->comment('執行排程列沒有執行紀錄的間隔上限時間'); // 項目名稱，必須
            $table->unsignedSmallInteger('task_completion_alert_threshold_minutes')->comment('執行排程執行任務的上限時間'); // 項目名稱，必須
            $table->unsignedSmallInteger('download_task_alert_threshold_minutes')->comment('下載排程執行任務的上限時間'); // 項目名稱，必須
            $table->unsignedSmallInteger('domain_list_chuck')->comment('DomainLists 的 切分數量'); // 項目名稱，必須
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
};
