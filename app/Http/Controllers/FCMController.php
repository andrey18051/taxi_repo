<?php

namespace App\Http\Controllers;

use App\Models\Orderweb;
use App\Models\UserTokenFmsS;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Factory;
use Google\Cloud\Firestore\FirestoreClient;

class FCMController extends Controller
{
    public function getUserByEmail($email, $app)
    {
        switch ($app) {
            case "PAS1":
                $firebaseAuth = app('firebase.auth')['app1'];
                break;
            case "PAS2":
                $firebaseAuth = app('firebase.auth')['app2'];
                break;
            default:
                $firebaseAuth = app('firebase.auth')['app4'];
        }
        switch ($app) {
            case "PAS1":
                $firebaseMessaging = app('firebase.messaging')['app1'];
                break;
            case "PAS2":
                $firebaseMessaging = app('firebase.messaging')['app2'];
                break;
            default:
                $firebaseMessaging = app('firebase.messaging')['app4'];
        }
        try {
            $user = $firebaseAuth->getUserByEmail($email);
            return $user;

//            $token = $firebaseMessaging->getDeviceToken($user->uid);
//            return $token;
        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
            return null;
        }
    }


    public function sendNotification($body, $app, $user_id)
    {
        $userToken = UserTokenFmsS::where("user_id", $user_id)->first();

        if ($userToken != null) {
            switch ($app) {
                case "PAS1":
                    $to = $userToken->token_app_pas_1;
                    $firebaseMessaging = app('firebase.messaging')['app1'];
                    break;
                case "PAS2":
                    $to = $userToken->token_app_pas_2;
                    $firebaseMessaging = app('firebase.messaging')['app2'];
                    break;
                default:
                    $to = $userToken->token_app_pas_4;
                    $firebaseMessaging = app('firebase.messaging')['app4'];
            }

            $message = CloudMessage::withTarget('token', $to)
                ->withNotification(Notification::create("Повідомлення", $body))
                ->withData(['key' => 'value']);

            $firebaseMessaging->send($message);

            return response()->json(['message' => 'Notification sent']);
        }

        return response()->json(['message' => 'User token not found'], 404);
    }



    public function readDocumentFromFirestore()
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('users');
            $document = $collection->document('pEePGRVPNNU6IeJexWRwBpohu9q2');

            // Получите снимок документа
            $snapshot = $document->snapshot();

