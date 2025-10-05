@echo off
chcp 65001 > nul

set PROJECT_DIR=C:\OpenServer\domains\taxi2012\docker\taxi_ai\app
set IMAGE_NAME=ghcr.io/andrey18051/taxi_ai:1.0
set CONTAINER_NAME=taxi_ai

cd /d "%PROJECT_DIR%"

echo === Starting build and push of %CONTAINER_NAME% ===

echo Stopping and removing existing container %CONTAINER_NAME%...
docker stop %CONTAINER_NAME% 2>nul || echo No container to stop
docker rm %CONTAINER_NAME% 2>nul || echo No container to remove

echo Cleaning up old images for %IMAGE_NAME%...
for /f "tokens=*" %%i in ('docker images -q %IMAGE_NAME%') do docker rmi %%i 2>nul
docker image prune -f
echo Old images cleaned up.

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

echo === Build and push finished ===
pause
