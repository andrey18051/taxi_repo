<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWfpCollumnToCityPas4STable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('city_pas_4_s', function (Blueprint $table) {
            $table->after('name', function ($table) {
                $table->string('wfp_merchantAccount')->nullable();
                $table->string('wfp_merchantSecretKey')->nullable();
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
        Schema::table('city_pas_4_s', function (Blueprint $table) {
            Schema::table('city_pas_1_s', function (Blueprint $table) {
                $table->dropColumn('wfp_merchantAccount');
                $table->dropColumn('wfp_merchantSecretKey');
            });
        });
    }
}
