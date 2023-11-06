<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaySystemColumnToOrderwebsTable extends Migration
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
                $table->string('pay_system')->nullable();
            });
            $table->after('fondy_status_pay', function ($table) {
                $table->string('mono_order_id')->nullable();
                $table->string('mono_status_pay')->nullable();
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
            $table->dropColumn('pay_system');
            $table->dropColumn('mono_order_id');
            $table->dropColumn('mono_status_pay');
        });
    }
}
