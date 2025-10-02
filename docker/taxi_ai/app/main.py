import logging
from fastapi import FastAPI
from pydantic import BaseModel
import spacy
from transformers import AutoTokenizer, AutoModelForTokenClassification

# Настройка логирования
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("TaxiAi")

app = FastAPI()

# ====== Загрузка spaCy ======
try:
    nlp = spacy.load("ru_core_news_sm")
    logger.info("✅ spaCy model loaded successfully")
except Exception as e:
    logger.error("❌ Failed to load spaCy model: %s", str(e))
    nlp = None  # чтобы сервер всё равно поднялся


# ====== Загрузка HuggingFace модели ======
HF_MODEL_PATH = "/app/model/distilbert-base-multilingual-cased"

try:
    tokenizer = AutoTokenizer.from_pretrained(HF_MODEL_PATH)
    model = AutoModelForTokenClassification.from_pretrained(HF_MODEL_PATH)
    logger.info("✅ HuggingFace model loaded successfully from %s", HF_MODEL_PATH)
except Exception as e:
    logger.error("❌ Failed to load HuggingFace model: %s", str(e))
    tokenizer = None
    model = None


class TextRequest(BaseModel):
    text: str


@app.post("/parse")
async def process_text(request: TextRequest):
    logger.info("📩 Received request with text: %s", request.text)

    response = {"text": request.text, "entities_spacy": [], "entities_hf": []}

    # spaCy обработка
    if nlp:
        try:
            doc = nlp(request.text)
            response["entities_spacy"] = [{"text": ent.text, "label": ent.label_} for ent in doc.ents]
            logger.info("spaCy entities: %s", response["entities_spacy"])
        except Exception as e:
            logger.error("Error in spaCy processing: %s", str(e))

    # Hugging Face обработка (пока только токенизация для теста)
    if tokenizer and model:
        try:
            inputs = tokenizer(request.text, return_tensors="pt")
            outputs = model(**inputs)
            logger.info("HF model inference successful")
            # Для упрощения пока не декодируем метки
            response["entities_hf"] = outputs.logits.shape  # debug info
        except Exception as e:
            logger.error("Error in HuggingFace processing: %s", str(e))

    return response
