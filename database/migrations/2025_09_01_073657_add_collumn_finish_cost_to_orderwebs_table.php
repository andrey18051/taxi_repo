<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCollumnFinishCostToOrderwebsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orderwebs', function (Blueprint $table) {
            $table->after('client_cost', function ($table) {
                $table->integer('finish_cost')->nullable();
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
        Schema::table('orderwebs', function (Blueprint $table) {
            $table->dropColumn('finish_cost');
        });
    }
}
