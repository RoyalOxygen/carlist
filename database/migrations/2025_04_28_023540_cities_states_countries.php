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
        Schema::create('countries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('states', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('countries');
        Schema::dropIfExists('states');
        Schema::dropIfExists('cities');
    }
};
