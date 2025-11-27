# 🔔 Como Adicionar Notificações ao Webhook ZapSign

## 📋 Instrução para Modificar o Webhook Existente

Para que o sistema de notificações funcione, você precisa adicionar algumas linhas no arquivo `webhook_zapsign.php` existente.

## 🎯 Localização das Modificações

Procure pelas seguintes seções no arquivo `webhook_zapsign.php` e adicione o código de notificação:

### 1. Após UPDATE de Registro Existente (Linha ~460)

```php
// LOCALIZAR esta seção (aproximadamente linha 460):
$updateStmt->execute([
    ':event' => $event,
    ':doc_token' => $docToken,
    ':doc_name' => $docName,
    ':signed_at' => $signedAt,
    ':name' => $signerName,
    ':email' => $signerEmail,
    ':has_signed' => $hasSigned ? 1 : 0,
    ':autorizado' => $hasSigned ? 1 : 0,
    ':cel_informado' => '',
    ':id' => $recordByCpf['id']
]);

writeLog("Registro atualizado com sucesso por CPF - ID: {$recordByCpf['id']}");

// ============================================
// ADICIONAR ESTAS LINHAS APÓS O writeLog ACIMA:
// ============================================

// Enviar notificação em tempo real se foi atualizado com sucesso
if ($hasSigned) {
    try {
        $notificationData = [
            'event_type' => 'signature_updated',
            'timestamp' => time(),
            'data' => [
                'id' => $recordByCpf['id'],
                'codigo' => $recordByCpf['codigo'],
                'nome' => $signerName,
                'celular' => $recordByCpf['celular'],
                'email' => $signerEmail,
                'cpf' => $cpfLimpo,
                'autorizado' => true,
                'aceitou_termo' => true,
                'has_signed' => true,
                'event' => $event,
                'doc_token' => $docToken,
                'doc_name' => $docName,
                'signed_at' => $signedAt,
                'data_hora' => date('Y-m-d H:i:s'),
                'changes' => [
                    'has_signed' => ['old' => false, 'new' => true],
                    'autorizado' => ['old' => false, 'new' => true]
                ]
            ]
        ];
        
        $notifyStmt = $pdo->prepare("SELECT pg_notify('update_assinatura_digital', ?)");
        $notifyStmt->execute([json_encode($notificationData)]);
        
        writeLog("NOTIFICAÇÃO ENVIADA: Atualização de assinatura para código {$recordByCpf['codigo']}");
        
    } catch (Exception $e) {
        writeLog("ERRO ao enviar notificação: " . $e->getMessage());
    }
}

$processedSigners++;
```

### 2. Após UPDATE de Registro Reutilizável (Linha ~500)

```php
// LOCALIZAR esta seção (aproximadamente linha 500):
$updateEmptyStmt->execute([
    ':codigo' => $codigoTemporario,
    ':nome' => $signerName,
    ':event' => $event,
    ':doc_token' => $docToken,
    ':doc_name' => $docName,
    ':signed_at' => $signedAt,
    ':name' => $signerName,
    ':email' => $signerEmail,
    ':cpf' => $cpfLimpo,
    ':has_signed' => $hasSigned ? 1 : 0,
    ':autorizado' => $hasSigned ? 1 : 0,
    ':cel_informado' => '',
    ':id' => $emptyRecord['id']
]);

writeLog("Registro reutilizável atualizado com sucesso - ID: {$emptyRecord['id']}, Código: {$codigoTemporario}");

// ============================================
// ADICIONAR ESTAS LINHAS APÓS O writeLog ACIMA:
// ============================================

// Enviar notificação em tempo real para nova assinatura
if ($hasSigned) {
    try {
        $notificationData = [
            'event_type' => 'new_signature',
            'timestamp' => time(),
            'data' => [
                'id' => $emptyRecord['id'],
                'codigo' => $codigoTemporario,
                'nome' => $signerName,
                'celular' => '',
                'email' => $signerEmail,
                'cpf' => $cpfLimpo,
                'autorizado' => true,
                'aceitou_termo' => true,
                'has_signed' => true,
                'event' => $event,
                'doc_token' => $docToken,
                'doc_name' => $docName,
                'signed_at' => $signedAt,
                'data_hora' => date('Y-m-d H:i:s')
            ]
        ];
        
        $notifyStmt = $pdo->prepare("SELECT pg_notify('new_assinatura_digital', ?)");
        $notifyStmt->execute([json_encode($notificationData)]);
        
        writeLog("NOTIFICAÇÃO ENVIADA: Nova assinatura para código {$codigoTemporario}");
        
    } catch (Exception $e) {
        writeLog("ERRO ao enviar notificação: " . $e->getMessage());
    }
}

$processedSigners++;
```

### 3. Após INSERT de Novo Registro (Linha ~550)

