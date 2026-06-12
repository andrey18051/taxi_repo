#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Replace giant switch in startNewProcessExecutionStatusJob with OrderForkLegExecutor."""

from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CONTROLLER = ROOT / "app" / "Http" / "Controllers" / "UniversalAndroidFunctionController.php"

REPLACEMENT = """                        $forkExecutor = new \\App\\Services\\OrderForkLegExecutor($this, [
                            'doubleOrderId' => $doubleOrderId,
                            'jobId' => $jobId,
                            'authorizationBonus' => $authorizationBonus,
                            'authorizationDouble' => $authorizationDouble,
                            'identificationId' => $identificationId,
                            'apiVersion' => $apiVersion,
                            'connectAPI' => $connectAPI,
                            'responseBonus' => $responseBonus,
                            'responseDouble' => $responseDouble,
                            'bonusOrderHold' => $bonusOrderHold,
                        ]);

                        $forkState = $forkExecutor->processIteration([
                            'uid_history' => $uid_history,
                            'doubleOrderRecord' => $doubleOrderRecord,
                            'bonusOrder' => $bonusOrder,
                            'doubleOrder' => $doubleOrder,
                            'newStatusBonus' => $newStatusBonus,
                            'newStatusDouble' => $newStatusDouble,
                            'lastStatusBonus' => $lastStatusBonus,
                            'lastStatusDouble' => $lastStatusDouble,
                            'lastStatusBonusTime' => $lastStatusBonusTime,
                            'lastStatusDoubleTime' => $lastStatusDoubleTime,
                            'lastTimeUpdate' => $lastTimeUpdate,
                            'updateTime' => $updateTime,
                            'no_required_time' => $no_required_time,
                        ]);

                        $bonusOrder = $forkState['bonusOrder'];
                        $doubleOrder = $forkState['doubleOrder'];
                        $newStatusBonus = $forkState['newStatusBonus'];
                        $newStatusDouble = $forkState['newStatusDouble'];
                        $lastStatusBonus = $forkState['lastStatusBonus'];
                        $lastStatusDouble = $forkState['lastStatusDouble'];
                        $lastStatusBonusTime = $forkState['lastStatusBonusTime'];
                        $lastStatusDoubleTime = $forkState['lastStatusDoubleTime'];
                        $lastTimeUpdate = $forkState['lastTimeUpdate'];
                        $updateTime = $forkState['updateTime'];

                        if (!empty($forkState['exit'])) {
                            return "exit";
                        }
"""

START_MARKER = "//Безнал ОБРАБОТКА статуса"  # only after live startNewProcessExecutionStatusJob
END_MARKER = 'return "exit";'


def main():
    text = CONTROLLER.read_text(encoding="utf-8")
    lines = text.splitlines(keepends=True)

    method_idx = None
    for i, line in enumerate(lines):
        if "public function startNewProcessExecutionStatusJob" in line and not line.lstrip().startswith("//"):
            method_idx = i
            break

    if method_idx is None:
        raise SystemExit("startNewProcessExecutionStatusJob not found")

    start_idx = None
    for i in range(method_idx, len(lines)):
        if START_MARKER in lines[i]:
            start_idx = i
            break

    if start_idx is None:
        raise SystemExit("Start marker not found")

  # End: return "exit" after nal-phase canceledFinish
    end_idx = None
    search_from = start_idx + 1
    for i in range(search_from, len(lines)):
        if 'Deleted doubleOrderRecord after double canceledAll true' in lines[i]:
            for j in range(i, min(len(lines), i + 10)):
                if END_MARKER in lines[j]:
                    end_idx = j
                    break
            break

    if end_idx is None:
        raise SystemExit("End marker not found")

    print(f"Replacing lines {start_idx + 1}-{end_idx + 1} ({end_idx - start_idx + 1} lines)")

    new_lines = (
        lines[:start_idx]
        + [REPLACEMENT]
        + lines[end_idx + 1 :]
    )

    CONTROLLER.write_text("".join(new_lines), encoding="utf-8")
    print("Done")


if __name__ == "__main__":
    main()
