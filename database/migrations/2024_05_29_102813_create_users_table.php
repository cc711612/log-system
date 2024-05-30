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
        Schema::create('users', function (Blueprint $table) {
            $table->id('id')->comment('唯一辨識代號');
            $table->string('display_name', 50)->nullable()->comment('使用者');
            $table->string('product_type', 10)->nullable()->comment('CDN種類');
            $table->string('account', 50)->comment('登入帳號');
            $table->string('password')->comment('加密後密碼');
            $table->string('tswd_account', 50)->nullable()->comment('TSWD帳號');
            $table->string('tswd_token', 255)->nullable()->comment('TSWD金鑰');
            $table->string('cf_account', 50)->nullable()->comment('CF帳號');
            $table->string('cf_token', 255)->nullable()->comment('CF金鑰');
            $table->string('influx_db_connection', 255)->nullable()->comment('InfluxDB Connection');
            $table->string('influx_db_bucket', 255)->nullable()->comment('InfluxDB Bucket Name');
            $table->string('influx_db_token', 255)->nullable()->comment('InfluxDB Token');
            $table->string('influx_db_org', 255)->nullable()->comment('InfluxDB Org');
            $table->timestamps(); // Laravel auto adds created_at and updated_at columns
            $table->softDeletes()->comment('Deleted At');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
