<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailAndUserAgentToIPSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('i_p_s', function (Blueprint $table) {
            $table->string('email', 255)->nullable()->after('IP_ADDR');
            $table->text('user_agent')->nullable()->after('email');

            // Опционально: добавить индексы для быстрого поиска
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('i_p_s', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn(['email', 'user_agent']);
        });
    }
}
