import logging
from fastapi import FastAPI
from pydantic import BaseModel
from transformers import pipeline, AutoTokenizer, AutoModelForTokenClassification

# Настройка логирования
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("TaxiAi")

app = FastAPI()

# Загрузка модели NER
MODEL_PATH = "/app/model/bert-base-multilingual-cased-ner-hrl"
try:
    logger.info("Loading tokenizer from %s", MODEL_PATH)
    tokenizer = AutoTokenizer.from_pretrained(MODEL_PATH)
    logger.info("Loading model from %s", MODEL_PATH)
    model = AutoModelForTokenClassification.from_pretrained(MODEL_PATH)
    logger.info("Creating NER pipeline")
    ner_pipeline = pipeline("ner", model=model, tokenizer=tokenizer, aggregation_strategy="simple")
    logger.info("✅ Multilingual NER model loaded successfully from %s", MODEL_PATH)
except Exception as e:
    logger.error("❌ Failed to load NER model: %s", str(e))
    ner_pipeline = None

class TextRequest(BaseModel):
    text: str

class SpacyEntity(BaseModel):
    text: str
    label: str

class AiInnerResponse(BaseModel):
    entities_hf: list[dict[str, str]] = []
    entities_spacy: list[SpacyEntity] = []
    origin: str | None = None
    destination: str | None = None
    details: list[str] = []

class AiResponse(BaseModel):
    text: str
    response: AiInnerResponse

@app.post("/parse", response_model=AiResponse)
async def process_text(request: TextRequest):
    logger.info("📩 Received request with text: %s", request.text)
    response = AiResponse(
        text=request.text,
        response=AiInnerResponse(entities_hf=[], entities_spacy=[], origin=None, destination=None, details=[])
    )

    if ner_pipeline is None:
        logger.error("NER pipeline is not initialized")
        return response

    try:
        logger.info("Running NER pipeline on text: %s", request.text)
        entities = ner_pipeline(request.text)
        logger.info("NER pipeline returned: %s", entities)
        response.response.entities_hf = [
            {"text": ent["word"], "label": ent["entity_group"]} for ent in entities
        ]

        # Извлечение ORIGIN и DESTINATION на основе контекста
        origins = []
        destinations = []
        for i, ent in enumerate(entities):
            if ent["entity_group"] == "LOC":
                # Расширяем окно анализа до 5 слов
                preceding_text = request.text[:ent["start"]].lower().split()[-5:]
                logger.info("Processing entity: %s, preceding text: %s", ent["word"], preceding_text)
                if any(prep in preceding_text for prep in ["с", "из", "from", "з", "від", "зі", "with"]):
                    origins.append(ent["word"])
                elif any(prep in preceding_text for prep in ["в", "до", "to", "на", "у", "into", "towards"]):
                    destinations.append(ent["word"])
                else:
                    # Если предлог не найден, считаем вторую локацию destination
                    if len(origins) > 0 and not destinations:
                        destinations.append(ent["word"])

        response.response.origin = origins[0] if origins else None
        response.response.destination = destinations[0] if destinations else None

        # Извлечение дополнительных опций
        details = []
        text_lower = request.text.lower()
        if any(word in text_lower for word in ["завтра", "tomorrow"]):
            details.append("tomorrow")
        if any(word in text_lower for word in ["большой чемодан", "large luggage", "велика валіза"]):
            details.append("large luggage")
        response.response.details = details

        logger.info("Extracted: origin=%s, destination=%s, details=%s",
                   response.response.origin, response.response.destination, details)
    except Exception as e:
        logger.error("Error in NER processing: %s", str(e))

    return response
