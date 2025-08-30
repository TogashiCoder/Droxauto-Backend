@echo off
REM Comprehensive Test Runner Script for Droxstock API
REM This script provides easy access to various testing scenarios

echo 🚀 Droxstock API Test Suite Runner
echo ==================================

REM Function to display help
if "%1"=="help" goto show_help
if "%1"=="" goto show_help

REM Check if Pest is available
if not exist "vendor\bin\pest" (
    echo ❌ Error: Pest PHP not found. Please install dependencies first:
    echo    composer install
    pause
    exit /b 1
)

REM Main script logic
if "%1"=="all" goto run_all
if "%1"=="auth" goto run_auth
if "%1"=="admin" goto run_admin
if "%1"=="coverage" goto run_coverage
if "%1"=="parallel" goto run_parallel
if "%1"=="verbose" goto run_verbose
goto show_help

:run_all
echo 🧪 Running: All tests
echo ⏱️  Started at: %date% %time%
echo.
call vendor\bin\pest
goto end

:run_auth
echo 🧪 Running: Authentication tests only
echo ⏱️  Started at: %date% %time%
echo.
call vendor\bin\pest tests\Feature\Auth\
goto end

:run_admin
echo 🧪 Running: Admin user management tests only
echo ⏱️  Started at: %date% %time%
echo.
call vendor\bin\pest tests\Feature\Admin\
goto end

:run_coverage
echo 🧪 Running: Tests with coverage report
echo ⏱️  Started at: %date% %time%
echo.
call vendor\bin\pest --coverage --min=90
goto end

:run_parallel
echo 🧪 Running: Tests in parallel
echo ⏱️  Started at: %date% %time%
echo.
call vendor\bin\pest --parallel
goto end

:run_verbose
echo 🧪 Running: Tests with verbose output
echo ⏱️  Started at: %date% %time%
echo.
call vendor\bin\pest --verbose
goto end

:show_help
echo.
echo Usage: run-tests.bat [OPTION]
echo.
echo Options:
echo   all              Run all tests
echo   auth             Run authentication tests only
echo   admin            Run admin user management tests only
echo   coverage         Run tests with coverage report
echo   parallel         Run tests in parallel
echo   verbose          Run tests with verbose output
echo   help             Show this help message
echo.
echo Examples:
echo   run-tests.bat all
echo   run-tests.bat auth
echo   run-tests.bat coverage
echo.

:end
echo.
echo ✅ Completed at: %date% %time%
pause
