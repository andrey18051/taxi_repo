FROM python:3.10-slim

WORKDIR /app

# Обновляем пакеты и pip
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential libstdc++6 libgomp1 curl && \
    rm -rf /var/lib/apt/lists/*
RUN pip install --no-cache-dir --upgrade pip

# Ставим зависимости и модели
COPY requirements.txt /app/requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

# Копируем проект
COPY main.py /app/main.py
COPY logging.conf /app/logging.conf

# Лог-файл
RUN touch /app/app.log && chmod 666 /app/app.log

# Открываем порт
EXPOSE 8001

# Запуск uvicorn
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001", "--log-level", "info"]
