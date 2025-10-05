import spacy
import json
from pathlib import Path
from spacy.training import Example

# Пути
TRAIN_DATA_FILE = Path("training_data/data.json")
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

EPOCHS = 20

def train_language(lang: str):
    print(f"Training language: {lang}")
    nlp = spacy.load(BASE_MODELS[lang])

    # NER pipe
    if "ner" not in nlp.pipe_names:
        ner = nlp.add_pipe("ner", last=True)
    else:
        ner = nlp.get_pipe("ner")

    # Загружаем тренировочные данные
    with TRAIN_DATA_FILE.open("r", encoding="utf-8") as f:
        all_data = json.load(f)

    # Фильтруем примеры по языку
    if lang == "uk":
        data = [x for x in all_data if any(c in "іїґє" for c in x["text"])]
    elif lang == "ru":
        data = [x for x in all_data if any(c in "ёйыэ" for c in x["text"])]
    else:
        data = [x for x in all_data if all(c.isascii() for c in x["text"])]

    # Добавляем все лейблы в NER
    for item in data:
        entities = item.get("entities", [])
        for ent in entities:
            ner.add_label(ent["label"])

    # Дообучение
    optimizer = nlp.begin_training()
    for epoch in range(EPOCHS):
        for item in data:
            doc = nlp.make_doc(item["text"])
            example = Example.from_dict(
                doc,
                {"entities": [(e["start"], e["end"], e["label"]) for e in item.get("entities", [])]}
            )
            nlp.update([example], sgd=optimizer)
        print(f"{lang} epoch {epoch+1} done")

    # Сохраняем модель
    MODEL_DIRS[lang].mkdir(parents=True, exist_ok=True)
    nlp.to_disk(MODEL_DIRS[lang])
    print(f"{lang} model saved to {MODEL_DIRS[lang]}")

if __name__ == "__main__":
    for lang in ["uk", "ru", "en"]:
        train_language(lang)
