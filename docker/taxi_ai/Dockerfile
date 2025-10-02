FROM python:3.10-slim

WORKDIR /app

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y git curl build-essential && rm -rf /var/lib/apt/lists/*

# Копируем и устанавливаем зависимости
COPY requirements.txt /app/requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

# Скачиваем spacy модель
RUN python -m spacy download ru_core_news_sm

# Подготовка директории для модели
RUN mkdir -p /app/model/distilbert-base-multilingual-cased

# Скачиваем и сохраняем HuggingFace модель
RUN python - <<EOF
from transformers import AutoTokenizer, AutoModelForTokenClassification
path = "/app/model/distilbert-base-multilingual-cased"
tokenizer = AutoTokenizer.from_pretrained("distilbert-base-multilingual-cased")
model = AutoModelForTokenClassification.from_pretrained("distilbert-base-multilingual-cased")
tokenizer.save_pretrained(path)
model.save_pretrained(path)
print("✅ Model saved to", path)
EOF

# Проверка наличия ключевых файлов (без жёсткого pytorch_model.bin)
RUN ls -lh /app/model/distilbert-base-multilingual-cased && \
    test -f /app/model/distilbert-base-multilingual-cased/config.json && \
    test -f /app/model/distilbert-base-multilingual-cased/tokenizer_config.json && \
    test -f /app/model/distilbert-base-multilingual-cased/special_tokens_map.json && \
    (test -f /app/model/distilbert-base-multilingual-cased/pytorch_model.bin || \
     test -f /app/model/distilbert-base-multilingual-cased/model.safetensors) && \
    echo "✅ All required model files are present!"

# Копируем код приложения
COPY app /app

EXPOSE 8001

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001", "--workers", "2", "--log-level", "info"]
