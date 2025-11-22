@echo off
REM API Health Check Runner for Windows
REM Usage: run_tests.bat [base_url] [admin_email] [admin_password] [user_email] [user_password]

set BASE_URL=http://localhost:8000/api
set ADMIN_EMAIL=
set ADMIN_PASSWORD=
set USER_EMAIL=
set USER_PASSWORD=

if not "%1"=="" set BASE_URL=%1
if not "%2"=="" set ADMIN_EMAIL=%2
if not "%3"=="" set ADMIN_PASSWORD=%3
if not "%4"=="" set USER_EMAIL=%4
if not "%5"=="" set USER_PASSWORD=%5

cd /d "%~dp0\.."

php api_check/test_all_apis.php --base-url=%BASE_URL% --admin-email=%ADMIN_EMAIL% --admin-password=%ADMIN_PASSWORD% --user-email=%USER_EMAIL% --user-password=%USER_PASSWORD%

pause

