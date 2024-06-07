<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCachePayOnlyCollumnToAndroidSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('android_settings', function (Blueprint $table) {
            $table->after('pay_system', function ($table) {
                $table->string('cache_pay_PAS1')->nullable();
                $table->string('cache_pay_PAS2')->nullable();
                $table->string('cache_pay_PAS4')->nullable();
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
        Schema::table('android_settings', function (Blueprint $table) {
            $table->dropColumn('cache_pay_PAS1');
            $table->dropColumn('cache_pay_PAS2');
            $table->dropColumn('cache_pay_PAS4');
        });
    }
}
