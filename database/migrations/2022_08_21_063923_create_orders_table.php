<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('IP_ADDR')->nullable();//IP пользователя
            $table->string('user_full_name');//Полное имя пользователя
            $table->string('user_phone');//Телефон пользователя
            $table->string('client_sub_card')->nullable();
            $table->dateTime('required_time')->nullable(); //Время подачи предварительного заказа
            $table->boolean('reservation')->default(false); //Обязательный. Признак предварительного заказа: True, False
            $table->string('route_address_entrance_from')->nullable();
            $table->string('comment')->nullable();  //Комментарий к заказу
            $table->float('add_cost')->default('0'); //Добавленная стоимость
            $table->boolean('wagon')->default(false); //Универсал: True, False
            $table->boolean('minibus')->default(false); //Микроавтобус: True, False
            $table->boolean('premium')->default(false); //Машина премиум-класса: True, False
            $table->string('flexible_tariff_name')->nullable(); //Гибкий тариф
            $table->boolean('route_undefined')->default(false); //По городу: True, False
            $table->string('routefrom'); //Обязательный. Улица откуда.
            $table->string('routefromnumber')->nullable(); //Обязательный. Дом откуда.
            $table->string('routeto'); //Обязательный. Улица куда.
            $table->string('routetonumber')->nullable(); //Обязательный. Дом куда.
            $table->set('taxiColumnId', ['0','1','2'])->default('0'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            $table->set('payment_type', ['0','1'])->default('0'); //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
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
        Schema::dropIfExists('orders');
    }
}
