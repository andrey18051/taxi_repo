<?php

namespace App\Services;

/**
 * Действия над плечами вилки (опрос / отмена / восстановление / ничего)
 * по листам «Безнал» и «Нал» из «Действия статусы.xlsx».
 */
class OrderLegActionMatrix
{
    /** @var array */
    private $bonusRules;

    /** @var array */
    private $nalRules;

    public function __construct(?string $bonusRulesPath = null, ?string $nalRulesPath = null)
    {
        $bonusPath = $bonusRulesPath ?? config_path('order_status/leg_action_bonus.json');
        $nalPath = $nalRulesPath ?? config_path('order_status/leg_action_nal.json');

        $this->bonusRules = $this->loadRules($bonusPath);
        $this->nalRules = $this->loadRules($nalPath);
    }

    /**
     * @return array{bonus_action: string, double_action: string}|null
     */
    public function resolve(string $bonusState, string $doubleState): ?array
    {
        return $this->resolveBonusPhase($bonusState, $doubleState, null);
    }

    /**
     * Фаза «Безнал»: внешний статус — bonus, внутренний — double.
     *
     * @return array{bonus_action: string, double_action: string, rule: array}|null
     */
    public function resolveBonusPhase(string $bonusState, string $doubleState, ?string $lastBonusState): ?array
    {
        $rule = $this->findRule(
            $this->bonusRules,
            'bonus',
            $bonusState,
            'double_options',
            $doubleState,
            'last_bonus_options',
            $lastBonusState
        );

        if ($rule === null) {
            return null;
        }

        return [
            'bonus_action' => $rule['bonus_action'],
            'double_action' => $rule['double_action'],
            'rule' => $rule,
        ];
    }

    /**
     * Фаза «Нал»: внешний статус — double, внутренний — bonus.
     *
     * @return array{double_action: string, bonus_action: string, rule: array}|null
     */
    public function resolveNalPhase(string $doubleState, string $bonusState, ?string $lastDoubleState): ?array
    {
        $rule = $this->findRule(
            $this->nalRules,
            'double',
            $doubleState,
            'bonus_options',
            $bonusState,
            'last_double_options',
            $lastDoubleState
        );

        if ($rule === null) {
            return null;
        }

        return [
            'double_action' => $rule['double_action'],
            'bonus_action' => $rule['bonus_action'],
            'rule' => $rule,
        ];
    }

    /**
     * @return array
     */
    private function loadRules(string $path)
    {
        if (!is_readable($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array $rules
     * @param string $primaryKey
     * @param string $primaryState
     * @param string $secondaryOptionsKey
     * @param string $secondaryState
     * @param string $lastOptionsKey
     * @param string|null $lastState
     * @return array|null
     */
    private function findRule(
        array $rules,
        $primaryKey,
        $primaryState,
        $secondaryOptionsKey,
        $secondaryState,
        $lastOptionsKey,
        $lastState
    ) {
        $matched = [];

        foreach ($rules as $rule) {
            if (!isset($rule[$primaryKey]) || $rule[$primaryKey] !== $primaryState) {
                continue;
            }
            if (!$this->matchesOptions($rule[$secondaryOptionsKey] ?? [], $secondaryState)) {
                continue;
            }
            $matched[] = $rule;
        }

        if ($matched === []) {
            return null;
        }

        foreach ($matched as $rule) {
            if (!empty($rule[$lastOptionsKey])) {
                if ($lastState !== null && $this->matchesOptions($rule[$lastOptionsKey], $lastState)) {
                    return $rule;
                }
            }
        }

        foreach ($matched as $rule) {
            if (empty($rule[$lastOptionsKey])) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * @param list<string> $options
     */
    private function matchesOptions(array $options, string $state): bool
    {
        if (in_array('*', $options, true)) {
            return true;
        }

        return in_array($state, $options, true);
    }
}
