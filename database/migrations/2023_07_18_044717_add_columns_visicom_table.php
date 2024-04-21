<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsVisicomTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('visicoms', function ($table) {
            $table->after('id', function ($table) {
                $table->string('street_type')->nullable();
                $table->string('street')->nullable();
            });
            $table->after('name', function ($table) {
                $table->string('settlement_type')->nullable();
                $table->string('settlement')->nullable();
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
        Schema::table('services', function (Blueprint $table) {
            $table->string('street_type');
            $table->string('street');
            $table->string('settlement_type');
            $table->string('settlement');
        });
    }
}
