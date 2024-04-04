<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnerEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partner_emails', function (Blueprint $table) {
            $table->id();
            $table->integer('partner_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('text_message')->nullable();
            $table->boolean('sent_message_info')->nullable();
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
        Schema::dropIfExists('partner_emails');
    }
}