```php
// LOCALIZAR esta seção (aproximadamente linha 550):
$insertStmt->execute([
    ':codigo' => $codigoNovo,
    ':nome' => $signerName,
    ':celular' => '',
    ':autorizado' => $hasSigned ? 1 : 0,
    ':aceitou_termo' => 1,
    ':event' => $event,
    ':doc_token' => $docToken,
    ':doc_name' => $docName,
    ':signed_at' => $signedAt,
    ':name' => $signerName,
    ':email' => $signerEmail,
    ':cpf' => $cpfLimpo,
    ':has_signed' => $hasSigned ? 1 : 0,
    ':cel_informado' => ''
]);

writeLog("Novo registro criado com sucesso - Código: {$codigoNovo}");

// ============================================
// ADICIONAR ESTAS LINHAS APÓS O writeLog ACIMA:
// ============================================

// Enviar notificação em tempo real para nova assinatura
if ($hasSigned) {
    try {
        $notificationData = [
            'event_type' => 'new_signature',
            'timestamp' => time(),
            'data' => [
                'id' => $pdo->lastInsertId(),
                'codigo' => $codigoNovo,
                'nome' => $signerName,
                'celular' => '',
                'email' => $signerEmail,
                'cpf' => $cpfLimpo,
                'autorizado' => true,
                'aceitou_termo' => true,
                'has_signed' => true,
                'event' => $event,
                'doc_token' => $docToken,
                'doc_name' => $docName,
                'signed_at' => $signedAt,
                'data_hora' => date('Y-m-d H:i:s')
            ]
        ];
        
        $notifyStmt = $pdo->prepare("SELECT pg_notify('new_assinatura_digital', ?)");
        $notifyStmt->execute([json_encode($notificationData)]);
        
        writeLog("NOTIFICAÇÃO ENVIADA: Nova assinatura para código {$codigoNovo}");
        
    } catch (Exception $e) {
        writeLog("ERRO ao enviar notificação: " . $e->getMessage());
    }
}

$processedSigners++;
```

## 🧪 Como Testar as Modificações

### 1. Verificar se Triggers Existem
```sql
-- Execute no PostgreSQL para verificar se os triggers estão instalados
SELECT trigger_name, event_manipulation, action_timing
FROM information_schema.triggers 
WHERE event_object_table = 'associados_sasmais' 
AND event_object_schema = 'sind';
```

### 2. Testar Notificação Manual
```sql
-- Execute no PostgreSQL para testar se as notificações funcionam
SELECT pg_notify('new_assinatura_digital', '{"test": true, "message": "Teste de notificação"}');
```

### 3. Simular Assinatura
- Use o arquivo `testar_sistema_notificacoes.php`
- Clique em "Simular Assinatura Digital"
- Verifique se a notificação chega no app

## 📝 Exemplo Completo de Uma das Modificações

```php
// Exemplo completo da modificação na seção UPDATE:

$updateStmt->execute([
    ':event' => $event,
    ':doc_token' => $docToken,
    ':doc_name' => $docName,
    ':signed_at' => $signedAt,
    ':name' => $signerName,
    ':email' => $signerEmail,
    ':has_signed' => $hasSigned ? 1 : 0,
    ':autorizado' => $hasSigned ? 1 : 0,
    ':cel_informado' => '',
    ':id' => $recordByCpf['id']
]);

writeLog("Registro atualizado com sucesso por CPF - ID: {$recordByCpf['id']}");

// =================== NOTIFICAÇÃO EM TEMPO REAL ===================
if ($hasSigned) {
    try {
        // Preparar dados da notificação
        $notificationData = [
            'event_type' => 'signature_updated',
            'timestamp' => time(),
            'data' => [
                'id' => $recordByCpf['id'],
                'codigo' => $recordByCpf['codigo'],
                'nome' => $signerName,
                'celular' => $recordByCpf['celular'],
                'email' => $signerEmail,
                'cpf' => $cpfLimpo,
                'autorizado' => true,
                'aceitou_termo' => true,
                'has_signed' => true,
                'event' => $event,
                'doc_token' => $docToken,
                'doc_name' => $docName,
                'signed_at' => $signedAt,
                'data_hora' => date('Y-m-d H:i:s'),
                'changes' => [
                    'has_signed' => ['old' => false, 'new' => true],
                    'autorizado' => ['old' => false, 'new' => true]
                ]
            ]
        ];
        
        // Enviar notificação via PostgreSQL NOTIFY
        $notifyStmt = $pdo->prepare("SELECT pg_notify('update_assinatura_digital', ?)");
        $notifyStmt->execute([json_encode($notificationData)]);
        
        writeLog("NOTIFICAÇÃO ENVIADA: Atualização de assinatura para código {$recordByCpf['codigo']}");
        
    } catch (Exception $e) {
        writeLog("ERRO ao enviar notificação: " . $e->getMessage());
        // Continuar execução mesmo se notificação falhar
    }
}
// ===============================================================

$processedSigners++;
```

## ⚠️ Observações Importantes

1. **Não altere outras funções** - Adicione apenas as linhas de notificação
2. **Mantenha o código existente** - As modificações são apenas adições
3. **Teste primeiro** - Use o script de teste antes de colocar em produção
4. **Logs detalhados** - As notificações são logadas para debug
5. **Tolerância a falhas** - Se a notificação falhar, o webhook continua funcionando

## 🔄 Fluxo Após as Modificações

```
Usuário assina documento na ZapSign
          ↓
Webhook recebe notificação
          ↓
Dados são inseridos/atualizados na tabela
          ↓
🆕 NOTIFICAÇÃO é enviada via pg_notify
          ↓
App mobile recebe notificação em tempo real
          ↓
Menu completo é habilitado automaticamente
```

## 🎯 Resultado Final

Com essas modificações, quando um usuário assinar digitalmente:

1. ✅ Dados são gravados no banco (como já funcionava)
2. ✅ **NOVO:** Notificação é enviada em tempo real 
3. ✅ **NOVO:** App recebe notificação automaticamente
4. ✅ **NOVO:** Menu completo aparece IMEDIATAMENTE
5. ✅ **NOVO:** Usuário não precisa mais sair e entrar no app

🎉 **Problema resolvido!** 