from fastapi import FastAPI, HTTPException
from core.text_parser import parse_text_logic
from langdetect import detect, DetectorFactory
import logging.config

DetectorFactory.seed = 0
logging.config.fileConfig('logging.conf', disable_existing_loggers=False)
logger = logging.getLogger(__name__)

app = FastAPI()

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

    try:
        language = detect(text)
        text_lower = text.lower()
        if any(w in text_lower for w in LANGUAGE_KEYWORDS["ru"]):
            language = "ru"
        elif any(w in text_lower for w in LANGUAGE_KEYWORDS["uk"]):
            language = "uk"
        elif any(w in text_lower for w in LANGUAGE_KEYWORDS["en"]):
            language = "en"
    except:
        language = "en"

    result = parse_text_logic(text, language)
    return {"text": text, "response": result}
