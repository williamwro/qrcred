@echo off
REM Script para reprocessamento automático no Windows
REM Este arquivo deve ser executado pelo Agendador de Tarefas do Windows

REM Configurar codificação para UTF-8
chcp 65001 > nul

REM Log de execução
set LOG_FILE=%~dp0reprocessamento_windows.log
set TIMESTAMP=%date% %time%

echo [%TIMESTAMP%] Iniciando reprocessamento automatico >> "%LOG_FILE%"

REM Usar PowerShell para fazer a requisição HTTP
powershell -Command "try { $response = Invoke-RestMethod -Uri 'https://sas.makecard.com.br/webhook_zapsign.php?reprocessar=1' -TimeoutSec 45; Write-Host 'Sucesso:' $response.status 'Encontrados:' $response.documentos_encontrados 'Sucessos:' $response.sucessos; echo \"[%TIMESTAMP%] Sucesso: $($response.status) - Encontrados: $($response.documentos_encontrados) - Sucessos: $($response.sucessos)\" >> '%LOG_FILE%' } catch { Write-Host 'Erro:' $_.Exception.Message; echo \"[%TIMESTAMP%] Erro: $($_.Exception.Message)\" >> '%LOG_FILE%' }"

echo [%TIMESTAMP%] Reprocessamento concluido >> "%LOG_FILE%"
