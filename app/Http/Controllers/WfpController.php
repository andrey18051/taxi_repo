<?php

namespace App\Http\Controllers;

use App\Models\City_PAS1;
use App\Models\City_PAS2;
use App\Models\City_PAS4;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WfpController extends Controller
{
    public function createInvoice(
        $application,
        $city,
        $orderReference,
        $amount,
        $language,
        $productName,
        $clientEmail,
        $clientPhone
    ): \Illuminate\Http\Client\Response {
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
        }
//            dd(" /merchantAccount- " . $merchantAccount . "\n"
//                . " /secretKey- " . $secretKey . "\n"
//                . " /orderReference- " . $orderReference . "\n"
//                . " /amount- " . $amount . "\n"
//                . " /language- " . $language . "\n"
//                . " /productName- " . $productName . "\n"
//                . " /clientEmail- " . $clientEmail . "\n"
//                . " /clientPhone- " . $clientPhone
//            );
//
        $orderDate =  strtotime(date('Y-m-d H:i:s'));

        $params = [
            "merchantAccount" => $merchantAccount,
            "merchantDomainName" => "m.easy-order-taxi.site",
            "orderReference" => $orderReference,
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1]
        ];
//dd($params);

//        $merchantAccount = "test_merch_n1";
//        $secretKey = "flk3409refn54t54t*FNJRET";

        $params = [
            "transactionType" => "CREATE_INVOICE",
            "merchantAccount" => $merchantAccount,
            "merchantAuthType" => "SimpleSignature",
            "merchantDomainName" => "m.easy-order-taxi.site",
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey),
            "apiVersion" => 1,
            "language" => $language,
            "returnUrl" => "https://m.easy-order-taxi.site/wfp/returnUrl",
            "serviceUrl" => "https://m.easy-order-taxi.site/wfp/serviceUrl",
            "orderReference" => $orderReference,
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "orderTimeout" => 86400,
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1],
            "paymentSystems" => "card;privat24",
            "clientEmail" => $clientEmail,
            "clientPhone" => $clientPhone
        ];

// Відправлення POST-запиту
        return Http::post('https://api.wayforpay.com/api', $params);
    }

    public function returnUrl()
    {
        Log::debug("returnUrl");
        return "returnUrl";
    }

    public function serviceUrl(Request $request)
    {
        Log::debug($request);
        $time = strtotime(date('Y-m-d H:i:s'));

        $params = [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time
        ];
        $secretKey = "7aca3657f12fca79d876dcb50e2d84d71f544516";

        $signature = self::generateHmacMd5Signature($params, $secretKey);

        return [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time,
             "signature" =>  $signature
        ];
    }
    public function serviceUrlVerify(Request $request)
    {
        Log::debug($request);
    }

    public function verify(
        $application,
        $city,
        $orderReference,
        $clientEmail,
        $clientPhone
    ): \Illuminate\Http\Client\Response {
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
        }

        $params = [
            "merchantAccount" => $merchantAccount,
            "merchantDomainName" => "m.easy-order-taxi.site",
            "orderReference" => $orderReference,
            "amount" => "0",
            "currency" => "UAH",
        ];
//dd($params);

//        $merchantAccount = "test_merch_n1";
//        $secretKey = "flk3409refn54t54t*FNJRET";

        $params = [
            "merchantAccount" => $merchantAccount,
            "merchantDomainName" => "m.easy-order-taxi.site",
            "merchantAuthType" => "SimpleSignature",
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey),
            "orderReference" => $orderReference,
            "amount" => "0",
            "currency" => "UAH",
            "clientEmail" => $clientEmail,
            "clientPhone" => $clientPhone,
            "returnUrl" => "https://m.easy-order-taxi.site/wfp/returnUrl",
            "serviceUrl" => "https://m.easy-order-taxi.site/wfp/serviceUrl",
            "language"=> "RU",
            "paymentSystems" => "lookupCard",
            "verifyType" => "confirm",
        ];
