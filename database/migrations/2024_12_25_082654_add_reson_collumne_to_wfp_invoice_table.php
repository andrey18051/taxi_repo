<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResonCollumneToWfpInvoiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wfp_invoices', function (Blueprint $table) {
            $table->after('transactionStatus', function ($table) {
                $table->string('reason')->nullable();
                $table->string('reasonCode')->nullable();
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
        Schema::table('wfp_invoices', function (Blueprint $table) {
            $table->dropColumn('reason');
            $table->dropColumn('reasonCode');
        });
    }
}
