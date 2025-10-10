<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaxiAiTestController extends Controller
{
    /**
     * Тестовый метод для вызова parseRequest из TaxiAiController
     */
    public function runTest()
    {
        $testRequests = [
            [
                "text" => "Замовити таксі з Києва, вул. Лесі Українки, 20 до Києва, вул. Вокзальна, 1 з кондиціонером",
                "lang" => "uk",
            ],
//            [
//                "text" => "Замовити таксі з Києва, вул. Січових Стрільців, 15 до Києва, вул. Хрещатик, 10 без водія",
//                "lang" => "uk",
//            ],
            [
                "text" => "Заказать такси из Киева, ул. Леси Украинки, 20 до Киева, ул. Вокзальная, 1 с кондиционером",
                "lang" => "ru",
            ],
//            [
//                "text" => "Заказать такси из Киева, ул. Сечевых Стрельцов, 15 до Киева, ул. Крещатик, 10 без водителя",
//                "lang" => "ru",
//            ],
            [
                "text" => "Order a taxi from Kyiv, Lesi Ukrainky St, 20 to Kyiv, Vokzalna St, 1 with air conditioning",
                "lang" => "en",
            ],
//            [
//                "text" => "Order a taxi from Kyiv, Sichovykh Striltsiv St, 15 to Kyiv, Khreshchatyk St, 10 without driver",
//                "lang" => "en",
//            ],
        ];


        $controller = new TaxiAiController(); // основной контроллер

        $results = [];

        foreach ($testRequests as $testData) {
            // Создаем "виртуальный" Request с тестовыми данными
            $request = Request::create('/fake', 'POST', $testData);

            // Вызываем основной метод
            $response = $controller->parseRequest($request);

            // Получаем JSON ответ
            $results[] = $response->getData(true);
        }

        return response()->json($results);
    }
}
