# train.py (исправленная версия)
import spacy
import json
import random
import asyncio
import aiofiles
import logging
from tqdm import tqdm
from pathlib import Path
from spacy.training import Example
from spacy.scorer import Scorer
from concurrent.futures import ProcessPoolExecutor

# Пути
TRAIN_DATA_FILE = Path("training_data/data.json")
TEST_DATA_FILE = Path("training_data/test_data.json")
MODEL_DIRS = {
    "uk": Path("models/custom_ner_uk"),
    "ru": Path("models/custom_ner_ru"),
    "en": Path("models/custom_ner_en")
}
BASE_MODELS = {
    "uk": "uk_core_news_lg",
    "ru": "ru_core_news_lg",
    "en": "en_core_web_lg"
}
LOG_DIR = Path("logs")
LOG_DIR.mkdir(exist_ok=True)

EPOCHS = 20


def setup_logger(lang: str) -> logging.Logger:
    """Создаёт отдельный логгер для каждого языка."""
    log_file = LOG_DIR / f"training_{lang}.log"
    logger = logging.getLogger(f"train_{lang}")
    logger.setLevel(logging.INFO)

    # Убираем дублирование хендлеров
    if not logger.handlers:
        handler = logging.FileHandler(log_file, encoding="utf-8")
        formatter = logging.Formatter("%(asctime)s [%(levelname)s] %(message)s")
        handler.setFormatter(formatter)
        logger.addHandler(handler)

    return logger


def validate_entities(data, lang, logger):
    """Проверяет корректность индексов сущностей (offsets)."""
    nlp = spacy.blank(lang)
    problems = []
    for i, item in enumerate(data):
        doc = nlp.make_doc(item["text"])
        entities = [(e["start"], e["end"], e["label"]) for e in item.get("entities", [])]
        try:
            biluo = spacy.training.offsets_to_biluo_tags(doc, entities)
            if '-' in biluo:
                problems.append({
                    'index': i,
                    'text': item["text"][:100],
                    'entities': entities,
                    'biluo_tags': biluo
                })
        except Exception as e:
            problems.append({'index': i, 'error': str(e)})

    if problems:
        logger.warning(f"⚠️ Found {len(problems)} misaligned examples for {lang}")
        for p in problems[:3]:
            logger.warning(str(p))
        return False
    return True


def evaluate_model(nlp, test_data, lang, logger):
    """Оценивает точность модели с помощью Scorer."""
    scorer = Scorer()
    examples = []

    for item in test_data:
        doc = nlp.make_doc(item["text"])
        example = Example.from_dict(
            doc,
            {"entities": [(e["start"], e["end"], e["label"]) for e in item.get("entities", [])]}
        )
        examples.append(example)

    scores = scorer.score(examples)
    logger.info(f"📊 {lang.upper()} Evaluation Results:")
    logger.info(json.dumps(scores["ents_p"], ensure_ascii=False, indent=2))
    logger.info(json.dumps(scores["ents_r"], ensure_ascii=False, indent=2))
    logger.info(json.dumps(scores["ents_f"], ensure_ascii=False, indent=2))

    print(f"📊 {lang.upper()} Evaluation: "
          f"P={scores['ents_p']:.3f}, R={scores['ents_r']:.3f}, F1={scores['ents_f']:.3f}")


