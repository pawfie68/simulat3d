@echo off
cd /d "%~dp0"

echo.
echo  Simulat3D -- Git Push
echo  =====================
echo.

git add -A

for /f "tokens=*" %%i in ('powershell -NoProfile -Command "Get-Date -Format \"yyyy-MM-dd HH:mm\""') do set DT=%%i

git commit -m "update %DT%"

git push

echo.
echo  Gotowe!
pause
