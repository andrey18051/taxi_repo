<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBlackListColumnsInUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Переименование существующего столбца
            $table->renameColumn('black_list', 'black_list_PAS1');
        });

        Schema::table('users', function (Blueprint $table) {
            // Добавление новых столбцов после black_list_PAS1
            $table->string('black_list_PAS2', 255)->nullable()->after('black_list_PAS1');
            $table->string('black_list_PAS4', 255)->nullable()->after('black_list_PAS2');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Удаление новых столбцов
            $table->dropColumn(['black_list_PAS2', 'black_list_PAS4']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Переименование обратно
            $table->renameColumn('black_list_PAS1', 'black_list');
        });
    }
}