def train_language_sync(lang: str, all_data: list, test_data: list):
    """Синхронное обучение одной модели (в отдельном процессе)."""
    logger = setup_logger(lang)

    # Фильтруем данные по языку
    if lang == "uk":
        data = [x for x in all_data if any(c in "іїґє" for c in x["text"])]
    elif lang == "ru":
        data = [x for x in all_data if any(c in "ёйыэ" for c in x["text"])]
    else:
        data = [x for x in all_data if all(c.isascii() for c in x["text"])]

    logger.info(f"🔍 {lang.upper()}: Found {len(data)} training examples")

    if not validate_entities(data, lang, logger):
        logger.error(f"❌ {lang.upper()}: Misaligned entities. Training aborted.")
        return

    nlp = spacy.load(BASE_MODELS[lang])
    if "ner" not in nlp.pipe_names:
        ner = nlp.add_pipe("ner", last=True)
    else:
        ner = nlp.get_pipe("ner")

    # Добавляем лейблы
    for item in data:
        for ent in item.get("entities", []):
            ner.add_label(ent["label"])

    optimizer = nlp.initialize()
    logger.info(f"🚀 {lang.upper()}: Training started...")

    # Прогресс-бар по эпохам
    for epoch in tqdm(range(EPOCHS), desc=f"{lang.upper()} Training", position=0, leave=True):
        random.shuffle(data)
        losses = {}
        for item in data:
            doc = nlp.make_doc(item["text"])
            example = Example.from_dict(
                doc,
                {"entities": [(e["start"], e["end"], e["label"]) for e in item.get("entities", [])]}
            )
            nlp.update([example], sgd=optimizer, losses=losses)
        logger.info(f"✅ {lang.upper()} — Epoch {epoch + 1}/{EPOCHS}, Losses: {losses}")

    MODEL_DIRS[lang].mkdir(parents=True, exist_ok=True)
    nlp.to_disk(MODEL_DIRS[lang])
    logger.info(f"💾 {lang.upper()} model saved to {MODEL_DIRS[lang]}")

    # Оценка модели
    if test_data:
        evaluate_model(nlp, test_data, lang, logger)
    else:
        logger.warning(f"⚠️ {lang.upper()}: No test data provided — skipping evaluation.")

    print(f"✅ {lang.upper()} training completed!")


async def load_data_async(file_path: Path):
    """Асинхронная загрузка JSON данных с валидацией и flatten вложенных массивов."""
    import aiofiles
    import json
    import logging

    async with aiofiles.open(file_path, "r", encoding="utf-8") as f:
        content = await f.read()

    try:
        data = json.loads(content)
    except json.JSONDecodeError as e:
        logging.error(f"❌ Ошибка чтения JSON {file_path}: {e}")
        return []

    # Flatten вложенных массивов
    def flatten_data(obj):
        if isinstance(obj, list):
            result = []
            for item in obj:
                if isinstance(item, list):
                    result.extend(flatten_data(item))
                else:
                    result.append(item)
            return result
        return [obj]

    data = flatten_data(data)

    # Оставляем только словари с текстом
    valid_data = []
    for i, item in enumerate(data):
        if not isinstance(item, dict) or "text" not in item:
            logging.warning(f"⚠️ Пропущен элемент #{i} (неверный формат): {item}")
            continue
        valid_data.append(item)

    logging.info(f"✅ Загружено {len(valid_data)} валидных примеров из {file_path.name}")
    return valid_data


async def train_all_languages():
    """Асинхронный запуск обучения для всех языков."""
    print("📥 Loading training data...")
    all_data = await load_data_async(TRAIN_DATA_FILE)
    if not all_data:
        print("❌ Training data is empty or invalid. Aborting training.")
        return
    print(f"✅ Loaded {len(all_data)} total training examples")

    # Загружаем тестовый датасет (если есть)
    test_data = []
    if TEST_DATA_FILE.exists():
        print("📥 Loading test data...")
        test_data = await load_data_async(TEST_DATA_FILE)
        print(f"✅ Loaded {len(test_data)} test examples")
    else:
        print("⚠️ test_data.json not found — using 10% of training data as test.")
        test_data = random.sample(all_data, max(1, len(all_data) // 10))

    languages = ["uk", "ru", "en"]

    loop = asyncio.get_running_loop()
    with ProcessPoolExecutor(max_workers=3) as executor:
        tasks = [
            loop.run_in_executor(executor, train_language_sync, lang, all_data, test_data)
            for lang in languages
        ]
        await asyncio.gather(*tasks)

    print("🎉 All trainings completed successfully!")


if __name__ == "__main__":
    asyncio.run(train_all_languages())
