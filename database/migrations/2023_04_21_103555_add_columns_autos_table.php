<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsAutosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('autos', function ($table) {
            $table->string('model');
            $table->string('type');
            $table->string('color');
            $table->string('year');
            $table->string('number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('model');
            $table->dropColumn('type');
            $table->dropColumn('color');
            $table->dropColumn('year');
            $table->dropColumn('number');
        });
    }
}
