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
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->string('car_view')->nullable()->default('gird/list');
            $table->string('google_map_api_key')->nullable();
            $table->string('google_map_api_key_status')->nullable()->default('0');
            $table->integer('radius')->nullable()->default(1000);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->dropColumn([
                'car_view',
                'google_map_api_key',
                'google_map_api_key_status',
                'radius',
            ]);
        });
    }
};
