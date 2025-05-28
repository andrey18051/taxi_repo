<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
//    public function up()
//    {
//        $connection = config('audit.drivers.database.connection', config('database.default'));
//        $table = config('audit.drivers.database.table', 'audits');
//
//        Schema::connection($connection)->create($table, function (Blueprint $table) {
//
//            $morphPrefix = config('audit.user.morph_prefix', 'user');
//
//            $table->bigIncrements('id');
//            $table->string($morphPrefix . '_type')->nullable();
//            $table->unsignedBigInteger($morphPrefix . '_id')->nullable();
//            $table->string('event');
////            $table->morphs('auditable');
//
//            $table->string('auditable_type', 191);
//            $table->unsignedBigInteger('auditable_id');
//            $table->index(['auditable_type', 'auditable_id']);
//
//            $table->text('old_values')->nullable();
//            $table->text('new_values')->nullable();
//            $table->text('url')->nullable();
//            $table->ipAddress('ip_address')->nullable();
//            $table->string('user_agent', 1023)->nullable();
//            $table->string('tags')->nullable();
//            $table->timestamps();
//
//            $table->index([$morphPrefix . '_id', $morphPrefix . '_type']);
//        });
//    }
    public function up()
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        Schema::connection($connection)->create($table, function (Blueprint $table) {

            $morphPrefix = config('audit.user.morph_prefix', 'user');

            $table->bigIncrements('id');

            // User (morph) columns
            $table->string($morphPrefix . '_type', 191)->nullable();
            $table->unsignedBigInteger($morphPrefix . '_id')->nullable();
            $table->index([$morphPrefix . '_id', $morphPrefix . '_type'], 'audits_user_id_user_type_index');

            // Event info
            $table->string('event');

            // Auditable (morph) columns
            $table->string('auditable_type', 191);
            $table->unsignedBigInteger('auditable_id');
            $table->index(['auditable_type', 'auditable_id'], 'audits_auditable_type_auditable_id_index');

            // Optional columns
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();

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
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        Schema::connection($connection)->drop($table);
    }
}