            if ($snapshot->exists()) {
                // Получите данные из документа
                $data = $snapshot->data();
                Log::info("Name: " . $data['name']);
                Log::info("Email: " . $data['email']);
                return $data['name'];
            } else {
                Log::info("Document does not exist!");
                return "Document does not exist!";
            }
        } catch (\Exception $e) {
            Log::error("Error reading document from Firestore: " . $e->getMessage());
            return "Error reading document from Firestore.";
        }
    }


    public function writeDocumentToFirestore($uid)
    {
        // Найти запись в базе данных по $orderId
        Log::info("Order with ID {$uid} ");
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();

        if (!$order) {
            Log::info("Order with ID {$uid} not found.");
            return "Order not found.";
        }

        // Получаем все атрибуты модели в виде массива
        $data = $order->toArray();

        // Проверка и замена 'no_name' на 'Не указано' в user_full_name
        if (isset($data['user_full_name']) && str_contains($data['user_full_name'], 'no_name')) {
            $data['user_full_name'] = 'Не указано';
        } else {
            // Удаление текста внутри скобок и самих скобок, если нет 'no_name'
            if (isset($data['user_full_name'])) {
                $data['user_full_name'] = preg_replace('/\s*\[.*?\]/', '', $data['user_full_name']);
            }
        }
        $data['created_at'] = $order->created_at->toDateTimeString(); // Преобразуем дату в строку
        // Пример: если нужно добавить другие поля или изменить их формат, можно сделать это здесь

        $documentId = $order->id;

        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('orders');
            $document = $collection->document($documentId);

            // Запишите данные в документ
            $document->set($data);

            Log::info("Document successfully written!");
            return "Document successfully written!";
        } catch (\Exception $e) {
            Log::error("Error writing document to Firestore: " . $e->getMessage());
            return "Error writing document to Firestore.";
        }
    }

    public function deleteDocumentFromFirestore($uid)
    {
        // Найти запись в базе данных по $uid
        Log::info("Attempting to delete order with ID {$uid}");
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();

        if (!$order) {
            Log::info("Order with ID {$uid} not found.");
            return "Order not found.";
        }

        $documentId = $order->id;

        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('orders');
            $document = $collection->document($documentId);

            // Удалите документ
            $document->delete();

            Log::info("Document successfully deleted!");
            return "Document successfully deleted!";
        } catch (\Exception $e) {
            Log::error("Error deleting document from Firestore: " . $e->getMessage());
            return "Error deleting document from Firestore.";
        }
    }

    public function deleteDocumentFromFirestoreOrdersTaking($uid)
    {
        // Найти запись в базе данных по $uid
        Log::info("Attempting to delete order with ID {$uid}");
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();

        if (!$order) {
            Log::info("Order with ID {$uid} not found.");
            return "Order not found.";
        }

        $documentId = $order->id;

        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('orders_taking');
            $document = $collection->document($uid);

            // Удалите документ
            $document->delete();

            Log::info("Document successfully deleted!");
            return "Document successfully deleted!";
        } catch (\Exception $e) {
            Log::error("Error deleting document from Firestore: " . $e->getMessage());
            return "Error deleting document from Firestore.";
        }
    }
    public function readDriverInfoFromFirestore($uid)
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('orders_taking');
            $document = $collection->document($uid);

            // Получите снимок документа
            $snapshot = $document->snapshot();

            if ($snapshot->exists()) {
                // Получите данные из документа
                $data = $snapshot->data();
                Log::info("driver_uid: " . $data['driver_uid']);

                $collectionDriver = $firestore->collection('users');
                $documentDriver = $collectionDriver->document($data['driver_uid']);
                $snapshotDriver = $documentDriver->snapshot();
                if ($snapshotDriver->exists()) {
                    $dataDriver = $snapshotDriver->data();
//                    $name = $dataDriver["name"];
//                    $color = $dataDriver["color"];
//                    $model = $dataDriver["model"];
//                    $phoneNumber = $dataDriver["phoneNumber"];
                    Log::info("DataDriver readDriverInfoFromFirestore:", $dataDriver);
                    return $dataDriver;
                } else {
                    Log::info("Document does not exist!");
                    return "Document does not exist!";
                }

            } else {
                Log::info("Document does not exist!");
                return "Document does not exist!";
            }
        } catch (\Exception $e) {
            Log::error("Error reading document from Firestore: " . $e->getMessage());
            return "Error reading document from Firestore.";
        }
    }

    public function deleteOrderTakingDocumentFromFirestore($uid)
    {

        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('orders_taking');
            $document = $collection->document($uid);

            // Удалите документ
            $document->delete();
            (new MessageSentController())->sentDriverUnTakeOrder($uid);



            Log::info("Document successfully deleted!");
            return "Document successfully deleted!";
        } catch (\Exception $e) {
            Log::error("Error deleting document from Firestore: " . $e->getMessage());
            return "Error deleting document from Firestore.";
        }
    }

    public function writeDocumentToHistoryFirestore($uid, $status)
    {
        // Найти запись в базе данных по $orderId
        Log::info("Order with ID {$uid} ");
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();

        if (!$order) {
            Log::info("Order with ID {$uid} not found.");
            return "Order not found.";
        }

        // Получаем все атрибуты модели в виде массива
        $data = $order->toArray();

        // Проверка и замена 'no_name' на 'Не указано' в user_full_name
        if (isset($data['user_full_name']) && str_contains($data['user_full_name'], 'no_name')) {
            $data['user_full_name'] = 'Не указано';
        } else {
            // Удаление текста внутри скобок и самих скобок, если нет 'no_name'
            if (isset($data['user_full_name'])) {
                $data['user_full_name'] = preg_replace('/\s*\[.*?\]/', '', $data['user_full_name']);
            }
        }
        if (isset($data['auto']) && $data['auto'] != null) {
            $storedData = $data["auto"];
            $dataDriver = json_decode($storedData, true);
            $uid = $dataDriver["uid"];
            $data["driver_uid"] = $uid;
        } else {
            $data["driver_uid"] = "";
        }

        $data['created_at'] = $order->created_at->toDateTimeString(); // Преобразуем дату в строку
        // Пример: если нужно добавить другие поля или изменить их формат, можно сделать это здесь

        $data['status'] = $status;

        $documentId = $order->id;

        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('orders_history');
            $document = $collection->document($documentId);

            // Запишите данные в документ
            $document->set($data);

            Log::info("Document successfully written!");
            return "Document successfully written!";
        } catch (\Exception $e) {
            Log::error("Error writing document to Firestore: " . $e->getMessage());
            return "Error writing document to Firestore.";
        }
    }

    /**
     * @throws \Exception
     */
    public function writeDocumentToBalanceFirestore($uid, $uidDriver, $status)
    {
        // Найти запись в базе данных по $orderId
        Log::info("Order with ID {$uid} ");
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();

        if (!$order) {
            Log::info("Order with ID {$uid} not found.");
            return "Order not found.";
        }

        // Получаем все атрибуты модели в виде массива
        $data = $order->toArray();

        // Проверка и замена 'no_name' на 'Не указано' в user_full_name
        if (isset($data['user_full_name']) && str_contains($data['user_full_name'], 'no_name')) {
            $data['user_full_name'] = 'Не указано';
        } else {
            // Удаление текста внутри скобок и самих скобок, если нет 'no_name'
            if (isset($data['user_full_name'])) {
                $data['user_full_name'] = preg_replace('/\s*\[.*?\]/', '', $data['user_full_name']);
            }
        }
        Log::info("data", $data);

        $data["driver_uid"] = $uidDriver;

        // Создаем уникальный идентификатор с меткой времени и случайным числом
        $currentDateTime = Carbon::now();
        $kievTimeZone = new DateTimeZone('Europe/Kiev');
        $dateTime = new DateTime($currentDateTime);
        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        $randomNumber = rand(1000, 9999); // Генерируем случайное число от 1000 до 9999
        $documentId = "{$data["driver_uid"]}_{$currentDateTime}_{$randomNumber}";

        $data['status'] = $status;

        $data['commission'] = 1 + $data["web_cost"] *0.01;


        $data['created_at'] = $formattedTime;



        switch ($status) {
            case "delete":
            case "hold":
                $amountToCurrentBalance = (-1) * $data["commission"];
                break;
            case "return":
                $amountToCurrentBalance = (1) * $data["commission"];
                break;
            default:
                $amountToCurrentBalance =  $data["commission"];

        }
        self::writeDocumentToBalanceCurrentFirestore($uidDriver, $amountToCurrentBalance);
        $data['current_balance'] = self::readDriverBalanceFromFirestore($uidDriver);
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('balance');
            $document = $collection->document($documentId);

            // Запишите данные в документ
            $document->set($data);

            Log::info("Document successfully written!");
            return "Document successfully written!";
        } catch (\Exception $e) {
            Log::error("Error writing document to Firestore: " . $e->getMessage());
            return "Error writing document to Firestore.";
        }
    }

    /**
     * @throws \Exception
     */
    public function writeDocumentToBalanceAddFirestore($uidDriver, $amount, $status)
    {
        // Создаем уникальный идентификатор с меткой времени и случайным числом
        $timestamp = Carbon::now()->format('YmdHis'); // Форматируем текущее время
        $randomNumber = rand(1000, 9999); // Генерируем случайное число от 1000 до 9999
        $documentId = "{$uidDriver}_{$timestamp}_{$randomNumber}";


        $currentDateTime = Carbon::now();
        $kievTimeZone = new DateTimeZone('Europe/Kiev');
        $dateTime = new DateTime($currentDateTime);
        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        $data['driver_uid'] = $uidDriver;
        $data['status'] = $status;
        $data['amount'] = $amount;
        $data['created_at'] = $formattedTime; // Преобразуем дату в строку
        self::writeDocumentToBalanceCurrentFirestore($uidDriver, $amount);
        $data['current_balance'] = self::readDriverBalanceFromFirestore($uidDriver);
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('balance');
            $document = $collection->document($documentId);

            // Запишите данные в документ
            $document->set($data);

            Log::info("Document successfully written!");
            return "Document successfully written!";
        } catch (\Exception $e) {
            Log::error("Error writing document to Firestore: " . $e->getMessage());
            return "Error writing document to Firestore.";
        }
    }

    /**
     * @throws \Exception
     */
    public function writeDocumentToBalanceCurrentFirestore($uidDriver, $amount)
    {
        $currentDateTime = Carbon::now();
        $kievTimeZone = new DateTimeZone('Europe/Kiev');
        $dateTime = new DateTime($currentDateTime);
        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        try {
            // Получаем экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получаем ссылку на коллекцию и документ (идентификатором документа является $uidDriver)
            $collection = $firestore->collection('balance_current');
            $document = $collection->document($uidDriver);

            // Получаем существующий документ
            $snapshot = $document->snapshot();

            $previousAmount = 0;

            // Если документ существует, получаем предыдущее значение amount
            if ($snapshot->exists()) {
                $previousAmount = $snapshot->data()['amount'] ?? 0;
            }

            // Добавляем новое значение к предыдущему
            $newAmount = $previousAmount + $amount;

            $data = [
                'driver_uid' => $uidDriver,
                'amount' => $newAmount,
                'created_at' => $formattedTime, // Преобразуем дату в строку
            ];

            // Записываем или обновляем документ с новым значением
            $document->set($data);

            Log::info("Document successfully written with updated amount!");
            return "Document successfully written with updated amount!";
        } catch (\Exception $e) {
            Log::error("Error writing document to Firestore: " . $e->getMessage());
            return "Error writing document to Firestore.";
        }
    }
    public function readDriverBalanceFromFirestore($uidDriver)
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('balance_current');
            $document = $collection->document($uidDriver);

            // Получите снимок документа
            $snapshot = $document->snapshot();

            if ($snapshot->exists()) {
                // Получите данные из документа
                $data = $snapshot->data();
                $balance = $data['amount'] ?? 0.0;

                // Логирование информации
                Log::info("Driver UID: " . $uidDriver);
                Log::info("Balance: " . number_format($balance, 2, '.', ''));

                return $balance;
            } else {
                Log::info("Document with UID {$uidDriver} does not exist!");
                return "Document does not exist!";
            }
        } catch (\Exception $e) {
            Log::error("Error reading driver balance from Firestore: " . $e->getMessage());
            return "Error reading driver balance from Firestore.";
        }
    }
    public function readUserInfoFromFirestore($uidDriver)
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('users');
            $document = $collection->document($uidDriver);

            // Получите снимок документа
            $snapshot = $document->snapshot();

            if ($snapshot->exists()) {
                // Получите данные из документа
                $data = $snapshot->data();
                Log::info("Name: " . $data['name']);
                Log::info("Email: " . $data['email']);
                return $data;
            } else {
                Log::info("Document does not exist!");
                return "Document does not exist!";
            }
        } catch (\Exception $e) {
            Log::error("Error reading document from Firestore: " . $e->getMessage());
            return "Error reading document from Firestore.";
        }
    }

    public function findUserByEmail($email)
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию
            $collection = $firestore->collection('users');

            // Выполните запрос для поиска по полю email
            $query = $collection->where('email', '=', $email);
            $documents = $query->documents();

            if ($documents->isEmpty()) {
                Log::info("No user found with email: " . $email);
                return "No user found with this email.";
            } else {
                // Поскольку email должен быть уникальным, ожидаем только один документ
                $document = $documents->rows()[0];
                $data = $document->data();
                Log::info("User found: " . json_encode($data));
                return $data;
            }
        } catch (\Exception $e) {
            Log::error("Error finding document from Firestore: " . $e->getMessage());
            return "Error finding document from Firestore.";
        }
    }

    public function saveCardDataToFirestore($uidDriver, $cardData)
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию
            $collection = $firestore->collection('cards');

            // Создайте уникальный документ и сохраните данные
            $documentReference = $collection->add([
                'uidDriver' => $uidDriver,
                'cardData' => $cardData,
                'created_at' => new \DateTime() // Добавьте дату создания, если нужно
            ]);

            Log::info("Card data saved successfully with Document ID: " . $documentReference->id());
        } catch (\Exception $e) {
            Log::error("Error saving card data to Firestore: " . $e->getMessage());
        }
    }




}
