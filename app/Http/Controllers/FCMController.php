<?php

namespace App\Http\Controllers;

use App\Helpers\OpenStreetMapHelper;
use App\Jobs\DeleteOrderPersonal;
use App\Mail\Admin;
use App\Mail\DriverInfo;
use App\Mail\InfoEmail;
use App\Models\DriverPosition;
use App\Models\Orderweb;
use App\Models\UserTokenFmsS;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Google\Cloud\Core\Exception\GoogleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Factory;
use Google\Cloud\Firestore\FirestoreClient;
use SebastianBergmann\Diff\Exception;


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
            case "PAS4":
                $firebaseAuth = app('firebase.auth')['app4'];
                break;
            default:
                $firebaseAuth = app('firebase.auth')['app5'];
        }
        switch ($app) {
            case "PAS1":
                $firebaseMessaging = app('firebase.messaging')['app1'];
                break;
            case "PAS2":
                $firebaseMessaging = app('firebase.messaging')['app2'];
                break;
            case "PAS4":
                $firebaseMessaging = app('firebase.messaging')['app4'];
                break;
            default:
                $firebaseMessaging = app('firebase.messaging')['app5'];
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
        Log::info("=== Запуск sendNotification ===", [
            'body'    => $body,
            'app'     => $app,
            'user_id' => $user_id,
        ]);

        // Получаем токен пользователя
        $userToken = UserTokenFmsS::where("user_id", $user_id)->first();

        if ($userToken === null) {
            Log::warning("UserTokenFmsS не найден", ['user_id' => $user_id]);
            return response()->json(['message' => 'User token not found'], 404);
        }

        // Определяем токен и Firebase Messaging
        $to = null;
        $firebaseMessaging = null;
        switch ($app) {
            case "PAS1":
                $to = $userToken->token_app_pas_1;
                $firebaseMessaging = app('firebase.messaging')['app1'] ?? null;
                Log::info("Выбран PAS1", ['token' => $to]);
                break;
            case "PAS2":
                $to = $userToken->token_app_pas_2;
                $firebaseMessaging = app('firebase.messaging')['app2'] ?? null;
                Log::info("Выбран PAS2", ['token' => $to]);
                break;
            case "PAS4":
                $to = $userToken->token_app_pas_4;
                $firebaseMessaging = app('firebase.messaging')['app4'] ?? null;
                Log::info("Выбран PAS4", ['token' => $to]);
                break;
            default:
                $to = $userToken->token_app_pas_5;
                $firebaseMessaging = app('firebase.messaging')['app5'] ?? null;
                Log::info("Выбран PAS5 (default)", ['token' => $to]);
        }

        // Логируем все токены пользователя
        Log::info("Все токены пользователя", [
            'token_app_pas_1' => $userToken->token_app_pas_1,
            'token_app_pas_2' => $userToken->token_app_pas_2,
            'token_app_pas_4' => $userToken->token_app_pas_4,
            'token_app_pas_5' => $userToken->token_app_pas_5,
        ]);

        // Проверяем токен
        if (empty($to)) {
            Log::warning("Токен пустой для пользователя", ['user_id' => $user_id, 'app' => $app]);
            return response()->json(['message' => 'Empty token for user'], 400);
        }

        // Логируем конфигурацию firebase.messaging
        Log::info("Firebase messaging конфиг", [
            'config_keys' => array_keys(app('firebase.messaging')),
        ]);

        if ($firebaseMessaging === null) {
            Log::error("Firebase Messaging не настроен для app", ['app' => $app]);
            return response()->json(['message' => 'Firebase messaging config not found'], 500);
        }

        // Формируем сообщение
        $dataPayload = ['key' => 'value'];

        $message = CloudMessage::withTarget('token', $to)
            ->withNotification(Notification::create("Повідомлення", $body))
            ->withData($dataPayload);

        Log::info("Формируем CloudMessage", [
            'token'        => $to,
            'notification' => ['title' => "Повідомлення", 'body' => $body],
            'data'         => $dataPayload
        ]);

        // Пытаемся отправить
        try {
            $firebaseMessaging->send($message);
            Log::info("Уведомление успешно отправлено", [
                'user_id' => $user_id,
                'token'   => $to
            ]);

            return response()->json(['message' => 'Notification sent']);
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            Log::warning("FCM токен недействителен (NotFound)", [
                'user_id' => $user_id,
                'token'   => $to,
                'error'   => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Invalid FCM token',
                'error'   => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error("Ошибка при отправке уведомления", [
                'user_id' => $user_id,
                'token'   => $to,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to send notification',
                'error'   => $e->getMessage()
            ], 500);
        }
    }




    public function sendNotificationAuto($body, $app, $user_id, $uid)
    {
        Log::info("=== Запуск sendNotificationAuto ===", [
            'body'    => $body,
            'app'     => $app,
            'user_id' => $user_id,
            'uid'     => $uid,
        ]);

        // Получаем токен пользователя
        $userToken = UserTokenFmsS::where("user_id", $user_id)->first();

        if ($userToken === null) {
            Log::warning("UserTokenFmsS не найден", ['user_id' => $user_id]);
            return response()->json(['message' => 'User token not found'], 404);
        }

        Log::info("Найден UserTokenFmsS", ['user_id' => $user_id]);

        // Определяем токен и firebaseMessaging
        $to = null;
        $firebaseMessaging = null;

        switch ($app) {
            case "PAS1":
                $to = $userToken->token_app_pas_1;
                $firebaseMessaging = app('firebase.messaging')['app1'] ?? null;
                Log::info("Выбран PAS1", ['token' => $to]);
                break;
            case "PAS2":
                $to = $userToken->token_app_pas_2;
                $firebaseMessaging = app('firebase.messaging')['app2'] ?? null;
                Log::info("Выбран PAS2", ['token' => $to]);
                break;
            case "PAS4":
                $to = $userToken->token_app_pas_4;
                $firebaseMessaging = app('firebase.messaging')['app4'] ?? null;
                Log::info("Выбран PAS4", ['token' => $to]);
                break;
            default:
                $to = $userToken->token_app_pas_5;
                $firebaseMessaging = app('firebase.messaging')['app5'] ?? null;
                Log::info("Выбран PAS5 (default)", ['token' => $to]);
        }

        // Проверяем токен
        if (empty($to)) {
            Log::error("Токен пустой для пользователя", [
                'user_id' => $user_id,
                'app'     => $app
            ]);
            return response()->json(['message' => 'Empty token for user'], 400);
        }

        // Проверяем конфигурацию firebase.messaging
        if ($firebaseMessaging === null) {
            Log::error("FirebaseMessaging не настроен для приложения", ['app' => $app]);
            return response()->json(['message' => 'Firebase messaging config not found'], 500);
        }

        // Формируем сообщение
        $dataPayload = [
            'message_uk' => 'Знайдено авто: ' . $body,
            'message_en' => 'Found car: ' . $body,
            'message_ru' => 'Найдено авто: ' . $body,
            'uid'        => $uid,
        ];

        Log::info("Формируем CloudMessage", ['data' => $dataPayload]);

        try {
            $message = CloudMessage::withTarget('token', $to)
                ->withData($dataPayload);

            // Отправляем уведомление
            $firebaseMessaging->send($message);
            Log::info("Уведомление успешно отправлено", [
                'token' => $to,
                'uid'   => $uid
            ]);

            return response()->json(['message' => 'Notification sent']);
        } catch (\Exception $e) {
            Log::error("Ошибка при отправке уведомления", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'token' => $to
            ]);
            return response()->json([
                'message' => 'Failed to send notification',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function sendNotificationOrderCost($orderCost, $app, $user_id)
    {
        Log::info("=== Запуск sendNotificationOrderCost ===", [
            'order_cost' => $orderCost,
            'app'        => $app,
            'user_id'    => $user_id
        ]);

        // 🔹 Получаем токен пользователя
        $userToken = UserTokenFmsS::where("user_id", $user_id)->first();

        if ($userToken === null) {
            Log::warning("UserTokenFmsS не найден", ['user_id' => $user_id]);
            return response()->json(['message' => 'User token not found'], 404);
        }

        Log::info("Найден UserTokenFmsS", ['user_id' => $user_id]);

        // 🔹 Определяем токен и FirebaseMessaging
        $to = null;
        $firebaseMessaging = null;
        $tokenField = null;

        switch ($app) {
            case "PAS1":
                $to = $userToken->token_app_pas_1;
                $firebaseMessaging = app('firebase.messaging')['app1'] ?? null;
                $tokenField = 'token_app_pas_1';
                Log::info("Выбран PAS1", ['token' => $to]);
                break;

            case "PAS2":
                $to = $userToken->token_app_pas_2;
                $firebaseMessaging = app('firebase.messaging')['app2'] ?? null;
                $tokenField = 'token_app_pas_2';
                Log::info("Выбран PAS2", ['token' => $to]);
                break;

            case "PAS4":
                $to = $userToken->token_app_pas_2;
                $firebaseMessaging = app('firebase.messaging')['app4'] ?? null;
                $tokenField = 'token_app_pas_4';
                Log::info("Выбран PAS4", ['token' => $to]);
                break;

            default:
                $to = $userToken->token_app_pas_5;
                $firebaseMessaging = app('firebase.messaging')['app5'] ?? null;
                $tokenField = 'token_app_pas_5';
                Log::info("Выбран PAS5 (default)", ['token' => $to]);
                break;
        }

        // 🔹 Проверяем наличие токена
        if (empty($to)) {
            Log::warning("Пустой токен для пользователя", [
                'user_id' => $user_id,
                'app'     => $app
            ]);
            return response()->json(['message' => 'Empty token for user'], 400);
        }

        // 🔹 Проверяем конфигурацию FirebaseMessaging
        if ($firebaseMessaging === null) {
            Log::error("FirebaseMessaging не настроен для приложения", ['app' => $app]);
            return response()->json(['message' => 'Firebase messaging config not found'], 500);
        }

        // 🔹 Формируем сообщение
        $dataPayload = [
            'order_cost' => (string)$orderCost,
        ];

        Log::info("Формируем CloudMessage", ['data' => $dataPayload]);

        try {
            $message = CloudMessage::withTarget('token', $to)
                ->withData($dataPayload);

            // 🔹 Отправляем уведомление
            $firebaseMessaging->send($message);

            Log::info("Уведомление успешно отправлено", [
                'user_id' => $user_id,
                'token'   => $to,
            ]);

            return response()->json(['message' => 'Notification sent']);

        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            // 🔸 Невалидный токен (удален, устарел, другой проект)
            Log::warning("Невалидный Firebase токен", [
                'user_id' => $user_id,
                'app'     => $app,
                'token'   => $to,
                'reason'  => $e->getMessage(),
            ]);

            // 🔹 Очищаем токен в базе, чтобы не использовать его повторно
            $userToken->update([$tokenField => null]);

            return response()->json([
                'message' => 'Invalid or expired Firebase token cleared',
            ], 410);

        } catch (\Exception $e) {
            Log::error("Ошибка при отправке уведомления", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'token' => $to
            ]);

            return response()->json([
                'message' => 'Failed to send notification',
                'error'   => $e->getMessage()
            ], 500);
        }
    }



    public function readDocumentFromUsersFirestore($uidDriver)
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

    public function readFullUsersFirestore($uidDriver)
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
    public function writeDocumentToFirestore($uid)
    {
        // Найти запись в базе данных по $orderId
        Log::info("Order with ID {$uid} ");
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();

        if (!$order) {
            Log::info("Order with ID {$uid} not found.");
            return "Order not found.";
        }

        $nearestDriver = (new UniversalAndroidFunctionController)->findDriverInSector(
            (float) $order->startLat,
            (float) $order->startLan
        );
        $order->city = (new UniversalAndroidFunctionController)->findCity($order->startLat, $order->startLan);
        $order->save();


        $osrmHelper = new OpenStreetMapHelper();
        $routeDistance = round(
            $osrmHelper->getRouteDistance(
                (float) $order->startLat,
                (float) $order->startLan,
                (float) $order->to_lat,
                (float) $order->to_lng
            ) / 1000,
            2 // Округляем до 2 знаков после запятой
        );

        $order->rout_distance = $routeDistance;
        $order->save();


//        $verifyRefusal = self::verifyRefusal($order->id, $nearestDriver['driver_uid']);
        $verifyRefusal = (new UniversalAndroidFunctionController())->verifyRefusal($uid, $nearestDriver['driver_uid']);

        Log::info("DriverController verifyRefusal $verifyRefusal");
        if ($nearestDriver['driver_uid'] !== null && !$verifyRefusal) { //проверяем есть ли ближайший водитель и не отказывался ли он от заказа
            self::writeDocumentToOrdersPersonalDriverToFirestore($order, $nearestDriver['driver_uid']);
        } else {
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
            $data['created_at'] = self::currentKievDateTime(); // Преобразуем дату в строку
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
                Log::info('[writeDocumentToFirestore] Документ успешно создан в Firestore', ['documentId' => $documentId]);

                return "Document successfully written!";
            } catch (\Exception $e) {
                Log::error("1 Error writing document to Firestore: " . $e->getMessage());
                return "Error writing document to Firestore.";
            }
        }
    }

    function checkOrderExists(string $documentId): bool
    {
//        $order = Orderweb::find($documentId);
//
//        $uid = (new MemoryOrderChangeController)->show($uid);
        try {
            // Получаем Firestore
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Берем документ по id
            $document = $firestore->collection('orders')->document($documentId);

            // Проверка
            $snapshot = $document->snapshot();
            if ($snapshot->exists()) {
                Log::info("Документ {$documentId} существует.");
                return true;
            } else {
                Log::info("Документ {$documentId} не найден.");
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Ошибка проверки документа Firestore: " . $e->getMessage());
            return false;
        }
    }

    function checkOrdersTaking(string $documentId): bool
    {
        try {
            // Получаем Firestore
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            $collection = $firestore->collection('orders_taking');
            $document   = $collection->document($documentId);
            $snapshot   = $document->snapshot();

            if (!$snapshot->exists()) {
                Log::info("[checkOrdersTaking] Документ не найден", [
                    'documentId' => $documentId
                ]);
                return false;
            }

            $data = $snapshot->data();
            $uid  = $data['uid'] ?? null;

            if ($uid === null) {
                Log::warning("[checkOrdersTaking] Документ найден, но поле uid отсутствует", [
                    'documentId' => $documentId
                ]);
                return false;
            }

            if ($uid === $documentId) {
                Log::info("[checkOrdersTaking] Документ найден, поле uid совпадает с ID", [
                    'documentId' => $documentId,
                    'uid' => $uid
                ]);
                return true;
            } else {
                Log::info("[checkOrdersTaking] Документ найден, но uid НЕ совпадает с ID", [
                    'documentId' => $documentId,
                    'uid' => $uid
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error("[checkOrdersTaking] Ошибка проверки документа Firestore: " . $e->getMessage(), [
                'documentId' => $documentId
            ]);
            return false;
        }
    }

    public function checkOrdersSector($uid): bool
    {
        Log::info('[checkOrdersSector] start', ['dispatching_order_uid' => $uid]);

        // 1) Находим локальный Orderweb по dispatching_order_uid
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();
        if (!$order) {
            Log::warning('[checkOrdersSector] Orderweb не найден по dispatching_order_uid', [
                'dispatching_order_uid' => $uid
            ]);
            return false;
        }

        $documentId = (string) $order->id;
        Log::info('[checkOrdersSector] Найден Orderweb', [
            'dispatching_order_uid' => $uid,
            'order_id' => $documentId
        ]);

        try {
            // 2) Проверяем наличие переменной окружения (не логируем её содержимое)
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            if (empty($serviceAccountPath)) {
                Log::error('[checkOrdersSector] FIREBASE_CREDENTIALS_DRIVER_TAXI не установлен', [
                    'dispatching_order_uid' => $uid,
                    'order_id' => $documentId
                ]);
                return false;
            }
            Log::info('[checkOrdersSector] Есть значение для service account (не раскрываем путь)', [
                'has_service_account' => true
            ]);

            // 3) Подключаемся к Firestore и берем документ
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();
            $collection = $firestore->collection('orders_personal');

            Log::info('[checkOrdersSector] Запрос документа в Firestore', [
                'collection' => 'orders_personal',
                'documentId' => $documentId
            ]);

            $document = $collection->document($documentId);
            $snapshot = $document->snapshot();

            if (!$snapshot->exists()) {
                Log::info('[checkOrdersSector] Документ в Firestore не найден', [
                    'collection' => 'orders_personal',
                    'documentId' => $documentId
                ]);
                return false;
            }

            // 4) Документ найден — логируем ключи (чтобы не сливать данные)
            $data = $snapshot->data();
            Log::info('[checkOrdersSector] Документ найден в Firestore', [
                'collection' => 'orders_personal',
                'documentId' => $documentId,
                'data_keys' => is_array($data) ? array_keys($data) : []
            ]);

//            // 5) Доп. проверка поля uid внутри документа — логируем совпадение
//            $docUid = $data['uid'] ?? null;
//            if ($docUid === null) {
//                Log::warning('[checkOrdersSector] В документе отсутствует поле uid', [
//                    'documentId' => $documentId
//                ]);
//            } else {
//                $matches = ($docUid === $uid);
//                Log::info('[checkOrdersSector] Поле uid в документе', [
//                    'documentId' => $documentId,
//                    // не рекомендуется логировать полное содержимое uid в prod, но здесь для отладки оставляю:
//                    'doc_uid' => $docUid,
//                    'dispatching_order_uid' => $uid,
//                    'uid_matches' => $matches
//                ]);
//            }

            // Поведение сохраняем прежним: возвращаем true если документ существует
            return true;

        } catch (\Exception $e) {
            Log::error('[checkOrdersSector] Ошибка при обращении к Firestore', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'documentId' => $documentId,
                'dispatching_order_uid' => $uid
            ]);
            return false;
        }
    }


    public function writeDocumentToOrdersPersonalDriverToFirestore($order, $driver_uid)
    {
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
        $data['created_at'] = self::currentKievDateTime();// Преобразуем дату в строку


//

        $data['driver_uid'] = $driver_uid;

        $documentId = $order->id;

        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            Log::info("Путь к Firebase учетным данным: " . $serviceAccountPath);

            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('orders_personal');
            $document = $collection->document($documentId);

            // Запишите данные в документ
            $document->set($data);

            sleep(20);
            (new FCMController)->deleteOrderPersonalDocumentFromFirestore($order->dispatching_order_uid, $driver_uid);

            Log::info("Document successfully written!");
            return "Document successfully written!";
        } catch (\Exception $e) {
            Log::error("2 Error writing document to Firestore: " . $e->getMessage());
            return "Error writing document to Firestore.";
        }
    }


    public function toggleFirestoreBlackListEmail(string $email, string $action, string $appCode): JsonResponse
    {
        try {
            // Выбор нужного файла учетных данных Firebase по коду приложения
            $credentialsMap = [
                'PAS1' => env('FIREBASE_CREDENTIALS_PAS_1'),
                'PAS2' => env('FIREBASE_CREDENTIALS_PAS_2'),
                'PAS4' => env('FIREBASE_CREDENTIALS_PAS_4'),
            ];

            if (!isset($credentialsMap[$appCode])) {
                return response()->json([
                    'success' => false,
                    'message' => "Неизвестный код приложения: $appCode"
                ], 400);
            }

            $serviceAccountPath = $credentialsMap[$appCode];
            Log::info("Firebase credentials [$appCode]: $serviceAccountPath");

            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            $normalizedEmail = strtolower(trim($email));
            $docId = md5($normalizedEmail); // безопасный ID (если email содержит спец. символы)
            $document = $firestore->collection('blackList')->document($docId);

            if ($action === 'add') {
                $document->set([
                    'email' => $normalizedEmail,
                    'created_at' => now()->toDateTimeString()
                ]);
                Log::info("[$appCode] Email добавлен в blackList: $normalizedEmail");

                return response()->json(['success' => true, 'message' => 'Email добавлен в черный список.']);
            }

            if ($action === 'remove') {
                $document->delete();
                Log::info("[$appCode] Email удален из blackList: $normalizedEmail");

                return response()->json(['success' => true, 'message' => 'Email удален из черного списка.']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Недопустимое действие. Разрешены: add, remove.'
            ], 400);
        } catch (\Exception $e) {
            Log::error("Ошибка Firestore blackList [$appCode][$action]: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при работе с Firestore blackList.'
            ], 500);
        }
    }

    public function checkEmailInAllFirestoreBlackLists(string $email): JsonResponse
    {
        try {
            $credentialsMap = [
                'PAS1' => env('FIREBASE_CREDENTIALS_PAS_1'),
                'PAS2' => env('FIREBASE_CREDENTIALS_PAS_2'),
                'PAS4' => env('FIREBASE_CREDENTIALS_PAS_4'),
                'PAS5' => env('FIREBASE_CREDENTIALS_PAS_5'),
            ];

            $fieldMap = [
                'PAS1' => 'black_list_PAS1',
                'PAS2' => 'black_list_PAS2',
                'PAS4' => 'black_list_PAS4',
                'PAS5' => 'black_list_PAS5',
            ];

            $normalizedEmail = strtolower(trim($email));
            $docId = md5($normalizedEmail);

            // Найти пользователя по email
            $user = \App\Models\User::where('email', $normalizedEmail)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => "Пользователь с email [$normalizedEmail] не найден.",
                ], 404);
            }

            $results = [];

            foreach ($credentialsMap as $code => $serviceAccountPath) {
                try {
                    $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
                    $firestore = $firebase->createFirestore()->database();

                    $document = $firestore->collection('blackList')->document($docId);
                    $snapshot = $document->snapshot();
                    $exists = $snapshot->exists();
                    $resultText = $exists ? 'true' : 'false';

                    $fieldName = $fieldMap[$code];
                    $user->$fieldName = $resultText;

                    $results[$code] = $resultText;
                    Log::info("[$code] Проверка: {$normalizedEmail} => {$resultText}");
                } catch (\Exception $e) {
                    Log::error("Ошибка при проверке [$code]: " . $e->getMessage());
                    $results[$code] = 'error';
                    // В случае ошибки — можно записать 'error' или оставить старое значение
                }
            }

            $user->save();

            return response()->json([
                'success' => true,
                'email' => $normalizedEmail,
                'results' => $results,
                'message' => 'Проверка завершена.',
            ]);
        } catch (\Exception $e) {
            Log::error("Общая ошибка при проверке email в Firestore: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Внутренняя ошибка сервера.',
            ], 500);
        }
    }


    public function deleteDocumentFromFirestore($uid)
    {
        // Найти запись в базе данных по $uid
        $uid = (new MemoryOrderChangeController)->show($uid);
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

            $collection_personal = $firestore->collection('orders_personal');
            $document_personal = $collection_personal->document($documentId);

            // Удалите документ
            $document_personal->delete();
            Log::info("Document successfully deleted!");
            return "Document successfully deleted!";
        } catch (\Exception $e) {
            Log::error("11 Error deleting document from Firestore: " . $e->getMessage());
            return "Error deleting document from Firestore.";
        }
    }

    public function deleteDocumentFromSectorFirestore($uid)
    {
        // Найти запись в базе данных по $uid
        Log::info("Attempting to deleteDocumentFromSectorFirestore order with ID {$uid}");
        $uid = (new MemoryOrderChangeController)->show($uid);
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
            $collection = $firestore->collection('orders_personal');
            $document = $collection->document($documentId);

            // Удалите документ
            $document->delete();

            Log::info("Document successfully deleted!");
            return "Document successfully deleted!";
        } catch (\Exception $e) {
            Log::error("22 Error deleting document from Firestore: " . $e->getMessage());
            return "Error deleting document from Firestore.";
        }
    }

    public function deleteDocumentFromFirestoreOrdersTakingCancel($uid)
    {
        // Найти запись в базе данных по $uid
//        $uid = (new MemoryOrderChangeController)->show($uid);
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
            $snapshot = $document->snapshot();
            if ($snapshot->exists()) {
                // Получаем данные документа
                $data = $snapshot->data();
                if (isset($data['driver_uid'])) {
                    $uidDriver = $data['driver_uid'];
                    Log::info("driver_uid: " . $uidDriver);
                    // Удалите документ
                    $document->delete();

                    $status = "return";

                    self::writeDocumentToBalanceFirestore($uid, $uidDriver, $status);

                    Log::info("Document successfully deleted!");
                    return "Document successfully deleted!";
                } else {
                    Log::warning("Поле 'driver_uid' не найдено в документе.");
                }
            } else {
                Log::warning("Документ с uid: " . $uid . " не найден.");
            }

        } catch (\Exception $e) {
            Log::error("33 Error deleting document from Firestore: " . $e->getMessage());
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
            $snapshot = $document->snapshot();
            if ($snapshot->exists()) {
                // Получаем данные документа
                $data = $snapshot->data();
                if (isset($data['driver_uid'])) {
                    $uidDriver = $data['driver_uid'];
                    (new FCMController)->waitForReturnAndSendDelete($uid, $uidDriver);
                    Log::info("driver_uid: " . $uidDriver);
                } else {
                    Log::warning("Поле 'driver_uid' не найдено в документе.");
                }
            } else {
                Log::warning("Документ с uid: " . $uid . " не найден.");
            }
            // Удалите документ
            $document->delete();


            Log::info("Document successfully deleted!");
            return "Document successfully deleted!";
        } catch (\Exception $e) {
            Log::error("44 Error deleting document from Firestore: " . $e->getMessage());
            return "Error deleting document from Firestore.";
        }
    }

    public function ordersTakingStatus($uid, $status)
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('orders_taking');
            $document = $collection->document($uid);

