@echo off
setlocal enabledelayedexpansion

rem Obtener el ID del commit actual (corto)
for /f "delims=" %%i in ('git rev-parse --short HEAD') do set COMMIT_ID=%%i

rem Crear el directorio Updates si no existe
if not exist Updates mkdir Updates

rem Construir la lista de archivos modificados (excluyendo los eliminados: A=Added, C=Copied, M=Modified, R=Renamed, T=Type change)
set FILES_TO_ARCHIVE=
for /f "delims=" %%F in ('git diff --name-only --diff-filter=ACMRT HEAD~1 HEAD') do (
    set FILES_TO_ARCHIVE=!FILES_TO_ARCHIVE! "%%F"
)

rem Crear el archivo de actualización
echo Creando Updates/Update_%COMMIT_ID%.zip...

if "%FILES_TO_ARCHIVE%"=="" (
    echo No se encontraron archivos modificados entre HEAD~1 y HEAD. No se creara el ZIP.
) else (
    rem Ejecutar git archive con la lista de archivos
    git archive -o Updates/Update_%COMMIT_ID%.zip HEAD %FILES_TO_ARCHIVE%
    if %errorlevel% neq 0 (
        echo Error al crear el archivo ZIP.
    ) else (
        echo Update_%COMMIT_ID%.zip creado exitosamente en la carpeta Updates.
    )
)

endlocal
pause