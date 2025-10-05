Param()
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
Push-Location "$PSScriptRoot/../mini-wallet-frontend"
try {
  npm install
} finally {
  Pop-Location
}


