from fastapi import FastAPI, HTTPException
import spacy
from pymorphy3 import MorphAnalyzer
import regex as re
import logging
import logging.config
from langdetect import detect, DetectorFactory

# Фиксируем seed для reproducibility langdetect
DetectorFactory.seed = 0

# Логирование
logging.config.fileConfig('logging.conf', disable_existing_loggers=False)
logger = logging.getLogger(__name__)

app = FastAPI()

# Загрузка spaCy моделей
try:
    nlp_uk = spacy.load("uk_core_news_lg")
    nlp_ru = spacy.load("ru_core_news_lg")
    nlp_en = spacy.load("en_core_web_lg")
except Exception as e:
    logger.error(f"Failed to load spaCy models: {str(e)}")
    raise

# Инициализация pymorphy3
analyzer_ru = MorphAnalyzer(lang='ru')
analyzer_uk = MorphAnalyzer(lang='uk')

# Ключевые слова для деталей
DETAILS_KEYWORDS = {
    "ru": ["завтра", "сегодня", "срочно", "большой чемодан", "с багажом",
           "с ребенком", "вечером", "утром", "днем", "с животным", "с собакой",
           "прямо сейчас", "кондиционер", "встреча с табличкой", "некурящий водитель"],
    "uk": ["завтра", "сьогодні", "терміново", "велика валіза", "з багажем",
           "з дитиною", "ввечері", "вранці", "вдень", "з твариною", "з собакою",
           "зараз", "кондиціонер", "зустріч з табличкою", "некурящий водій"],
    "en": ["tomorrow", "today", "urgently", "large suitcase", "with luggage",
           "with child", "evening", "morning", "afternoon", "with pet", "with dog",
           "right now", "conditioner", "meet with the sign", "non-smoking driver"]
}

# Регулярки для ORIGIN и DESTINATION
PREPOSITIONS = {
    "ru": {
        "origin": r"(?:с|из)\s+([а-яА-Я\s,-]+?)(?=\s+(?:в|до|на)\s+)",
        "destination": r"(?:в|до|на)\s+([а-яА-Я\s,-]+?)(?=$|\s)"
    },
    "uk": {
        "origin": r"(?:з|із)\s+([а-яА-ЯїіґєЇІҐЄ\s,-]+?)(?=\s+до\s+)",
        "destination": r"(?:до)\s+([а-яА-ЯїіґєЇІҐЄ\s,-]+?)(?=$|\s)"
    },
    "en": {
        "origin": r"from\s+([a-zA-Z\s,-]+?)(?=\s+to\s+)",
        "destination": r"to\s+(.+)"  # всё после "to" до конца строки
    }
}

LANGUAGE_KEYWORDS = {
    "ru": ["нужно", "закажи", "такси"],
    "uk": ["потрібно", "замов", "таксі"],
    "en": ["need", "order", "taxi"]
}

@app.post("/parse")
async def parse_text(data: dict):
    text = data.get("text", "").strip()
    if not text:
        raise HTTPException(status_code=400, detail="Text is required")
    logger.info(f"Processing: {text}")

    # Определяем язык
    try:
        language = detect(text)
        text_lower = text.lower().replace("'", " ")
        if any(w in text_lower for w in LANGUAGE_KEYWORDS["ru"]):
            language = "ru"
        elif any(w in text_lower for w in LANGUAGE_KEYWORDS["uk"]):
            language = "uk"
        elif any(w in text_lower for w in LANGUAGE_KEYWORDS["en"]):
            language = "en"
    except:
        language = "en"
    logger.info(f"Detected language: {language}")

    # Детали
    details = [kw for kw in DETAILS_KEYWORDS.get(language, []) if kw.lower() in text_lower]

    # Модели
    nlp = nlp_uk if language == "uk" else nlp_ru if language == "ru" else nlp_en
    analyzer = analyzer_uk if language == "uk" else analyzer_ru if language == "ru" else None

    # ORIGIN и DESTINATION через регулярки
    origin = None
    destination = None

    origin_match = re.search(PREPOSITIONS[language]["origin"], text_lower, re.UNICODE)
    destination_match = re.search(PREPOSITIONS[language]["destination"], text_lower, re.UNICODE)

    if origin_match:
        origin_candidate = origin_match.group(1).strip()
        if analyzer:
            origin = analyzer.parse(origin_candidate)[0].normal_form
        else:
            origin = origin_candidate

    if destination_match:
        destination_candidate = destination_match.group(1).strip()
        if analyzer:
            destination = analyzer.parse(destination_candidate)[0].normal_form if analyzer else destination_candidate
        else:
            destination = destination_candidate

    # spaCy для entity
    entities = []
    try:
        doc = nlp(text)
        for ent in doc.ents:
            if ent.label_ in ["GPE", "LOC"]:
                lemma = ent.text
                if analyzer:
                    lemma = analyzer.parse(ent.text)[0].normal_form
                # Присваиваем ORIGIN/DESTINATION если совпадает
                label = "LOC"
                if origin and lemma.lower() == origin.lower():
                    label = "ORIGIN"
                if destination and lemma.lower() == destination.lower():
                    label = "DESTINATION"
                entities.append({"text": lemma, "label": label})
    except Exception as e:
        logger.warning(f"spaCy failed: {e}")

    # Добавляем origin/destination, если не попали
    if origin and not any(e["label"]=="ORIGIN" for e in entities):
        entities.append({"text": origin, "label":"ORIGIN"})
    if destination and not any(e["label"]=="DESTINATION" for e in entities):
        entities.append({"text": destination, "label":"DESTINATION"})

    response = {
        "text": text,
        "response": {
            "text": text,
            "entities_spacy": entities,
            "entities_hf": [],
            "origin": origin,
            "destination": destination,
            "details": details
        }
    }
    return response
