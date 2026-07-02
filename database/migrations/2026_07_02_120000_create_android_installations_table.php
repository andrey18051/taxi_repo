<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAndroidInstallationsTable extends Migration
{
    public function up()
    {
        Schema::create('android_installations', function (Blueprint $table) {
            $table->id();

            $table->string('installation_id', 128);
            $table->string('app', 16);

            $table->string('fcm_token')->nullable();
            $table->string('locale', 8)->nullable();
            $table->string('timezone', 64)->default('Europe/Kyiv');

            $table->timestamp('first_open_at')->nullable();

            // Запланированное напоминание "не вошел после установки" (UTC)
            $table->timestamp('reminder_due_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('reminder_cancelled_at')->nullable();
            $table->boolean('reminder_opt_out')->default(false);

            $table->timestamps();

            $table->unique(['installation_id', 'app'], 'android_installations_installation_app_unique');
            $table->index(['app', 'reminder_due_at'], 'android_installations_app_due_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('android_installations');
    }
}

