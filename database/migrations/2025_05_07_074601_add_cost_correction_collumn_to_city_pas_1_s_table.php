<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCostCorrectionCollumnToCityPas1STable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('city_pas_1_s', function (Blueprint $table) {
            $table->after('versionApi', function ($table) {
                $table->integer('cost_correction')->nullable()->default(1);
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
        Schema::table('city_pas_1_s', function (Blueprint $table) {
            $table->dropColumn('cost_correction');
        });
    }
}
