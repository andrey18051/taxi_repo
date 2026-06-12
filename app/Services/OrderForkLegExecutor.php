<?php

namespace App\Services;

use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Models\DoubleOrder;
use App\Models\Uid_history;
use Illuminate\Support\Facades\Log;

/**
 * Выполняет итерацию вилки заказа по матрице Excel вместо вложенных switch.
 */
class OrderForkLegExecutor
{
    private const LOG_PREFIX = '[ForkMatrix]';

    private const ACTION_POLL = 'опрос';
    private const ACTION_CANCEL = 'отмена';
    private const ACTION_RESTORE = 'востановление';
    private const ACTION_NOTHING = 'ничего';

    /** @var UniversalAndroidFunctionController */
    private $controller;

    /** @var array */
    private $config;

    /** @var OrderLegActionMatrix */
    private $matrix;

    public function __construct(UniversalAndroidFunctionController $controller, array $config)
    {
        $this->controller = $controller;
        $this->config = $config;
        $this->matrix = new OrderLegActionMatrix();
    }

    /**
     * Одна итерация цикла: фаза «Безнал», canceledFinish, фаза «Нал», canceledFinish.
     *
     * @param array $state
     * @return array
     */
    public function processIteration(array $state)
    {
        $this->logPhaseStart('iteration', $state);

        $state = $this->runBonusPhase($state);

        if ($this->handleCanceledFinish($state, 'after_bonus')) {
            $state['exit'] = true;
            return $state;
        }

        $state = $this->runNalPhase($state);

        if ($this->handleCanceledFinish($state, 'after_nal')) {
            $state['exit'] = true;
            return $state;
        }

        $state['exit'] = false;
        $this->log('iteration_complete', $this->snapshot($state));

        return $state;
    }

    /**
     * @param array $state
     * @return array
     */
    private function runBonusPhase(array $state)
    {
        $this->log('bonus_phase_start', [
            'newStatusBonus' => $state['newStatusBonus'],
            'newStatusDouble' => $state['newStatusDouble'],
            'lastStatusBonus' => $state['lastStatusBonus'],
            'bonusOrder' => $state['bonusOrder'],
            'doubleOrder' => $state['doubleOrder'],
        ]);

        $resolved = $this->matrix->resolveBonusPhase(
            (string) $state['newStatusBonus'],
            (string) $state['newStatusDouble'],
            isset($state['lastStatusBonus']) ? (string) $state['lastStatusBonus'] : null
        );

        if ($resolved === null) {
            $this->log('bonus_phase_no_rule', [
                'bonus' => $state['newStatusBonus'],
                'double' => $state['newStatusDouble'],
                'lastBonus' => $state['lastStatusBonus'] ?? null,
            ]);
            return $state;
        }

        $this->log('bonus_phase_rule', [
            'rule' => $resolved['rule'],
            'bonus_action' => $resolved['bonus_action'],
            'double_action' => $resolved['double_action'],
        ]);

        $state = $this->applyLegActions(
            $state,
            $resolved['bonus_action'],
            $resolved['double_action'],
            'bonus_phase'
        );

        if ($state['newStatusBonus'] !== null) {
            $state['lastStatusBonus'] = $state['newStatusBonus'];
            $this->log('lastStatusBonus_updated', ['lastStatusBonus' => $state['lastStatusBonus']]);
        }

        $state['bonusOrder'] = $state['uid_history']->uid_bonusOrder;
        $state['doubleOrder'] = $state['uid_history']->uid_doubleOrder;

        $this->log('bonus_phase_end', $this->snapshot($state));

        return $state;
    }

