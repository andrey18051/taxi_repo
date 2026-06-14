<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPaymentFlowToCityTables extends Migration
{
    private const TABLES = [
        'cities',
        'city_pas_1_s',
        'city_pas_2_s',
        'city_pas_4_s',
        'city_pas_5_s',
    ];

    /**
     * @return void
     */
    public function up()
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedTinyInteger('payment_flow')->default(0);
            });
        }

        foreach (self::TABLES as $tableName) {
            $rows = DB::table($tableName)->get(['id', 'card_max_pay']);
            foreach ($rows as $row) {
                if ((int) $row->card_max_pay > 0) {
                    DB::table($tableName)->where('id', $row->id)->update(['payment_flow' => 1]);
                }
            }
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('payment_flow');
            });
        }
    }
}
