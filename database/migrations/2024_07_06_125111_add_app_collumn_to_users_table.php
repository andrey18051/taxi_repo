<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAppCollumnToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->after('email', function ($table) {
                $table->string('app_pas_1')->nullable();
                $table->string('app_pas_2')->nullable();
                $table->string('app_pas_4')->nullable();
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
            $table->dropColumn('app_pas_1');
            $table->dropColumn('app_pas_2');
            $table->dropColumn('app_pas_4');
        });
    }
}
