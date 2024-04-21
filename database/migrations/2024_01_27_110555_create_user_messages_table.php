<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('user_messages', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('text_message')->nullable();
            $table->boolean('sent_message_info')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_messages');
    }
}
