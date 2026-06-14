<?php

namespace Tests\Unit;

use App\Services\OrderLegActionMatrix;
use Tests\TestCase;

/**
 * Кросс-проверка всех 490 строк листа «Лист1» из «Действия статусы.xlsx».
 * Фикстура: tests/fixtures/leg_action_matrix_cases.json
 */
class OrderLegActionMatrixList1Test extends TestCase
{
    /** @var OrderLegActionMatrix */
    private $matrix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matrix = new OrderLegActionMatrix();
    }

    public function test_all_list1_matrix_rows_match_config(): void
    {
        $cases = $this->loadMatrixCases();

        foreach ($cases as $index => $case) {
            $row = $index + 1;
            $lastNal = $case['last_nal'] ?? null;
            $lastBonus = $case['last_bonus'] ?? null;

            if ($lastBonus !== null && $lastNal === null) {
                $result = $this->matrix->resolveBonusPhase(
                    (string) $case['bonus'],
                    (string) $case['nal'],
                    (string) $lastBonus
                );
                $label = sprintf(
                    'row %d bonus=%s nal=%s last_bonus=%s',
                    $row,
                    $case['bonus'],
                    $case['nal'],
                    $lastBonus
                );
                $this->assertNotNull($result, "No bonus-phase rule for {$label}");
                $this->assertSame($case['bonus_action'], $result['bonus_action'], "bonus_action {$label}");
                $this->assertSame($case['nal_action'], $result['double_action'], "nal_action {$label}");
                continue;
            }

            if ($lastNal !== null && $lastBonus === null) {
                $result = $this->matrix->resolveNalPhase(
                    (string) $case['nal'],
                    (string) $case['bonus'],
                    (string) $lastNal
                );
                $label = sprintf(
                    'row %d nal=%s bonus=%s last_nal=%s',
                    $row,
                    $case['nal'],
                    $case['bonus'],
                    $lastNal
                );
                $this->assertNotNull($result, "No nal-phase rule for {$label}");
                $this->assertSame($case['nal_action'], $result['double_action'], "nal_action {$label}");
                $this->assertSame($case['bonus_action'], $result['bonus_action'], "bonus_action {$label}");
                continue;
            }

            $this->fail(sprintf(
                'row %d: ambiguous last_nal=%s last_bonus=%s',
                $row,
                $lastNal ?? 'null',
                $lastBonus ?? 'null'
            ));
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadMatrixCases(): array
    {
        $path = base_path('tests/fixtures/leg_action_matrix_cases.json');
        $decoded = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($decoded);
        $this->assertCount(490, $decoded, 'Expected 490 List1 rows in leg_action_matrix_cases.json');

        return $decoded;
    }
}
