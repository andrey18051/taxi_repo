<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriverMemoryOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_memory_orders', function (Blueprint $table) {
            $table->id();
            $table->text('response')->nullable();
            $table->text('authorization')->nullable();
            $table->string('connectAPI')->nullable();
            $table->string('identificationId')->nullable();
            $table->string('apiVersion')->nullable();
            $table->string('dispatching_order_uid')->nullable();
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
        Schema::dropIfExists('driver_memory_orders');
    }
}
