<?php

namespace App\Http\Controllers;

use App\Helpers\Telegram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function index(Request $request, Telegram $telegram)
    {
        $telegram_id = $request->input('callback_query')['from']['id'];
        $data = $request->input('callback_query')['data'];

        switch ($data) {
            case 0:
                $buttons = [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Замовити таксі за адресою',
                                'url' => 'https://m.easy-order-taxi.site/home-Combo'
                            ],
                        ],
                        [
                            [
                                'text' => 'Замовити таксі по мапі',
                                'url' => 'https://m.easy-order-taxi.site/home-Map-Combo'
                            ],
                        ],
                        [
                            [
                                'text' => 'Надіслати повідомлення адміністратору',
                                'url' => 'https://m.easy-order-taxi.site/feedback'
                            ],
                        ],
                        [
                            [
                                'text' => 'Усі послуги',
                                'url' => 'https://m.easy-order-taxi.site'
                            ],
                        ],
                        [
                            [
                                'text' => 'Екстренна допомога',
                                'url' => 'https://m.easy-order-taxi.site/callBackForm'
                            ],
                        ],
                    ]
                ];
                $telegram->sendButtons($telegram_id, 'Усі можливости 🚕', json_encode($buttons));
                break;
            case 1:
                $buttons = [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Бориспіль',
                                'url' => 'https://m.easy-order-taxi.site/transfer/Аэропорт%20Борисполь%20терминал%20Д/taxi.transferBorispol'
                            ],
                            [
                                'text' => 'Жуляни',
                                'url' => 'https://m.easy-order-taxi.site/transfer/Аэропорт%20Жуляны%20новый%20%28ул.Медовая%202%29/taxi.transferJulyany'
                            ],
                        ],
                        [
                            [
                                'text' => 'Південний вокзал',
                                'url' => 'https://m.easy-order-taxi.site/transfer/ЖД%20Южный/taxi.transferUZ'
                            ],
                            [
                                'text' => 'Автовокзал',
                                'url' => 'https://m.easy-order-taxi.site/transfer/Центральный%20автовокзал%20%28у%20шлагбаума%20пл.Московская%203%29/taxi.transferAuto'
                            ],
                        ],
                    ]
                ];
                $telegram->sendButtons($telegram_id, 'Замовити трансфер 🏠', json_encode($buttons));
                break;
            case 2:
                $buttons = [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Бориспіль',
                                'url' => 'https://m.easy-order-taxi.site/transferfrom/Аэропорт%20Борисполь%20терминал%20Д/taxi.transferFromBorispol'
                            ],
                            [
                                'text' => 'Жуляни',
                                'url' => 'https://m.easy-order-taxi.site/transferfrom/Аэропорт%20Жуляны%20новый%20%28ул.Медовая%202%29/taxi.transferFromJulyany'
                            ],
                        ],
                        [
                            [
                                'text' => 'Південний вокзал',
                                'url' => 'https://m.easy-order-taxi.site/transferfrom/ЖД%20Южный/taxi.transferFromUZ'
                            ],
                            [
                                'text' => 'Автовокзал',
                                'url' => 'https://m.easy-order-taxi.site/transferfrom/Центральный%20автовокзал%20%28у%20шлагбаума%20пл.Московская%203%29/taxi.transferFromAuto'
                            ],
                        ],
                    ]
                ];
                $telegram->sendButtons($telegram_id, 'Замовити зустрич ✈️', json_encode($buttons));
                break;
            case 3:
                $telegram->sendDocument($telegram_id, 'questionnaire.docx');
                break;
        }
    }
}
