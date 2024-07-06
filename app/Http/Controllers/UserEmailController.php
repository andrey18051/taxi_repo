<?php

namespace App\Http\Controllers;

use App\Mail\InfoEmail;
use App\Mail\PromoList;
use App\Models\User;
use App\Models\UserEmail;
use App\Models\UserVisit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserEmailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userMessages = DB::table('user_emails')
            ->join('users', 'user_emails.user_id', '=', 'users.id')
            ->select('user_emails.*', 'users.name', 'users.user_phone', 'users.email')
//            ->where('user_emails.sent_email', '!=', 1) // Exclude users with sent_email equal to 1
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($userMessages);
    }

    public function usersForEmail(): \Illuminate\Http\JsonResponse
    {
        $users = User::where("sent_email", null)->orWhere("sent_email", false)->get();

        return response()->json($users);
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
    public function store(Request $request)
    {
        //
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
    public function update(int $id, string $subject, string $text_message, $sent_message_info)
    {
        try {
            $s_message_info = 1;

            if ($sent_message_info === "false" || $sent_message_info === "0"|| $sent_message_info === "null") {
                $s_message_info = 0;
            }
            // Получаем сообщение пользователя по ID

            $userMessage = UserEmail::find($id);

            // Проверяем, найдено ли сообщение
            if (!$userMessage) {
                return response()->json(['error' => 'Сообщение пользователя не найдено'], 404);
            }

            // Обновляем данные

            $userMessage->subject = $subject;
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
    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
            $message = UserEmail::find($id);

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
    public function newMessage($email, $subject, $text_message, $app)
    {
        $emailString = $email;
        if ($emailString !== "xx") {
            $emailArray = explode(',', $emailString);

            foreach ($emailArray as $value) {
                $user = User::where('email', $value)->first();
                if (!$user->sent_email) {
                    // Проверить, найден ли пользователь
                    if ($user) {
                        // Если пользователь найден, добавить новое сообщение в таблицу UserMessage
                        $newMessage = new UserEmail();
                         $newMessage->user_id = $user->id;
                        $newMessage->subject = $user->name . " по " . $subject;
                        $newMessage->text_message = $text_message;
                        $newMessage->sent_message_info = 0;
                        $newMessage->save();

                        $paramsMail = [
                            'subject' => $user->name . " по " .$subject,
                            'message' => $text_message,
                            'email' => $user->email,
                            'text_button' => "Відписатися для $user->name",
                            'url' =>"https://m.easy-order-taxi.site/unsubscribe/$user->email"
                        ];
                        Mail::to($value)->send(new InfoEmail($paramsMail));
                    }
                }
            }
        } else {
            $userVisits = UserVisit::where("app_name", $app)->get()->unique('user_id')->toArray();
            $userIds = array_column($userVisits, 'user_id');
            $users = User::whereIn('id', $userIds)->get()->toArray();


//            dd($users );
            foreach ($users as $value) {
                // Проверить, найден ли пользователь
                if ($value["sent_email"] != 1) {
                    if ($value["id"] == "33" || $value["id"] == "125") {
                        // Если пользователь найден, добавить новое сообщение в таблицу UserMessage
                        $newMessage = new UserEmail();
                        $newMessage->user_id = $value["id"];
                        $newMessage->subject = $value["name"] . " по " . $subject;
                        $newMessage->text_message = $text_message;
                        $newMessage->sent_message_info = 0;
                        $newMessage->save();

                        $name = $value["name"];
                        $email = $value['email'];

                        $paramsMail = [
                            'subject' => $value["name"] . " по " .$subject,
                            'message' => $text_message,
                            'email' => $email,
                            'text_button' => "Відписатися для $name",
                            'url' =>"https://m.easy-order-taxi.site/unsubscribe/$email"
                        ];
                        Mail::to($email)->send(new InfoEmail($paramsMail));
                    }
                }
            }
        }
    }

    public function newMessageEmail($email, $subject, $text_message)
    {
        $user = User::where('email', $email)->first();

        // Проверить, найден ли пользователь
        if ($user) {
            $newMessage = new UserEmail();
            $newMessage->user_id = $user->id;
            $newMessage->subject = $user->name . " по " . $subject;
            $newMessage->text_message = $text_message;
            $newMessage->sent_message_info = 0;
            $newMessage->save();
        }
    }

    public function sleepUsersEmails()
    {
        $inactiveUserDetails = (new UserController)->userList();
        $text_message = "Новое сообщение";

        foreach ($inactiveUserDetails as $user) {
            $email = $user->email;
            $app_1 = $user->app_pas_1;
            $app_2 = $user->app_pas_2;
            $app_4 = $user->app_pas_4;

            if ($app_1 == 1) {
                self::newMessageEmail($email, "PAS1", $text_message);
            }
            if ($app_2 == 1) {
                self::newMessageEmail($email, "PAS2", $text_message);
            }
            if ($app_4 == 1) {
                self::newMessageEmail($email, "PAS4", $text_message);
            }
        }
    }

    public function repeatEmail($id_array)
    {
        $idArray = explode(',', $id_array);

        foreach ($idArray as $value) {
            $email = UserEmail::where('id', $value)->first();
            $user = User::find($email->user_id);
            if (!$user->sent_email) {
                if ($email) {
                    $email->sent_message_info = 0;
                    $email->save();

                    $paramsMail = [
                        'subject' => $email->subject,
                        'message' => $email->text_message,
                        'email' => $user->email,
                        'text_button' => "Відписатися  для $user->name",
                        'url' =>"https://m.easy-order-taxi.site/unsubscribe/$user->email"
                    ];
                    Mail::to($user->email)->send(new InfoEmail($paramsMail));
                }
            }
        }
    }

    public function unsubscribe($email)
    {
        $user = User::where("email", $email)->first();
        $user->sent_email = 1;
        $user->save();
        $subject = "Повідомлення с сайту Такси Лайт Юа для $user->name.";
        $text_message  = "Підписка на повідомлення з сайту Таксі Лайт Юа успішно скасована.";
        $paramsMail = [
            'subject' => $subject,
            'message' => $text_message,
            'email' => $user->email,
            'text_button' => "Лист адміністратору",
            'url' =>"mailto:taxi.easy.ua@gmail.com"
        ];
        Mail::to($email)->send(new InfoEmail($paramsMail));
        return redirect()->route('home-news')
            ->with('success', 'Підписка на повідомлення з сайту Таксі Лайт Юа успішно скасована.');
    }
}
