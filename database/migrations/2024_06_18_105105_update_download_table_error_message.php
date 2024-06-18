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
        Schema::table('downloads', function (Blueprint $table) {
            $table->text('error_message')->nullable()->comment('錯誤訊息')->after('pid'); // 執行 ID，必須
        });
        Schema::table('execute_schedules', function (Blueprint $table) {
            $table->text('error_message')->nullable()->comment('錯誤訊息')->after('status'); // 執行 ID，必須
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('downloads', function (Blueprint $table) {
            $table->dropColumn('error_message');
        });
        Schema::table('execute_schedules', function (Blueprint $table) {
            $table->dropColumn('error_message');
        });
    }
};
