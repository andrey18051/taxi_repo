<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLocalAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_local_apps', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->string('local_app_pas_1')->nullable();
            $table->string('local_app_pas_2')->nullable();
            $table->string('local_app_pas_4')->nullable();
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
        Schema::dropIfExists('user_local_apps');
    }
}
