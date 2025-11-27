# Configuração do Agendador de Tarefas do Windows

## Como configurar o reprocessamento automático no Windows

### Opção 1: Via Interface Gráfica (Recomendada)

1. **Abrir o Agendador de Tarefas:**
   - Pressione `Win + R`
   - Digite `taskschd.msc` e pressione Enter

2. **Criar Nova Tarefa:**
   - Clique em "Criar Tarefa Básica..." no painel direito
   - Nome: `ZapSign Reprocessamento`
   - Descrição: `Reprocessamento automático de documentos pendentes ZapSign`

3. **Configurar Disparador:**
   - Selecione "Diariamente"
   - Horário de início: `00:00:00`
   - Repetir tarefa a cada: `5 minutos`
   - Por um período de: `1 dia`

4. **Configurar Ação:**
   - Selecione "Iniciar um programa"
   - Programa/script: `C:\xampp2\htdocs\qrcred\reprocessamento_windows.bat`
   - Iniciar em: `C:\xampp2\htdocs\qrcred`

5. **Configurações Avançadas:**
   - Marcar "Executar se o usuário estiver conectado ou não"
   - Marcar "Executar com privilégios mais altos"
   - Marcar "Configurar para: Windows 10"

### Opção 2: Via PowerShell (Para usuários avançados)

```powershell
# Executar como Administrador
$action = New-ScheduledTaskAction -Execute "C:\xampp2\htdocs\qrcred\reprocessamento_windows.bat" -WorkingDirectory "C:\xampp2\htdocs\qrcred"

$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 5) -RepetitionDuration (New-TimeSpan -Days 365)

$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RunOnlyIfNetworkAvailable

Register-ScheduledTask -TaskName "ZapSign Reprocessamento" -Action $action -Trigger $trigger -Settings $settings -Description "Reprocessamento automático de documentos pendentes ZapSign"
```

### Opção 3: Teste Manual

Para testar se está funcionando, execute no PowerShell:

```powershell
# Navegar para o diretório
cd C:\xampp2\htdocs\qrcred

# Executar o script manualmente
.\reprocessamento_windows.bat
```

### Verificar Logs

Os logs serão salvos em:
- `C:\xampp2\htdocs\qrcred\reprocessamento_windows.log`

### Monitoramento

Para verificar se a tarefa está funcionando:

1. Abra o Agendador de Tarefas
2. Vá em "Biblioteca do Agendador de Tarefas"
3. Encontre "ZapSign Reprocessamento"
4. Verifique a última execução e próxima execução
5. Consulte o arquivo de log para ver os resultados

### Desabilitar/Remover

Para desabilitar:
```powershell
Disable-ScheduledTask -TaskName "ZapSign Reprocessamento"
```

Para remover:
```powershell
Unregister-ScheduledTask -TaskName "ZapSign Reprocessamento" -Confirm:$false
```

## Alternativa Simples: Executar Manualmente

Se não quiser configurar o agendador, você pode executar manualmente quando necessário:

```powershell
Invoke-RestMethod -Uri "https://sas.makecard.com.br/webhook_zapsign.php?reprocessar=1"
```

Ou usar o script PHP:
```powershell
php auto_reprocessar.php
```
