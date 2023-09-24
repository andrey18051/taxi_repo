<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExecStatusHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exec_status_histories', function (Blueprint $table) {
            $table->id();
            $table->string('order')->nullable();
            $table->string('order_type')->nullable();
            $table->string('execution_status')->nullable();
            $table->string('cancel')->nullable();
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
        Schema::dropIfExists('exec_status_histories');
    }
}
