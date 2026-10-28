<?php

namespace App\Services;

use App\City\PaymentFlow;
use App\City\SimpleCashlessDispatchStatusSync;
use App\Http\Controllers\AndroidTestOSMController;
use App\Http\Controllers\CentrifugoController;
use App\Http\Controllers\DriverMemoryOrderController;
use App\Http\Controllers\MemoryOrderChangeController;
use App\Http\Controllers\MessageSentController;
use App\Http\Controllers\PusherController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Jobs\WriteDocumentToFirestore;
use App\Models\DriverMemoryOrder;
use App\Models\Orderweb;
use App\Models\Uid_history;
use App\Models\WfpInvoice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PAS 2, простой одиночный нал или безнал: сервер такси снял заказ не по вине клиента
 * во время поиска — создаём новый UID теми же параметрами и ведём цепочку.
 * Неоплата (1 мин), таймер нашего бека и кнопка клиента сюда не попадают.
 */
final class Pas2SimpleDispatchRecreate
{
    public const SKIP = 'skip';

    public const BUSY = 'busy';

    public const RECREATED = 'recreated';

    /** POST не прошёл. Клиенту оставляем поиск и пробуем на следующем опросе. */
    public const RETRY = 'retry';

    /** Цепочка уже переключена другим опросом — снимок старого UID не записывать. */
    public const MOVED = 'moved';

    private const COUNT_PREFIX = 'pas2_dispatch_recreate_count:';

    private const LOCK_PREFIX = 'pas2_dispatch_recreate_lock:';

    /** @var list<string> */
    private const CARD_PAY_SYSTEMS = [
        'wfp_payment',
        'google_pay_payment',
        'fondy_payment',
        'mono_payment',
        'card_payment',
    ];

    /** @var list<string> */
    private const PAID_STATUSES = ['WaitingAuthComplete', 'Approved'];

    public static function isPas2(Orderweb $order): bool
    {
        $comment = (string) ($order->comment ?? '');

        return $comment === 'taxi_easy_ua_pas2'
            || $comment === (string) config('app.X-WO-API-APP-ID-PAS2');
    }

