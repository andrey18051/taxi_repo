<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Models\DoubleOrder;
use App\Services\OrderForkLegExecutor;
use ReflectionMethod;
use Tests\Support\ForkDispatchRecorder;
use Tests\Support\ForkUidHistoryStub;
use Tests\TestCase;

/**
 * Каждая строка листов «Безнал» и «Нал» из «Действия статусы.xlsx» —
 * проверка, что executor выполняет те же действия, что задаёт матрица.
 *
 * Фикстуры: tests/fixtures/leg_action_bonus_cases.json, leg_action_nal_cases.json
 * Генерация: python scripts/export_leg_action_rules.py
 */
class OrderForkLegExecutorExhaustiveTest extends TestCase
{
    private const BONUS_URL = 'http://dispatcher.test/bonus';

    private const DOUBLE_URL = 'http://dispatcher.test/double';

    /** @var ForkDispatchRecorder */
    private $recorder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recorder = new ForkDispatchRecorder(self::BONUS_URL, self::DOUBLE_URL);
    }

    public function test_all_excel_bonus_phase_executor_actions(): void
    {
        $cases = $this->loadCases('leg_action_bonus_cases.json');

        foreach ($cases as $case) {
            $this->recorder->reset();
            $this->recorder->bonusPollStatus = $case['bonus'];
            $this->recorder->doublePollStatus = $case['double'];

            $uidHistory = ForkDispatchRecorder::makeUidHistoryStub();
            $this->applyClosedLegStatusForRestore($uidHistory, $case['bonus_action'], $case['double_action']);

            $state = $this->baseState($uidHistory, [
                'newStatusBonus' => $case['bonus'],
                'newStatusDouble' => $case['double'],
                'lastStatusBonus' => $case['last_bonus'],
                'lastStatusDouble' => $case['double'],
            ]);

            $state = $this->invokeBonusPhase($state);

            $label = sprintf(
                'bonus=%s double=%s last_bonus=%s',
                $case['bonus'],
                $case['double'],
                $case['last_bonus'] ?? 'null'
            );

            $expected = ForkDispatchRecorder::expectedPhaseCalls(
                $case['bonus_action'],
                $case['double_action'],
                $this->isRestoreLegClosed($uidHistory, 'bonus', $state),
                $this->isRestoreLegClosed($uidHistory, 'double', $state)
            );

            $this->assertSame(
                $expected,
                $this->recorder->calls,
                "Executor calls mismatch for bonus phase {$label}"
            );
        }
    }

    public function test_all_excel_nal_phase_executor_actions(): void
    {
        $cases = $this->loadCases('leg_action_nal_cases.json');

        foreach ($cases as $case) {
            $this->recorder->reset();
            $this->recorder->bonusPollStatus = $case['bonus'];
            $this->recorder->doublePollStatus = $case['double'];

            $uidHistory = ForkDispatchRecorder::makeUidHistoryStub();
            $this->applyClosedLegStatusForRestore($uidHistory, $case['bonus_action'], $case['double_action']);

            $state = $this->baseState($uidHistory, [
                'newStatusBonus' => $case['bonus'],
                'newStatusDouble' => $case['double'],
                'lastStatusBonus' => $case['bonus'],
                'lastStatusDouble' => $case['last_double'],
            ]);

            $state = $this->invokeNalPhase($state);

            $label = sprintf(
                'double=%s bonus=%s last_double=%s',
                $case['double'],
                $case['bonus'],
                $case['last_double'] ?? 'null'
            );

            $expected = ForkDispatchRecorder::expectedPhaseCalls(
                $case['bonus_action'],
                $case['double_action'],
                $this->isRestoreLegClosed($uidHistory, 'bonus', $state),
                $this->isRestoreLegClosed($uidHistory, 'double', $state)
            );

            $this->assertSame(
                $expected,
                $this->recorder->calls,
                "Executor calls mismatch for nal phase {$label}"
            );
        }
    }

    public function test_search_to_canceled_preserves_last_for_next_bonus_phase(): void
    {
        $this->recorder->reset();
        $this->recorder->bonusPollStatus = 'Canceled';
        $this->recorder->doublePollStatus = 'SearchesForCar';

        $uidHistory = ForkDispatchRecorder::makeUidHistoryStub();
        $state = $this->baseState($uidHistory, [
            'newStatusBonus' => 'SearchesForCar',
            'newStatusDouble' => 'SearchesForCar',
            'lastStatusBonus' => 'SearchesForCar',
            'lastStatusDouble' => 'SearchesForCar',
        ]);

        $state = $this->invokeBonusPhase($state);

        $this->assertSame('Canceled', $state['newStatusBonus']);
        $this->assertSame('SearchesForCar', $state['lastStatusBonus']);

        $this->recorder->reset();
        $this->recorder->bonusPollStatus = 'Canceled';
        $this->recorder->doublePollStatus = 'SearchesForCar';

        $state = $this->invokeBonusPhase($state);

        $this->assertSame(['poll_double'], $this->recorder->calls);
    }

    public function test_restore_bonus_duplicate_cancels_double_when_nal_still_searching(): void
    {
        $this->recorder->reset();
        $this->recorder->restoreFailureMessage = 'New UID Дублирование заказа';
        $this->recorder->bonusPollStatus = 'Canceled';
        $this->recorder->doublePollStatus = 'SearchesForCar';

        $uidHistory = ForkDispatchRecorder::makeUidHistoryStub();
        $uidHistory->bonus_status = ForkDispatchRecorder::closedLegJson();

        $state = $this->baseState($uidHistory, [
            'newStatusBonus' => 'Canceled',
            'newStatusDouble' => 'SearchesForCar',
            'lastStatusBonus' => 'CarFound',
            'lastStatusDouble' => 'SearchesForCar',
        ]);

        $state = $this->invokeBonusPhase($state);

        $this->assertContains('restore_bonus', $this->recorder->calls);
        $this->assertContains('cancel_double', $this->recorder->calls);
        $this->assertContains('poll_double', $this->recorder->calls);
    }

    public function test_restore_double_skipped_when_client_cancel_requested(): void
    {
        $this->recorder->reset();
        $this->recorder->bonusPollStatus = 'SearchesForCar';
        $this->recorder->doublePollStatus = 'CostCalculation';

        $uidHistory = ForkDispatchRecorder::makeUidHistoryStub();
        $uidHistory->cancel = '1';
        $uidHistory->double_status = ForkDispatchRecorder::closedLegJson();

        $state = $this->baseState($uidHistory, [
            'newStatusBonus' => 'SearchesForCar',
            'newStatusDouble' => 'CostCalculation',
            'lastStatusBonus' => 'SearchesForCar',
            'lastStatusDouble' => 'SearchesForCar',
        ]);

        $state = $this->invokeBonusPhase($state);

        $this->assertNotContains('restore_double', $this->recorder->calls);
        $this->assertContains('poll_bonus', $this->recorder->calls);
        $this->assertContains('poll_double', $this->recorder->calls);
    }

    public function test_restore_double_skipped_when_both_legs_already_canceled(): void
    {
        $this->recorder->reset();
        $this->recorder->bonusPollStatus = 'Canceled';
        $this->recorder->doublePollStatus = 'Canceled';

        $uidHistory = ForkDispatchRecorder::makeUidHistoryStub();
        $uidHistory->double_status = ForkDispatchRecorder::closedLegJson();

        $state = $this->baseState($uidHistory, [
            'newStatusBonus' => 'Canceled',
            'newStatusDouble' => 'Canceled',
            'lastStatusBonus' => 'SearchesForCar',
            'lastStatusDouble' => 'Canceled',
        ]);

        $state = $this->invokeBonusPhase($state);

        $this->assertNotContains('restore_double', $this->recorder->calls);
        $this->assertNotContains('restore_bonus', $this->recorder->calls);
    }

    /**
     * Сценарий Oleg: Поиск/Поиск → Нет авто/Поиск — карту не отменять (опрос, не cancel_bonus).
     */
    public function test_nal_phase_waiting_car_search_does_not_cancel_bonus(): void
    {
        $this->recorder->reset();
        $this->recorder->bonusPollStatus = 'SearchesForCar';
        $this->recorder->doublePollStatus = 'WaitingCarSearch';

        $uidHistory = ForkDispatchRecorder::makeUidHistoryStub();
        $state = $this->baseState($uidHistory, [
            'newStatusBonus' => 'SearchesForCar',
            'newStatusDouble' => 'WaitingCarSearch',
            'lastStatusBonus' => 'SearchesForCar',
            'lastStatusDouble' => 'SearchesForCar',
        ]);

        $state = $this->invokeNalPhase($state);

        $this->assertNotContains('cancel_bonus', $this->recorder->calls);
        $this->assertSame(['poll_double'], $this->recorder->calls);
    }

    /**
     * Регрессия кейса из логов: Canceled+4 на нал при поиске карты — не отменять bonus.
     */
    public function test_nal_phase_technical_canceled_double_with_search_bonus_only_polls(): void
    {
        $matrix = new \App\Services\OrderLegActionMatrix();
        $resolved = $matrix->resolveNalPhase(
            'WaitingCarSearch',
            'SearchesForCar',
            'SearchesForCar'
        );

        $this->assertNotNull($resolved);
        $this->assertSame('опрос', $resolved['double_action']);
        $this->assertSame('ничего', $resolved['bonus_action']);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function baseState($uidHistory, array $overrides): array
    {
        $doubleOrderRecord = $this->createMock(DoubleOrder::class);

        return array_merge([
            'uid_history' => $uidHistory,
            'doubleOrderRecord' => $doubleOrderRecord,
            'bonusOrder' => 'uid-bonus-test',
            'doubleOrder' => 'uid-double-test',
            'newStatusBonus' => 'SearchesForCar',
            'newStatusDouble' => 'SearchesForCar',
            'lastStatusBonus' => 'SearchesForCar',
            'lastStatusDouble' => 'SearchesForCar',
            'lastStatusBonusTime' => time() - 60,
            'lastStatusDoubleTime' => time() - 60,
            'lastTimeUpdate' => time() - 60,
            'updateTime' => 5,
            'no_required_time' => true,
        ], $overrides);
    }

    /**
     * @param ForkUidHistoryStub $uidHistory
     */
    private function applyClosedLegStatusForRestore($uidHistory, string $bonusAction, string $doubleAction): void
    {
        if ($bonusAction === 'востановление') {
            $uidHistory->bonus_status = ForkDispatchRecorder::closedLegJson();
        }

        if ($doubleAction === 'востановление') {
            $uidHistory->double_status = ForkDispatchRecorder::closedLegJson();
        }
    }

    /**
     * @param ForkUidHistoryStub $uidHistory
     * @param array<string, mixed> $state
     */
    private function isRestoreLegClosed($uidHistory, string $leg, array $state): bool
    {
        if ($leg === 'bonus') {
            $raw = $this->decodeLegStatus($uidHistory->bonus_status);

            return OrderStatusController::isLegClosedForForkRecreate(
                $raw,
                isset($state['newStatusBonus']) ? (string) $state['newStatusBonus'] : null
            );
        }

        $raw = $this->decodeLegStatus($uidHistory->double_status);

        return OrderStatusController::isLegClosedForForkRecreate(
            $raw,
            isset($state['newStatusDouble']) ? (string) $state['newStatusDouble'] : null
        );
    }

    /**
     * @param string|null $json
     * @return array<string, mixed>|null
     */
    private function decodeLegStatus($json): ?array
    {
        if ($json === null || $json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function createExecutor(): OrderForkLegExecutor
    {
        $mock = $this->createMock(UniversalAndroidFunctionController::class);
        $this->recorder->wireControllerMock($mock);

        return new OrderForkLegExecutor(
            $mock,
            ForkDispatchRecorder::executorConfig(self::BONUS_URL, self::DOUBLE_URL)
        );
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function invokeBonusPhase(array $state): array
    {
        $executor = $this->createExecutor();

        $method = new ReflectionMethod(OrderForkLegExecutor::class, 'runBonusPhase');
        $method->setAccessible(true);

        return $method->invoke($executor, $state);
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function invokeNalPhase(array $state): array
    {
        $executor = $this->createExecutor();

        $method = new ReflectionMethod(OrderForkLegExecutor::class, 'runNalPhase');
        $method->setAccessible(true);

        return $method->invoke($executor, $state);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCases(string $filename): array
    {
        $path = base_path('tests/fixtures/' . $filename);
        $decoded = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($decoded);
        $this->assertNotEmpty($decoded, 'Run: python scripts/export_leg_action_rules.py');

        return $decoded;
    }
}
