<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserMessage;
use App\Models\UserVisit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserMessageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userMessages = DB::table('user_messages')
            ->join('users', 'user_messages.user_id', '=', 'users.id')
            ->select('user_messages.*', 'users.name', 'users.user_phone', 'users.email')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($userMessages);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(
        $user_id,
        $text_message
    ) {
        // Создание нового объекта UserMessage
        $userMessage = UserMessage::create([
            'user_id' => $user_id,
            'text_message' => $text_message,
            'sent_message_info' => 0,
        ]);

        // Возвращение успешного ответа или редиректа
        return response()->json(['message' => 'User message created successfully'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function show($email, $app): \Illuminate\Http\JsonResponse
    {
        // Найти пользователя по email
        $user = User::where("email", $email)->first();

        // Проверить, найден ли пользователь
        if (!$user) {
            // Возвращение ошибки, если пользователь не найден
            return response()->json(['error' => 'User not found'], 404);
        }
        $city_result = (new IPController)->ipCityPush();
        switch ($city_result) {
            case "Kyiv City":
            case "Dnipropetrovsk Oblast":
            case "Odessa":
            case "Zaporizhzhia":
            case "Cherkasy Oblast":
                $city = $city_result;
                break;
            default:
                $city = "foreign countries";
        }
        (new UserVisitController)->create($user->id, $app, $city_result);

// Найти сообщения пользователя, где sent_message_info равно 0
        $userMessages = UserMessage::where('user_id', $user->id)
            ->where('sent_message_info', 0);

// Условие для app (включая "ALL PASS")
        if ($app !== null) {
            $userMessages->where(function ($query) use ($app) {
                $query->where('app', $app)
                    ->orWhere('app', 'ALL PASS');
            });
        }

// Условие для city (включая "ALL CITY")
        if ($city !== null) {
            $userMessages->where(function ($query) use ($city) {
                $query->where('city', $city)
                    ->orWhere('city', 'ALL CITY');
            });
        }

        $userMessages = $userMessages->orderBy('id', 'desc')->get();



        // Проверить, найдены ли сообщения
        if ($userMessages->isEmpty()) {
            // Возвращение ошибки, если сообщения не найдены
            return response()->json(['error' => 'No messages found for the user with sent_message_info equal to 0'], 404);
        } else {
            // Возвращение JSON-ответа с данными сообщений пользователя
            $responseData = $userMessages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'user_id' => $message->user_id,
                    'text_message' => $message->text_message,
                    'sent_message_info' => $message->sent_message_info,
                    'app' => $message->app,
                    'created_at' => $message->created_at,
                    'updated_at' => $message->updated_at,
                ];
            });
            $userMessages->each(function ($message) {
                $message->update(['sent_message_info' => 1]);
            });
        }






        return response()->json($responseData, 200);
    }




    public function newMessage($email, $text_message, $app, $city)
    {
        $emailString = $email;

        $emailArray = explode(',', $emailString);

        foreach ($emailArray as $value) {
            $user = User::where('email', $value)->first();

            // Проверить, найден ли пользователь
            if ($user) {
                // Если пользователь найден, добавить новое сообщение в таблицу UserMessage
                $newMessage = new UserMessage();
                $newMessage->user_id = $user->id;
                $newMessage->text_message = $text_message;
                $newMessage->sent_message_info = 0;
                $newMessage->app = $app;
                $newMessage->city = $city;
                $newMessage->save();
            }
        }
    }

    public function sleepUsersMessages ()
    {
        $inactiveUserDetails = (new UserController)->userList();
        $city = "ALL CITY";
        $text_message = "Спишь бродяга";

        foreach ($inactiveUserDetails as $user) {
            $email = $user->email;
            $app_1 = $user->app_pas_1;
            $app_2= $user->app_pas_2;
            $app_4 = $user->app_pas_4;

            if ($app_1 == 1) {
                self::newMessage($email, $text_message, "PAS1", $city);
            }
            if ($app_2 == 1) {
                self::newMessage($email, $text_message, "PAS2", $city);
            }
            if ($app_4 == 1) {
                self::newMessage($email, $text_message, "PAS4", $city);
            }
        }
    }

    public function update(int $id, string $text_message, $sent_message_info, $app, $city)
    {
        try {
            $s_message_info = 1;

            if ($sent_message_info === "false" || $sent_message_info === "0"|| $sent_message_info === "null") {
                $s_message_info = 0;
            }
            // Получаем сообщение пользователя по ID

            $userMessage = UserMessage::find($id);

            // Проверяем, найдено ли сообщение
            if (!$userMessage) {
                return response()->json(['error' => 'Сообщение пользователя не найдено'], 404);
            }

            // Обновляем данные

            $userMessage->text_message = $text_message;
            $userMessage->sent_message_info = $s_message_info;
            $userMessage->app = $app;
            $userMessage->city = $city;
            // Добавьте обновление других полей, если необходимо

            // Сохраняем изменения
            $userMessage->save();

            // Возвращаем успешный ответ
            return response()->json(['message' => 'Данные успешно обновлены'], 200);
        } catch (\Exception $e) {
            // Логирование ошибки
            Log::error($e);

            // Возвращаем ответ об ошибке
            return response()->json(['error' => 'Произошла ошибка при обновлении данных'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
            $message = UserMessage::find($id);

            if (!$message) {
                return response()->json(['error' => 'Пользователь не найден'], 404);
            }

            $message->delete();

            DB::commit();

            return response()->json(['message' => 'Сообщение для пользователя успешно удалено'], 204);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Произошла ошибка при удалении сообщения', 'details' => $e->getMessage()], 500);

        }
    }
}
