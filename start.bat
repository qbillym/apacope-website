@echo off
rem Starts GS APACOPE with 100 MB uploads. Run from the project folder.
cd /d "%~dp0public"
echo GS APACOPE is starting at http://127.0.0.1:8000  (uploads up to 100 MB)
php -d upload_max_filesize=100M -d post_max_size=105M -d max_execution_time=300 -d memory_limit=256M -S 127.0.0.1:8000 ..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php
pause
