<?php

namespace Tests\Unit;

use App\Services\OrderLegActionMatrix;
use Tests\TestCase;

/**
 * Каждая строка листов «Безнал» и «Нал» из «Действия статусы.xlsx».
 * Генерация: python scripts/export_leg_action_rules.py
 */
class OrderLegActionMatrixExhaustiveTest extends TestCase
{
    /** @var OrderLegActionMatrix */
    private $matrix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matrix = new OrderLegActionMatrix();
    }

    public function test_all_excel_beznal_phase_rows(): void
    {
        $cases = $this->loadCases('leg_action_bonus_cases.json');

        foreach ($cases as $case) {
            $result = $this->matrix->resolveBonusPhase(
                $case['bonus'],
                $case['double'],
                $case['last_bonus']
            );

            $label = sprintf(
                'bonus=%s double=%s last_bonus=%s',
                $case['bonus'],
                $case['double'],
                $case['last_bonus'] ?? 'null'
            );

            $this->assertNotNull($result, "No bonus-phase rule for {$label}");
            $this->assertSame($case['bonus_action'], $result['bonus_action'], "bonus_action for {$label}");
            $this->assertSame($case['double_action'], $result['double_action'], "double_action for {$label}");
        }
    }

    public function test_all_excel_nal_phase_rows(): void
    {
        $cases = $this->loadCases('leg_action_nal_cases.json');

        foreach ($cases as $case) {
            $result = $this->matrix->resolveNalPhase(
                $case['double'],
                $case['bonus'],
                $case['last_double']
            );

            $label = sprintf(
                'double=%s bonus=%s last_double=%s',
                $case['double'],
                $case['bonus'],
                $case['last_double'] ?? 'null'
            );

            $this->assertNotNull($result, "No nal-phase rule for {$label}");
            $this->assertSame($case['double_action'], $result['double_action'], "double_action for {$label}");
            $this->assertSame($case['bonus_action'], $result['bonus_action'], "bonus_action for {$label}");
        }
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
