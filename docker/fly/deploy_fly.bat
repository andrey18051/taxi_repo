@echo off
chcp 65001 > nul

SET IMAGE=ghcr.io/andrey18051/taxi_fly:1.0
SET FLYCTL_PATH=C:\Users\andre_s40\.fly\bin\flyctl.exe

IF NOT EXIST "%FLYCTL_PATH%" (
    echo flyctl not found at %FLYCTL_PATH%.
    echo Install Fly.io CLI: https://fly.io/docs/hands-on/installing/
    pause
    exit /b 1
)

echo Using flyctl: %FLYCTL_PATH%
echo Deploying image %IMAGE% ...
"%FLYCTL_PATH%" deploy --image %IMAGE%
IF %ERRORLEVEL% NEQ 0 (
    echo Deployment failed.
    pause
    exit /b 1
)

echo Deployment completed successfully.
pause

