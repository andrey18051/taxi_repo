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
    public function getActiveCard($email, $city, $application): array
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

        $user = User::where("email", $email)->first();

        if($user) {
            $activeCard = Card::
                where("user_id", $user->id)->
                where('merchant', $merchantAccount)->
                where("active", true) -> first();

            if($activeCard) {
                return ["rectoken" => $activeCard->rectoken];
            } else {
                return ["rectoken" => null];
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

    public function getCardTokenIdApp(
        $application,
        $cityApp,
        $email,
        $pay_system
    ): \Illuminate\Http\JsonResponse {
        $user = User::where('email', $email)->first();

        switch ($application) {
            case "PAS1":
                $merchantInfo = City_PAS1::where("name", $cityApp)->first();
                break;
            case "PAS2":
                $merchantInfo = City_PAS2::where("name", $cityApp)->first();
                break;
            default:
                $merchantInfo = City_PAS4::where("name", $cityApp)->first();
        }

        $response = [];
        if ($merchantInfo) {
            $merchant = $merchantInfo->toArray();
            $merchantAccount = $merchant["wfp_merchantAccount"];
            $cards = Card::where('pay_system', $pay_system)
                ->where('user_id', $user->id)
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
        $merchantAccount = $card->merchant;

        $user_id = $card->user_id; // Сохраняем ID пользователя
        $active = $card->active;  // Сохраняем статус "active"
        $card->delete();          // Удаляем карту

        // Если удаляемая карта была активной, назначаем новую активную карту
        if ($active) {
            // Ищем другую карту пользователя
            $userCards = Card::where("user_id", $user_id)
                ->where('merchant', $merchantAccount)
                ->get();

            // Если у пользователя остались карты, делаем первую из них активной
            if ($userCards->isNotEmpty()) {
                $firstCard = $userCards->first();
                $firstCard->active = true;
                $firstCard->save();

                // Делаем остальные карты неактивными
                foreach ($userCards as $value) {
                    if ($value->id != $firstCard->id) {
                        $value->active = false;
                        $value->save();
                    }
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
