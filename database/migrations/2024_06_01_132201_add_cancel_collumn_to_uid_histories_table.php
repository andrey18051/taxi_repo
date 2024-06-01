<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancelCollumnToUidHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uid_histories', function (Blueprint $table) {
            $table->after('uid_bonusOrderHold', function ($table) {
                $table->boolean('cancel')->nullable();
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
            $table->dropColumn('cancel');
        });
    }
}
