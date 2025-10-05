from pathlib import Path
import spacy
import logging

logger = logging.getLogger(__name__)

# Пути к моделям
MODEL_DIRS = {
    "uk": Path("/app/models/custom_ner_uk"),
    "ru": Path("/app/models/custom_ner_ru"),
    "en": Path("/app/models/custom_ner_en"),
}

# Функция загрузки модели с fallback
def load_model(lang: str, default_model: str):
    path = MODEL_DIRS.get(lang)
    if path and path.exists():
        try:
            logger.info(f"Loading custom model for {lang} from {path}")
            return spacy.load(path)
        except Exception as e:
            logger.warning(f"Failed to load custom {lang} model: {e}")
    logger.info(f"Loading default spaCy model for {lang}: {default_model}")
    return spacy.load(default_model)

# Загружаем все модели
MODELS = {
    "uk": load_model("uk", "uk_core_news_lg"),
    "ru": load_model("ru", "ru_core_news_lg"),
    "en": load_model("en", "en_core_web_lg"),
}

# Если нужны морфологические анализаторы
ANALYZERS = {
    "uk": None,
    "ru": None,
    "en": None
}
