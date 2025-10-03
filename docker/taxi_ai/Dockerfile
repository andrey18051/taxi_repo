# Этап 1: Сборка зависимостей и модели
FROM python:3.10-slim AS builder

WORKDIR /app

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl build-essential && \
    rm -rf /var/lib/apt/lists/*

# Копируем и устанавливаем зависимости
COPY requirements.txt /app/requirements.txt
RUN pip install --no-cache-dir --user -r requirements.txt \
    --index-url https://pypi.org/simple \
    --extra-index-url https://download.pytorch.org/whl/cpu

# Подготовка директории для модели
RUN mkdir -p /app/model/bert-base-multilingual-cased-ner-hrl

# Скачиваем и сохраняем модель
RUN python - <<EOF
import os
from transformers import AutoTokenizer, AutoModelForTokenClassification
path = "/app/model/bert-base-multilingual-cased-ner-hrl"
try:
    print("Downloading tokenizer...")
    tokenizer = AutoTokenizer.from_pretrained("Davlan/bert-base-multilingual-cased-ner-hrl")
    print("Downloading model...")
    model = AutoModelForTokenClassification.from_pretrained("Davlan/bert-base-multilingual-cased-ner-hrl")
    print("Saving tokenizer and model...")
    tokenizer.save_pretrained(path)
    model.save_pretrained(path)
    print("✅ Model and tokenizer saved to", path)
    print("Model directory contents:", os.listdir(path))
except Exception as e:
    print("❌ Failed to download or save model:", str(e))
    exit(1)
EOF

# Проверка файлов модели перед копированием
RUN ls -lh /app/model/bert-base-multilingual-cased-ner-hrl && \
    test -f /app/model/bert-base-multilingual-cased-ner-hrl/config.json && \
    test -f /app/model/bert-base-multilingual-cased-ner-hrl/tokenizer_config.json && \
    test -f /app/model/bert-base-multilingual-cased-ner-hrl/special_tokens_map.json && \
    (test -f /app/model/bert-base-multilingual-cased-ner-hrl/pytorch_model.bin || \
     test -f /app/model/bert-base-multilingual-cased-ner-hrl/model.safetensors) && \
    echo "✅ Model files verified in builder stage!"

# Этап 2: Финальный образ
FROM python:3.10-slim

WORKDIR /app

# Устанавливаем минимальные runtime-зависимости
RUN apt-get update && apt-get install -y --no-install-recommends \
    libstdc++6 libgomp1 && \
    rm -rf /var/lib/apt/lists/*

# Копируем зависимости из builder
COPY --from=builder /root/.local /root/.local
ENV PATH=/root/.local/bin:$PATH

# Копируем модель
COPY --from=builder /app/model /app/model

# Проверка наличия ключевых файлов модели
RUN ls -lh /app/model/bert-base-multilingual-cased-ner-hrl && \
    test -f /app/model/bert-base-multilingual-cased-ner-hrl/config.json && \
    test -f /app/model/bert-base-multilingual-cased-ner-hrl/tokenizer_config.json && \
    test -f /app/model/bert-base-multilingual-cased-ner-hrl/special_tokens_map.json && \
    (test -f /app/model/bert-base-multilingual-cased-ner-hrl/pytorch_model.bin || \
     test -f /app/model/bert-base-multilingual-cased-ner-hrl/model.safetensors) && \
    echo "✅ All required model files are present!"

# Копируем код приложения
COPY app /app

# Очистка временных файлов
RUN rm -rf /tmp/*

EXPOSE 8001

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001", "--workers", "2", "--log-level", "info"]
