@echo off
chcp 65001 > nul

echo === Start building taxi_ai Docker image ===

set PROJECT_DIR=C:\OpenServer\domains\taxi2012\docker\taxi_ai
set IMAGE_NAME=ghcr.io/andrey18051/taxi_ai:1.0

cd /d "%PROJECT_DIR%"

echo Building Docker image...
docker build -t %IMAGE_NAME% .
if %ERRORLEVEL% NEQ 0 (
    echo Error building Docker image.
    exit /b 1
)
echo Docker image built successfully.

echo Pushing Docker image to GitHub...
docker push %IMAGE_NAME%
if %ERRORLEVEL% NEQ 0 (
    echo Error pushing Docker image.
    exit /b 1
)
echo Docker image pushed to GitHub successfully.

echo === Finished ===
pause
