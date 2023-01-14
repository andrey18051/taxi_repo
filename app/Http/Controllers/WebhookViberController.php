<?php

namespace App\Http\Controllers;

use App\Helpers\Viber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookViberController extends Controller
{
    public function index(Request $request, Viber $viber)
    {

        // Log::debug($request->all());

        /**
         * Кнопки и ответы
         */
        $user_id = $request->input('user')['id'];
        $message = 'Привіт Andrii 18051 👋! Я віртуальний помічник служби Таксі Лайт Юа 🚕! Я розумію поки що трохи слів (наприклад - трансфер, зустрич, робота, послуги), але я дуже швидко вчуся 😺"';
        $keyboard_main = [
            "Type" => "keyboard",
            "DefaultHeight" => false,
            "Buttons" => [
                [
                    "Columns" => 3,
                    "Rows" => 1,
                    "Text" => "<b>Трансфер 🏠</b>",
                    "TextSize" => "large",
                    "TextHAlign" => "center",
                    "TextVAlign" => "middle",
                    "ActionType" => "reply",
                    "ActionBody" => "Трансфер",
                ],
                [
                    "Columns" => 3,
                    "Rows" => 1,
                    "Text" => "<b>Зустрич ✈</b>",
                    "TextSize" => "large",
                    "TextHAlign" => "center",
                    "TextVAlign" => "middle",
                    "ActionType" => "reply",
                    "ActionBody" => "Зустрич",
                ],
                [
                    "Columns" => 3,
                    "Rows" => 1,
                    "Text" => "<b>Послуги 🚕</b>",
                    "TextSize" => "large",
                    "TextHAlign" => "center",
                    "TextVAlign" => "middle",
                    "ActionType" => "reply",
                    "ActionBody" => "Послуги",
                ],
                [
                    "Columns" => 3,
                    "Rows" => 1,
                    "Text" => "<b>Робота в 🚕</b>",
                    "TextSize" => "large",
                    "TextHAlign" => "center",
                    "TextVAlign" => "middle",
                    "ActionType" => "open-url",
                    "ActionBody" => "https://m.easy-order-taxi.site/callWorkForm",
                ],
            ],
        ];
        $viber->sendKeyboard($user_id, $message, $keyboard_main);


        $user_id = $request->input('sender')['id'];
        $data = mb_strtolower($request->input('message')['text']);

        $borispol = asset('img/borispolViber.png');
        $sikorskogo = asset('img/sikorskogoViber.png');
        $UzViber = asset('img/UzViber.png');
        $autoViber = asset('img/autoViber.jpeg');

        switch ($data) {
            case "послуги":
                $keyboard = [
                    "Type" => "keyboard",
                    "DefaultHeight" => false,
                    "Buttons" => [
                        [
                            "Columns" => 6,
                            "Rows" => 1,
                            "Text" => "Замовити таксі за адресою",
                            "TextSize" => "large",
                            "TextHAlign" => "center",
                            "TextVAlign" => "middle",
                            "ActionType" => "open-url",
                            "ActionBody" => "https://m.easy-order-taxi.site/home-Combo",
                        ],
                        [
                            "Columns" => 6,
                            "Rows" => 1,
                            "Text" => "Замовити таксі по мапі",
                            "TextSize" => "large",
                            "TextHAlign" => "center",
                            "TextVAlign" => "middle",
                            "ActionType" => "open-url",
                            "ActionBody" => "https://m.easy-order-taxi.site/home-Map-Combo",
                        ],
                        [
                            'Columns' => 6,
                            'Rows' => 1,
                            'Text' => "Надіслати повідомлення адміністратору",
                            "TextSize" => "large",
                            "TextHAlign" => "center",
                            "TextVAlign" => "middle",
                            "ActionType" => "open-url",
                            "ActionBody" => "https://m.easy-order-taxi.site/feedback",
                        ],
                        [
                            "Columns" => 6,
                            "Rows" => 1,
                            "Text" => "Усі послуги",
                            "TextSize" => "large",
                            "TextHAlign" => "center",
                            "TextVAlign" => "middle",
                            "ActionType" => "open-url",
                            "ActionBody" => "https://m.easy-order-taxi.site",
                        ],
                        [
                            "Columns" => 6,
                            "Rows" => 1,
                            "Text" => "Екстренна допомога",
                            "TextSize" => "large",
                            "TextHAlign" => "center",
                            "TextVAlign" => "middle",
                            "ActionType" => "open-url",
                            "ActionBody" => "https://m.easy-order-taxi.site/callBackForm",
                        ],
                        [
                            "Columns" => 6,
                            "Rows" => 1,
                            "Text" => "На головну",
                            "TextSize" => "large",
                            "TextHAlign" => "center",
                            "TextVAlign" => "middle",
                            "ActionType" => "reply",
                            "ActionBody" => "На головну",
                        ],
                    ],
                ];
                $message = 'Усі можливости 🚕';
                $viber->sendKeyboard($user_id, $message, $keyboard);
                break;
            case "трансфер":
                $keyboard = [
                    "Type" => "keyboard",
                    "DefaultHeight" => false,
                    "Buttons" => [
                        [
                            "Columns" => 3,
                            "Rows" => 2,
                            "ActionType" => "open-url",
                            "ActionBody" => "https://m.easy-order-taxi.site/transfer/Аэропорт%20Борисполь%20терминал%20Д/taxi.transferBorispol",
                            "Image" => $borispol,
                        ],
                        [
                            "Columns" => 3,
                            "Rows" => 2,
                            "ActionType" => "open-url",
                            "ActionBody" => "https://m.easy-order-taxi.site/transfer/Аэропорт%20Жуляны%20новый%20%28ул.Медовая%202%29/taxi.transferJulyany",
                            "Image" => $sikorskogo,
                        ],
                        [
                            'Columns' => 3,
                            'Rows' => 2,
                            "ActionType" => "open-url",
                            "ActionBody" => "https://m.easy-order-taxi.site/transfer/ЖД%20Южный/taxi.transferUZ",
                            "Image" => $UzViber,
                        ],
                        [
                            "Columns" => 3,
                            "Rows" => 2,
                            "ActionType" => "open-url",
                            "ActionBody" => "https://m.easy-order-taxi.site/transfer/Центральный%20автовокзал%20%28у%20шлагбаума%20пл.Московская%203%29/taxi.transferAuto",
                            "Image" => $autoViber,
                        ],
                        [
                            "Columns" => 6,
                            "Rows" => 1,
                            "Text" => "На головну",
                            "TextSize" => "large",
                            "TextHAlign" => "center",
                            "TextVAlign" => "middle",
                            "ActionType" => "reply",
                            "ActionBody" => "На головну",
                        ],
                    ],
                ];
                $message = 'Замовити трансфер ✈ 🚂 🚌';
                $viber->sendKeyboard($user_id, $message, $keyboard);
                break;
            case "зустрич":
                $keyboard = [
                    "Type" => "keyboard",
                    "DefaultHeight" => false,
                    "Buttons" => [
                        [
                            "Columns" => 3,
                            "Rows" => 2,
                            "ActionType" => "open-url",
                            "ActionBody" => "https://m.easy-order-taxi.site/transferfrom/Аэропорт%20Борисполь%20терминал%20Д/taxi.transferFromBorispol",
                            "Image" => $borispol,
                        ],
                        [
                            "Columns" => 3,
                            "Rows" => 2,
                            "ActionType" => "open-url",
                            "ActionBody" => "https://m.easy-order-taxi.site/transferfrom/Аэропорт%20Жуляны%20новый%20%28ул.Медовая%202%29/taxi.transferFromJulyany",
                            "Image" => $sikorskogo,
                        ],
                        [
                            'Columns' => 3,
                            'Rows' => 2,
                            "ActionType" => "open-url",
                            "ActionBody" => "https://m.easy-order-taxi.site/transferfrom/ЖД%20Южный/taxi.transferFromUZ",
                            "Image" => $UzViber,
                        ],
                        [
                            "Columns" => 3,
                            "Rows" => 2,
                            "ActionType" => "open-url",
                            "ActionBody" => "https://m.easy-order-taxi.site/transferfrom/Центральный%20автовокзал%20%28у%20шлагбаума%20пл.Московская%203%29/taxi.transferFromAuto",
                            "Image" => $autoViber,
                        ],
                        [
                            "Columns" => 6,
                            "Rows" => 1,
                            "Text" => "На головну",
                            "TextSize" => "large",
                            "TextHAlign" => "center",
                            "TextVAlign" => "middle",
                            "ActionType" => "reply",
                            "ActionBody" => "На головну",
                        ],
                    ],
                ];
                $message = 'Замовити зустрич ✈ 🚂 🚌';
                $viber->sendKeyboard($user_id, $message, $keyboard);
                break;
            case "на головну":
                $message = 'Головне меню';
                $viber->sendKeyboard($user_id, $message, $keyboard_main);
                break;
            default:
                $needle = 'https://';
                $pos = strripos($data, $needle);
                if ($pos !== false) {
                    $message = 'Головне меню';
                } else {
                    $message = 'Вибачьте! Я розумію поки що трохи слів (наприклад - трансфер, зустрич, робота, послуги), але я дуже швидко вчуся 😺"';
                }
                $viber->sendKeyboard($user_id, $message, $keyboard_main);
        }
    }
}
