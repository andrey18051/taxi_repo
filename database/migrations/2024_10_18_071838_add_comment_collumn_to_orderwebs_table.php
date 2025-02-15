<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommentCollumnToOrderwebsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orderwebs', function (Blueprint $table) {
            $table->after('routetonumber', function ($table) {
                $table->string('rout_distance')->nullable();
                $table->string('comment_info')->nullable();
                $table->string('extra_charge_codes')->nullable();
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
            $table->dropColumn('comment_info');
            $table->dropColumn('extra_charge_codes');
            $table->string('rout_distance')->nullable();
        });
    }
}
