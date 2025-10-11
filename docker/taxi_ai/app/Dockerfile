# Используем базовый образ
FROM taxi_ai_base:latest

WORKDIR /app

# Копируем изменяемые файлы
COPY main.py /app/main.py
COPY logging.conf /app/logging.conf
COPY core/ /app/core/
COPY training/train.py /app/train.py
COPY training_data/ /app/training_data/

# Устанавливаем переменные окружения (опционально)св
ENV MODEL_DIR_UK=/app/models/custom_ner_uk
ENV MODEL_DIR_RU=/app/models/custom_ner_ru
ENV MODEL_DIR_EN=/app/models/custom_ner_en


RUN mkdir -p /app/models
RUN pip install --no-cache-dir spacy aiofiles

# Запускаем train.py для дообучения моделей при сборке образа
# Если train.py сохраняет модели в /app/models/custom_ner_*
RUN python train.py

# Открываем порт
EXPOSE 8001

# Запуск приложения
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001", "--log-level", "info"]

