@echo off

rem Change console encoding to UTF-8
chcp 65001 > nul

rem 1. Copy Dockerfile
echo Copying Dockerfile...
copy "D:\OpenServer\domains\taxi2012\docker\dockerFinish\Work\Dockerfile" "D:\OpenServer\domains\taxi2012"
if %ERRORLEVEL% NEQ 0 (
    echo Error while copying Dockerfile.
    exit /b 1
)
echo Dockerfile copied successfully.

echo Copying env...
copy "D:\OpenServer\domains\taxi2012\app\env\work" "D:\OpenServer\domains\taxi2012"
if %ERRORLEVEL% NEQ 0 (
     echo Error copying Env.
     exit /b 1
 )
 echo Env copied successfully.


rem 2. Change directory
echo Changing directory to D:\OpenServer\domains\taxi2012...
cd /d "D:\OpenServer\domains\taxi2012"

rem 3. Build Docker image
echo Building Docker image...
docker build -t ghcr.io/andrey18051/taxi_work:1.0 .
if %ERRORLEVEL% NEQ 0 (
    echo Error while building Docker image.
    exit /b 1
)
echo Docker image built successfully.

rem 4. Push Docker image to GitHub Container Registry
echo Pushing Docker image to GitHub...
docker push ghcr.io/andrey18051/taxi_work:1.0
if %ERRORLEVEL% NEQ 0 (
    echo Error while pushing Docker image to GitHub.
    exit /b 1
)
echo Docker image pushed to GitHub successfully.