// Используем set() с параметром merge, чтобы обновить/создать документ
            $document->set([
                'status' => $status
            ], ['merge' => true]);

            Log::info("Document successfully deleted!");
            return "Document successfully deleted!";
        } catch (\Exception $e) {
            Log::error("55 Error deleting document from Firestore: " . $e->getMessage());
            return "Error deleting document from Firestore.";
        }
    }


    public function writeDocumentToVerifyUserFirestore($uidDriver)
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
                $data['verified'] = true;
                // Запишите данные в документ
                $document->set($data);

                Log::info("Document writeDocumentToVerifyUserFirestore  successfully written!");
                return "Document successfully written!";
            } else {
                Log::error("Error writeDocumentToVerifyUserFirestore writing document to Firestore: ");
                return "Error writeDocumentToVerifyUserFirestore  document to Firestore.";
            }
        } catch (\Exception $e) {
            Log::error("3 Error writing document to Firestore: " . $e->getMessage());
            return "Error writeDocumentToVerifyCarFirestore writing document to Firestore.";
        }
    }

    public function writeDocumentToBlockUserFirestore($uidDriver)
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
                $data['blocked'] = true;
                // Запишите данные в документ
                $document->set($data);

                Log::info("Document writeDocumentToBlockUserFirestore  successfully written!");
                return "Document successfully written!";
            } else {
                Log::error("Error writeDocumentToBlockUserFirestore writing document to Firestore: ");
                return "Error writeDocumentToBlockUserFirestore  document to Firestore.";
            }
        } catch (\Exception $e) {
            Log::error("31 Error writing document to Firestore: " . $e->getMessage());
            return "Error writeDocumentToBlockUserFirestore writing document to Firestore.";
        }
    }

    public function writeDocumentToVerifyCarFirestore($carId)
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');

            // Логируем путь к файлу с учетными данными
            Log::info("Путь к файлу учетных данных Firebase: " . $serviceAccountPath);

            if (!file_exists($serviceAccountPath)) {
                Log::error("Файл учетных данных Firebase не найден: " . $serviceAccountPath);
                return "Файл учетных данных Firebase не найден.";
            }

            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);

            if (!$firebase) {
                Log::error("Не удалось создать экземпляр Firebase.");
                return "Не удалось создать экземпляр Firebase.";
            }

            $firestore = $firebase->createFirestore()->database();

            // Логируем информацию о попытке доступа к документу
            Log::info("Попытка доступа к документу с carId: " . $carId);

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('cars');
            $document = $collection->document($carId);

            // Получите снимок документа
            $snapshot = $document->snapshot();

            if ($snapshot->exists()) {
                // Получите данные из документа
                $data = $snapshot->data();
                $data['verified'] = true;

                // Логируем данные, которые будут записаны в документ
                Log::info("Данные для записи в Firestore: " . json_encode($data));

                // Запишите данные в документ
                $document->set($data);

                Log::info("Документ успешно записан в Firestore!");
                return "Документ успешно записан!";
            } else {
                Log::error("Ошибка: документ с carId не существует.");
                return "Ошибка: документ не существует.";
            }
        } catch (\Exception $e) {
            // Логируем сообщение об ошибке и стек вызовов для отладки
            Log::error("Ошибка при записи документа в Firestore: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return "Ошибка при записи документа в Firestore.";
        }
    }


    public function readDriverInfoFromFirestore($uidDriver)
    {

        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();
            $collectionDriver = $firestore->collection('users');

            $documentDriver = $collectionDriver->document($uidDriver);
            $snapshotDriver = $documentDriver->snapshot();
            if ($snapshotDriver->exists()) {
                $dataDriver = $snapshotDriver->data();

                // Получаем доступ к коллекции 'cars'
                $collectionCar = $firestore->collection('cars');

                // Выполняем запрос, чтобы найти документ, где activeCar равно true и driverNumber совпадает с $data['driver_uid']
                $query = $collectionCar
                    ->where('activeCar', '==', true)
                    ->where('driverNumber', '==', $uidDriver);

                // Получаем результаты запроса
                $documents = $query->documents();

                // Проверяем, были ли найдены документы
                if ($documents->isEmpty()) {
                    echo "Нет активных автомобилей для водителя с UID: " . $uidDriver;
                } else {
                    // Получаем первый документ (поскольку ожидаем только один)
                    $document = $documents->rows()[0]; // Получаем первый документ

                    // Получаем данные автомобиля
                    $dataCar = $document->data();

                    // Присваиваем данные автомобиля в массив $dataDriver
                    $dataDriver["brand"] = $dataCar['brand'];
                    $dataDriver["model"] = $dataCar['model'];
                    $dataDriver["number"] = $dataCar['number'];
                    $dataDriver["color"] = $dataCar['color'];
                    // Логируем информацию об автомобиле
                    Log::info("Active Car Info:", $dataCar);
                }
                // Логируем информацию о водителе
                Log::info("DataDriver readDriverInfoFromFirestore:", $dataDriver);
                return $dataDriver;
            } else {
                Log::info("Document does not exist!");
                return "Document does not exist!";

            }
        } catch (\Exception $e) {
            Log::error("Error reading document from Firestore: " . $e->getMessage());
            return "Error reading document from Firestore.";
        }
    }

    public function deleteOrderTakingDocumentFromFirestore($uid, $driver_uid)
    {
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
            $snapshot = $document->snapshot();
            $data = $snapshot->data();
            // Удалите документ
            $document->delete();
            if (!is_null($data)) {
                // Удаление документа
                $document->delete();

//                // Перемещение данных в другую коллекцию
//                $collection = $firestore->collection('orders');
//                $document = $collection->document($documentId);
//                $document->set($data);
//
//
//                $order->auto = "";
//                $order->closeReason = "-1";
//                $order->closeReasonI = "0";
//                $order->save();

                // Обновление данных в коллекции 'orders_refusal'
//                $collection = $firestore->collection('orders_refusal');
//
//                $document = $collection->document($documentId);
//                $data["driver_uid"] = $driver_uid;
//                $document->set($data);

                // Сохраняем на сервере у себя
                (new OrdersRefusalController)->store($driver_uid, $uid);

                // Отправка уведомления водителю
                (new MessageSentController())->sentDriverUnTakeOrder($uid);

                Log::info("Document successfully deleted!");
                return "Document successfully deleted!";
            } else {
                Log::error("Document with UID $uid has no data or doesn't exist.");
                return "Document not found or has no data.";
            }

        } catch (\Exception $e) {
            Log::error("66 Error deleting document from Firestore: " . $e->getMessage());
            return "Error deleting document from Firestore.";
        }
    }

    public function deleteOrderPersonalDocumentFromFirestore($uid, $driver_uid)
    {
        Log::info("1111 Attempting to delete order with UID: {$uid} and driver UID: {$driver_uid}");

        $uid = (new MemoryOrderChangeController)->show($uid);
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();


        if (!$order) {
            Log::error("Order not found for dispatching_order_uid: {$uid}");
            return "Order not found.";
        }

        $status = "orderUnTakingPersonal";


        $documentId = $order->id;
        Log::info("Found order with ID: {$documentId}");

        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            Log::info("Using service account path: {$serviceAccountPath}");
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('orders_personal');
            $document = $collection->document($documentId);
            $snapshot = $document->snapshot();

            // Получите данные из документа
            $data = $snapshot->data();

            if (!is_null($data)) {
                Log::info("Data retrieved from document: ", $data);
                // Удаление документа
                $document->delete();
                Log::info("Document with ID {$documentId} successfully deleted from orders_personal.");
                (new DriverKarmaController)->store($driver_uid, $order->id, $status);
                // Перемещение данных в другую коллекцию
                $collection = $firestore->collection('orders');
                $document = $collection->document($documentId);
                $document->set($data);
                Log::info("Data moved to orders collection for document ID: {$documentId}");

                OrdersRefusalController::store($driver_uid, $uid);

                Log::info("Data moved to orders_refusal for document ID: {$documentId}");

                // Обновление истории заказов
                $collection = $firestore->collection('orders_history');
                $document = $collection->document($documentId);
                $data["status"] = "refusal";
                $data["updated_at"] = self::currentKievDateTime();
                $document->set($data);
                Log::info("Order history updated for document ID: {$documentId}");

                // Отправка уведомления водителю
                (new MessageSentController())->sentDriverUnTakeOrder($uid);
                Log::info("Notification sent to driver for order UID: {$uid}");

                return "Document successfully deleted!";
            } else {
                Log::error("Document with UID {$uid} has no data or doesn't exist.");
                return "Document not found or has no data.";
            }

        } catch (\Exception $e) {
            Log::error("Error deleting document from Firestore: " . $e->getMessage());
            return "Error deleting document from Firestore.";
        }
    }



    /**
     * @throws \Exception
     */
    public function writeDocumentToHistoryFirestore($uid, $status)
    {
        // Найти запись в базе данных по $orderId
//        $uid = (new MemoryOrderChangeController)->show($uid);
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

        $data['created_at'] = self::currentKievDateTime(); // Преобразуем дату в строку
        // Пример: если нужно добавить другие поля или изменить их формат, можно сделать это здесь
// Преобразуем дату в объект Carbon
        $kievTimeZone = new DateTimeZone('Europe/Kiev');

        $dateTime = new DateTime($order->updated_at);
        $dateTime->setTimezone($kievTimeZone);

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
            $data["updated_at"] = self::currentKievDateTime();
            // Запишите данные в документ
            $document->set($data);

            Log::info("Document successfully written!");
            return "Document successfully written!";
        } catch (\Exception $e) {
            Log::error("4 Error writing document to Firestore: " . $e->getMessage());
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


        $randomNumber = rand(1000, 9999); // Генерируем случайное число от 1000 до 9999


        $data['status'] = $status;

        $data['commission'] = 1 + ($data["web_cost"] + $data["add_cost"]) *0.01;


        $data['created_at'] = self::currentKievDateTime();
        $documentId = "{$data["driver_uid"]}_{$data['created_at']}_{$randomNumber}";


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
            Log::error("5 Error writing document to Firestore: " . $e->getMessage());
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

        $data['driver_uid'] = $uidDriver;
        $data['status'] = $status;
        $data['amount'] = $amount;
        $data['created_at'] = self::currentKievDateTime(); // Преобразуем дату в строку
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
            Log::error(" 6 Error writing document to Firestore: " . $e->getMessage());
            return "Error writing document to Firestore.";
        }
    }

    /**
     * @throws \Exception
     */
    public function calculateTimeToStart($uid)
    {

        $uid = (new MemoryOrderChangeController)->show($uid);
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();



        $currentDateTime = Carbon::now(); // Получаем текущее время
        $kievTimeZone = new DateTimeZone('Europe/Kiev'); // Создаем объект временной зоны для Киева
        $dateTime = new DateTime($currentDateTime->format('Y-m-d H:i:s')); // Создаем объект DateTime
        $dateTime->setTimezone($kievTimeZone); // Устанавливаем временную зону на Киев

        $storedData = $orderweb->auto;

        $dataDriver = json_decode($storedData, true);

        $driver_uid = $dataDriver["uid"];
        $driverPositions = DriverPosition::where('driver_uid', $driver_uid)->first();

        $driver_latitude = $driverPositions['latitude'];
        $driver_longitude = $driverPositions['longitude'];
        Log::info("Driver location: lat=$driver_latitude, lon=$driver_longitude");

        if ($driver_latitude !== null && $driver_longitude !== null) {
            $start_point_latitude = $orderweb->startLat;
            $start_point_longitude = $orderweb->startLan;

            $osrmHelper = new OpenStreetMapHelper();
            $driverDistance = round(
                $osrmHelper->getRouteDistance(
                    (float)$driver_latitude,
                    (float)$driver_longitude,
                    (float)$start_point_latitude,
                    (float)$start_point_longitude
                ) / 1000,
                2 // Округляем до 2 знаков после запятой
            );

            Log::info("driverDistance: " . $driverDistance);

            // Скорость водителя (60 км/ч)
            $speed = 60;
            // Расчет времени в минутах
            $minutesToAdd = max(1, round(($driverDistance / $speed) * 60, 0));

            Log::info("minutesToAdd: " . $minutesToAdd);
            $dateTime->modify("+{$minutesToAdd} minutes");

            $orderweb->time_to_start_point = $dateTime->format('Y-m-d H:i:s');
            $orderweb->save();

            Log::info("Updated time_to_start_point: " . $orderweb->time_to_start_point);
            Log::info("Document successfully written!");
        }

        return "calculateTimeToStart Document successfully written!";


//        try {
//            // Получаем путь к сервисному аккаунту Firebase из .env
//            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
//            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
//            $firestore = $firebase->createFirestore()->database();
//
//            // Получаем документ из коллекции 'orders_taking'
//            $collection = $firestore->collection('orders_taking');
//            $document = $collection->document($uid);
//            $snapshot = $document->snapshot();
//
//            if (!$snapshot->exists()) {
//                Log::error("Firestore document 'orders_taking/$uid' does not exist.");
//                return "Document not found in Firestore.";
//            }
//
//            $data = $snapshot->data();
//            Log::info("Snapshot data: " . json_encode($data));
//
//            // Проверяем наличие driver_uid в документе
//            if (!isset($data['driver_uid']) || empty($data['driver_uid'])) {
//                Log::error("Missing or empty driver_uid in Firestore document.");
//                return "Driver UID is missing or empty.";
//            }
//
//            $uidDriver = $data['driver_uid'];
//            Log::info("uidDriver: " . $uidDriver);
//
//            // Получаем документ из коллекции 'sector'
//            $sectorDoc = $firestore->collection('sector')->document($uidDriver);
//            $sectorSnapshot = $sectorDoc->snapshot();
//
//            if (!$sectorSnapshot->exists()) {
//                Log::error("Firestore document 'sector/$uidDriver' does not exist.");
//                return "Sector document not found.";
//            }
//
//            $sectorData = $sectorSnapshot->data();
//            if (!isset($sectorData['latitude'], $sectorData['longitude'])) {
//                Log::error("Missing latitude or longitude in sector document.");
//                return "Sector document is missing location data.";
//            }
//
//            $driver_latitude = $sectorData['latitude'];
//            $driver_longitude = $sectorData['longitude'];
//            Log::info("Driver location: lat=$driver_latitude, lon=$driver_longitude");
//
//            if ($driver_latitude !== null && $driver_longitude !== null) {
//                $start_point_latitude = $orderweb->startLat;
//                $start_point_longitude = $orderweb->startLan;
//
//                $osrmHelper = new OpenStreetMapHelper();
//                $driverDistance = round(
//                    $osrmHelper->getRouteDistance(
//                        (float)$driver_latitude,
//                        (float)$driver_longitude,
//                        (float)$start_point_latitude,
//                        (float)$start_point_longitude
//                    ) / 1000,
//                    2 // Округляем до 2 знаков после запятой
//                );
//
//                Log::info("driverDistance: " . $driverDistance);
//
//                // Скорость водителя (60 км/ч)
//                $speed = 60;
//                // Расчет времени в минутах
//                $minutesToAdd = max(1, round(($driverDistance / $speed) * 60, 0));
//
//                Log::info("minutesToAdd: " . $minutesToAdd);
//                $dateTime->modify("+{$minutesToAdd} minutes");
//
//                $orderweb->time_to_start_point = $dateTime->format('Y-m-d H:i:s');
//                $orderweb->save();
//
//                Log::info("Updated time_to_start_point: " . $orderweb->time_to_start_point);
//                Log::info("Document successfully written!");
//            }
//
//            return "calculateTimeToStart Document successfully written!";
//        } catch (\Exception $e) {
//            Log::error("calculateTimeToStart Error: " . $e->getMessage());
//            return "Error writing document to Firestore.";
//        }
    }


    public function calculateTimeToStartOffline($uid, $minutesToAdd)
    {
        $uid = (new MemoryOrderChangeController)->show($uid);
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
        $currentDateTime = Carbon::now(); // Получаем текущее время
        $kievTimeZone = new DateTimeZone('Europe/Kiev'); // Создаем объект временной зоны для Киева
        $dateTime = new DateTime($currentDateTime->format('Y-m-d H:i:s')); // Создаем объект DateTime
        $dateTime->setTimezone($kievTimeZone); // Устанавливаем временную зону на Киев

        Log::info("minutesToAdd " . $minutesToAdd);
        // Устанавливаем время прибытия
        $dateTime->modify("+{$minutesToAdd} minutes");
        $orderweb->time_to_start_point = $dateTime->format('Y-m-d H:i:s'); // Сохраняем время в нужном формате

        $orderweb->save();

        Log::info("orderweb->time_to_start_point" . $orderweb->time_to_start_point);
        Log::info("Document successfully written!");
        return "calculateTimeToStart Document successfully written!";
    }
    /**
     * @throws \Exception
     */
    public function writeDocumentToBalanceCaschAddFirestore($uidDriver, $amount, $status)
    {
        // Создаем уникальный идентификатор с меткой времени и случайным числом
        $timestamp = Carbon::now()->format('YmdHis'); // Форматируем текущее время
        $randomNumber = rand(1000, 9999); // Генерируем случайное число от 1000 до 9999
        $documentId = "{$uidDriver}_{$timestamp}_{$randomNumber}";

        $data['driver_uid'] = $uidDriver;
        $data['status'] = $status;
        $data['amount'] = $amount;
        $data['created_at'] = self::currentKievDateTime(); // Преобразуем дату в строку
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
            Log::error(" 7 Error writing document to Firestore: " . $e->getMessage());
            return "Error writing document to Firestore.";
        }
    }


    function currentKievDateTime()
    {
        $currentDateTime = Carbon::now();
        $kievTimeZone = new DateTimeZone('Europe/Kiev');
        $dateTime = new DateTime($currentDateTime);
        $dateTime->setTimezone($kievTimeZone);
        return $dateTime->format('d.m.Y H:i:s');
    }

    function secondsDifference($time1)
    {

        $time2 = self::currentKievDateTime();

// Преобразуем строки в объекты Carbon
        $carbonTime1 = Carbon::createFromFormat('d.m.Y H:i:s', $time1, 'Europe/Kiev');
        $carbonTime2 = Carbon::createFromFormat('d.m.Y H:i:s', $time2, 'Europe/Kiev');

// Вычисляем разницу во времени в секундах
        return $carbonTime1->diffInSeconds($carbonTime2);
    }
    /**
     * @throws \Exception
     */
    public function writeDocumentCurrentSectorLocationFirestore(
        $uidDriver,
        $latitude,
        $longitude
    ) {

        $documentId = $uidDriver;
        Log::info("writeDocumentCurrentSectorLocationFirestore! $documentId");



        $data['driver_uid'] = $uidDriver;
        $data['latitude'] = $latitude;
        $data['longitude'] = $longitude;

        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('sector');
            $document = $collection->document($documentId);
            $data['created_at'] = self::currentKievDateTime();
            // Запишите данные в документ
            $document->set($data);

            Log::info("Document successfully written!");
            return "Document successfully written!";
        } catch (\Exception $e) {
            Log::error("8 Error writing document to Firestore: " . $e->getMessage());
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
            Log::error("9 Error writing document to Firestore: " . $e->getMessage());
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
                Log::info("driver_uid: " . $data['driver_uid']);

                $collectionDriver = $firestore->collection('users');
                $documentDriver = $collectionDriver->document($data['driver_uid']);
                $snapshotDriver = $documentDriver->snapshot();
                if ($snapshotDriver->exists()) {
                    $dataDriver = $snapshotDriver->data();

                    // Получаем доступ к коллекции 'cars'
                    $collectionCar = $firestore->collection('cars');

                    // Выполняем запрос, чтобы найти документ, где activeCar равно true и driverNumber совпадает с $data['driver_uid']
                    $query = $collectionCar
                        ->where('activeCar', '==', true)
                        ->where('driverNumber', '==', $data['driver_uid']);

                    // Получаем результаты запроса
                    $documents = $query->documents();

                    // Проверяем, были ли найдены документы
                    if ($documents->isEmpty()) {
                        echo "Нет активных автомобилей для водителя с UID: " . $data['driver_uid'];
                    } else {
                        // Получаем первый документ (поскольку ожидаем только один)
                        $document = $documents->rows()[0]; // Получаем первый документ

                        // Получаем данные автомобиля
                        $dataCar = $document->data();

                        // Присваиваем данные автомобиля в массив $dataDriver
                        $dataDriver["brand"] = $dataCar['brand'];
                        $dataDriver["model"] = $dataCar['model'];
                        $dataDriver["number"] = $dataCar['number'];
                        $dataDriver["color"] = $dataCar['color'];


                        // Логируем информацию об автомобиле
                        Log::info("Active Car Info:", $dataCar);
                    }

                    // Логируем информацию о водителе
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

    /**
     * Найти ближайшего водителя в секторе из Firestore.
     *
     * @param float $latitude Широта точки
     * @param float $longitude Долгота точки
     * @return array|null Данные ближайшего водителя или null, если не найден
     */
    public function findDriverInSectorFromFirestore(float $latitude, float $longitude): ?array
    {
        Log::info("findDriverInSectorFromFirestore: Starting search for driver in sector.", [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            Log::info("findDriverInSectorFromFirestore: Firebase credentials loaded.", [
                'serviceAccountPath' => $serviceAccountPath,
            ]);

            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию
            $collection = $firestore->collection('sector');
            Log::info("findDriverInSectorFromFirestore: Connected to Firestore sector collection.");

            // Инициализируем переменные для ближайшего водителя
            $nearestDriver = null;
            $nearestDistance = PHP_FLOAT_MAX;

            // Перебираем документы в коллекции
            $snapshot = $collection->documents();
            Log::info("findDriverInSectorFromFirestore: Fetched sector collection documents.", [
                'documents_count' => $snapshot->size(),
            ]);

            foreach ($snapshot as $document) {
                // Получаем данные документа
                $data = $document->data();
                Log::info("findDriverInSectorFromFirestore: Processing document.", [
                    'document_id' => $document->id(),
                    'driver_data' => $data,
                ]);

                // Вычисляем расстояние до водителя
                $driverLatitude = (float)$data['latitude'];
                $driverLongitude = (float)$data['longitude'];

                // Используем OpenStreetMapHelper для вычисления расстояния
                $osrmHelper = new OpenStreetMapHelper();
                if ($driverLatitude != 0 && $driverLongitude !=0) {
                    $distance = $osrmHelper->getRouteDistance(
                        $driverLatitude,
                        $driverLongitude,
                        $latitude,
                        $longitude,
                    );
                    Log::info("findDriverInSectorFromFirestore: Calculated distance to driver.", [
                        'driver_id' => $document->id(),
                        'distance' => $distance,
                    ]);

                    // Если расстояние меньше 3 км и ближе предыдущего, обновляем ближайшего водителя
                    if ($distance !== null && $distance < 3000 && $distance < $nearestDistance) {
                        $nearestDriver = $data;
                        $nearestDistance = $distance;
                        Log::info("findDriverInSectorFromFirestore: Found closer driver.", [
                            'driver_id' => $document->id(),
                            'new_nearest_distance' => $nearestDistance,
                        ]);
                    }
                }
            }

            // Возвращаем данные ближайшего водителя или null, если не найден
            if ($nearestDriver) {
                Log::info("findDriverInSectorFromFirestore: Nearest driver found.", [
                    'nearest_driver' => $nearestDriver,
                    'nearest_distance' => $nearestDistance,
                ]);
            } else {
                Log::info("findDriverInSectorFromFirestore: No driver found within 3km range.");
            }

            return $nearestDriver;
        } catch (\Exception $e) {
            // Обработка ошибок
            Log::error('Error fetching driver from Firestore: ' . $e->getMessage());
            return null;
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

    public function driverAll()
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию
            $collection = $firestore->collection('users');

            // Получаем все документы из коллекции
            $documents = $collection->documents();

            // Проверка на наличие пользователей
            if ($documents->isEmpty()) {
                Log::info("No users found.");
                return "No users found.";
            } else {
                // Инициализируем массив для хранения всех пользователей
                $users = [];

                // Перебираем все документы и извлекаем их данные
                foreach ($documents as $document) {
                    $data = $document->data();
                    Log::info("User found: " . json_encode($data));

                    $collection = $firestore->collection('balance_current');
                    $document_balance_current = $collection->document($data['uid']);
                    // Добавляем данные пользователя в массив

                    $snapshot_balance_current = $document_balance_current->snapshot();
                    $previousAmount = $snapshot_balance_current->data()['amount'] ?? 0;
                    $data["balance_current"] = $previousAmount;
                    $users[] = $data;
                }

                // Возвращаем массив с данными всех пользователей
                return $users;
            }
        } catch (\Exception $e) {
            Log::error("Error retrieving users from Firestore: " . $e->getMessage());
            return "Error retrieving users from Firestore.";
        }
    }
    public function driverAllBalanceRecord()
    {
        try {
            // Получаем экземпляр клиента Firestore
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получаем ссылку на коллекцию
            $collection = $firestore->collection('balance');

            // Получаем все документы из коллекции
            $documents = $collection->documents();

            // Массив для хранения всех записей
            $balanceRecords = [];

            // Перебор всех документов
            foreach ($documents as $document) {
                if ($document->exists()) {
                    // Добавляем данные документа в массив
                    $balanceRecords[] = $document->data();
                }
            }

            // Возвращаем записи в виде массива
            return $balanceRecords;

        } catch (\Exception $e) {
            Log::error("Error retrieving users from Firestore: " . $e->getMessage());
            return []; // Возвращаем пустой массив в случае ошибки
        }
    }



    public function saveCardDataToFirestore($uidDriver, $cardData, $status, $amount)
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию
            $collection = $firestore->collection('cards');

            // Получение значений полей
            $recToken = $cardData['recToken'];
            $maskedCard = $cardData['maskedCard'];

            // Проверка на наличие карты с таким же recToken внутри cardData
            $existingRecTokenDocs = $collection->where('cardData.recToken', '=', $recToken)->documents();

            if (!$existingRecTokenDocs->isEmpty()) {
                // Если запись с таким recToken уже существует, не добавляем новую
                Log::info("Card data not saved because a record with recToken already exists.");
                return;
            }

            // Проверка на наличие карты с такой же маскированной картой (maskedCard) внутри cardData
            $existingMaskedCardDocs = $collection->where('cardData.maskedCard', '=', $maskedCard)->documents();

            if (!$existingMaskedCardDocs->isEmpty()) {
                // Если карта с такой же маской уже существует, обновляем recToken
                foreach ($existingMaskedCardDocs as $doc) {
                    $docReference = $doc->reference();
                    $docReference->update([
                        ['path' => 'cardData.recToken', 'value' => $recToken],
                        ['path' => 'updated_at', 'value' => new \DateTime()] // Обновляем дату изменения
                    ]);

                    Log::info("Card recToken updated successfully for maskedCard: " . $maskedCard);
                }

                (new FCMController)->writeDocumentToBalanceAddFirestore($uidDriver, $amount, $status);
                (new MessageSentController())->sentDriverPayToBalance($uidDriver, $amount);
            } else {
                // Если карта с такой маской не найдена, добавляем новую запись
                $documentReference = $collection->add([
                    'uidDriver' => $uidDriver,
                    'cardData' => $cardData,
                    'created_at' => new \DateTime(), // Добавляем дату создания
                    'updated_at' => new \DateTime()  // Добавляем дату изменения
                ]);

                (new FCMController)->writeDocumentToBalanceAddFirestore($uidDriver, $amount, $status);
                (new MessageSentController())->sentDriverPayToBalance($uidDriver, $amount);

                Log::info("Card data saved successfully with Document ID: " . $documentReference->id());
            }

        } catch (\Exception $e) {
            Log::error("Error saving card data to Firestore: " . $e->getMessage());
        }
    }

    public function deleteDocumentsByDriverUid($uidDriver)
    {
        try {
            // Получаем экземпляр клиента Firestore
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получаем ссылку на коллекцию
            $collection = $firestore->collection('balance');

            // Выполняем запрос для получения всех документов, где driver_uid равен $uidDriver
            $documents = $collection->where('driver_uid', '=', $uidDriver)->documents();

            // Проверяем, есть ли документы для удаления
            if ($documents->isEmpty()) {
                Log::info("No documents found for uidDriver: {$uidDriver}");
                return "No documents found for uidDriver: {$uidDriver}";
            }

            // Удаляем каждый найденный документ
            foreach ($documents as $document) {
                $document->reference()->delete();
            }

            Log::info("All documents for uidDriver: {$uidDriver} have been successfully deleted.");
            return "All documents for uidDriver: {$uidDriver} have been successfully deleted.";
        } catch (\Exception $e) {
            Log::error("Error deleting documents for uidDriver: " . $e->getMessage());
            return "Error deleting documents for uidDriver.";
        }
    }

    /**
     * @throws \Exception
     */
    public function waitForReturnAndSendDelete($uid, $uidDriver)
    {
        if (!self::isHoldCompleted($uid, $uidDriver)) {
            self::writeDocumentToBalanceFirestore($uid, $uidDriver, "delete");
            return "Delete request sent after return completion.";
        } else {
            $maxWaitTime = 30; // Максимальное время ожидания (например, 30 секунд)
            $interval = 1; // Интервал между проверками (1 секунда)
            self::writeDocumentToBalanceFirestore($uid, $uidDriver, "return");
            for ($i = 0; $i < $maxWaitTime; $i++) {
                if (self::isReturnRequestCompleted($uid, $uidDriver)) {
                    Log::info("Return request completed for UID {$uidDriver}. Sending delete request...");
                    sleep($interval);
                    self::writeDocumentToBalanceFirestore($uid, $uidDriver, "delete");
                    return "Delete request sent after return completion.";
                } else {
                    Log::info("Return request not yet completed for UID {$uidDriver}. Retrying in {$interval} seconds...");
                    sleep($interval);
                }
            }

            Log::info("Return request did not complete within {$maxWaitTime} seconds for UID {$uidDriver}. No delete request sent.");
            (new MessageSentController())->sentDriverNoDelCommission($uid);

            return "Return request did not complete in time. No delete request sent.";
        }
    }

    public function isReturnRequestCompleted($uid, $uidDriver)
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Фильтрация по полям driver_uid и status == 'return'
            $collection = $firestore->collection('balance');
            $query = $collection->where('driver_uid', '=', $uidDriver)
                ->where('status', '=', 'return')
                ->where('dispatching_order_uid', '=', $uid);

            $documents = $query->documents();

            // Проверяем, завершен ли запрос с типом "return"
            if (!$documents->isEmpty()) {
                Log::info("Return request completed for UID {$uidDriver}");
                return true;
            } else {
                Log::info("Return request not completed for UID {$uidDriver}");
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Error checking return request: " . $e->getMessage());
            return false;
        }
    }

    public function isHoldCompleted($uid, $uidDriver)
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Фильтрация по полям driver_uid и status == 'return'
            $collection = $firestore->collection('balance');
            $query = $collection->where('driver_uid', '=', $uidDriver)
                ->where('status', '=', 'hold')
                ->where('dispatching_order_uid', '=', $uid);

            $documents = $query->documents();

            // Проверяем, завершен ли запрос с типом "return"
            if (!$documents->isEmpty()) {
                Log::info("Return request completed for UID {$uidDriver}");
                return true;
            } else {
                Log::info("Return request not completed for UID {$uidDriver}");
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Error checking return request: " . $e->getMessage());
            return false;
        }
    }

    public function autoDeleteOrderPersonal($order, $driver_uid)
    {
        $documentId = $order->id;
        $data = $order->toArray();
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Удаление из личных заказов
            $collection = $firestore->collection('orders_personal');
            $document = $collection->document($documentId);

            $document->delete();

            // Запись в эфир

            $collection = $firestore->collection('orders');
            $document = $collection->document($documentId);
            $document->set($data);

            // Запись в отказные

//            $collection = $firestore->collection('orders_refusal');
//            $document = $collection->document($documentId);
//            $data["driver_uid"] = $driver_uid;
//            $document->set($data);

            $uid = $order->dispatching_order_uid;
            (new OrdersRefusalController)->store($driver_uid, $uid);

            // Запись в историю

            $collection = $firestore->collection('orders_history');
            $document = $collection->document($documentId);
            $data["updated_at"] = self::currentKievDateTime();
            $data["status"] = "refusal";
            $document->set($data);

            Log::info("Document successfully written!");
            return "Document successfully written!";
        } catch (\Exception $e) {
            Log::error("10 Error writing document to Firestore: " . $e->getMessage());
            return "Error writing document to Firestore.";
        }
    }

    public function driverCardPayDownBalance($uidDriver, $amount, $comment, $selectedTypeCode)
    {
        try {
            $currentDateTime = Carbon::now();
            $kievTimeZone = new DateTimeZone('Europe/Kiev');
            $dateTime = new DateTime($currentDateTime);
            $dateTime->setTimezone($kievTimeZone);
            $formattedTime = $dateTime->format('d.m.Y H:i:s');


            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('users');
            $document = $collection->document($uidDriver);

            $collection = $firestore->collection('balance_current');
            $document_balance_current = $collection->document($uidDriver);

            // Получаем существующий документ
            $snapshot_balance_current = $document_balance_current->snapshot();

            $previousAmount = 0;

            // Если документ существует, получаем предыдущее значение amount
            if ($snapshot_balance_current->exists()) {
                $previousAmount = $snapshot_balance_current->data()['amount'] ?? 0;
            }

            // Добавляем новое значение к предыдущему
            $newAmount = $previousAmount - $amount;

            $dataBalance = [
                'driver_uid' => $uidDriver,
                'amount' => $newAmount,
                'created_at' => $formattedTime,
            ];

            // Записываем или обновляем документ с новым значением
            $document_balance_current->set($dataBalance);


            // Получите снимок документа
            $snapshot = $document->snapshot();



            if ($snapshot->exists()) {
                // Получите данные из документа
                $dataDriver = $snapshot->data();

                $name = $dataDriver['name'] ?? 'Unknown';
                $phoneNumber = $dataDriver['phoneNumber'] ?? 'Unknown';
                $driverNumber = $dataDriver['driverNumber'] ?? 'Unknown';
                $email = $dataDriver['email'] ?? 'Unknown';



                $randomNumber = rand(1000000, 9999999); // Генерируем случайное число от 1000 до 9999

                $currentTimeInMilliseconds = round(microtime(true) * 1000);
                $documentId = "R_{$dataDriver['driverNumber']}_{$randomNumber}_{$currentTimeInMilliseconds}";

                $collection = $firestore->collection('balance');
                $document = $collection->document($documentId);
                $data['status'] = "holdDown";
                $data['amount'] = (float)$amount;  // Записываем как число
                $data['commission'] = (float)$amount;  // Записываем как число
                $data['id'] = $documentId;
                $data['selectedTypeCode'] = $selectedTypeCode;
                $data['created_at'] = $formattedTime;
                $data['current_balance'] = $newAmount;
                $data['driver_uid'] = $uidDriver;
                $data['complete'] = false;

                // Запишите данные в документ
                $document->set($data);


$subject = "Водитель
ФИО $name
телефон $phoneNumber
позывной $driverNumber
google_id: $uidDriver ожидает возврата средств:
Сумма  $amount
Способ возврата $selectedTypeCode
Комментарии $comment
Время заявки $formattedTime
Ссылка для подтверждения https://m.easy-order-taxi.site/driver/driverDownBalanceAdmin/$documentId/$uidDriver";

                $messageAdmin = $subject;

                $alarmMessage = new TelegramController();

                try {
                    $alarmMessage->sendAlarmMessage($messageAdmin);
                    $alarmMessage->sendMeMessage($messageAdmin);
                } catch (Exception $e) {
                    Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
                }
                Log::debug("sentCancelInfo  $messageAdmin");


                $subject_email = "Заявка на возврат средств  (позывной $driverNumber)";
    //            https://m.easy-order-taxi.site/driver/driverDownBalanceAdmin/R_105226_5138963/pEePGRVPNNU6IeJexWRwBpohu9q2
                $url = "https://m.easy-order-taxi.site/driver/driverDownBalanceAdmin/$documentId/$uidDriver";
                $paramsAdmin = [
                    'email' => $email,
                    'subject' => $subject_email,
                    'message' => $messageAdmin,
                    'url' => $url,

                ];

                Mail::to('taxi.easy.ua.sup@gmail.com')->send(new DriverInfo($paramsAdmin));

                Mail::to('cartaxi4@gmail.com')->send(new DriverInfo($paramsAdmin));

        }


        } catch (\Exception $e) {
            Log::error("Error reading document from Firestore: " . $e->getMessage());
            return "Error reading document from Firestore.";
        }
    }

    public function driverDownBalanceAdmin($documentId, $uidDriver)
    {
        try {
            $currentDateTime = Carbon::now();
            $kievTimeZone = new DateTimeZone('Europe/Kiev');
            $dateTime = new DateTime($currentDateTime);
            $dateTime->setTimezone($kievTimeZone);
            $formattedTime = $dateTime->format('d.m.Y H:i:s');


            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('users');
            $document_users = $collection->document($uidDriver);
            $snapshot_users = $document_users->snapshot();
            $data_users = $snapshot_users->data();

            $name = $data_users['name'] ?? 'Unknown';
            $phoneNumber = $data_users['phoneNumber'] ?? 'Unknown';
            $driverNumber = $data_users['driverNumber'] ?? 'Unknown';
            $driver_uid = $data_users['uid'] ?? 'Unknown';
            $email = $data_users['email'] ?? 'Unknown';



            $collection = $firestore->collection('balance_current');
            $document_balance_current = $collection->document($uidDriver);
            $snapshot_balance_current = $document_balance_current->snapshot();
            $dataDriver_balance_current = $snapshot_balance_current->data();
            $balance_current = $dataDriver_balance_current['amount'] ?? 'Unknown';

            $collection = $firestore->collection('balance');
            $document_balance = $collection->document($documentId);
            $snapshot_balance = $document_balance->snapshot();
            $dataDriver_balance = $snapshot_balance->data();
            $amount_to_return = $dataDriver_balance['amount'] ?? 'Unknown';
            $order_to_return = $documentId;
            $selectedTypeCode = $dataDriver_balance['selectedTypeCode'] ?? 'Unknown';
            $order_to_return_date = $dataDriver_balance['created_at'] ?? 'Unknown';

            $params= [
                'name' => $name,
                'phoneNumber' => $phoneNumber,
                'driverNumber' => $driverNumber,
                'driver_uid' => $driver_uid,
                'email' => $email,
                'balance_current' => $balance_current,
                'amount_to_return' => $amount_to_return,
                'order_to_return' => $order_to_return,
                'selectedTypeCode' => $selectedTypeCode,
                'order_to_return_date' => $order_to_return_date,

            ];

            if ($dataDriver_balance['complete'] == true) {
                return redirect()->route('driverDownBalanceAdminfinish', $params);
            } else {
                return redirect()->route('driverDownBalanceAdmin', $params);
            }

//            return view('driver.driver_amount', ['params' => $params]);
//            return response()->json($params, 200);


        } catch (\Exception $e) {
            Log::error("driverDownBalanceAdmin Error reading document from Firestore: " . $e->getMessage());
            return "driverDownBalanceAdmin Error reading document from Firestore.";
        }
    }


    public function driverDeleteOrder($id)
    {
        try {

            $formattedTime = self::currentKievDateTime();


            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('balance');
            $document = $collection->document($id);
            $snapshot = $document->snapshot();

            if ($snapshot->exists()) {
                // Получите данные из документа
                $data = $snapshot->data();

                $uidDriver = $data['driver_uid'] ?? 'Unknown';
                $orderId = $id ?? 'Unknown';
                $amount = $data['amount'];
                $selectedTypeCode = $data['selectedTypeCode'];

                $collection = $firestore->collection('users');
                $document_users = $collection->document($uidDriver);
                $snapshot_users = $document_users->snapshot();
                $data_users = $snapshot_users->data();

                $name = $data_users['name'] ?? 'Unknown';
                $phoneNumber = $data_users['phoneNumber'] ?? 'Unknown';
                $driverNumber = $data_users['driverNumber'] ?? 'Unknown';
                $driver_uid = $data_users['uid'] ?? 'Unknown';
                $email = $data_users['email'] ?? 'Unknown';

                $subject = "Водитель
ФИО $name
телефон $phoneNumber
позывной $driverNumber
google_id: $uidDriver ожидает отмены заявки на возврат средств:
Номер заявки $orderId
Сумма  $amount
Способ возврата $selectedTypeCode
Время заявки $formattedTime
Ссылка для подтверждения https://m.easy-order-taxi.site/driver/driverAdminDeleteOrder/$orderId";

                $messageAdmin = $subject;

                $alarmMessage = new TelegramController();

//                try {
//                    $alarmMessage->sendAlarmMessage($messageAdmin);
//                    $alarmMessage->sendMeMessage($messageAdmin);
//                } catch (Exception $e) {
//                    Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
//                }
//                Log::debug("sentCancelInfo  $messageAdmin");


                $subject_email = "Заявка на отмену вывода средств  (позывной $driverNumber)";
                $url = "https://m.easy-order-taxi.site/driver/driverAdminDeleteOrder/$orderId";
                $paramsAdmin = [
                    'email' => $email,
                    'subject' => $subject_email,
                    'message' => $messageAdmin,
                    'url' => $url,

                ];

                Mail::to('taxi.easy.ua.sup@gmail.com')->send(new DriverInfo($paramsAdmin));

                Mail::to('cartaxi4@gmail.com')->send(new DriverInfo($paramsAdmin));

            }


        } catch (\Exception $e) {
            Log::error("Error reading document from Firestore: " . $e->getMessage());
            return "Error reading document from Firestore.";
        }
    }

    public function driverDeleteOrderAdmin($orderId)
    {
        try {

            $formattedTime = self::currentKievDateTime();

            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получаем старый документ
            $collection = $firestore->collection('balance');
            $document_balance = $collection->document($orderId);
            $snapshot_balance = $document_balance->snapshot();
            $updateData = [
                'complete' => true // Или false, в зависимости от вашего требования
            ];

            try {
                // Выполняем обновление документа
                $document_balance->set($updateData, ['merge' => true]);
            } catch (Exception $e) {
            }
            // Извлекаем данные из старого документа
            $dataDriver_balance = $snapshot_balance->data();
            $driver_uid = $dataDriver_balance['driver_uid'] ?? 0;
            $amount_to_return = $dataDriver_balance['amount'] ?? 0;

            // Получаем текущий баланс
            $collection_current = $firestore->collection('balance_current');
            $document_balance_current = $collection_current->document($driver_uid);
            $snapshot_balance_current = $document_balance_current->snapshot();
            $newAmount = $snapshot_balance_current->data()['amount'] ?? 0;
            $selectedTypeCode = $snapshot_balance_current->data()['selectedTypeCode'] ?? 0;

            $formattedTime = self::currentKievDateTime();
            // Обновляем сумму
            $newAmount += $amount_to_return;
            $dataBalance = [
                'driver_uid' => $driver_uid,
                'amount' => $newAmount,
                'created_at' => $formattedTime,
            ];


            $document_balance_current->set($dataBalance);

            // Данные для новой записи с неизменёнными полями
            $data = [
                'driver_uid' => $driver_uid,
                'amount' => $amount_to_return,
                'commission' => $amount_to_return,
                'created_at' => $formattedTime,
                'status' => 'holdDownReturnToBalance', // Сохраняем неизменённые поля
                'id' => $orderId,
                'current_balance' => $newAmount,
                'selectedTypeCode' => $selectedTypeCode,
                'complete' => true // Устанавливаем нужное значение
            ];

            // Создаём новый документ в коллекции balance
            $randomNumber = rand(1000000, 9999999); // Генерируем случайное число от 1000 до 9999
            // Получение текущего времени в миллисекундах
            $currentTimeInMilliseconds = round(microtime(true) * 1000);


            $collection_driver = $firestore->collection('users');
            $document_driver = $collection_driver->document($driver_uid);
            $snapshot = $document_driver->snapshot();
            $dataDriver = $snapshot->data();

            $documentId = "R_{$dataDriver['driverNumber']}_{$randomNumber}_{$currentTimeInMilliseconds}";

            $document = $collection->document($documentId);
            $document->set($data);

            return "Заявка водителя на отмену возврата средств выполнена " ; // Возвращаем ID нового документа
        } catch (Exception $e) {
            return "Ошибка: " . $e->getMessage();
        }
    }

    public function returnAmountSave(Request $request)
    {
        Log::info('Запрос на возврат суммы получен.', [
            'request_data' => $request->all() // Логируем все данные запроса
        ]);



        $name = $request->name;
        $phoneNumber = $request->phoneNumber;
        $driverNumber = $request->driverNumber;
        $driver_uid = $request->driver_uid;
        $email = $request->email;
        $balance_current = $request->balance_current;
        $amount_to_return = $request->amount_to_return;
        $amount_to_return_admin = $request->amount_to_return_admin;
        $order_to_return = $request->order_to_return;
        $selectedTypeCode = $request->selectedTypeCode;
        $order_to_return_date = $request->order_to_return_date;

        if ($request->code_verify == "123456") {
            try {

                // Получите экземпляр клиента Firestore из сервис-провайдера
                $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
                $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
                $firestore = $firebase->createFirestore()->database();


//Запись о воврате холда заявки на баланс
                $formattedTime = self::currentTime();

                $collection = $firestore->collection('balance_current');
                $document_balance_current = $collection->document($driver_uid);
                $snapshot_balance_current = $document_balance_current->snapshot();
                $newAmount = $snapshot_balance_current->data()['amount'] ?? 0;

                $newAmount = $newAmount + $amount_to_return;
                $dataBalance = [
                    'driver_uid' => $driver_uid,
                    'amount' => $newAmount,
                    'created_at' => $formattedTime,
                ];

                // Записываем или обновляем баланс с новым значением
                $document_balance_current->set($dataBalance);

                $randomNumber = rand(1000000, 9999999); // Генерируем случайное число от 1000 до 9999
                $documentId = "R_{$driverNumber}_{$formattedTime}_{$randomNumber}";

                $collection = $firestore->collection('balance');
                $document = $collection->document($documentId);
                $data['amount'] = (float)$amount_to_return;  // Записываем как число
                $data['commission'] = (float)$amount_to_return;  // Записываем как число
                $data['current_balance'] = $newAmount;
                $data['status'] = "holdDownReturnToBalance";
                $data['current_balance'] = $newAmount;
                $data['driver_uid'] = $driver_uid;
                $data['complete'] = true;
                $data['created_at'] = $formattedTime;
                $data['id'] = $order_to_return;
                $data['selectedTypeCode'] = $selectedTypeCode;
                // Запишите данные в документ
                $document->set($data);

//запись о выполнении заявки
                $document = $collection->document($order_to_return);
                $snapshot = $document->snapshot();
                $data = $snapshot->data();
                $data['complete'] = true;

                // Запишите данные в документ
                $document->set($data);
//Запись о списании с баланса
                sleep(1);
                $collection = $firestore->collection('balance_current');
                $document_balance_current = $collection->document($driver_uid);
                $snapshot_balance_current = $document_balance_current->snapshot();
                $newAmount = $snapshot_balance_current->data()['amount'] ?? 0;
                $newAmount = $newAmount - $amount_to_return_admin;

                $dataBalance = [
                    'driver_uid' => $driver_uid,
                    'amount' => $newAmount,
                    'created_at' => $formattedTime,
                ];

                // Записываем или обновляем баланс с новым значением
                $document_balance_current->set($dataBalance);

                $randomNumber = rand(1000000, 9999999); // Генерируем случайное число от 1000 до 9999
                $formattedTime = self::currentTime();
                $documentId = "R_{$driverNumber}_{$formattedTime}_{$randomNumber}";
                $collection = $firestore->collection('balance');
                $document = $collection->document($documentId);

                $data['status'] = "holdDownComplete";
                $data['amount'] = (float)$amount_to_return_admin;  // Записываем как число
                $data['commission'] = (float)$amount_to_return_admin;  // Записываем как число
                $data['id'] = $request->input('order_to_return');
                $data['selectedTypeCode'] = $selectedTypeCode;
                $data['created_at'] = $formattedTime;
                $data['current_balance'] = $newAmount;
                $data['driver_uid'] = $driver_uid;
                $data['complete'] = true;

                // Запишите данные в документ
                $document->set($data);

      //Записываем результат для ответа

                // Получите ссылку на коллекцию и документ
                $collection = $firestore->collection('users');
                $document_users = $collection->document($driver_uid);
                $snapshot_users = $document_users->snapshot();
                $data_users = $snapshot_users->data();

                $name = $data_users['name'] ?? 'Unknown';
                $phoneNumber = $data_users['phoneNumber'] ?? 'Unknown';
                $driverNumber = $data_users['driverNumber'] ?? 'Unknown';
                $email = $data_users['email'] ?? 'Unknown';


                $collection = $firestore->collection('balance_current');
                $document_balance_current = $collection->document($driver_uid);
                $snapshot_balance_current = $document_balance_current->snapshot();
                $dataDriver_balance_current = $snapshot_balance_current->data();
                $balance_current = $dataDriver_balance_current['amount'] ?? 'Unknown';

                $collection = $firestore->collection('balance');
                $document_balance = $collection->document($documentId);
                $snapshot_balance = $document_balance->snapshot();
                $dataDriver_balance = $snapshot_balance->data();
                $amount_to_return = $dataDriver_balance['amount'] ?? 'Unknown';
                $order_to_return = $documentId;
                $selectedTypeCode = $dataDriver_balance['selectedTypeCode'] ?? 'Unknown';
                $order_to_return_date = $dataDriver_balance['created_at'] ?? 'Unknown';

                $params= [
                    'name' => $name,
                    'phoneNumber' => $phoneNumber,
                    'driverNumber' => $driverNumber,
                    'email' => $email,
                    'balance_current' => $balance_current,
                    'amount_to_return' => $amount_to_return,
                    'order_to_return' => $order_to_return,
                    'selectedTypeCode' => $selectedTypeCode,
                    'order_to_return_date' => $order_to_return_date,
                ];
//                dd($params);

                return redirect()->route('driverDownBalanceAdminfinish', $params);


            } catch (\Exception $e) {
                Log::error("1111 driverDownBalanceAdmin Error reading document from Firestore: " . $e->getMessage());
                return "111 driverDownBalanceAdmin Error reading document from Firestore.";
            }
        } else {
            try {
                // Получите экземпляр клиента Firestore из сервис-провайдера
                $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
                $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
                $firestore = $firebase->createFirestore()->database();

                // Получите ссылку на коллекцию и документ
                $collection = $firestore->collection('users');
                $document_users = $collection->document($driver_uid);
                $snapshot_users = $document_users->snapshot();
                $data_users = $snapshot_users->data();

                $name = $data_users['name'] ?? 'Unknown';
                $phoneNumber = $data_users['phoneNumber'] ?? 'Unknown';
                $driverNumber = $data_users['driverNumber'] ?? 'Unknown';
                $email = $data_users['email'] ?? 'Unknown';



                $collection = $firestore->collection('balance_current');
                $document_balance_current = $collection->document($driver_uid);
                $snapshot_balance_current = $document_balance_current->snapshot();
                $dataDriver_balance_current = $snapshot_balance_current->data();
                $balance_current = $dataDriver_balance_current['amount'] ?? 'Unknown';

                $documentId = $order_to_return;
                $collection = $firestore->collection('balance');
                $document_balance = $collection->document($documentId);
                $snapshot_balance = $document_balance->snapshot();
                $dataDriver_balance = $snapshot_balance->data();
                $amount_to_return = $dataDriver_balance['amount'] ?? 'Unknown';

                $selectedTypeCode = $dataDriver_balance['selectedTypeCode'] ?? 'Unknown';
                $order_to_return_date = $dataDriver_balance['created_at'] ?? 'Unknown';

                $params= [
                    'name' => $name,
                    'phoneNumber' => $phoneNumber,
                    'driverNumber' => $driverNumber,
                    'driver_uid' => $driver_uid,
                    'email' => $email,
                    'balance_current' => $balance_current,
                    'amount_to_return' => $amount_to_return,
                    'order_to_return' => $order_to_return,
                    'selectedTypeCode' => $selectedTypeCode,
                    'order_to_return_date' => $order_to_return_date,

                ];


//            return redirect()->route('driverDownBalanceAdmin', $params);
                return redirect()->route('driverDownBalanceAdmin', $params)->with('error', "Ошибка кода проверки. Попробуйте еще");
//                return view('admin.driver_amount', ['params' => $params])->with("error", "Ошибка кода проверки. Попробуйте еще");
//            return response()->json($params, 200);


            } catch (\Exception $e) {
                Log::error("driverDownBalanceAdmin Error reading document from Firestore: " . $e->getMessage());
                return "driverDownBalanceAdmin Error reading document from Firestore.";
            }
        }
    }


    public function addAmountToBalanceCurrent($uidDriver, $amount)
    {

        $formattedTime = self::currentKievDateTime();

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
                'created_at' => $formattedTime,
            ];

            // Записываем или обновляем документ с новым значением
            $document->set($data);


            $collection = $firestore->collection('users');
            $document = $collection->document($uidDriver);

            // Получите снимок документа
            $snapshot = $document->snapshot();

            if ($snapshot->exists()) {
                // Получите данные из документа
                $data_driver = $snapshot->data();
                $driverNumber = $data_driver['driverNumber'];
            }


            $randomNumber = rand(1000000, 9999999); // Генерируем случайное число от 1000 до 9999
            $formattedTime = self::currentTime();
            $documentId = "A_{$driverNumber}_{$formattedTime}_{$randomNumber}";
            $collection = $firestore->collection('balance');
            $document = $collection->document($documentId);

            $admin = Auth::user();

            $data['admin_name'] = $admin->name;
            $data['admin_email'] =  $admin->email;


            $data['amount'] = (float)$amount;  // Записываем как число
            $data['id'] = $documentId;
            $data['status'] = "payment_nal";
            $data['created_at'] = $formattedTime;
            $data['current_balance'] = $newAmount;
            $data['driver_uid'] = $uidDriver;
            $data['complete'] = true;
            $data['selectedTypeCode'] = "";

            $document->set($data);

            Log::info("Document successfully written with updated amount!");
            return "Document successfully written with updated amount!";
        } catch (\Exception $e) {
            Log::error("11 Error writing document to Firestore: " . $e->getMessage());
            return "Error writing document to Firestore.";
        }
    }

    private function currentTime ()
    {
        $currentDateTime = Carbon::now();
        $kievTimeZone = new DateTimeZone('Europe/Kiev');
        $dateTime = new DateTime($currentDateTime);
        $dateTime->setTimezone($kievTimeZone);
        return $dateTime->format('d.m.Y H:i:s');
    }
}
