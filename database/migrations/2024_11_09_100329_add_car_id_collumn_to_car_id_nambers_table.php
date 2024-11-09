<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCarIdCollumnToCarIdNambersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_id_numbers', function (Blueprint $table) {
            $table->after('id', function ($table) {
                $table->string('car_id')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('car_id_nambers', function (Blueprint $table) {
            $table->dropColumn('car_id');
        });
    }
}