    /**
     * @param array $state
     * @return array
     */
    private function runNalPhase(array $state)
    {
        $this->log('nal_phase_start', [
            'newStatusBonus' => $state['newStatusBonus'],
            'newStatusDouble' => $state['newStatusDouble'],
            'lastStatusDouble' => $state['lastStatusDouble'],
            'bonusOrder' => $state['bonusOrder'],
            'doubleOrder' => $state['doubleOrder'],
        ]);

        $resolved = $this->matrix->resolveNalPhase(
            (string) $state['newStatusDouble'],
            (string) $state['newStatusBonus'],
            isset($state['lastStatusDouble']) ? (string) $state['lastStatusDouble'] : null
        );

        if ($resolved === null) {
            $this->log('nal_phase_no_rule', [
                'double' => $state['newStatusDouble'],
                'bonus' => $state['newStatusBonus'],
                'lastDouble' => $state['lastStatusDouble'] ?? null,
            ]);
            return $state;
        }

        $this->log('nal_phase_rule', [
            'rule' => $resolved['rule'],
            'double_action' => $resolved['double_action'],
            'bonus_action' => $resolved['bonus_action'],
        ]);

        $state = $this->applyLegActions(
            $state,
            $resolved['bonus_action'],
            $resolved['double_action'],
            'nal_phase'
        );

        if ($state['newStatusDouble'] !== null) {
            $state['lastStatusDouble'] = $state['newStatusDouble'];
            $this->log('lastStatusDouble_updated', ['lastStatusDouble' => $state['lastStatusDouble']]);
        }

        $state['bonusOrder'] = $state['uid_history']->uid_bonusOrder;
        $state['doubleOrder'] = $state['uid_history']->uid_doubleOrder;

        $this->log('nal_phase_end', $this->snapshot($state));

        return $state;
    }

    /**
     * Сначала действие на bonus, затем на double (как в старом switch).
     *
     * @param array $state
     * @param string $bonusAction
     * @param string $doubleAction
     * @param string $phaseTag
     * @return array
     */
    private function applyLegActions(array $state, $bonusAction, $doubleAction, $phaseTag)
    {
        $this->log('apply_actions', [
            'phase' => $phaseTag,
            'bonus_action' => $bonusAction,
            'double_action' => $doubleAction,
        ]);

        $state = $this->executeLegAction($state, 'bonus', $bonusAction, $phaseTag);
        $state = $this->executeLegAction($state, 'double', $doubleAction, $phaseTag);

        return $state;
    }

    /**
     * @param array $state
     * @param string $leg bonus|double
     * @param string $action
     * @param string $phaseTag
     * @return array
     */
    private function executeLegAction(array $state, $leg, $action, $phaseTag)
    {
        if ($action === self::ACTION_NOTHING || $action === '') {
            $this->log('leg_skip', ['phase' => $phaseTag, 'leg' => $leg, 'action' => $action]);
            return $state;
        }

        $this->log('leg_action_start', [
            'phase' => $phaseTag,
            'leg' => $leg,
            'action' => $action,
            'orderUid' => $leg === 'bonus' ? $state['bonusOrder'] : $state['doubleOrder'],
        ]);

        if ($action === self::ACTION_RESTORE) {
            $state = $this->restoreLeg($state, $leg, $phaseTag);
        } elseif ($action === self::ACTION_CANCEL) {
            $state = $this->cancelLeg($state, $leg, $phaseTag);
        } elseif ($action === self::ACTION_POLL) {
            $state = $this->pollLeg($state, $leg, $phaseTag);
        } else {
            $this->log('leg_unknown_action', ['leg' => $leg, 'action' => $action]);
        }

        $this->log('leg_action_end', [
            'phase' => $phaseTag,
            'leg' => $leg,
            'action' => $action,
            'newStatusBonus' => $state['newStatusBonus'],
            'newStatusDouble' => $state['newStatusDouble'],
        ]);

        return $state;
    }

