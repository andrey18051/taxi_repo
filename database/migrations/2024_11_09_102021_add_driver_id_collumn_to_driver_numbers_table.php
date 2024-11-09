<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDriverIdCollumnToDriverNumbersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('driver_numbers', function (Blueprint $table) {
            $table->after('id', function ($table) {
                $table->string('driver_id')->nullable();
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
        Schema::table('driver_numbers', function (Blueprint $table) {
            $table->dropColumn('driver_id');
        });
    }
}
