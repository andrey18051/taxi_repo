from fastapi import FastAPI, HTTPException
from natasha import Segmenter, MorphVocab, NewsEmbedding, NewsMorphTagger, NewsNERTagger, Doc
from pymorphy3 import MorphAnalyzer
import regex as re
import logging
import logging.config
from langdetect import detect, DetectorFactory

# Фиксируем seed для воспроизводимости langdetect
DetectorFactory.seed = 0

# Настройка логирования
logging.config.fileConfig('logging.conf', disable_existing_loggers=False)
logger = logging.getLogger(__name__)

app = FastAPI()

# Инициализация Natasha и pymorphy3
segmenter = Segmenter()
morph_vocab = MorphVocab()
emb = NewsEmbedding()
morph_tagger = NewsMorphTagger(emb)
ner_tagger = NewsNERTagger(emb)
analyzer_ru = MorphAnalyzer(lang='ru')
analyzer_uk = MorphAnalyzer(lang='uk')

# Ключевые слова для деталей
DETAILS_KEYWORDS = {
    "ru": [
        "завтра", "сегодня", "срочно", "большой чемодан", "с багажом", "с ребенком",
        "вечером", "утром", "днем", "с животным", "с собакой", "прямо сейчас",
        "загрузка салона", "животное", "кондиционер", "встреча с табличкой",
        "курьер доставка", "терминал", "чек", "детское кресло", "драйвер",
        "некурящий водитель", "англоязычный водитель", "трос", "подвоз топлива",
        "провод", "я буду курить"
    ],
    "uk": [
        "завтра", "сьогодні", "терміново", "велика валіза", "з багажем", "з дитиною",
        "ввечері", "вранці", "вдень", "з твариною", "з собакою", "зараз",
        "завантаження салону", "тварина", "кондиціонер", "зустріч з табличкою",
        "кур'єр доставка", "термінал", "чек", "дитяче крісло", "драйвер",
        "некурящий водій", "англомовний водій", "трос", "підвезення палива",
        "провід", "я куритиму"
    ],
    "en": [
        "tomorrow", "today", "urgently", "large suitcase", "with luggage", "with child",
        "evening", "morning", "afternoon", "with pet", "with dog", "right now",
        "carbon loading", "animal", "conditioner", "meet with the sign",
        "courier delivery", "terminal", "check", "baby seat", "driver",
        "non-smoking driver", "english speaking driver", "cable", "fuel delivery",
        "wire", "i will smoke"
    ]
}

# Регулярные выражения для предлогов
PREPOSITIONS = {
    "ru": {
        "origin": r"(?:с|из)\s+([а-яА-Я\s,-]+?)(?=\s+(?:в|до|на)\s+|\s*$)",
        "destination": r"(?:в|до|на)\s+([а-яА-Я\s,-]+?)(?=\s+(?:с|з|и|завтра|сегодня|срочно|большой|с багажом|с ребенком|вечером|утром|днем|с животным|с собакой|прямо сейчас|загрузка|животное|кондиционер|встреча|курьер|терминал|чек|детское|драйвер|некурящий|англоязычный|трос|подвоз|провод|я|\s|$))"
    },
    "uk": {
        "origin": r"(?:з|із)\s+([а-яА-ЯїіґєЇІҐЄ\s,-]+?)(?=\s+(?:до)\s+|\s*$)",
        "destination": r"(?:до)\s+([а-яА-ЯїіґєЇІҐЄ\s,-]+?)(?=\s+(?:з|із|і|завтра|сьогодні|терміново|велика|з багажем|з дитиною|ввечері|вранці|вдень|з твариною|з собакою|зараз|завантаження|тварина|кондиціонер|зустріч|кур'єр|термінал|чек|дитяче|драйвер|некурящий|англомовний|трос|підвезення|провід|я|\s|$))"
    },
    "en": {
        "origin": r"(?:from)\s+([a-zA-Z\s,-]+?)(?=\s+(?:to)\s+|\s*$)",
        "destination": r"(?:to)\s+([a-zA-Z\s,-]+?)(?=\s+(?:with|and|tomorrow|today|urgently|large|conditioner|meet|courier|terminal|check|baby|driver|non-smoking|english|cable|fuel|wire|i|\s|$))"
    }
}

# Ключевые слова для определения языка
LANGUAGE_KEYWORDS = {
    "ru": ["нужно", "закажи", "с", "из", "в", "до", "на", "такси"],
    "uk": ["потрібно", "замов", "з", "із", "до", "таксі"],
    "en": ["need", "order", "from", "to", "taxi"]
}

