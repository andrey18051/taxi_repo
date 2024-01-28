<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserMessage;
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


    public function show($email): \Illuminate\Http\JsonResponse
    {
        // Найти пользователя по email
        $user = User::where("email", $email)->first();

        // Проверить, найден ли пользователь
        if (!$user) {
            // Возвращение ошибки, если пользователь не найден
            return response()->json(['error' => 'User not found'], 404);
        }

        // Найти сообщения пользователя, где sent_message_info равно 0
        $userMessages = UserMessage::where('user_id', $user->id)
            ->where('sent_message_info', 0)
            ->get();

        // Проверить, найдены ли сообщения
        if ($userMessages->isEmpty()) {
            // Возвращение ошибки, если сообщения не найдены
            return response()->json(['error' => 'No messages found for the user with sent_message_info equal to 0'], 404);
        } else {
            // Обновить sent_message_info на 1 для всех сообщений пользователя
            UserMessage::where('user_id', $user->id)->update(['sent_message_info' => 1]);
        }

        // Возвращение JSON-ответа с данными сообщений пользователя
        $responseData = $userMessages->map(function ($message) {
            return [
                'id' => $message->id,
                'user_id' => $message->user_id,
                'text_message' => $message->text_message,
                'sent_message_info' => $message->sent_message_info,
                'created_at' => $message->created_at,
                'updated_at' => $message->updated_at,
            ];
        });

        return response()->json($responseData, 200);
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function newMessage($email, $text_message)
    {
        $user = User::where('email', $email)->first();

        // Проверить, найден ли пользователь
        if ($user) {
            // Если пользователь найден, добавить новое сообщение в таблицу UserMessage
            $newMessage = new UserMessage();
            $newMessage->user_id = $user->id;
            $newMessage->text_message = $text_message;
            $newMessage->sent_message_info = 0;
            $newMessage->save();

//            UserMessage::create([
//                'user_id' => $user->id,
//                'text_message' => $text_message,
//                'sent_message_info' => 0,
//                // Другие поля сообщения, которые вы хотите добавить
//            ]);

            return response()->json(['message' => 'Сообщение успешно добавлено'], 200);
        } else {
            return response()->json(['message' => 'Пользователь с указанным email не найден'], 404);
        }
    }


    public function update(int $id, string $text_message, $sent_message_info)
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
