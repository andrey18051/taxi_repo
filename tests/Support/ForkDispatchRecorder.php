<?php

namespace Tests\Support;

use App\Http\Controllers\UniversalAndroidFunctionController;

/**
 * Подмена диспетчера: записывает вызовы poll / cancel / restore по ногам вилки.
 */
final class ForkDispatchRecorder
{
    /** @var list<string> */
    public $calls = [];

    /** @var string */
    public $bonusPollStatus = 'SearchesForCar';

    /** @var string */
    public $doublePollStatus = 'SearchesForCar';

    /** @var string */
    public $restoreUid = 'a1b2c3d4e5f6478990ab12cd34ef56ab';

    /** @var string|null */
    public $restoreFailureMessage;

    /** @var string */
    private $bonusRestoreUrl;

    /** @var string */
    private $doubleRestoreUrl;

    public function __construct(string $bonusRestoreUrl, string $doubleRestoreUrl)
    {
        $this->bonusRestoreUrl = $bonusRestoreUrl;
        $this->doubleRestoreUrl = $doubleRestoreUrl;
    }

    /**
     * @param UniversalAndroidFunctionController&\PHPUnit\Framework\MockObject\MockObject $mock
     */
    public function wireControllerMock($mock): void
    {
        $mock->method('newStatus')->willReturnCallback(function (
            $authorization,
            $identificationId,
            $apiVersion,
            $url,
            $order,
            $orderType,
            $lastTimeUpdate,
            $updateTime,
            $uid_history
        ) {
            $leg = $orderType === 'bonus' ? 'bonus' : 'double';
            $this->calls[] = 'poll_' . $leg;

            return $leg === 'bonus' ? $this->bonusPollStatus : $this->doublePollStatus;
        });

        $mock->method('orderCanceled')->willReturnCallback(function (
            $order,
            $orderType
        ) {
            $this->calls[] = 'cancel_' . $orderType;
        });

        $mock->method('orderNewCreat')->willReturnCallback(function (
            $authorization,
            $identificationId,
            $apiVersion,
            $url,
            $parameter
        ) {
            if ($url === $this->bonusRestoreUrl) {
                $this->calls[] = 'restore_bonus';
            } elseif ($url === $this->doubleRestoreUrl) {
                $this->calls[] = 'restore_double';
            } else {
                $this->calls[] = 'restore_unknown';
            }

            if ($this->restoreFailureMessage !== null) {
                return $this->restoreFailureMessage;
            }

            return $this->restoreUid;
        });

        $mock->method('canceledFinish')->willReturn(false);
    }

    public function reset(): void
    {
        $this->calls = [];
        $this->restoreFailureMessage = null;
    }

    /**
     * @return list<string>
     */
    public static function expectedCallsForLeg(string $leg, string $action, bool $restoreLegClosed = true): array
    {
        if ($action === 'ничего' || $action === '') {
            return [];
        }

        if ($action === 'опрос') {
            return ['poll_' . $leg];
        }

        if ($action === 'отмена') {
            return ['cancel_' . $leg, 'poll_' . $leg];
        }

        if ($action === 'востановление') {
            if ($restoreLegClosed) {
                return ['restore_' . $leg, 'poll_' . $leg];
            }

            return ['poll_' . $leg];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    public static function expectedPhaseCalls(
        string $bonusAction,
        string $doubleAction,
        bool $bonusRestoreClosed = true,
        bool $doubleRestoreClosed = true
    ): array {
        return array_merge(
            self::expectedCallsForLeg('bonus', $bonusAction, $bonusRestoreClosed),
            self::expectedCallsForLeg('double', $doubleAction, $doubleRestoreClosed)
        );
    }

    public static function makeUidHistoryStub(): ForkUidHistoryStub
    {
        $stub = new ForkUidHistoryStub();
        $stub->uid_bonusOrder = 'uid-bonus-test';
        $stub->uid_doubleOrder = 'uid-double-test';
        $stub->uid_bonusOrderHold = 'uid-bonus-test';
        $stub->bonus_status = null;
        $stub->double_status = null;

        return $stub;
    }

    public static function closedLegJson(): string
    {
        return json_encode([
            'execution_status' => 'Canceled',
            'close_reason' => 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function executorConfig(string $bonusUrl, string $doubleUrl): array
    {
        return [
            'doubleOrderId' => 2706,
            'jobId' => 'test-job-id',
            'authorizationBonus' => 'auth-bonus',
            'authorizationDouble' => 'auth-double',
            'identificationId' => 'taxi_easy_ua_pas4',
            'apiVersion' => '1',
            'connectAPI' => 'http://dispatcher.test',
            'responseBonus' => [
                'url' => $bonusUrl,
                'parameter' => [],
            ],
            'responseDouble' => [
                'url' => $doubleUrl,
                'parameter' => [],
            ],
            'bonusOrderHold' => 'uid-bonus-test',
        ];
    }
}
