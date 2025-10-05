import regex as re
from core.config import MODELS, ANALYZERS, logger
from core.keywords import DETAILS_KEYWORDS
from core.prepositions import PREPOSITIONS  # импортируем отдельный файл

def group_addresses(loc_entities, text):
    """
    Группирует сущности LOC/ADDRESS в полный адрес (город + улица + номер дома)
    """
    addresses = []
    tokens = text.split()
    used_indexes = set()

    for ent in loc_entities:
        # Ищем позицию сущности в тексте
        try:
            start = next(i for i, tok in enumerate(tokens) if ent in tok and i not in used_indexes)
        except StopIteration:
            continue

        addr_tokens = [tokens[start]]
        idx = start + 1
        while idx < len(tokens):
            t = tokens[idx]
            # Добавляем, если это часть адреса (буквы, цифры, запятые)
            if re.search(r'[А-Яа-яA-Za-z0-9,]', t):
                addr_tokens.append(t)
                idx += 1
            else:
                break

        addresses.append(" ".join(addr_tokens))
        used_indexes.update(range(start, idx))

    return addresses

def parse_text_logic(text: str, lang: str):
    text_lower = text.lower()
    details = [kw for kw in DETAILS_KEYWORDS.get(lang, []) if kw.lower() in text_lower]

    nlp = MODELS.get(lang)
    origin = None
    destination = None
    entities = []

    # 1. Сначала пробуем регулярки из PREPOSITIONS
    try:
        origin_match = re.search(PREPOSITIONS[lang]["origin"], text)
        destination_match = re.search(PREPOSITIONS[lang]["destination"], text)

        if origin_match:
            origin = origin_match.group(1).strip()
        if destination_match:
            destination = destination_match.group(1).strip()
    except Exception as e:
        logger.warning(f"Regex PREPOSITIONS failed: {e}")

    # 2. spaCy извлечение сущностей LOC/GPE/ADDRESS
    try:
        doc = nlp(text)
        entities = [{"text": ent.text, "label": ent.label_} for ent in doc.ents if ent.label_ in ["GPE", "LOC", "ADDRESS"]]
        loc_entities = [ent["text"] for ent in entities]

        combined = group_addresses(loc_entities, text)

        if combined:
            origin = combined[0] if origin is None else origin
        if len(combined) > 1:
            destination = combined[1] if destination is None else destination

    except Exception as e:
        logger.warning(f"spaCy failed: {e}")

    # 3. Фолбэк: если origin/destination не найдено, берем первые два LOC или слова
    if origin is None or destination is None:
        tokens = text.split()
        if origin is None and len(tokens) >= 1:
            origin = tokens[0]
        if destination is None and len(tokens) >= 2:
            destination = tokens[1]

    return {
        "details": details,
        "entities": entities,
        "origin": origin,
        "destination": destination
    }
