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
    Schema::create('app_sliders', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('language_id');
      $table->string('background_image')->nullable();
      $table->string('color')->nullable();
      $table->string('title')->nullable();
      $table->string('type')->nullable();
      $table->string('previous_price')->nullable();
      $table->string('current_price')->nullable();
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
    Schema::dropIfExists('app_sliders');
  }
};
