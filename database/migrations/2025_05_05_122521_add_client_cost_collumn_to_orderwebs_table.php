<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientCostCollumnToOrderwebsTable extends Migration
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
                $table->string('client_cost')->nullable();
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
            $table->dropColumn('client_cost');
        });
    }
}
