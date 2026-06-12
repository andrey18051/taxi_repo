#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Export leg action rules from Excel CSV fixtures to config/order_status/*.json"""

import csv
import json
import re
from pathlib import Path

ACTIONS = {"опрос", "отмена", "востановление", "ничего"}
STATES = {
    "SearchesForCar",
    "WaitingCarSearch",
    "CarFound",
    "Running",
    "Canceled",
    "Executed",
    "CostCalculation",
}

ROOT = Path(__file__).resolve().parents[1]
FIXTURES = ROOT / "tests" / "fixtures"
OUT = ROOT / "config" / "order_status"


def split_glued_token(token: str):
    remaining = token
    ordered = sorted(STATES, key=len, reverse=True)
    result = []
    while remaining:
        matched = False
        for state in ordered:
            if remaining.startswith(state):
                result.append(state)
                remaining = remaining[len(state) :]
                matched = True
                break
        if not matched:
            break
    return result


def split_states(text: str):
    if not text or not text.strip():
        return []
    result = []
    for token in re.split(r"[\s,]+", text.strip()):
        if not token:
            continue
        if token in STATES:
            result.append(token)
        else:
            result.extend(split_glued_token(token))
    deduped = []
    for state in result:
        if state not in deduped:
            deduped.append(state)
    return deduped


def find_actions(row):
    """Return (bonus_action, double_action) scanning row for known action tokens."""
    found = [c.strip() for c in row if c.strip() in ACTIONS]
    if len(found) >= 2:
        return found[0], found[1]
    if len(found) == 1:
        return found[0], "ничего"
    return None, None


def parse_beznal(path: Path):
    rows = list(csv.reader(open(path, encoding="utf-8-sig")))
    rules = []
    seen = set()
    cur_bonus = None
    cur_double_opts = None
    cur_last_bonus_opts = None

    for row in rows[2:]:
        if not row or all(not c.strip() for c in row):
            cur_bonus = None
            cur_double_opts = None
            cur_last_bonus_opts = None
            continue

        bonus_action, double_action = find_actions(row)
        if not bonus_action:
            continue

        col0 = row[0].strip() if len(row) > 0 else ""
        col1 = row[1].strip() if len(row) > 1 else ""
        col2 = row[2].strip() if len(row) > 2 else ""

        if col0 in STATES:
            cur_bonus = col0
        if col1 and split_states(col1):
            cur_double_opts = split_states(col1)
        if col2:
            cur_last_bonus_opts = split_states(col2) or None

        if not cur_bonus:
            continue

        item = {
            "bonus": cur_bonus,
            "double_options": cur_double_opts or ["*"],
            "bonus_action": bonus_action,
            "double_action": double_action,
        }
        if cur_last_bonus_opts:
            item["last_bonus_options"] = cur_last_bonus_opts

        key = (
            item["bonus"],
            tuple(item["double_options"]),
            tuple(item.get("last_bonus_options", ("*",))),
            item["bonus_action"],
            item["double_action"],
        )
        if key in seen:
            continue
        seen.add(key)
        rules.append(item)

    return rules


def parse_nal(path: Path):
    rows = list(csv.reader(open(path, encoding="utf-8-sig")))
    rules = []
    seen = set()
    cur_double = None
    cur_bonus_opts = None
    cur_last_double_opts = None

    for row in rows[2:]:
        if not row or all(not c.strip() for c in row):
            cur_double = None
            cur_bonus_opts = None
            cur_last_double_opts = None
            continue

        double_action, bonus_action = find_actions(row)
        if not double_action:
            continue

        col0 = row[0].strip() if len(row) > 0 else ""
        col1 = row[1].strip() if len(row) > 1 else ""
        col2 = row[2].strip() if len(row) > 2 else ""

        if col0 in STATES:
            cur_double = col0
        if col1 and split_states(col1):
            cur_bonus_opts = split_states(col1)
        if col2:
            cur_last_double_opts = split_states(col2) or None

        if not cur_double:
            continue

        item = {
            "double": cur_double,
            "bonus_options": cur_bonus_opts or ["*"],
            "double_action": double_action,
            "bonus_action": bonus_action,
        }
        if cur_last_double_opts:
            item["last_double_options"] = cur_last_double_opts

        key = (
            item["double"],
            tuple(item["bonus_options"]),
            tuple(item.get("last_double_options", ("*",))),
            item["double_action"],
            item["bonus_action"],
        )
        if key in seen:
            continue
        seen.add(key)
        rules.append(item)

    return rules


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    bez = parse_beznal(FIXTURES / "order_status_Безнал.csv")
    nal = parse_nal(FIXTURES / "order_status_Нал.csv")
    (OUT / "leg_action_bonus.json").write_text(
        json.dumps(bez, ensure_ascii=False, indent=2), encoding="utf-8"
    )
    (OUT / "leg_action_nal.json").write_text(
        json.dumps(nal, ensure_ascii=False, indent=2), encoding="utf-8"
    )
    print(f"bonus rules: {len(bez)}")
    print(f"nal rules: {len(nal)}")
    for r in bez:
        print("  BEZ", r)


if __name__ == "__main__":
    main()
