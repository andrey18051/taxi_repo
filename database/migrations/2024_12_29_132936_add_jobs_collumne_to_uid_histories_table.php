<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJobsCollumneToUidHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uid_histories', function (Blueprint $table) {
            $table->after('cancel', function ($table) {
                $table->string('jobId')->nullable();
                $table->string('orderId')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.reason
     *
     * @return void
     */
    public function down()
    {
        Schema::table('uid_histories', function (Blueprint $table) {
            $table->dropColumn('jobId');
            $table->dropColumn('orderId');
        });
    }
}
