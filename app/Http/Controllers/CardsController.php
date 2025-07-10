<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\City_PAS1;
use App\Models\City_PAS2;
use App\Models\City_PAS4;
use App\Models\User;
use DateTime;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class CardsController extends Controller
{
    public function getActiveCard($email, $city, $application)
    {

        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                break;
            case "foreign countries":
                $city = "Kyiv City";
                break;
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
        }


        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $user = User::where("email", $email)->first();

            if ($user) {
                $activeCard = Card::
                where("user_id", $user->id)->
                where('merchant', $merchantAccount)->
                where('app', $application)->
                where("active", true)->first();
                $messageAdmin = "getActiveCard" . $activeCard;
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
                if ($activeCard) {
                    return ["rectoken" => $activeCard->rectoken];
                } else {
                    return null;
                }
            }
        }
    }

    public function setActiveCard($email, $id, $city, $application)
    {

        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
        }


        $activeCard = Card::where("id", $id)->first();
        if($activeCard) {
            $activeCard->active = true;
            $activeCard->save();

            $user = User::where("email", $email)->first();
            $userCards = Card::where("user_id", $user->id)
                ->where('merchant', $merchantAccount)
                ->get();

            foreach ($userCards as $value) {
                if($value->id != $id) {
                    $value->active = false;
                    $value->save();
                }
            }
        }
        return ["result" => "ok"];
    }

    public function setActiveCardApp($email, $id, $city, $application)
    {

        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                break;
            case "foreign countries":
                $city = "Kyiv City";
                break;
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
        }


        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;

            $activeCard = Card::where("id", $id)->first();
            if ($activeCard) {
                $activeCard->active = true;
                $activeCard->save();

                $user = User::where("email", $email)->first();
                $userCards = Card::where("user_id", $user->id)
                    ->where('merchant', $merchantAccount)
                    ->where('app', $application)
                    ->get();

                foreach ($userCards as $value) {
                    if ($value->id != $id) {
                        $value->active = false;
                        $value->save();
                    }
                }
            }
            return ["result" => "ok"];
        }
    }

    public function setActiveCardAfterDelete(
        $merchant,
        $user_id,
        $id,
        $application
    ) {
        $userCards = Card::where("user_id", $user_id)
            ->where('merchant', $merchant)
            ->where('app', $application)
            ->get();

        foreach ($userCards as $value) {
            $value->active = true;
            $value->save();
            $id = $value->id;

            if($value->id != $id) {
                $value->active = false;
                $value->save();
            }
        }
        return ["result" => "ok"];
    }


    public function setActiveFirstCard($email, $id )
    {
        $activeCard = Card::where("id", $id)->first();
        if($activeCard) {
            $activeCard->active = true;
            $activeCard->save();
            $merchantAccount = $activeCard->merchant;

            $user = User::where("email", $email)->first();
            $userCards = Card::where("user_id", $user->id)
                ->where('merchant', $merchantAccount)
                ->get();

            foreach ($userCards as $value) {
                if($value->id != $id) {
                    $value->active = false;
                    $value->save();
                }
            }
        }
        return ["result" => "ok"];
    }

    public function setActiveFirstCardApp($email, $id, $app )
    {
        $activeCard = Card::where("id", $id)->first();
        if($activeCard) {
            $activeCard->active = true;
            $activeCard->save();
            $merchantAccount = $activeCard->merchant;

            $user = User::where("email", $email)->first();
            $userCards = Card::where("user_id", $user->id)
                ->where('merchant', $merchantAccount)
                ->where('app', $app)
                ->get();

            foreach ($userCards as $value) {
                if($value->id != $id) {
                    $value->active = false;
                    $value->save();
                }
            }
        }
        return ["result" => "ok"];
    }
    public function getCardTokenIdApp(
        $application,
        $cityApp,
        $email,
        $pay_system
    ): \Illuminate\Http\JsonResponse {
        $user = User::where('email', $email)->first();

        switch ($cityApp) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                break;
            case "foreign countries":
                $city = "Kyiv City";
                break;
        }




        switch ($application) {
            case "PAS1":
                $merchantInfo = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchantInfo = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchantInfo = City_PAS4::where("name", $city)->first();
        }

        $response = [];
        if ($merchantInfo) {
            $merchant = $merchantInfo->toArray();
            $merchantAccount = $merchant["wfp_merchantAccount"];
            $cards = Card::where('pay_system', $pay_system)
                ->where('user_id', $user->id)
                ->where('app', $application)
                ->where('merchant', $merchantAccount)
                ->get();
            if ($cards != null) {
                foreach ($cards as $card) {
                    $rectokenLifetimeString = $card->rectoken_lifetime;
                    $rectokenLifetimeDateTime = DateTime::createFromFormat('d.m.Y H:i:s', $rectokenLifetimeString);

                    $cardData = [
                        'masked_card' => $card->masked_card,
                        'card_type' => $card->card_type,
                        'bank_name' => $card->bank_name,
                        'merchant' => $card->merchant,
                        'rectoken' => $card->id,
                        'active' => $card->active
                    ];

                    if ($rectokenLifetimeDateTime instanceof DateTime) {
                        $currentTime = new DateTime();

                        if ($rectokenLifetimeDateTime < $currentTime) {
                            $card->delete();
                        }
                    }
                    $response[] = $cardData;
                }
            }
        }

        return response()->json(['cards' => $response]);
    }

    public function deleteCardToken($id): array
    {
        // Найти карту по ID
        $card = Card::find($id);

        if (!$card) {
            return ["result" => "error", "message" => "Card not found"];
        }

        $user_id = $card->user_id; // Сохраняем ID пользователя
        $application = $card->app;

        $userCards = Card::where("user_id", $user_id)->get();
        foreach ($userCards as $value) {
            if (
                $value->masked_card == $card->masked_card &&
                $value->app == $card->app
            ) {
                $active = $value->active;
                $merchant = $value->merchant;

                $value->delete(); // Удаляем карту
                if($active)
                {
                    // Если удаляемая карта была активной, назначаем новую активную карту
                    self::setActiveCardAfterDelete(
                        $merchant,
                        $user_id,
                        $id,
                        $application
                    );
                }

            }
        }



        return ["result" => "ok"];
    }

    public function encryptToken(string $token): string
    {
        Log::debug("token  $token");
        Log::debug("token" .  Crypt::encryptString($token));

        return Crypt::encryptString($token);
    }

    public function decryptToken(string $encryptedData): string
    {
        return Crypt::decryptString($encryptedData);
    }
}
