<?php

namespace App\Http\Controllers;

use App\Helpers\ViberCustoms;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WebhookCustomsViberController extends Controller
{
    public function index(Request $request, ViberCustoms $viber)
    {

         Log::debug($request->all());

        /**
         * Кнопки и ответы
         */
        $user_id = $request->input('user')['id'];
        $name = $request->input('user')['name'];

        $message = "Привіт, $name! Я віртуальний помічник! Я розумію поки що трохи слів (наприклад -
            Слава Україні, ЄАІС, NCTS),
але я дуже швидко вчуся 😺";

        $keyboard_main = [
            "Type" => "keyboard",
            "DefaultHeight" => false,
            "Buttons" => [
                [
                    "Columns" => 3,
                    "Rows" => 1,
                    "Text" => "<b>Митний тариф</b>",
                    "TextSize" => "large",
                    "TextHAlign" => "center",
                    "TextVAlign" => "middle",
                    "ActionType" => "open-url",
                    "ActionBody" => "https://cabinet.customs.gov.ua/tnvinfo",
                ],
                [
                    "Columns" => 3,
                    "Rows" => 1,
                    "Text" => "<b>Класифікація</b>",
                    "TextSize" => "large",
                    "TextHAlign" => "center",
                    "TextVAlign" => "middle",
                    "ActionType" => "open-url",
                    "ActionBody" => "https://cabinet.customs.gov.ua/cld",
                ],
                [
                    "Columns" => 3,
                    "Rows" => 1,
                    "Text" => "<b>Cтатус МД</b>",
                    "TextSize" => "large",
                    "TextHAlign" => "center",
                    "TextVAlign" => "middle",
                    "ActionType" => "open-url",
                    "ActionBody" => "https://cabinet.customs.gov.ua/ccdcheck",
                ],
                [
                    "Columns" => 3,
                    "Rows" => 1,
                    "Text" => "<b>Кабінет</b>",
                    "TextSize" => "large",
                    "TextHAlign" => "center",
                    "TextVAlign" => "middle",
                    "ActionType" => "open-url",
                    "ActionBody" => "https://cabinet.customs.gov.ua/login",
                ],
                [
                    "Columns" => 6,
                    "Rows" => 1,
                    "Text" => "<b>Головна сторінка</b>",
                    "TextSize" => "large",
                    "TextHAlign" => "center",
                    "TextVAlign" => "middle",
                    "ActionType" => "open-url",
                    "ActionBody" => "https://cabinet.customs.gov.ua",
                ],
            ],
        ];

        $viber->sendKeyboard($user_id, $message, $keyboard_main);
        $user_id = $request->input('sender')['id'];
        $name = $request->input('sender')['name'];
        $data = mb_strtolower($request->input('message')['text']);

        switch ($data) {
            case "слава україні":
                $message = "Героям слава!!!";
                    $viber->sendKeyboard($user_id, $message, $keyboard_main);
                break;
            case "єаіс":
                $message = "ЄАІС - це скорочення від " . "Єдиного автоматизованого інформаційного системи Державної митної служби України" .", який є спеціально розробленою інформаційною системою, призначеною для автоматизації митних процедур та контролю за переміщенням товарів через митний кордон України. Ця система дозволяє митницям та іншим зацікавленим сторонам ефективно обмінюватися інформацією та забезпечувати більш ефективний та прозорий контроль за митними процедурами.";
                $viber->sendKeyboard($user_id, $message, $keyboard_main);
                break;
            case "ncts":
                $message = "ЄАІС взаємодіє з системою NCTS (New Computerized Transit System) як частина своєї ролі в забезпеченні митної безпеки та сприянні міжнародній торгівлі. NCTS - це система електронного митного оформлення для перевезення товарів через митний кордон Європейського Союзу, що використовується для керування транзитними операціями та митними процедурами.";
                $viber->sendKeyboard($user_id, $message, $keyboard_main);
                break;
            $viber->sendKeyboard($user_id, $message, $keyboard_main);
            break;
            case "на головну":
                $message = "Головне меню";
                    $viber->sendKeyboard($user_id, $message, $keyboard_main);
                break;
            default:
                $needle = 'https://';
                $pos = strripos($data, $needle);
                if ($pos !== false) {
                    $message = 'Головне меню';
                } else {
                    $message = 'Вибачьте! Я розумію поки що трохи слів (наприклад - Слава Україні, ЄАІС, NCTS), але я дуже швидко вчуся 😺"';
                }
                $viber->sendKeyboard($user_id, $message, $keyboard_main);
        }
    }
}
