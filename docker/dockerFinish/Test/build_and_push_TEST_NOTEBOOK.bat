@echo off

chcp 65001 > nul

echo Copying Dockerfile...

copy "C:\Users\andre\PhpstormProjects\laravel-taxi2012\docker\dockerFinish\Test\Dockerfile" "C:\Users\andre\PhpstormProjects\laravel-taxi2012\"
if %ERRORLEVEL% NEQ 0 (
    echo Error copying Dockerfile.
    exit /b 1
)
echo Dockerfile copied successfully.

echo Copying env...
copy "C:\Users\andre\PhpstormProjects\laravel-taxi2012\app\env\test" "C:\Users\andre\PhpstormProjects\laravel-taxi2012\"
if %ERRORLEVEL% NEQ 0 (
     echo Error copying Env.
     exit /b 1
 )
echo Env copied successfully.

cd /d "D:\OpenServer\domains\taxi2012"

echo Building Docker image...
docker build -t ghcr.io/andrey18051/taxi_test:1.0 .
if %ERRORLEVEL% NEQ 0 (
    echo Error building Docker image.
    exit /b 1
)
echo Docker image built successfully.

echo Pushing Docker image to GitHub...
docker push ghcr.io/andrey18051/taxi_test:1.0
if %ERRORLEVEL% NEQ 0 (
    echo Error pushing Docker image.
    exit /b 1
)
echo Docker image pushed to GitHub successfully.
