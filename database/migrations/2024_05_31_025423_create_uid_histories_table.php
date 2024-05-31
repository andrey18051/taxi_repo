<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUidHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('uid_histories', function (Blueprint $table) {
            $table->id();
            $table->string('uid_bonusOrder')->nullable();
            $table->string('uid_doubleOrder')->nullable();
            $table->string('uid_bonusOrderHold')->nullable();
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
        Schema::dropIfExists('uid_histories');
    }
}