//        $params = [
//            "merchantAccount" => $merchantAccount,
//            "merchantDomainName" => "merchant.com.ua",
//            "merchantAuthType" => "SimpleSignature",
//            "merchantSignature" => "9a9b6f197eea8319ee87c4b7079c4c28",
//            "orderReference" => "VRF-PP-1445852171",
//            "amount" => "0",
//            "currency" => "UAH",
//            "clientEmail" => "some@mail.com",
//            "clientPhone" => "+38(066)0000000",
//            "returnUrl" => "http://local.com/service",
//            "serviceUrl" => "http://local.com/service",
//            "language"=> "RU",
//            "paymentSystems" => "card",
//            "verifyType" => "simple",
//        ];

// Відправлення POST-запиту
        return Http::post('https://secure.wayforpay.com/verify', $params);
    }

    public function pay(
        $application,
        $city,
        $orderReference,
        $amount,
        $language,
        $productName,
        $clientEmail,
        $clientPhone
    )  {
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
        }
//            dd(" /merchantAccount- " . $merchantAccount . "\n"
//                . " /secretKey- " . $secretKey . "\n"
//                . " /orderReference- " . $orderReference . "\n"
//                . " /amount- " . $amount . "\n"
//                . " /language- " . $language . "\n"
//                . " /productName- " . $productName . "\n"
//                . " /clientEmail- " . $clientEmail . "\n"
//                . " /clientPhone- " . $clientPhone
//            );
//
        $orderDate =  1421412898;
//        $currentTimestampMillis = round(microtime(true) * 1000);
//        dd( "Поточний час в мілісекундах: " . $currentTimestampMillis);

        $params = [
            "merchantAccount" => $merchantAccount,
            "merchantDomainName" => "m.easy-order-taxi.site",
            "orderReference" => $orderReference,
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1]
        ];
//dd($params);

//        $merchantAccount = "test_merch_n1";
//        $secretKey = "flk3409refn54t54t*FNJRET";

        $params = [
            "merchantAccount" => $merchantAccount,
            "merchantAuthType" => "SimpleSignature",
            "merchantDomainName" => "m.easy-order-taxi.site",
            "merchantTransactionSecureType" => "AUTO",
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey),
            "apiVersion" => 1,
            "language" => $language,
//            "returnUrl" => "http://returnUrl.com",
            "serviceUrl" => "http://serviceUrl.com",
            "orderReference" => $orderReference,
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "holdTimeout" => 60,
//            "recToken" => "recToken",

            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1],
            "paymentSystems" => "card;privat24",
            "clientAccountId" => $clientEmail,
            "clientEmail" => $clientEmail,
            "clientPhone" => $clientPhone
        ];

// Відправлення POST-запиту
        $response = Http::post('https://secure.wayforpay.com/pay', $params);

        if ($response->successful()) {
            // Отримуємо URL з тіла відповіді
            $url = 'https://secure.wayforpay.com/pay'; // Змініть на фактичний URL, якщо він різний

            // Перенаправлення користувача за URL
            return new RedirectResponse($url);
        } else {
            // Обробка помилки, якщо перенаправлення неможливе
            // Виводимо повідомлення про помилку
            dd('Неможливо перенаправити користувача на сторінку оплати');
        }


//        return ["response" => $responseData, "errorCode" =>$response->status()];

    }

    private function generateHmacMd5Signature($params, $secretKey)
    {
        // Формуємо рядок, який підлягає підпису
        if (isset($params['merchantAccount'])) {
            if ($params['amount'] != 0) {
                $signatureString = implode(';', [
                    $params['merchantAccount'],
                    $params['merchantDomainName'],
                    $params['orderReference'],
                    $params['orderDate'],
                    $params['amount'],
                    $params['currency'],
                ]);
                foreach ($params['productName'] as $index => $productName) {
                    $signatureString .= ';' . $productName . ';' . $params['productCount'][$index] . ';' . $params['productPrice'][$index];
                }
            } else {
                $signatureString = implode(';', [
                    $params['merchantAccount'],
                    $params['merchantDomainName'],
                    $params['orderReference'],
                    $params['amount'],
                    $params['currency']
                ]);
            }
        } else {
            $signatureString = implode(';', [
                $params['orderReference'],
                $params['status'],
                $params['time']
            ]);
        }

        // Генеруємо HMAC_MD5 контрольний підпис
        return hash_hmac('md5', $signatureString, $secretKey);
    }
}
