import regex as re

PREPOSITIONS = {
    "ru": {
        "origin": r"(?:с|из)\s+([А-ЯЁа-яё\s\d,\.]+?)(?=\s+(?:в|до|на)\b)",
        "destination": r"(?:в|до|на)\s+([А-ЯЁа-яё\s\d,\.]+?)(?=\s|$)"
    },
    "uk": {
        "origin": r"(?:з|із)\s+([А-ЯЇІҐЄа-яїіґє\s\d,\.]+?)(?=\s+до\b)",
        "destination": r"(?:до)\s+([А-ЯЇІҐЄа-яїіґє\s\d,\.]+)"
    },
    "en": {
        "origin": r"from\s+([A-Za-z\s\d,]+?)(?=\s+to\b)",
        "destination": r"to\s+([A-Za-z\s\d,]+?)(?=\s|$)"
    }
}

