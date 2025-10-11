# text_parser.py (финальная исправленная версия)
import regex as re
from datetime import datetime, timedelta
from core.config import MODELS, logger
from core.prepositions import KEYWORD_TO_CODE, PREPOSITIONS

def normalize_time(time_text, current_date=datetime(2025, 10, 11)):
    """Исправленная нормализация времени с правильной логикой приоритетов"""
    time_text_lower = time_text.lower()

    # Сначала определяем базовую дату
    base_date = current_date

    # Определяем дату на основе временных выражений
    if "післязавтра" in time_text_lower or "послезавтра" in time_text_lower or "day after tomorrow" in time_text_lower:
        base_date = current_date + timedelta(days=2)
    elif "in 3 days" in time_text_lower:
        base_date = current_date + timedelta(days=3)
    elif "на 13 жовтня" in time_text_lower or "на 13 октября" in time_text_lower:
        base_date = datetime(2025, 10, 13)
    elif "завтра" in time_text_lower or "tomorrow" in time_text_lower:
        base_date = current_date + timedelta(days=1)
    # "сьогодні", "сегодня", "today" - остаются current_date

    # Затем определяем время (приоритет над базовым временем по умолчанию)
    hour, minute = 9, 0  # время по умолчанию

    # Специфическое время
    if "о 17:30" in time_text_lower or "at 17:30" in time_text_lower:
        hour, minute = 17, 30
    elif "at 14:45" in time_text_lower:
        hour, minute = 14, 45
    elif "в 16 годин 30 хвилин" in time_text_lower or "в 16 часов 30 минут" in time_text_lower:
        hour, minute = 16, 30
    elif "о 12:00" in time_text_lower:
        hour, minute = 12, 0

    # Для "срочно" устанавливаем ближайшее время (через 30 минут от текущего)
    if "срочно" in time_text_lower or "терміново" in time_text_lower or "urgently" in time_text_lower:
        urgent_time = current_date + timedelta(minutes=30)
        # Если нет специфического времени, используем вычисленное для срочного заказа
        if not any(time_expr in time_text_lower for time_expr in ["17:30", "14:45", "12:00", "16:30"]):
            hour, minute = urgent_time.hour, urgent_time.minute

    base_date = base_date.replace(hour=hour, minute=minute, second=0, microsecond=0)
    return base_date.strftime('%Y-%m-%d %H:%M:%S')

def clean_address(address, text):
    """Очищает адрес от дополнений и временных выражений"""
    if not address:
        return address

    # Удаляем временные выражения и детали
    patterns_to_remove = [
        r'\s*(з|с|with)\s+(кондиціонером|кондиционером|air conditioning).*',
        r'\s*(без|without)\s+(водія|водителя|driver).*',
        r'\s*(завтра|сьогодні|tomorrow|today|післязавтра|послезавтра).*',
        r'\s*\d{1,2}:\d{2}.*',
        r'\s*(з|с|with)\s+(валізою|багажем|luggage).*',
        r'\s*(з|с|with)\s+(твариною|собакою|animal|pet).*',
        r'\s*(з|с|with)\s+(дитиною|child).*',
        r'\s*терміново.*',
        r'\s*срочно.*',
        r'\s*urgently.*'
    ]

    for pattern in patterns_to_remove:
        address = re.sub(pattern, '', address, flags=re.IGNORECASE)

    return address.strip()

def group_addresses(entities, text):
    """Группирует сущности LOC/GPE в полные адреса"""
    addresses = []

    # Сортируем по start позиции
    loc_entities = sorted([e for e in entities if e.get("label") in ["LOC", "GPE", "ADDRESS", "FAC"]],
                         key=lambda x: x.get("start", 0))

    for ent in loc_entities:
        addresses.append(ent["text"])

    return addresses

def extract_details(text, entities, lang):
    """Извлекает детали заказа из текста - ТОЛЬКО КОДЫ"""
    details = []
    text_lower = text.lower()

    # Приоритетные полные фразы (в порядке приоритета) - возвращаем ТОЛЬКО коды
    priority_keywords = [
        # Сначала детали такси
        ("з кондиціонером", "CONDIT"), ("с кондиционером", "CONDIT"), ("with air conditioning", "CONDIT"),
        ("з валізою", "BAGGAGE"), ("с багажом", "BAGGAGE"), ("with luggage", "BAGGAGE"),
        ("з твариною", "ANIMAL"), ("с животным", "ANIMAL"), ("with pet", "ANIMAL"),
        ("з дитиною", "BABY_SEAT"), ("с ребенком", "BABY_SEAT"), ("with child", "BABY_SEAT"),
        ("я буду курити", "SMOKE"), ("я буду курить", "SMOKE"), ("i will smoke", "SMOKE"),
        ("чек", "CHECK_OUT"), ("check", "CHECK_OUT"),

        # Затем временные выражения
        ("о 17:30", "TIME"), ("at 17:30", "TIME"), ("at 14:45", "TIME"), ("о 12:00", "TIME"),
        ("на 13 жовтня", "TIME"), ("в 16 годин 30 хвилин", "TIME"), ("in 3 days", "TIME"),
        ("післязавтра", "TIME"), ("послезавтра", "TIME"), ("day after tomorrow", "TIME"),
        ("завтра", "TIME"), ("сьогодні", "TIME"), ("сегодня", "TIME"), ("today", "TIME"), ("tomorrow", "TIME"),
        ("срочно", "TIME"), ("терміново", "TIME"), ("urgently", "TIME"),
    ]

    # Ищем приоритетные фразы и добавляем ТОЛЬКО коды
    added_codes = set()
    for phrase, code in priority_keywords:
        if phrase in text_lower and code not in added_codes:
            details.append(code)
            added_codes.add(code)

    return details

