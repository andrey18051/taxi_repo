<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForkRecreateArmedToUidHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uid_histories', function (Blueprint $table) {
            $table->boolean('bonus_recreate_armed')->nullable()->default(false);
            $table->boolean('double_recreate_armed')->nullable()->default(false);
            $table->boolean('bonus_dispatcher_canceled')->nullable()->default(false);
            $table->boolean('double_dispatcher_canceled')->nullable()->default(false);
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
            $table->dropColumn([
                'bonus_recreate_armed',
                'double_recreate_armed',
                'bonus_dispatcher_canceled',
                'double_dispatcher_canceled',
            ]);
        });
    }
}
