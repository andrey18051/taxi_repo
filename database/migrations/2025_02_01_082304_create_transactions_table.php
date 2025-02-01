<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('merchantAccount');
            $table->string('order_reference');
            $table->string('transaction_type');
            $table->decimal('amount', 10, 2);
            $table->string('currency');
            $table->decimal('base_amount', 10, 2);
            $table->string('base_currency');
            $table->string('transaction_status');
            $table->timestamp('created_date');
            $table->timestamp('processing_date')->nullable();
            $table->string('reason_code');
            $table->string('reason');
            $table->timestamp('settlement_date')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('payment_system');
            $table->string('card_pan')->nullable();
            $table->string('card_type')->nullable();
            $table->string('issuer_bank_country')->nullable();
            $table->string('issuer_bank_name')->nullable();
            $table->decimal('fee', 10, 2);
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
        Schema::dropIfExists('transactions');
    }
}
