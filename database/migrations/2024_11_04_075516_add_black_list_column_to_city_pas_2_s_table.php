<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBlackListColumnToCityPas2STable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('city_pas_2_s', function (Blueprint $table) {
            $table->after('bonus_max_pay', function ($table) {
                $table->string('black_list')->nullable()->default('cards only');
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
        Schema::table('city_pas_2_s', function (Blueprint $table) {
            $table->dropColumn('black_list');
        });
    }
}
