@echo off

chcp 65001 > nul

echo Copying Dockerfile...
copy "D:\OpenServer\domains\taxi2012\docker\fly\Dockerfile" "D:\OpenServer\domains\taxi2012\Dockerfile"
if %ERRORLEVEL% NEQ 0 (
    echo Error copying Dockerfile.
    exit /b 1
)
echo Dockerfile copied successfully.

echo Copying .env file...
copy "D:\OpenServer\domains\taxi2012\app\env\fly\.env" "D:\OpenServer\domains\taxi2012\.env"
if %ERRORLEVEL% NEQ 0 (
    echo Error copying .env file.
    exit /b 1
)
echo .env file copied successfully.

cd /d "D:\OpenServer\domains\taxi2012"

echo Building Docker image...
docker build -t ghcr.io/andrey18051/taxi_fly:1.0 .
if %ERRORLEVEL% NEQ 0 (
    echo Error building Docker image.
    exit /b 1
)
echo Docker image built successfully.

echo Pushing Docker image to GitHub...
docker push ghcr.io/andrey18051/taxi_fly:1.0
if %ERRORLEVEL% NEQ 0 (
    echo Error pushing Docker image.
    exit /b 1
)
echo Docker image pushed to GitHub successfully.
