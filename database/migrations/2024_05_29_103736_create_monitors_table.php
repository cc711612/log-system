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
        Schema::create('monitors', function (Blueprint $table) {
            $table->id()->comment('ID'); // 唯一辨識代號，主鍵，唯一且非空
            $table->string('item_name', 100)->comment('Item Name'); // 項目名稱，必須
            $table->enum('status', ['Normal', 'Alert', 'Error'])->default('Normal')->comment('Status'); // 狀態，必須，預設為 Normal
            $table->timestamp('last_check_timestamp')->useCurrent()->comment('Last Check Time'); // 最後檢查時間，必須，默認當前時間
            $table->unsignedBigInteger('last_setting_id')->comment('Last Setting ID')->index(); // 外鍵 Last Setting ID，必須且索引
            $table->integer('schedule_check_interval_minutes')->comment('Schedule Check Interval Minutes'); // 定時檢查間隔分鐘數，必須
            $table->integer('task_completion_alert_threshold_minutes')->comment('Task Completion Alert Threshold Minutes'); // 任務完成警報閾值分鐘數，必須
            $table->integer('download_task_alert_threshold_minutes')->comment('Download Task Alert Threshold Minutes'); // 下載任務警報閾值分鐘數，必須
            $table->string('email_alert_address', 255)->nullable()->comment('Email Alert Address'); // 郵件警報地址，可空
            $table->string('webhook_url', 255)->nullable()->comment('Webhook URL'); // Webhook URL，可空
            $table->string('influx_db_connection', 255)->nullable()->comment('InfluxDB Connection'); // InfluxDB Connection，可空
            $table->timestamps(); // created_at 和 updated_at 列
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
        Schema::dropIfExists('monitors');
    }
};
