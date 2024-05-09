<?php

namespace App\Http\Controllers;

use App\Models\City_PAS1;
use App\Models\City_PAS2;
use App\Models\City_PAS4;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WfpController extends Controller
{
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

        $signature = self::generateHmacMd5Signature($params, $secretKey, "serviceUrl");

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
    public function createInvoice(
        $application,
        $city,
        $orderReference,
        $amount,
        $language,
        $productName,
        $clientEmail,
        $clientPhone
    ): Response {
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
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "createInvoice"),
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


    public function verify(
        $application,
        $city,
        $orderReference,
        $clientEmail,
        $clientPhone
    ): Response {
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
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "verify"),
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
        $response = Http::post('https://secure.wayforpay.com/verify?behavior=offline', $params);

        Log::debug("verify: ", $response);
        return $response;
    }

    public function checkStatus(
        $application,
        $city,
        $orderReference
    ): Response {
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
            "orderReference" => $orderReference,
        ];
//dd($params);

//        $merchantAccount = "test_merch_n1";
//        $secretKey = "flk3409refn54t54t*FNJRET";

        $params = [
            "transactionType" => "CHECK_STATUS",
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "checkStatus"),
            "apiVersion" => 1,
        ];

// Відправлення POST-запиту
        $response = Http::post('https://api.wayforpay.com/api', $params);
        Log::debug($response);
        return $response;
    }

    public function refund(
        $application,
        $city,
        $orderReference,
        $amount
    ): Response {
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
        $params = [
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
            "amount" => $amount,
            "currency" => "UAH",
        ];
//dd($params);

//        $merchantAccount = "test_merch_n1";
//        $secretKey = "flk3409refn54t54t*FNJRET";

        $params = [
            "transactionType" => "REFUND",
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
            "amount" => $amount,
            "comment" => "Повернення платежу",
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "refund"),
            "apiVersion" => 1
        ];

// Відправлення POST-запиту
        $response = Http::post('https://api.wayforpay.com/api', $params);
        Log::debug("CHECK_STATUS:" . $response);

        return $response;
    }


    public function settle(
        $application,
        $city,
        $orderReference,
        $amount
    ): Response {
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
        $params = [
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
            "amount" => $amount,
            "currency" => "UAH",
        ];
//dd($params);

//        $merchantAccount = "test_merch_n1";
//        $secretKey = "flk3409refn54t54t*FNJRET";

        $params = [
            "transactionType" => "SETTLE",
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
            "amount" => $amount,
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "settle"),
            "apiVersion" => 1
        ];

// Відправлення POST-запиту
        $response = Http::post('https://api.wayforpay.com/api', $params);
        Log::debug("SETTLE:" . $response);

        return $response;
    }


    public function purchase(
        $application,
        $city,
        $orderReference,
        $amount,
        $productName,
        $recToken
    ) {
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
            "recToken" => $recToken,
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1]
        ];
//dd($params);

//        $merchantAccount = "test_merch_n1";
//        $secretKey = "flk3409refn54t54t*FNJRET";

        $params = [
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "purchase"),
            "merchantAuthType" => "SimpleSignature",
            "merchantDomainName" => "m.easy-order-taxi.site",
            "merchantTransactionSecureType" => "AUTO",
            "apiVersion" => 1,
            "returnUrl" => "https://m.easy-order-taxi.site/wfp/returnUrl",
            "serviceUrl" => "https://m.easy-order-taxi.site/wfp/serviceUrl",
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "recToken" => "recToken",
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1],
            "paymentSystems" => "card;privat24",
        ];

// Відправлення POST-запиту
        $response = Http::post('https:https://secure.wayforpay.com/pay', $params);
        Log::debug("purchase: ", $response);
        return $response;
    }

    private function generateHmacMd5Signature($params, $secretKey, $type)
    {
        // Формуємо рядок, який підлягає підпису

        switch ($type) {
            case "createInvoice":
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
                break;
            case "serviceUrl":
                $signatureString = implode(';', [
                    $params['orderReference'],
                    $params['status'],
                    $params['time']
                ]);
                break;
            case "verify":
                $signatureString = implode(';', [
                    $params['merchantAccount'],
                    $params['merchantDomainName'],
                    $params['orderReference'],
                    $params['amount'],
                    $params['currency']
                ]);
                break;
            case "checkStatus":
                $signatureString = implode(';', [
                    $params['merchantAccount'],
                    $params['orderReference'],
                ]);
                break;
            case "refund":
            case "settle":
                $signatureString = implode(';', [
                    $params['merchantAccount'],
                    $params['orderReference'],
                    $params['amount'],
                    $params['currency']
                ]);
                break;
            case "purchase":
                $signatureString = implode(';', [
                    $params['merchantAccount'],
                    $params['merchantDomainName'],
                    $params['orderReference'],
                    $params['orderDate'],
                    $params['amount'],
                    $params['currency'],
                    $params['recToken'],
                ]);
                foreach ($params['productName'] as $index => $productName) {
                    $signatureString .= ';' . $productName . ';' . $params['productCount'][$index] . ';' . $params['productPrice'][$index];
                }
                break;
        }


        // Генеруємо HMAC_MD5 контрольний підпис
        return hash_hmac('md5', $signatureString, $secretKey);
    }
}
