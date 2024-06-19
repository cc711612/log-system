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
        Schema::table('users', function (Blueprint $table) {
            $table->string('elasticsearch_index', 255)->after('cf_token')->nullable()->comment('Elasticsearch Index');
            $table->string('elasticsearch_token', 255)->after('cf_token')->nullable()->comment('Elasticsearch Token');
            $table->string('elasticsearch_connection', 255)->after('cf_token')->nullable()->comment('Elasticsearch Connection');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('elasticsearch_connection');
            $table->dropColumn('elasticsearch_token');
            $table->dropColumn('elasticsearch_index');
        });
    }
};
