# prepositions.py (расширенная версия)
import regex as re

PREPOSITIONS = {
    "ru": {
        "origin": r"(?:с|из)\s+([А-ЯЁа-яё\s\d,\.\-]+?)(?=\s+(?:в|до|на)\b)",
        "destination": r"(?:в|до|на)\s+([А-ЯЁа-яё\s\d,\.\-]+?)(?=\s+(?:с|з|без|with|завтра|сегодня|терміново|срочно|кондиціонер|багаж|тварин|собак|дитин|чек|через|на\s+\d+|о\s+\d+:|$))"
    },
    "uk": {
        "origin": r"(?:з|із)\s+([А-Я ЇІҐЄа-яїіґє\s\d,\.\-]+?)(?=\s+до\b)",
        "destination": r"(?:до)\s+([А-Я ЇІҐЄа-яїіґє\s\d,\.\-]+?)(?=\s+(?:з|із|без|with|завтра|сьогодні|терміново|срочно|кондиціонер|багаж|тварин|собак|дитин|чек|через|на\s+\d+|о\s+\d+:|$))"
    },
    "en": {
        "origin": r"from\s+([A-Za-z\s\d,\.\-]+?)(?=\s+to\b)",
        "destination": r"to\s+([A-Za-z\s\d,\.\-]+?)(?=\s+(?:with|without|tomorrow|today|urgently|air conditioning|luggage|pet|child|check|in\s+\d+|at\s+\d+:|$))"
    }
}

KEYWORD_TO_CODE = {
    # БАГАЖ
    "большой чемодан": "BAGGAGE",
    "с багажом": "BAGGAGE",
    "велика валіза": "BAGGAGE",
    "з багажем": "BAGGAGE",
    "large suitcase": "BAGGAGE",
    "with luggage": "BAGGAGE",
    "загрузка салона": "BAGGAGE",
    "carbon loading": "BAGGAGE",
    "чемодан": "BAGGAGE",
    "валіза": "BAGGAGE",
    "багаж": "BAGGAGE",
    "luggage": "BAGGAGE",

    # ЖИВОТНЫЕ
    "с животным": "ANIMAL",
    "с собакой": "ANIMAL",
    "з твариною": "ANIMAL",
    "з собакою": "ANIMAL",
    "with pet": "ANIMAL",
    "with dog": "ANIMAL",
    "животное": "ANIMAL",
    "animal": "ANIMAL",
    "тварина": "ANIMAL",
    "pet": "ANIMAL",

    # КОНДИЦИОНЕР
    "кондиционер": "CONDIT",
    "кондиціонер": "CONDIT",
    "conditioner": "CONDIT",
    "air conditioning": "CONDIT",
    "з кондиціонером": "CONDIT",
    "с кондиционером": "CONDIT",
    "with air conditioning": "CONDIT",

    # ВСТРЕЧА
    "встреча с табличкой": "MEET",
    "зустріч з табличкою": "MEET",
    "meet with the sign": "MEET",

    # КУРЬЕР
    "курьер. доставка": "COURIER",
    "courier. delivery": "COURIER",

    # ЧЕК/ТЕРМИНАЛ
    "чек": "CHECK_OUT",
    "check": "CHECK_OUT",
    "терминал": "CHECK_OUT",
    "terminal": "CHECK_OUT",

    # ДЕТСКОЕ КРЕСЛО
    "детское кресло": "BABY_SEAT",
    "baby seat": "BABY_SEAT",
    "с ребенком": "BABY_SEAT",
    "з дитиною": "BABY_SEAT",
    "with child": "BABY_SEAT",
    "дитяче крісло": "BABY_SEAT",

    # ВОДИТЕЛЬ
    "драйвер": "DRIVER",
    "driver": "DRIVER",

    # НЕКУРЯЩИЙ ВОДИТЕЛЬ
    "некурящий водитель": "NO_SMOKE",
    "некурящий водій": "NO_SMOKE",
    "non-smoking driver": "NO_SMOKE",

    # АНГЛОЯЗЫЧНЫЙ ВОДИТЕЛЬ
    "англоязычный водитель": "ENGLISH",
    "англомовний водій": "ENGLISH",
    "english speaking driver": "ENGLISH",

    # КАБЕЛЬ/ТРОС
    "трос": "CABLE",
    "cable": "CABLE",

    # ТОПЛИВО
    "подвоз топлива": "FUEL",
    "підвіз палива": "FUEL",
    "fuel delivery": "FUEL",

    # ПРОВОДА
    "провод": "WIRES",
    "провід": "WIRES",
    "wire": "WIRES",

    # КУРЕНИЕ
    "я буду курить": "SMOKE",
    "я буду курити": "SMOKE",
    "i will smoke": "SMOKE",

    # ВРЕМЕННЫЕ ВЫРАЖЕНИЯ
    "завтра": "TIME",
    "сегодня": "TIME",
    "сьогодні": "TIME",
    "терміново": "TIME",
    "срочно": "TIME",
    "послезавтра": "TIME",
    "післязавтра": "TIME",
    "через 3 дня": "TIME",
    "через 3 дні": "TIME",
    "tomorrow": "TIME",
    "today": "TIME",
    "urgently": "TIME",
    "day after tomorrow": "TIME",
    "in 3 days": "TIME",
    "на 11 октября": "TIME",
    "на 11 жовтня": "TIME",
    "on October 11": "TIME",
    "17 часов 30 минут": "TIME",
    "17 годин 30 хвилин": "TIME",
    "17 hours 30 minutes": "TIME",
    "о 17:30": "TIME",
    "at 17:30": "TIME",
    "через 4 дня": "TIME",
    "через 4 дні": "TIME",
    "in 4 days": "TIME",
    "на 15 октября": "TIME",
    "на 15 жовтня": "TIME",
    "on October 15": "TIME",
    "в 14 часов 45 минут": "TIME",
    "в 14 годин 45 хвилин": "TIME",
    "at 14 hours 45 minutes": "TIME",
    "послезавтра о 10:00": "TIME",
    "післязавтра о 10:00": "TIME",
    "day after tomorrow at 10:00": "TIME",
    "зараз": "TIME",
    "прямо сейчас": "TIME",
    "right now": "TIME",
    "з валізою": "BAGGAGE",
    "с багажом": "BAGGAGE",
    "with luggage": "BAGGAGE",
    "валіза": "BAGGAGE",
    "багаж": "BAGGAGE",
    "без водія": "",
    "без водителя": "",
    "without driver": "",
    "на 13 жовтня": "TIME",
    "в 16 годин 30 хвилин": "TIME",
    "in 3 days": "TIME",
    "at 14:45": "TIME",
    # Добавьте в KEYWORD_TO_CODE:
    "о 17:30": "TIME",
    "at 17:30": "TIME",
    "at 14:45": "TIME",
    "на 13 жовтня": "TIME",
    "в 16 годин 30 хвилин": "TIME",
    "in 3 days": "TIME",
    "післязавтра": "TIME",
    "послезавтра": "TIME",
    "day after tomorrow": "TIME"
}
