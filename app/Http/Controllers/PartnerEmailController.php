<?php

namespace App\Http\Controllers;

use App\Mail\InfoEmail;
use App\Mail\PartnerInfoEmail;
use App\Mail\PInfoEmail;
use App\Models\Partner;
use App\Models\PartnerEmail;
use App\Models\UserEmail;
use App\Models\UserVisit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PartnerEmailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $partnerMessages = DB::table('partner_emails')
            ->join('partners', 'partner_emails.partner_id', '=', 'partners.id')
            ->select('partner_emails.*', 'partners.name', 'partners.phone', 'partners.email')
//            ->where('partner_emails.sent_email', '!=', 1) // Exclude users with sent_email equal to 1
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($partnerMessages);
    }
    public function partnersForEmail(): \Illuminate\Http\JsonResponse
    {
        $partners = Partner::where("sent_email", null)->orWhere("sent_email", false)->get();

        return response()->json($partners);
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
    public function update(int $id, string $subject, string $text_message, $sent_message_info): \Illuminate\Http\Response
    {
        try {
            $s_message_info = 1;

            if ($sent_message_info === "false" || $sent_message_info === "0"|| $sent_message_info === "null") {
                $s_message_info = 0;
            }
            // Получаем сообщение пользователя по ID

            $partnerMessage = PartnerEmail::find($id);

            // Проверяем, найдено ли сообщение
            if (!$partnerMessage) {
                return response()->json(['error' => 'Сообщение пользователя не найдено'], 404);
            }

            // Обновляем данные

            $partnerMessage->subject = $subject;
            $partnerMessage->text_message = $text_message;
            $partnerMessage->sent_message_info = $s_message_info;
            // Добавьте обновление других полей, если необходимо

            // Сохраняем изменения
            $partnerMessage->save();

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
            $message = PartnerEmail::find($id);

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

    public function newMessage($email, $subject, $text_message)
    {

        $emailString = $email;
        if ($emailString !== "xx") {
            $emailArray = explode(',', $emailString);

            foreach ($emailArray as $value) {
                $partner = Partner::where('email', $value)->first();

//                if (!$partner->sent_email) {
                    // Проверить, найден ли пользователь
                    if ($partner) {
                        // Если пользователь найден, добавить новое сообщение в таблицу partnerMessage
                        $newMessage = new PartnerEmail();
                        $newMessage->partner_id = $partner->id;
                        $newMessage->subject = $partner->name . " по " . $subject;
                        $newMessage->text_message = $text_message;
                        $newMessage->sent_message_info = 0;
                        $newMessage->save();

                        $paramsMail = [
                            'subject' => $partner->name . " по " . $subject,
                            'message' => $text_message,
                            'email' => $partner->email,
                            'text_button' => "Відписатися для $partner->name",
                            'url' =>"https://m.easy-order-taxi.site/unsubscribe/$partner->email"
                        ];
                        $subject = $partner->name . " по " .$subject;
                        $message = $text_message;
                        $url = "https://m.easy-order-taxi.site/partners/unsubscribe/$partner->email";
                        $text_button = "Відписатися для $partner->email";

//                        Mail::to($partner->email)->send(new PartnerInfoEmail('custom_template', $paramsMail));
                        Mail::to($partner->email)
                            ->send(new PInfoEmail('custom_template', $subject, $message, $url, $text_button));

                    }
//                }
            }
        }
//        else {
//            $userVisits = UserVisit::where("app_name", $app)->get()->unique('user_id')->toArray();
//            $userIds = array_column($userVisits, 'user_id');
//            $users = User::whereIn('id', $userIds)->get()->toArray();
//
//
////            dd($users );
//            foreach ($users as $value) {
//                // Проверить, найден ли пользователь
//                if ($value["sent_email"] != 1) {
//                    if ($value["id"] == "33" || $value["id"] == "125") {
//                        // Если пользователь найден, добавить новое сообщение в таблицу UserMessage
//                        $newMessage = new UserEmail();
//                        $newMessage->user_id = $value["id"];
//                        $newMessage->subject = $value["name"] . " по " . $subject;
//                        $newMessage->text_message = $text_message;
//                        $newMessage->sent_message_info = 0;
//                        $newMessage->save();
//
//                        $name = $value["name"];
//                        $email = $value['email'];
//
//                        $paramsMail = [
//                            'subject' => $value["name"] . " по " .$subject,
//                            'message' => $text_message,
//                            'email' => $email,
//                            'text_button' => "Відписатися для $name",
//                            'url' =>"https://m.easy-order-taxi.site/unsubscribe/$email"
//                        ];
//                        Mail::to($email)->send(new InfoEmail($paramsMail));
//                    }
//                }
//            }
//        }

    }
    public function repeatEmail($id_array)
    {
        $idArray = explode(',', $id_array);

        foreach ($idArray as $value) {
            $email = PartnerEmail::where('id', $value)->first();
            $partner = Partner::find($email->partner_id);
            if (!$partner->sent_email) {
                if ($email) {
                    $email->sent_message_info = 0;
                    $email->save();

                    $paramsMail = [
                        'subject' => $email->subject,
                        'message' => $email->text_message,
                        'email' => $partner->email,
                        'text_button' => "Відписатися  для $partner->name",
                        'url' =>"https://m.easy-order-taxi.site/partners/unsubscribe/$partner->email"
                    ];
                    Mail::to($partner->email)->send(new InfoEmail($paramsMail));
                }
            }
        }
    }

    public function unsubscribe($email): \Illuminate\Http\RedirectResponse
    {
        $partner = Partner::where("email", $email)->first();
        $partner->sent_email = 1;
        $partner->save();
        $subject = "Повідомлення с сайту Такси Лайт Юа для $partner->name.";
        $text_message  = "Підписка на повідомлення з сайту Таксі Лайт Юа успішно скасована.";
        $paramsMail = [
            'subject' => $subject,
            'message' => $text_message,
            'email' => $partner->email,
            'text_button' => "Лист адміністратору",
            'url' =>"mailto:taxi.easy.ua@gmail.com"
        ];
        Mail::to($email)->send(new InfoEmail($paramsMail));
        return redirect()->route('home-news')
            ->with('success', 'Підписка на повідомлення з сайту Таксі Лайт Юа успішно скасована.');
    }
}
