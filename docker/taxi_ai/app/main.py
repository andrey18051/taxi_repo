import logging
from fastapi import FastAPI
from pydantic import BaseModel
import spacy

# Настройка логирования
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("TaxiAi")

app = FastAPI()

# Загрузка модели spacy
try:
    nlp = spacy.load("ru_core_news_sm")
    logger.info("Spacy model loaded successfully")
except Exception as e:
    logger.error("Failed to load spacy model: %s", str(e))
    raise

class TextRequest(BaseModel):
    text: str

@app.post("/")
async def process_text(request: TextRequest):
    logger.info("Received request with text: %s", request.text)
    try:
        # Обработка текста с помощью spacy
        doc = nlp(request.text)
        entities = [{"text": ent.text, "label": ent.label_} for ent in doc.ents]
        logger.info("Extracted entities: %s", entities)
        return {"text": request.text, "response": entities}
    except Exception as e:
        logger.error("Error processing request: %s", str(e))
        return {"text": request.text, "response": None, "error": str(e)}
