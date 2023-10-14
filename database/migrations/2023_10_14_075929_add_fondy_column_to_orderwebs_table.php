<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFondyColumnToOrderwebsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orderwebs', function (Blueprint $table) {
            $table->after('web_cost', function ($table) {
                $table->string('fondy_order_id')->nullable();
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
            $table->dropColumn('fondy_order_id');
        });
    }
}
