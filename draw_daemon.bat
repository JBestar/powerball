@echo off
REM 프로젝트 루트에서 추첨 데몬 실행 (상시 창 유지)
cd /d "%~dp0"
d:/xampp/php/php.exe -f draw_daemon.php
pause
