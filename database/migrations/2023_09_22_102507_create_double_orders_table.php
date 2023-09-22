<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoubleOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('double_orders', function (Blueprint $table) {
            $table->id();
            $table->string('responseBonusStr')->nullable();
            $table->string('responseDoubleStr')->nullable();
            $table->string('authorizationBonus')->nullable();
            $table->string('authorizationDouble')->nullable();
            $table->string('connectAPI')->nullable();
            $table->string('identificationId')->nullable();
            $table->string('apiVersion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('double_orders');
    }
}
