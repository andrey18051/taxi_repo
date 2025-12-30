<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserTokenFmsS;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserTokenFmsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     */
    public function store($email, $app, $token)
    {
        $user = User::where("email", $email)->first();
        if ($user) {
            $userToken = UserTokenFmsS::where("user_id", $user->id)->first();
            if ($userToken == null) {
                $userToken = new UserTokenFmsS();
            }
            $userToken->user_id = $user->id;
            switch ($app) {
                case "PAS1":
                    $userToken->token_app_pas_1 = $token;
                    break;
                case "PAS2":
                    $userToken->token_app_pas_2 = $token;
                    break;
                case "PAS4":
                    $userToken->token_app_pas_4 = $token;
                    break;
                default:
                    $userToken->token_app_pas_5 = $token;
            }
            $userToken->save();
        }
    }

    use Illuminate\Support\Facades\Log;

    public function storeLocal($email, $app, $token, $local)
    {
        Log::info('Начало сохранения FCM токена', [
            'email' => $email,
            'app' => $app,
            'token' => $token,
            'local' => $local,
            'timestamp' => now()->toDateTimeString()
        ]);

        // Поиск пользователя по email
        $user = User::where("email", $email)->first();

        if (!$user) {
            Log::info('Пользователь не найден, создаем нового', [
                'email' => $email
            ]);

            $user = new User();
            $user->name = "no_name";
            $user->email = $email;
            $user->password = "123245687";

            // Устанавливаем значения по умолчанию
            $user->facebook_id = null;
            $user->google_id = null;
            $user->linkedin_id = null;
            $user->github_id = null;
            $user->twitter_id = null;
            $user->telegram_id = null;
            $user->viber_id = null;
            $user->bonus = 0;
            $user->bonus_pay = 1;
            $user->card_pay = 1;

            try {
                $user->save();
                Log::info('Новый пользователь успешно создан', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'created_at' => $user->created_at
                ]);
            } catch (\Exception $e) {
                Log::error('Ошибка при создании пользователя', [
                    'email' => $email,
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        } else {
            Log::info('Пользователь найден', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        }

        if ($user) {
            Log::info('Начинаем обработку FCM токена для пользователя', [
                'user_id' => $user->id,
                'app' => $app
            ]);

            // Поиск существующего токена
            $userToken = UserTokenFmsS::where("user_id", $user->id)->first();

            if ($userToken == null) {
                Log::info('Токен пользователя не найден, создаем новый запись', [
                    'user_id' => $user->id
                ]);
                $userToken = new UserTokenFmsS();
            } else {
                Log::info('Существующий токен пользователя найден', [
                    'user_token_id' => $userToken->id,
                    'user_id' => $userToken->user_id
                ]);

                // Логируем текущие значения токенов
                Log::debug('Текущие значения токенов пользователя', [
                    'token_app_pas_1' => $userToken->token_app_pas_1 ? 'установлен' : 'не установлен',
                    'token_app_pas_2' => $userToken->token_app_pas_2 ? 'установлен' : 'не установлен',
                    'token_app_pas_4' => $userToken->token_app_pas_4 ? 'установлен' : 'не установлен',
                    'token_app_pas_5' => $userToken->token_app_pas_5 ? 'установлен' : 'не установлен'
                ]);
            }

            $userToken->user_id = $user->id;
            $previousToken = null;

            switch ($app) {
                case "PAS1":
                    $previousToken = $userToken->token_app_pas_1;
                    $userToken->token_app_pas_1 = $token;
                    Log::info('Устанавливаем токен для PAS1', [
                        'user_id' => $user->id,
                        'previous_token' => $previousToken ? 'установлен' : 'не установлен',
                        'new_token' => substr($token, 0, 20) . '...' // Логируем только часть токена для безопасности
                    ]);
                    break;
                case "PAS2":
                    $previousToken = $userToken->token_app_pas_2;
                    $userToken->token_app_pas_2 = $token;
                    Log::info('Устанавливаем токен для PAS2', [
                        'user_id' => $user->id,
                        'previous_token' => $previousToken ? 'установлен' : 'не установлен',
                        'new_token' => substr($token, 0, 20) . '...'
                    ]);
                    break;
                case "PAS4":
                    $previousToken = $userToken->token_app_pas_4;
                    $userToken->token_app_pas_4 = $token;
                    Log::info('Устанавливаем токен для PAS4', [
                        'user_id' => $user->id,
                        'previous_token' => $previousToken ? 'установлен' : 'не установлен',
                        'new_token' => substr($token, 0, 20) . '...'
                    ]);
                    break;
                default:
                    $previousToken = $userToken->token_app_pas_5;
                    $userToken->token_app_pas_5 = $token;
                    Log::info('Устанавливаем токен для PAS5 (или другое приложение)', [
                        'user_id' => $user->id,
                        'app_received' => $app,
                        'previous_token' => $previousToken ? 'установлен' : 'не установлен',
                        'new_token' => substr($token, 0, 20) . '...'
                    ]);
            }

            try {
                $userToken->save();
                Log::info('Токен успешно сохранен', [
                    'user_token_id' => $userToken->id,
                    'user_id' => $userToken->user_id,
                    'app' => $app,
                    'updated_at' => $userToken->updated_at
                ]);

                // Логируем итоговые значения
                Log::debug('Итоговые значения токенов после сохранения', [
                    'token_app_pas_1' => $userToken->token_app_pas_1 ? 'установлен' : 'не установлен',
                    'token_app_pas_2' => $userToken->token_app_pas_2 ? 'установлен' : 'не установлен',
                    'token_app_pas_4' => $userToken->token_app_pas_4 ? 'установлен' : 'не установлен',
                    'token_app_pas_5' => $userToken->token_app_pas_5 ? 'установлен' : 'не установлен'
                ]);

            } catch (\Exception $e) {
                Log::error('Ошибка при сохранении токена пользователя', [
                    'user_id' => $user->id,
                    'app' => $app,
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            // Сохранение локали приложения
            Log::info('Начинаем сохранение локали приложения', [
                'email' => $email,
                'app' => $app,
                'local' => $local
            ]);

            try {
                (new UserLocalAppController)->store($email, $app, $local);
                Log::info('Локаль приложения успешно сохранена', [
                    'email' => $email,
                    'app' => $app,
                    'local' => $local
                ]);
            } catch (\Exception $e) {
                Log::error('Ошибка при сохранении локали приложения', [
                    'email' => $email,
                    'app' => $app,
                    'local' => $local,
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString()
                ]);
                // Не прерываем выполнение, так как основной токен уже сохранен
            }

            Log::info('Метод storeLocal успешно завершен', [
                'user_id' => $user->id,
                'app' => $app,
                'operation' => 'complete'
            ]);

        } else {
            Log::error('Пользователь не найден и не был создан', [
                'email' => $email
            ]);
        }
    }

    /**
     * @param $title
     * @param $body
     * @param $app
     * @param $to
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage($body, $app, $user_id)
    {
        $userToken = UserTokenFmsS::where("user_id", $user_id)->first();
//        dd($userToken);
        if ($userToken != null) {
            switch ($app) {
                case "PAS1":
                    $to = $userToken->token_app_pas_1;
                    $firebaseApiKey = config("app.FIREBASE_API_KEY_PAS_1");
                    break;
                case "PAS2":
                    $to = $userToken->token_app_pas_2;
                    $firebaseApiKey = config("app.FIREBASE_API_KEY_PAS_2");
                    break;
                case "PAS4":
                    $to = $userToken->token_app_pas_4;
                    $firebaseApiKey = config("app.FIREBASE_API_KEY_PAS_4");
                    break;
                default:
                    $to = $userToken->token_app_pas_5;
                    $firebaseApiKey = config("app.FIREBASE_API_KEY_PAS_5");
            }

            $url  = "https://fcm.googleapis.com/v1/projects/taxieasyuaback4app/messages:send";
            $response = Http::withHeaders([
                'Authorization' => "Bearer ya29.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",
                'Content-Type' => 'application/json',
            ])->post($url, [
                'to' => $to, // FCM токен получателя
                'notification' => [
                    'title' => "title",
                    'body' => $body,
                ],
                'data' => "", // Дополнительные данные (если есть)
            ]);

            return response()->json([
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
