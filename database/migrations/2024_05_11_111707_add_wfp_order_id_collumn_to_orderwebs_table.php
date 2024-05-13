<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWfpOrderIdCollumnToOrderwebsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orderwebs', function (Blueprint $table) {
            $table->after('pay_system', function ($table) {
                $table->string('wfp_order_id')->nullable();
                $table->string('wfp_status_pay')->nullable();
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
            $table->dropColumn('wfp_order_id');
            $table->dropColumn('wfp_status_pay');
        });
    }
}
