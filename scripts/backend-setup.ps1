Param()
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
Push-Location "$PSScriptRoot/../mini-wallet-backend"
try {
  if (Test-Path composer.phar) { php composer.phar install } else { composer install }
  php artisan migrate
} finally {
  Pop-Location
}


