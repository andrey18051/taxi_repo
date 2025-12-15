@echo off
setlocal

echo.
echo ===========================================
echo    Build and push Docker image for taxi2012
echo ===========================================
echo.
echo Select environment:
echo 1 - TEST  (ghcr.io/andrey18051/taxi_test)
echo 2 - WORK  (ghcr.io/andrey18051/taxi_work)
echo 0 - Exit
echo.

set /p choice=Enter number (1/2/0):

if "%choice%"=="1" (
    set IMAGE=ghcr.io/andrey18051/taxi_test
    set DOCKERFILE=Dockerfile.test
    set ENV=TEST
)
if "%choice%"=="2" (
    set IMAGE=ghcr.io/andrey18051/taxi_work
    set DOCKERFILE=Dockerfile.work
    set ENV=WORK
)
if "%choice%"=="0" goto end
if not defined IMAGE (
    echo Invalid choice!
    pause
    goto end
)

echo.
echo Building %ENV% image: %IMAGE%
echo Using Dockerfile: %DOCKERFILE%
echo.

echo Checking Docker...
docker version >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo Docker is not running. Starting Docker Desktop...
    start "" "C:\Program Files\Docker\Docker\Docker Desktop.exe"
    timeout /t 60 >nul
    echo Waiting for Docker to start...
)

rem Простой тег на основе даты и времени (без wmic)
set TAG=build-%DATE:~10,4%%DATE:~4,2%%DATE:~7,2%-%TIME:~0,2%%TIME:~3,2%
set TAG=%TAG: =0%
set TAG=%TAG::=%

echo Tag: %TAG%  (example: build-20251215-1530)

echo.
echo Building image...
docker build -f %DOCKERFILE% -t %IMAGE%:latest -t %IMAGE%:%TAG% .

if %ERRORLEVEL% neq 0 (
    echo ERROR during build!
    pause
    exit /b 1
)

echo.
echo Pushing image to GitHub Container Registry...
docker push %IMAGE%:latest
if %ERRORLEVEL% neq 0 (
    echo ERROR pushing :latest
    pause
    exit /b 1
)

docker push %IMAGE%:%TAG%
if %ERRORLEVEL% neq 0 (
    echo ERROR pushing :%TAG%
    pause
    exit /b 1
)

echo.
echo ===========================================
echo %ENV% image successfully built and pushed!
echo %IMAGE%:latest
echo %IMAGE%:%TAG%
echo ===========================================
pause

:end
echo.
echo Goodbye!
endlocal