    /**
     * Нал PAS 2 в поиске: тот же live GET, что у простого безнала.
     * Поездка с машиной по-прежнему идёт старым опросом.
     */
    public static function shouldLivePoll(Orderweb $order): bool
    {
        if (!self::isPas2($order)) {
            return false;
        }

        if (PaymentFlow::normalize($order->payment_flow_mode ?? 0) !== PaymentFlow::SIMPLE) {
            return false;
        }

        if ((string) ($order->pay_system ?? '') !== 'nal_payment') {
            return false;
        }

        $server = (string) ($order->server ?? '');
        if ($server === '' || $server === 'my_server_api') {
            return false;
        }

        return in_array((string) ($order->closeReason ?? ''), ['-1', '0', '100'], true);
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    public static function attempt(Orderweb $order, array $snapshot, bool $recoverStored = false): string
    {
        $closeReason = (int) ($snapshot['close_reason'] ?? -999);
        if (!in_array($closeReason, Pas2SimpleDispatchRecreateGate::FOREIGN_CLOSE_REASONS, true)) {
            return self::SKIP;
        }

        if (!self::isPas2($order)) {
            return self::SKIP;
        }

        $polledUid = (string) ($order->dispatching_order_uid ?? '');
        if ($polledUid === '') {
            return self::SKIP;
        }

        $context = [
            'is_pas2' => true,
            'payment_flow' => PaymentFlow::normalize($order->payment_flow_mode ?? 0),
            'server' => (string) ($order->server ?? ''),
            'is_fork' => false,
            'close_reason' => $closeReason,
            'order_is_archive' => self::isArchivedFlag($snapshot['order_is_archive'] ?? null),
            'execution_status' => (string) ($snapshot['execution_status'] ?? ''),
            'previous_close_reason' => (string) ($order->closeReason ?? ''),
            'recover_stored' => $recoverStored,
            'has_car' => self::hasCar($order->auto, $snapshot),
            'cancel_requested' => self::cancelWasRequested($order, $polledUid),
            'payment_confirmed' => self::paymentConfirmed($order),
            'recreate_count' => self::recreateCount($polledUid),
            'add_cost_in_progress' => DispatchOrderCancelService::isAddCostRecreationInProgress($polledUid),
        ];

        if (!Pas2SimpleDispatchRecreateGate::shouldRecreate($context)) {
            return self::SKIP;
        }

        if (self::hasForkHistory($polledUid)) {
            return self::SKIP;
        }

        $lock = null;
        try {
            $lock = Cache::lock(self::LOCK_PREFIX . self::rootUid($polledUid), 25);
            if (!$lock->get()) {
                return self::BUSY;
            }
        } catch (\Throwable $e) {
            Log::warning('Pas2SimpleDispatchRecreate: lock unavailable', [
                'uid' => $polledUid,
                'error' => $e->getMessage(),
            ]);
            $lock = null;
        }

        try {
            $current = Orderweb::query()->find($order->getKey());
            if (!$current instanceof Orderweb) {
                return self::SKIP;
            }
            if ((string) $current->dispatching_order_uid !== $polledUid) {
                return self::MOVED;
            }

            $context['previous_close_reason'] = (string) ($current->closeReason ?? '');
            $context['cancel_requested'] = self::cancelWasRequested($current, $polledUid);
            $context['recreate_count'] = self::recreateCount($polledUid);
            $context['has_car'] = self::hasCar($current->auto, $snapshot);
            if (!Pas2SimpleDispatchRecreateGate::shouldRecreate($context)) {
                return self::SKIP;
            }

            return self::recreate($current, $polledUid);
        } catch (\Throwable $e) {
            Log::error('Pas2SimpleDispatchRecreate: failed', [
                'uid' => $polledUid,
                'error' => $e->getMessage(),
            ]);

            return self::SKIP;
        } finally {
            if ($lock !== null) {
                try {
                    $lock->release();
                } catch (\Throwable $e) {
                    Log::warning('Pas2SimpleDispatchRecreate: lock release failed', [
                        'uid' => $polledUid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private static function recreate(Orderweb $order, string $oldUid): string
    {
        $memory = self::findMemory($oldUid);
        if ($memory === null) {
            Log::warning('Pas2SimpleDispatchRecreate: no stored dispatch parameters', ['uid' => $oldUid]);

            return self::SKIP;
        }

        $parameter = json_decode((string) $memory->response, true);
        if (!is_array($parameter)) {
            Log::warning('Pas2SimpleDispatchRecreate: invalid stored parameters', ['uid' => $oldUid]);

            return self::SKIP;
        }

        unset($parameter['dispatching_order_uid'], $parameter['order_uid'], $parameter['uid']);

        $url = self::webordersEndpoint((string) $memory->connectAPI, (string) $order->server);
        if ($url === '') {
            Log::warning('Pas2SimpleDispatchRecreate: dispatch url is empty', ['uid' => $oldUid]);

            return self::SKIP;
        }

        try {
            $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                (string) $memory->authorization,
                (string) $memory->identificationId,
                (string) $memory->apiVersion
            );
            if ($response === null) {
                return self::RETRY;
            }
            $body = is_object($response) && method_exists($response, 'body')
                ? $response->body()
                : (string) $response;
            $responseArr = json_decode($body, true);
        } catch (\Throwable $e) {
            Log::error('Pas2SimpleDispatchRecreate: dispatch POST failed', [
                'uid' => $oldUid,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return self::RETRY;
        }

        if (!is_array($responseArr) || isset($responseArr['Message']) || empty($responseArr['dispatching_order_uid'])) {
            Log::error('Pas2SimpleDispatchRecreate: new order was not created', [
                'uid' => $oldUid,
                'url' => $url,
                'response' => $responseArr,
            ]);

            return self::RETRY;
        }

        $newUid = (string) $responseArr['dispatching_order_uid'];
        $order->refresh();
        if (self::cancelWasRequested($order, $oldUid)) {
            self::abandonDispatchUid($memory, $newUid);
            Log::info('Pas2SimpleDispatchRecreate: dropped new uid, cancel won the race', [
                'old_uid' => $oldUid,
                'new_uid' => $newUid,
            ]);

            return self::SKIP;
        }

        (new MemoryOrderChangeController())->store($oldUid, $newUid);

        $invoices = WfpInvoice::where('dispatching_order_uid', $oldUid)->get();
        foreach ($invoices as $invoice) {
            $invoice->dispatching_order_uid = $newUid;
            $invoice->save();
        }

        $order->dispatching_order_uid = $newUid;
        $order->auto = null;
        $order->closeReason = '-1';
        $order->closeReasonI = '0';
        $order->cancel_timestamp = null;
        if (!empty($responseArr['order_cost'])) {
            $order->web_cost = $responseArr['order_cost'];
        }
        $order->save();

        (new DriverMemoryOrderController())->store(
            $newUid,
            json_encode($parameter, JSON_UNESCAPED_UNICODE),
            (string) $memory->authorization,
            $url,
            (string) $memory->identificationId,
            (string) $memory->apiVersion
        );

        self::incrementCount($oldUid);

        self::notifyClient($order, $newUid);
        self::cleanupOldUid($oldUid);

        try {
            dispatch((new WriteDocumentToFirestore($newUid))->onQueue('high'));
        } catch (\Throwable $e) {
            Log::error('Pas2SimpleDispatchRecreate: firestore dispatch failed', [
                'uid' => $newUid,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Pas2SimpleDispatchRecreate: recreated', [
            'old_uid' => $oldUid,
            'new_uid' => $newUid,
            'close_reason' => $order->closeReason,
        ]);

        try {
            (new MessageSentController())->sentMessageAdmin(
                "PAS2: сервер такси снял заказ {$oldUid}, создан {$newUid}"
            );
        } catch (\Throwable $e) {
            Log::warning('Pas2SimpleDispatchRecreate: admin notify failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return self::RECREATED;
    }

    /**
     * Ответ опросу приложения, пока старый UID уже в архиве, а клиент должен остаться в поиске.
     *
     * @return array<string, mixed>
     */
    public static function holdingSearchResponse(Orderweb $order): array
    {
        $payload = [
            'dispatching_order_uid' => (string) ($order->dispatching_order_uid ?? ''),
            'close_reason' => -1,
            'execution_status' => 'SearchesForCar',
            'order_is_archive' => false,
            'order_car_info' => null,
        ];
        $cost = $order->client_cost ?? $order->web_cost ?? null;
        if ($cost !== null && $cost !== '') {
            $payload['order_cost'] = (string) $cost;
        }

        return $payload;
    }

    /**
     * @param mixed $value
     */
    public static function isArchivedFlag($value): bool
    {
        return $value === true || $value === 1 || $value === '1';
    }

    /**
     * В driver_memory_orders.connectAPI лежит уже полный URL создания
     * (`http://host:port/api/weborders`). Повторно путь не дописываем.
     * Пустое поле — база из order.server.
     */
    public static function webordersEndpoint(string $storedConnectApi, string $orderServer = ''): string
    {
        foreach ([$storedConnectApi, $orderServer] as $candidate) {
            $value = rtrim($candidate, '/');
            if ($value === '') {
                continue;
            }
            if (str_ends_with(strtolower($value), '/api/weborders')) {
                return $value;
            }

            return $value . '/api/weborders';
        }

        return '';
    }

    private static function notifyClient(Orderweb $order, string $newUid): void
    {
        $email = (string) ($order->email ?? '');
        if ($email === '' || $email === 'no email') {
            return;
        }

        $paySystem = (string) ($order->pay_system ?? 'nal_payment');
        $cost = (int) ($order->client_cost ?? $order->web_cost ?? 0);

        try {
            (new PusherController())->sentUidAppEmailPayType($newUid, 'PAS2', $email, $paySystem, $cost);
            (new CentrifugoController())->sentUidAppEmailPayType($newUid, 'PAS2', $email, $paySystem, $cost);
        } catch (\Throwable $e) {
            Log::error('Pas2SimpleDispatchRecreate: uid push failed', [
                'uid' => $newUid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function cleanupOldUid(string $oldUid): void
    {
        try {
            (new AndroidTestOSMController())->cleanupAddCostRecreationLocalState([$oldUid]);
        } catch (\Throwable $e) {
            Log::warning('Pas2SimpleDispatchRecreate: old uid cleanup failed', [
                'uid' => $oldUid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function abandonDispatchUid(DriverMemoryOrder $memory, string $newUid): void
    {
        $cancelUrl = self::webordersEndpoint((string) $memory->connectAPI, '');
        if ($cancelUrl === '' || (string) $memory->authorization === '') {
            return;
        }

        try {
            \Illuminate\Support\Facades\Http::timeout(15)->withHeaders([
                'Authorization' => (string) $memory->authorization,
                'X-WO-API-APP-ID' => (string) $memory->identificationId,
                'X-API-VERSION' => (string) $memory->apiVersion,
            ])->put($cancelUrl . '/cancel/' . $newUid);
        } catch (\Throwable $e) {
            Log::error('Pas2SimpleDispatchRecreate: failed to cancel orphan uid', [
                'uid' => $newUid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function findMemory(string $uid): ?DriverMemoryOrder
    {
        $memory = DriverMemoryOrder::where('dispatching_order_uid', $uid)->first();
        if ($memory !== null) {
            return $memory;
        }

        foreach ((new MemoryOrderChangeController())->collectPredecessorUids($uid) as $previous) {
            $memory = DriverMemoryOrder::where('dispatching_order_uid', $previous)->first();
            if ($memory !== null) {
                return $memory;
            }
        }

        return null;
    }

    /**
     * @param mixed $storedAuto
     * @param array<string, mixed> $snapshot
     */
    private static function hasCar($storedAuto, array $snapshot): bool
    {
        if (self::carValueFilled($storedAuto)) {
            return true;
        }

        return self::carValueFilled($snapshot['order_car_info'] ?? null);
    }

    /**
     * @param mixed $value
     */
    private static function carValueFilled($value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_array($value)) {
            return $value !== [];
        }

        $text = trim((string) $value);

        return $text !== '' && $text !== 'null' && $text !== '[]' && $text !== '{}';
    }

    private static function cancelWasRequested(Orderweb $order, string $uid): bool
    {
        if ($order->cancel_timestamp !== null) {
            return true;
        }

        if (OrderCancelOrigin::blocksRecreate($uid)) {
            return true;
        }

        if (DispatchOrderCancelService::hasActiveCampaign($uid)) {
            return true;
        }

        $latest = (new MemoryOrderChangeController())->findLatestOrderUid($uid);
        if ($latest !== $uid && OrderCancelOrigin::blocksRecreate($latest)) {
            return true;
        }

        return false;
    }

    private static function paymentConfirmed(Orderweb $order): bool
    {
        $paySystem = (string) ($order->pay_system ?? '');
        $card = in_array($paySystem, self::CARD_PAY_SYSTEMS, true)
            || SimpleCashlessDispatchStatusSync::isCashlessPaySystem($paySystem);
        if (!$card && (int) ($order->payment_type ?? 0) !== 1) {
            return true;
        }

        foreach (['wfp_status_pay', 'transactionStatus'] as $field) {
            if (in_array((string) ($order->{$field} ?? ''), self::PAID_STATUSES, true)) {
                return true;
            }
        }

        $reference = (string) ($order->wfp_order_id ?? '');
        if ($reference === '') {
            return false;
        }

        $invoice = WfpInvoice::where('orderReference', $reference)->first();

        return $invoice !== null
            && in_array((string) ($invoice->transactionStatus ?? ''), self::PAID_STATUSES, true);
    }

    private static function hasForkHistory(string $uid): bool
    {
        return Uid_history::query()
            ->where('uid_bonusOrder', $uid)
            ->orWhere('uid_doubleOrder', $uid)
            ->orWhere('uid_bonusOrderHold', $uid)
            ->exists();
    }

    private static function rootUid(string $uid): string
    {
        $chain = (new MemoryOrderChangeController())->collectPredecessorUids($uid);
        if ($chain === []) {
            return $uid;
        }

        return (string) $chain[count($chain) - 1];
    }

    private static function recreateCount(string $uid): int
    {
        return (int) Cache::get(self::COUNT_PREFIX . self::rootUid($uid), 0);
    }

    private static function incrementCount(string $oldUid): void
    {
        $key = self::COUNT_PREFIX . self::rootUid($oldUid);
        $next = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $next, now()->addHours(12));
    }
}
