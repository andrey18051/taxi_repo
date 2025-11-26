<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCityTariffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('city_tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('city', 191)->unique();
            $table->decimal('base_price', 8, 2);
            $table->integer('base_distance');
            $table->decimal('price_per_km', 8, 2);
            $table->boolean('is_test')->default(false);
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
        Schema::dropIfExists('city_tariffs');
    }
}
