<?php

namespace App\Console\Commands;

use App\Http\Controllers\CleanerTableController;
use App\Http\Controllers\OpenStreetMapController;
use Illuminate\Console\Command;

class CleanTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean-task:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистка таблиц БД';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //Очистка таблиці номеров отказных поездок
        (new CleanerTableController())->cleanOrderRefusalTable();

        //Очистка таблиці истории безнальных заказов
        (new CleanerTableController())->cleanUidHistoriesTable();

        //Проверка версии Visicom
        (new OpenStreetMapController)->checkVisicomRequest();

        return 0;
    }
}
