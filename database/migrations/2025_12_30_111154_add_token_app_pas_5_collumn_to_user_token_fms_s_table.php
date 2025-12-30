<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTokenAppPas5CollumnToUserTokenFmsSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_token_fms_s', function (Blueprint $table) {
            $table->after('token_app_pas_4', function ($table) {
                $table->string('token_app_pas_5')->nullable();
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
        Schema::table('user_token_fms_s', function (Blueprint $table) {
            $table->dropColumn('token_app_pas_5');
        });
    }
}
