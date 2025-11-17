@echo off
REM Script to create a properly named release ZIP file for Windows
REM Usage: create-release-zip.bat [version]
REM Example: create-release-zip.bat 1.0.6

setlocal enabledelayedexpansion

if "%1"=="" (
    echo Error: Please specify version number
    echo Usage: create-release-zip.bat 1.0.6
    exit /b 1
)

set VERSION=%1
set PLUGIN_NAME=ai-store-assistant
set ZIP_NAME=%PLUGIN_NAME%-v%VERSION%.zip

echo Creating release ZIP for version %VERSION%...

REM Create temporary directory
set TEMP_DIR=%TEMP%\asa-release-%RANDOM%
mkdir "%TEMP_DIR%"
mkdir "%TEMP_DIR%\%PLUGIN_NAME%"

REM Copy all files (excluding .git, .DS_Store, etc.)
xcopy /E /I /Y /EXCLUDE:exclude.txt . "%TEMP_DIR%\%PLUGIN_NAME%\" >nul 2>&1

REM Create ZIP file (requires PowerShell)
powershell -Command "Compress-Archive -Path '%TEMP_DIR%\%PLUGIN_NAME%' -DestinationPath '%ZIP_NAME%' -Force"

REM Cleanup
rmdir /S /Q "%TEMP_DIR%"

echo.
echo Created: %ZIP_NAME%
echo This ZIP file contains the 'ai-store-assistant' folder (no version number)
echo You can upload this ZIP directly to WordPress or attach it to GitHub release

