<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\OrderLegActionMatrix;

$matrix = new OrderLegActionMatrix();
$cases = json_decode(
    (string) file_get_contents(__DIR__ . '/../tests/fixtures/leg_action_matrix_cases.json'),
    true
);

$failures = [];
$okBonus = 0;
$okNal = 0;

foreach ($cases as $i => $case) {
    $nal = $case['nal'];
    $bonus = $case['bonus'];
    $lastNal = $case['last_nal'] ?? null;
    $lastBonus = $case['last_bonus'] ?? null;
    $expNal = $case['nal_action'];
    $expBonus = $case['bonus_action'];

    if ($lastBonus !== null && $lastNal === null) {
        $result = $matrix->resolveBonusPhase($bonus, $nal, $lastBonus);
        if ($result === null) {
            $failures[] = [
                'row' => $i + 1,
                'phase' => 'bonus',
                'case' => $case,
                'error' => 'no rule',
            ];
        } elseif ($result['bonus_action'] !== $expBonus || $result['double_action'] !== $expNal) {
            $failures[] = [
                'row' => $i + 1,
                'phase' => 'bonus',
                'case' => $case,
                'got_bonus' => $result['bonus_action'],
                'got_nal' => $result['double_action'],
            ];
        } else {
            $okBonus++;
        }
    } elseif ($lastNal !== null && $lastBonus === null) {
        $result = $matrix->resolveNalPhase($nal, $bonus, $lastNal);
        if ($result === null) {
            $failures[] = [
                'row' => $i + 1,
                'phase' => 'nal',
                'case' => $case,
                'error' => 'no rule',
            ];
        } elseif ($result['double_action'] !== $expNal || $result['bonus_action'] !== $expBonus) {
            $failures[] = [
                'row' => $i + 1,
                'phase' => 'nal',
                'case' => $case,
                'got_nal' => $result['double_action'],
                'got_bonus' => $result['bonus_action'],
            ];
        } else {
            $okNal++;
        }
    } else {
        $failures[] = [
            'row' => $i + 1,
            'phase' => 'ambiguous',
            'case' => $case,
            'error' => 'both or neither last set',
        ];
    }
}

echo 'Total Лист1 cases: ' . count($cases) . PHP_EOL;
echo 'OK bonus-phase rows: ' . $okBonus . PHP_EOL;
echo 'OK nal-phase rows: ' . $okNal . PHP_EOL;
echo 'Failures: ' . count($failures) . PHP_EOL;

if ($failures !== []) {
    $byPhase = [];
    foreach ($failures as $failure) {
        $key = ($failure['phase'] ?? '?') . ':' . ($failure['error'] ?? 'mismatch');
        $byPhase[$key] = ($byPhase[$key] ?? 0) + 1;
    }
    echo 'Failure breakdown:' . PHP_EOL;
    foreach ($byPhase as $k => $n) {
        echo "  {$k}: {$n}" . PHP_EOL;
    }
    file_put_contents(
        __DIR__ . '/../tests/fixtures/list1_verify_failures.json',
        json_encode($failures, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
    echo 'Full failure list: tests/fixtures/list1_verify_failures.json' . PHP_EOL;
    foreach (array_slice($failures, 0, 10) as $failure) {
        echo json_encode($failure, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    exit(1);
}

exit(0);