    /**
     * @param array $state
     * @param string $leg
     * @param string $phaseTag
     * @return array
     */
    private function restoreLeg(array $state, $leg, $phaseTag)
    {
        if ($leg === 'bonus') {
            $newUid = $this->controller->orderNewCreat(
                $this->config['authorizationBonus'],
                $this->config['identificationId'],
                $this->config['apiVersion'],
                $this->config['responseBonus']['url'],
                $this->config['responseBonus']['parameter']
            );
            $state['bonusOrder'] = $newUid;
            /** @var Uid_history $uidHistory */
            $uidHistory = $state['uid_history'];
            $uidHistory->uid_bonusOrder = $newUid;
            $uidHistory->save();
            $this->log('restore_bonus', ['newUid' => $newUid, 'phase' => $phaseTag]);
            return $this->pollLeg($state, 'bonus', $phaseTag . '_after_restore');
        }

        $newUid = $this->controller->orderNewCreat(
            $this->config['authorizationDouble'],
            $this->config['identificationId'],
            $this->config['apiVersion'],
            $this->config['responseDouble']['url'],
            $this->config['responseDouble']['parameter']
        );
        $state['doubleOrder'] = $newUid;
        /** @var Uid_history $uidHistory */
        $uidHistory = $state['uid_history'];
        $uidHistory->uid_doubleOrder = $newUid;
        $uidHistory->save();
        $this->log('restore_double', ['newUid' => $newUid, 'phase' => $phaseTag]);

        return $this->pollLeg($state, 'double', $phaseTag . '_after_restore');
    }

    /**
     * @param array $state
     * @param string $leg
     * @param string $phaseTag
     * @return array
     */
    private function cancelLeg(array $state, $leg, $phaseTag)
    {
        if ($leg === 'bonus') {
            $this->controller->orderCanceled(
                $state['bonusOrder'],
                'bonus',
                $this->config['connectAPI'],
                $this->config['authorizationBonus'],
                $this->config['identificationId'],
                $this->config['apiVersion']
            );
            $this->log('cancel_bonus', ['uid' => $state['bonusOrder'], 'phase' => $phaseTag]);
            return $this->pollLeg($state, 'bonus', $phaseTag . '_after_cancel');
        }

        $this->controller->orderCanceled(
            $state['doubleOrder'],
            'double',
            $this->config['connectAPI'],
            $this->config['authorizationDouble'],
            $this->config['identificationId'],
            $this->config['apiVersion']
        );
        $this->log('cancel_double', ['uid' => $state['doubleOrder'], 'phase' => $phaseTag]);

        return $this->pollLeg($state, 'double', $phaseTag . '_after_cancel');
    }

    /**
     * @param array $state
     * @param string $leg
     * @param string $phaseTag
     * @return array
     */
    private function pollLeg(array $state, $leg, $phaseTag)
    {
        if ($leg === 'bonus') {
            $state['lastTimeUpdate'] = $state['lastStatusBonusTime'];
            $status = $this->controller->newStatus(
                $this->config['authorizationBonus'],
                $this->config['identificationId'],
                $this->config['apiVersion'],
                $this->config['responseBonus']['url'],
                $state['bonusOrder'],
                'bonus',
                $state['lastTimeUpdate'],
                $state['updateTime'],
                $state['uid_history']
            );
            $state['newStatusBonus'] = $status;
            $state['lastStatusBonusTime'] = time();
            $state['bonusOrder'] = $state['uid_history']->uid_bonusOrder;
            $this->log('poll_bonus', [
                'phase' => $phaseTag,
                'status' => $status,
                'bonusOrder' => $state['bonusOrder'],
            ]);
        } else {
            $state['lastTimeUpdate'] = $state['lastStatusDoubleTime'];
            $status = $this->controller->newStatus(
                $this->config['authorizationDouble'],
                $this->config['identificationId'],
                $this->config['apiVersion'],
                $this->config['responseDouble']['url'],
                $state['doubleOrder'],
                'double',
                $state['lastTimeUpdate'],
                $state['updateTime'],
                $state['uid_history']
            );
            $state['newStatusDouble'] = $status;
            $state['lastStatusDoubleTime'] = time();
            $state['doubleOrder'] = $state['uid_history']->uid_doubleOrder;
            $this->log('poll_double', [
                'phase' => $phaseTag,
                'status' => $status,
                'doubleOrder' => $state['doubleOrder'],
            ]);
        }

        if (!empty($state['no_required_time'])) {
            $state['updateTime'] = 5;
            $this->log('updateTime_shortened', ['updateTime' => 5, 'phase' => $phaseTag, 'leg' => $leg]);
        }

        return $state;
    }

