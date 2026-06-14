<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\OrderLegActionMatrix;

$matrix = new OrderLegActionMatrix();

$scenarios = [
    ['label' => '09:26:25 карта снята из поиска', 'phase' => 'bonus', 'bonus' => 'Canceled', 'nal' => 'SearchesForCar', 'last' => 'SearchesForCar', 'exp_nal' => 'опрос', 'exp_bonus' => 'ничего'],
    ['label' => '09:26:28+ prev bonus=Canceled (Лист1:325)', 'phase' => 'bonus', 'bonus' => 'Canceled', 'nal' => 'SearchesForCar', 'last' => 'Canceled', 'exp_nal' => 'опрос', 'exp_bonus' => 'востановление'],
    ['label' => '09:27:31 нал Running, prev bonus Canceled', 'phase' => 'bonus', 'bonus' => 'Canceled', 'nal' => 'Running', 'last' => 'Canceled', 'exp_nal' => 'опрос', 'exp_bonus' => 'ничего'],
    ['label' => '09:27:31 нал Running, prev bonus SearchesForCar', 'phase' => 'bonus', 'bonus' => 'Canceled', 'nal' => 'Running', 'last' => 'SearchesForCar', 'exp_nal' => 'опрос', 'exp_bonus' => 'ничего'],
];

foreach ($scenarios as $s) {
    $r = $matrix->resolveBonusPhase($s['bonus'], $s['nal'], $s['last']);
    $ok = $r && $r['double_action'] === $s['exp_nal'] && $r['bonus_action'] === $s['exp_bonus'];
    echo ($ok ? 'OK' : 'FAIL') . ' — ' . $s['label'] . PHP_EOL;
    if (!$ok) {
        echo '  expected nal=' . $s['exp_nal'] . ' bonus=' . $s['exp_bonus'] . PHP_EOL;
        echo '  got: ' . json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}
