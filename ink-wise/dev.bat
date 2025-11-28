@echo off
cd /d "%~dp0"
set PATH=%PATH%;"C:\Program Files\nodejs\"
"C:\Program Files\nodejs\npm.cmd" run dev
