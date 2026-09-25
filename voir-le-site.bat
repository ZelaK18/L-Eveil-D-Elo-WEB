@echo off
cd /d "%~dp0"

set "PHPDIR=%~dp0php"

if not exist "%PHPDIR%\php.exe" (
    echo Le dossier php est introuvable a cote de ce fichier.
    pause
    exit /b 1
)

start "" "http://localhost:8000/"
echo Site disponible sur http://localhost:8000/
echo Ferme cette fenetre pour arreter le site.
"%PHPDIR%\php.exe" -c "%PHPDIR%\php.ini" -d extension_dir="%PHPDIR%\ext" -d curl.cainfo="%PHPDIR%\cacert.pem" -d openssl.cafile="%PHPDIR%\cacert.pem" -S localhost:8000