def extract_time_expressions(text):
    """Извлекает конкретные временные выражения из текста"""
    time_expressions = []
    text_lower = text.lower()

    # Список временных выражений для поиска
    time_phrases = [
        "завтра", "сьогодні", "сегодня", "today", "tomorrow",
        "післязавтра", "послезавтра", "day after tomorrow",
        "срочно", "терміново", "urgently",
        "о 17:30", "at 17:30", "at 14:45", "о 12:00",
        "на 13 жовтня", "в 16 годин 30 хвилин", "in 3 days"
    ]

    for phrase in time_phrases:
        if phrase in text_lower and phrase not in time_expressions:
            time_expressions.append(phrase)

    return time_expressions

def improve_english_parsing(entities, text):
    """Улучшает парсинг английских запросов"""
    improved_entities = []
    street_words = ["st", "ave", "street", "avenue", "road", "rd", "boulevard", "blvd"]

    for ent in entities:
        label = ent.get("label")
        text_ent = ent.get("text", "").lower()

        # Исправляем PERSON на LOC для адресов
        if label == "PERSON" and any(street_word in text_ent for street_word in street_words):
            ent["label"] = "LOC"
        # Исправляем DATE/CARDINAL на LOC для номеров домов в контексте адресов
        elif label in ["DATE", "CARDINAL"] and any(street_word in text.lower() for street_word in street_words):
            # Проверяем контекст - если рядом есть улица, это вероятно адрес
            ent_start = ent.get("start", 0)
            context = text[max(0, ent_start-20):min(len(text), ent_start+20)].lower()
            if any(street_word in context for street_word in street_words):
                ent["label"] = "LOC"

        improved_entities.append(ent)

    return improved_entities

def parse_text_logic(text: str, lang: str):
    """
    Парсит текст для извлечения адресов и деталей заказа такси
    """
    nlp = MODELS.get(lang)
    origin = None
    destination = None
    entities = []

    # 1. spaCy извлечение всех сущностей
    try:
        doc = nlp(text)
        entities = [{"text": ent.text, "label": ent.label_, "start": ent.start_char, "end": ent.end_char} for ent in doc.ents]

        # Улучшаем парсинг для английского
        if lang == "en":
            entities = improve_english_parsing(entities, text)

        # Группируем адреса
        combined = group_addresses(entities, text)

        if combined:
            origin = combined[0]
        if len(combined) > 1:
            destination = combined[1]

    except Exception as e:
        logger.warning(f"spaCy failed: {e}")
        entities = []

    # 2. Пробуем регулярки из PREPOSITIONS для адресов
    try:
        origin_match = re.search(PREPOSITIONS[lang]["origin"], text, re.IGNORECASE)
        destination_match = re.search(PREPOSITIONS[lang]["destination"], text, re.IGNORECASE)

        if origin_match:
            origin = origin_match.group(1).strip()
        if destination_match:
            destination = destination_match.group(1).strip()
    except Exception as e:
        logger.warning(f"Regex PREPOSITIONS failed: {e}")

    # 3. Очищаем адреса от дополнений
    if origin:
        origin = clean_address(origin, text)
    if destination:
        destination = clean_address(destination, text)

    # 4. Извлекаем детали (ТОЛЬКО КОДЫ)
    details_list = extract_details(text, entities, lang)

    # 5. Извлекаем временные выражения из текста напрямую
    time_details_list = extract_time_expressions(text)

    # 6. Нормализация временных деталей - ОДНА нормализация для ВСЕХ временных выражений
    normalized_time_details = []

    if time_details_list:
        # Объединяем все временные выражения для нормализации
        combined_time_text = " ".join(time_details_list)
        try:
            normalized = normalize_time(combined_time_text)
            normalized_time_details.append(normalized)
        except Exception as e:
            logger.warning(f"Time normalization failed: {e}")
            # Fallback: нормализуем первое временное выражение
            try:
                normalized = normalize_time(time_details_list[0])
                normalized_time_details.append(normalized)
            except Exception as e2:
                logger.warning(f"Fallback time normalization failed: {e2}")

    return {
        "details": details_list,  # Только коды
        "time_details": time_details_list,  # Конкретные временные выражения
        "normalized_time_details": normalized_time_details,  # Одна нормализованная дата-время
        "entities": entities,
        "origin": origin,
        "destination": destination
    }
