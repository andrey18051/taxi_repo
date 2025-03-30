<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimestampToOrderwebsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orderwebs', function (Blueprint $table) {
            $table->after('city', function ($table) {
                $table->timestamp('cancel_timestamp')->nullable();
            });

        });
    }

    public function down()
    {
        Schema::table('orderwebs', function (Blueprint $table) {
            $table->dropColumn('cancel_timestamp');
        });
    }
}