@app.post("/parse")
async def parse_text(data: dict):
    text = data.get("text", "").strip()
    if not text:
        logger.warning("Empty text received")
        raise HTTPException(status_code=400, detail="Text is required")

    logger.info(f"Processing request: {text}")

    # Определяем язык
    try:
        language = detect(text)
        if language not in ["ru", "uk", "en"]:
            language = "en"
        text_lower = text.lower()
        if any(word in text_lower for word in LANGUAGE_KEYWORDS["ru"]):
            language = "ru"
        elif any(word in text_lower for word in LANGUAGE_KEYWORDS["uk"]):
            language = "uk"
        elif any(word in text_lower for word in LANGUAGE_KEYWORDS["en"]):
            language = "en"
    except Exception as e:
        logger.warning(f"Language detection failed: {str(e)}, defaulting to English")
        language = "en"
    logger.info(f"Detected language: {language}")

    origin = None
    destination = None
    entities = []
    details = []

    # Извлекаем детали (проверяем полное совпадение фраз)
    text_lower = text_lower.replace("'", " ")  # Убираем апострофы для украинского
    for keyword in DETAILS_KEYWORDS.get(language, []):
        keyword_lower = keyword.lower().replace("'", " ")
        if keyword_lower in text_lower:
            details.append(keyword)
    logger.info(f"Extracted details: {details}")

    # Обработка русского и украинского с Natasha
    if language in ["ru", "uk"]:
        try:
            doc = Doc(text)
            doc.segment(segmenter)
            doc.tag_morph(morph_tagger)
            doc.tag_ner(ner_tagger)

            # Извлекаем локации (LOC) и лемматизируем
            analyzer = analyzer_ru if language == "ru" else analyzer_uk
            locations = []
            for span in doc.spans:
                if span.type == "LOC":
                    lemma = analyzer.parse(span.text)[0].normal_form
                    locations.append({"text": lemma, "label": "LOC"})
            logger.info(f"Natasha extracted locations: {locations}")

            # Определяем ORIGIN и DESTINATION по предлогам
            origin_match = re.search(PREPOSITIONS[language]["origin"], text_lower, re.UNICODE)
            destination_match = re.search(PREPOSITIONS[language]["destination"], text_lower, re.UNICODE)

            if origin_match:
                origin_candidate = origin_match.group(1).strip()
                for loc in locations:
                    if loc["text"].lower() in origin_candidate.lower():
                        origin = loc["text"]
                        loc["label"] = "ORIGIN"
                        break
                if not origin:
                    origin = analyzer.parse(origin_candidate)[0].normal_form if origin_candidate else None
                    if origin:
                        entities.append({"text": origin, "label": "ORIGIN"})

            if destination_match:
                destination_candidate = destination_match.group(1).strip()
                for loc in locations:
                    if loc["text"].lower() in destination_candidate.lower():
                        destination = loc["text"]
                        loc["label"] = "DESTINATION"
                        break
                if not destination:
                    destination = analyzer.parse(destination_candidate)[0].normal_form if destination_candidate else None
                    if destination:
                        entities.append({"text": destination, "label": "DESTINATION"})

            entities = locations if locations and any(loc["label"] != "LOC" for loc in locations) else entities
            # Добавляем origin и destination в entities, если они не попали из Natasha
            if origin and not any(e["text"] == origin and e["label"] == "ORIGIN" for e in entities):
                entities.append({"text": origin, "label": "ORIGIN"})
            if destination and not any(e["text"] == destination and e["label"] == "DESTINATION" for e in entities):
                entities.append({"text": destination, "label": "DESTINATION"})

        except Exception as e:
            logger.error(f"Natasha processing failed: {str(e)}")
            origin_match = re.search(PREPOSITIONS[language]["origin"], text_lower, re.UNICODE)
            destination_match = re.search(PREPOSITIONS[language]["destination"], text_lower, re.UNICODE)
            if origin_match:
                origin = analyzer.parse(origin_match.group(1).strip())[0].normal_form
                entities.append({"text": origin, "label": "ORIGIN"})
            if destination_match:
                destination = analyzer.parse(destination_match.group(1).strip())[0].normal_form
                entities.append({"text": destination, "label": "DESTINATION"})

    else:  # Английский язык (rule-based)
        origin_match = re.search(PREPOSITIONS["en"]["origin"], text_lower, re.UNICODE)
        destination_match = re.search(PREPOSITIONS["en"]["destination"], text_lower, re.UNICODE)

        if origin_match:
            origin = origin_match.group(1).strip()
            entities.append({"text": origin, "label": "ORIGIN"})
        if destination_match:
            destination = destination_match.group(1).strip()
            entities.append({"text": destination, "label": "DESTINATION"})

    logger.info(f"Extracted entities: {entities}, origin: {origin}, destination: {destination}")

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
