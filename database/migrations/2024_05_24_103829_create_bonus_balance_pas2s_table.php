<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBonusBalancePas2sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bonus_balance_pas2s', function (Blueprint $table) {
            $table->id();
            $table->integer('orderwebs_id')->nullable();
            $table->integer('users_id')->nullable();
            $table->integer('bonus_types_id')->nullable();
            $table->integer('bonusAdd')->nullable();
            $table->integer('bonusDel')->nullable();
            $table->integer('bonusBloke')->nullable();
            $table->integer('bonus')->nullable();
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
        Schema::dropIfExists('bonus_balance_pas2s');
    }
}
