FROM python:3.10-slim

WORKDIR /app

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y git curl build-essential && rm -rf /var/lib/apt/lists/*

# Копируем и устанавливаем зависимости
COPY requirements.txt /app/requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

# Устанавливаем модель spacy
RUN python -m spacy download ru_core_news_sm && python -c "import spacy; spacy.load('ru_core_news_sm')"

# Создаём директорию для модели
RUN mkdir -p /app/model/distilbert-base-multilingual-cased

# Скачиваем и сохраняем модель + отладочный вывод
RUN python - <<EOF
import os
from transformers import AutoTokenizer, AutoModelForTokenClassification
try:
    tokenizer = AutoTokenizer.from_pretrained('distilbert-base-multilingual-cased')
    model = AutoModelForTokenClassification.from_pretrained('distilbert-base-multilingual-cased')
    tokenizer.save_pretrained('/app/model/distilbert-base-multilingual-cased')
    model.save_pretrained('/app/model/distilbert-base-multilingual-cased')
    print("Files saved in /app/model/distilbert-base-multilingual-cased:")
    print(os.listdir('/app/model/distilbert-base-multilingual-cased'))
except Exception as e:
    print(f"Error downloading or saving model: {e}")
    exit(1)
EOF

# Проверяем файлы (учитываем model.safetensors вместо pytorch_model.bin)
RUN ls -lh /app/model/distilbert-base-multilingual-cased && \
    test -f /app/model/distilbert-base-multilingual-cased/config.json && \
    (test -f /app/model/distilbert-base-multilingual-cased/pytorch_model.bin || test -f /app/model/distilbert-base-multilingual-cased/model.safetensors) && \
    test -f /app/model/distilbert-base-multilingual-cased/vocab.txt && \
    test -f /app/model/distilbert-base-multilingual-cased/tokenizer_config.json && \
    test -f /app/model/distilbert-base-multilingual-cased/special_tokens_map.json && \
    echo "✅ Model distilbert-base-multilingual-cased downloaded and verified!" || \
    (echo "❌ Error: Some files are missing!" && exit 1)

# Копируем код приложения
COPY app /app

EXPOSE 8001

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001", "--workers", "2", "--log-level", "info"]
