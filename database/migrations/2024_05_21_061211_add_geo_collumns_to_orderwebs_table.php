<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGeoCollumnsToOrderwebsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orderwebs', function (Blueprint $table) {
            $table->after('routefromnumber', function ($table) {
                $table->string('startLat')->nullable();
                $table->string('startLan')->nullable();
            });
            $table->after('routetonumber', function ($table) {
                $table->string('to_lat')->nullable();
                $table->string('to_lng')->nullable();
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
            $table->dropColumn('startLat');
            $table->dropColumn('startLan');
            $table->dropColumn('to_lat');
            $table->dropColumn('to_lng');
        });
    }
}