    /**
     * @param array $state
     * @param string $checkpoint
     * @return bool true если нужно выйти из job
     */
    private function handleCanceledFinish(array &$state, $checkpoint)
    {
        $this->log('canceledFinish_call', [
            'checkpoint' => $checkpoint,
            'lastStatusBonus' => $state['lastStatusBonus'],
            'lastStatusDouble' => $state['lastStatusDouble'],
            'bonusOrder' => $state['bonusOrder'],
            'doubleOrder' => $state['doubleOrder'],
        ]);

        $canceledAll = $this->controller->canceledFinish(
            $state['lastStatusBonus'],
            $state['lastStatusDouble'],
            $this->config['bonusOrderHold'],
            $state['bonusOrder'],
            $this->config['connectAPI'],
            $this->config['authorizationBonus'],
            $this->config['identificationId'],
            $this->config['apiVersion'],
            $state['doubleOrder'],
            $this->config['authorizationDouble']
        );

        $this->log('canceledFinish_result', [
            'checkpoint' => $checkpoint,
            'canceledAll' => $canceledAll,
        ]);

        if (!$canceledAll) {
            return false;
        }

        $this->controller->newStatus(
            $this->config['authorizationBonus'],
            $this->config['identificationId'],
            $this->config['apiVersion'],
            $this->config['responseBonus']['url'],
            $state['bonusOrder'],
            'bonus',
            $state['lastTimeUpdate'],
            $state['updateTime'],
            $state['uid_history']
        );

        $this->controller->newStatus(
            $this->config['authorizationDouble'],
            $this->config['identificationId'],
            $this->config['apiVersion'],
            $this->config['responseDouble']['url'],
            $state['doubleOrder'],
            'double',
            $state['lastTimeUpdate'],
            $state['updateTime'],
            $state['uid_history']
        );

        /** @var DoubleOrder $doubleOrderRecord */
        $doubleOrderRecord = $state['doubleOrderRecord'];
        $doubleOrderRecord->delete();

        $this->log('canceledFinish_exit', [
            'checkpoint' => $checkpoint,
            'doubleOrderId' => $this->config['doubleOrderId'],
            'jobId' => $this->config['jobId'],
        ]);

        return true;
    }

    /**
     * @param string $event
     * @param array $state
     */
    private function logPhaseStart($event, array $state)
    {
        $this->log($event, array_merge($this->snapshot($state), [
            'doubleOrderId' => $this->config['doubleOrderId'] ?? null,
            'jobId' => $this->config['jobId'] ?? null,
        ]));
    }

    /**
     * @param array $state
     * @return array
     */
    private function snapshot(array $state)
    {
        return [
            'bonusOrder' => $state['bonusOrder'] ?? null,
            'doubleOrder' => $state['doubleOrder'] ?? null,
            'newStatusBonus' => $state['newStatusBonus'] ?? null,
            'newStatusDouble' => $state['newStatusDouble'] ?? null,
            'lastStatusBonus' => $state['lastStatusBonus'] ?? null,
            'lastStatusDouble' => $state['lastStatusDouble'] ?? null,
            'updateTime' => $state['updateTime'] ?? null,
        ];
    }

    /**
     * @param string $event
     * @param array $context
     */
    private function log($event, array $context = [])
    {
        $payload = array_merge(
            ['event' => $event],
            $context
        );
        Log::info(self::LOG_PREFIX . ' ' . $event . ' ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
