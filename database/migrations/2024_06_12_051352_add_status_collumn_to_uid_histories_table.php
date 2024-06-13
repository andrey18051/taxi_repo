<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusCollumnToUidHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uid_histories', function (Blueprint $table) {
            $table->after('uid_bonusOrder', function ($table) {
                $table->string('bonus_status')->nullable();
            });
            $table->after('uid_doubleOrder', function ($table) {
                $table->string('double_status')->nullable();
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
        Schema::table('uid_histories', function (Blueprint $table) {
            $table->dropColumn('bonus_status');
            $table->dropColumn('double_status');
        });
    }
}
