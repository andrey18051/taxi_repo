<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCityPAS4STable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('city_pas_4_s', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('merchant_fondy')->nullable();
            $table->string('fondy_key_storage')->nullable();
            $table->string('address')->nullable();
            $table->string('login')->nullable();
            $table->string('password')->nullable();
            $table->string('online')->nullable();
            $table->string('versionApi')->nullable();
            $table->string('card_max_pay')->nullable();
            $table->string('bonus_max_pay')->nullable();
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
        Schema::dropIfExists('city_pas_4_s');
    }
}
