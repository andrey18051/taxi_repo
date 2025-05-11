@echo off
echo Starting TEST build and push...
call C:\Users\andre\PhpstormProjects\laravel-taxi2012\docker\dockerFinish\Test\build_and_push_TEST_NOTEBOOK.bat
if %ERRORLEVEL% neq 0 (
    echo TEST build and push failed!
    exit /b %ERRORLEVEL%
)
echo TEST build and push completed successfully.

echo Starting WORK build and push...
call C:\Users\andre\PhpstormProjects\laravel-taxi2012\docker\dockerFinish\Work\build_and_push_WORK_NOTEBOOK.bat
if %ERRORLEVEL% neq 0 (
    echo WORK build and push failed!
    exit /b %ERRORLEVEL%
)
echo WORK build and push completed successfully.

echo All tasks completed.
pause
