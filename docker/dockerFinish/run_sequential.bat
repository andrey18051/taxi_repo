@echo off
echo Checking Docker Desktop...

:: Простая проверка через docker version
docker version >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo Docker Desktop is not running. Starting...
    start "" "C:\Users\user\Desktop\Docker Desktop.lnk"
    echo Waiting 45 seconds for Docker to start...
    timeout /t 45 /nobreak >nul

    :: Повторная проверка
    docker version >nul 2>&1
    if %ERRORLEVEL% neq 0 (
        echo ERROR: Failed to start Docker Desktop!
        pause
        exit /b 1
    )
)

echo Docker Desktop is ready!

:: Остальная часть скрипта без изменений
echo Starting TEST build and push...
call C:\OpenServer\domains\taxi2012\docker\dockerFinish\Test\build_and_push_TEST.bat
if %ERRORLEVEL% neq 0 (
    echo TEST build and push failed!
    exit /b %ERRORLEVEL%
)
echo TEST build and push completed successfully.

echo Starting WORK build and push...
call C:\OpenServer\domains\taxi2012\docker\dockerFinish\Work\build_and_push_WORK.bat
if %ERRORLEVEL% neq 0 (
    echo WORK build and push failed!
    exit /b %ERRORLEVEL%
)
echo WORK build and push completed successfully.

echo All tasks completed.
pause
