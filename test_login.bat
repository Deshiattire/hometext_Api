@echo off
echo Testing Admin Login...
curl -X POST http://127.0.0.1:8000/api/login ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"email\":\"admin@example.com\",\"password\":\"password\",\"user_type\":1}"

echo.
echo.
echo Testing Sales Manager Login...
curl -X POST http://127.0.0.1:8000/api/login ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"email\":\"salesmanager@example.com\",\"password\":\"password\",\"user_type\":2}"

pause
