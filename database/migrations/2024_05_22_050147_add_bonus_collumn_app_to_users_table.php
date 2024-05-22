<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBonusCollumnAppToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->after('bonus', function ($table) {
                $table->integer('bonus_pas_1')->nullable();
                $table->integer('bonus_pas_2')->nullable();
                $table->integer('bonus_pas_4')->nullable();
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bonus_pas_1');
            $table->dropColumn('bonus_pas_2');
            $table->dropColumn('bonus_pas_4');
        });
    }
}
