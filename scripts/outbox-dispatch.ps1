Param([int]$Limit = 500)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
Push-Location "$PSScriptRoot/../mini-wallet-backend"
try {
  php artisan outbox:dispatch --limit=$Limit
} finally {
  Pop-Location
}


