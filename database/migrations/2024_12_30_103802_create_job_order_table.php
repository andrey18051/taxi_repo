<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_order', function (Blueprint $table) {
            $table->id(); // Идентификатор записи
            $table->unsignedBigInteger('job_id'); // jobId
            $table->unsignedBigInteger('order_id'); // orderId
            $table->timestamps(); // Временные метки для отслеживания
            $table->unique(['job_id', 'order_id']); // Уникальность по комбинации jobId и orderId
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_order');
    }
}
