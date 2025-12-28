<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCityPAS5STable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('city_pas_5_s', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('wfp_merchantAccount')->nullable();
            $table->string('wfp_merchantSecretKey')->nullable();
            $table->string('merchant_fondy')->nullable();
            $table->string('fondy_key_storage')->nullable();
            $table->string('address')->nullable();
            $table->string('login')->nullable();
            $table->string('password')->nullable();
            $table->string('online')->nullable();
            $table->string('versionApi')->nullable();
            $table->integer('cost_correction')->nullable()->default(1);
            $table->string('card_max_pay')->nullable();
            $table->string('bonus_max_pay')->nullable();
            $table->string('black_list')->nullable()->default('cards only');
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
        Schema::dropIfExists('city_pas_5_s');
    }
}
