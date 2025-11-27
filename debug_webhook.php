<?php
/**
 * Script de Debug - Webhook ZapSign
 * 
 * Execute este script para debugar problemas do webhook
 * URL: https://sas.makecard.com.br/debug_webhook.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Webhook ZapSign</title>
    <style>
        body { font-family: monospace; line-height: 1.6; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; font-weight: bold; }
        .box { border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #f9f9f9; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>

<h1>🔍 Debug Webhook ZapSign</h1>

<?php

echo "<div class='box'>";
echo "<h2>📋 PASSO 1: Verificar Arquivos</h2>";

// Verificar se arquivos existem
$files = [
    'webhook_zapsign.php' => __DIR__ . '/webhook_zapsign.php',
    'webhook_zapsign_config.php' => __DIR__ . '/webhook_zapsign_config.php',
    'Adm/php/banco.php' => __DIR__ . '/Adm/php/banco.php'
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "<span class='success'>✅ {$name}</span> - Arquivo encontrado<br>";
    } else {
        echo "<span class='error'>❌ {$name}</span> - Arquivo NÃO encontrado em: {$path}<br>";
    }
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>🔌 PASSO 2: Testar Conexão com Banco</h2>";

try {
    // Tentar incluir arquivo de conexão
    if (file_exists(__DIR__ . '/Adm/php/banco.php')) {
        include __DIR__ . '/Adm/php/banco.php';
        
        if (class_exists('Banco')) {
            echo "<span class='success'>✅ Classe Banco</span> - Encontrada<br>";
            
            // Testar conexão
            try {
                $pdo = Banco::conectar_postgres();
                echo "<span class='success'>✅ Conexão PDO</span> - Estabelecida<br>";
                
                // Testar query simples
                $stmt = $pdo->prepare("SELECT 1 as test");
                $stmt->execute();
                $result = $stmt->fetch();
                echo "<span class='success'>✅ Query Teste</span> - Funcionando<br>";
                
            } catch (Exception $e) {
                echo "<span class='error'>❌ Conexão PDO</span> - Erro: " . $e->getMessage() . "<br>";
            }
            
        } else {
            echo "<span class='error'>❌ Classe Banco</span> - Não encontrada<br>";
        }
    } else {
        echo "<span class='error'>❌ Arquivo banco.php</span> - Não encontrado<br>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Erro Geral</span> - " . $e->getMessage() . "<br>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>🗃️ PASSO 3: Verificar Tabela</h2>";

try {
    if (isset($pdo)) {
        // Verificar se tabela existe
        $stmt = $pdo->prepare("
            SELECT column_name, data_type 
            FROM information_schema.columns 
            WHERE table_schema = 'sind' 
            AND table_name = 'associados_sasmais'
            ORDER BY ordinal_position
        ");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        if ($columns) {
            echo "<span class='success'>✅ Tabela sind.associados_sasmais</span> - Encontrada<br>";
            echo "<strong>Colunas encontradas:</strong><br>";
            echo "<pre>";
            foreach ($columns as $col) {
                echo "- {$col['column_name']} ({$col['data_type']})\n";
            }
            echo "</pre>";
            
            // Verificar colunas obrigatórias do webhook
            $required_columns = ['event', 'doc_token', 'doc_name', 'signed_at', 'name', 'email', 'cpf', 'has_signed', 'cel_informado'];
            $existing_columns = array_column($columns, 'column_name');
            
            echo "<strong>Verificação de colunas do webhook:</strong><br>";
            foreach ($required_columns as $req_col) {
                if (in_array($req_col, $existing_columns)) {
                    echo "<span class='success'>✅ {$req_col}</span><br>";
                } else {
                    echo "<span class='warning'>⚠️ {$req_col}</span> - Coluna ausente<br>";
                }
            }
            
        } else {
            echo "<span class='error'>❌ Tabela sind.associados_sasmais</span> - NÃO encontrada<br>";
            echo "<span class='info'>💡 Execute o script setup_table_webhook.sql</span><br>";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Erro ao verificar tabela</span> - " . $e->getMessage() . "<br>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>📝 PASSO 4: Verificar Logs</h2>";

$log_file = __DIR__ . '/webhook_zapsign.log';
if (file_exists($log_file)) {
    echo "<span class='success'>✅ Arquivo de log</span> - Encontrado<br>";
    
    $log_size = filesize($log_file);
    echo "<strong>Tamanho:</strong> " . number_format($log_size) . " bytes<br>";
    
    if ($log_size > 0) {
        echo "<strong>Últimas 20 linhas do log:</strong><br>";
        echo "<pre>";
        $lines = file($log_file);
        $last_lines = array_slice($lines, -20);
        foreach ($last_lines as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
    } else {
        echo "<span class='warning'>⚠️ Arquivo de log vazio</span> - Webhook pode não ter sido chamado<br>";
    }
} else {
    echo "<span class='warning'>⚠️ Arquivo de log</span> - Não encontrado em: {$log_file}<br>";
    echo "<span class='info'>💡 Logs podem estar desabilitados ou webhook não foi chamado</span><br>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>🧪 PASSO 5: Teste Manual do Webhook</h2>";

echo "<p><strong>Para testar o webhook manualmente:</strong></p>";
echo "<pre>";
echo 'curl -X POST https://sas.makecard.com.br/webhook_zapsign.php \
  -H "Content-Type: application/json" \
  -d \'{
    "event_type": "doc_signed",
    "token": "teste-debug-' . date('His') . '",
    "name": "Termo Adesão SasPyx",
    "signed_at": "' . date('c') . '",
    "signers": [{
      "name": "Teste Debug",
      "email": "teste@debug.com",
      "cpf": "12345678901",
      "status": "signed",
      "signed_at": "' . date('c') . '"
    }]
  }\'';
echo "</pre>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>🔍 PASSO 6: Verificar Dados no Banco</h2>";

try {
    if (isset($pdo)) {
        // Buscar registros com CPF específico do webhook real
        $stmt = $pdo->prepare("
            SELECT id, codigo, nome, cpf, email, event, doc_token, has_signed, signed_at, data_hora
            FROM sind.associados_sasmais 
            WHERE cpf = '02399513606' OR nome ILIKE '%William%' OR email ILIKE '%william@makecard.com.br%'
            ORDER BY data_hora DESC
            LIMIT 5
        ");
        $stmt->execute();
        $records = $stmt->fetchAll();
        
        if ($records) {
            echo "<span class='success'>✅ Registros encontrados</span> para CPF 02399513606:<br>";
            echo "<pre>";
            foreach ($records as $record) {
                echo "ID: {$record['id']}\n";
                echo "Nome: {$record['nome']}\n";
                echo "CPF: {$record['cpf']}\n";
                echo "Email: {$record['email']}\n";
                echo "Event: {$record['event']}\n";
                echo "Doc Token: {$record['doc_token']}\n";
                echo "Has Signed: {$record['has_signed']}\n";
                echo "Signed At: {$record['signed_at']}\n";
                echo "Data Hora: {$record['data_hora']}\n";
                echo "---\n";
            }
            echo "</pre>";
        } else {
            echo "<span class='warning'>⚠️ Nenhum registro</span> encontrado para William (CPF: 02399513606)<br>";
            
            // Verificar se há algum registro recente
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM sind.associados_sasmais 
                WHERE data_hora >= (CURRENT_TIMESTAMP - INTERVAL '1 hour')
            ");
            $stmt->execute();
            $recent = $stmt->fetch();
            
            echo "<span class='info'>📊 Registros</span> criados na última hora: {$recent['total']}<br>";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Erro ao consultar banco</span> - " . $e->getMessage() . "<br>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>✅ RESUMO DE PRÓXIMOS PASSOS</h2>";

echo "<ol>";
echo "<li><strong>Se arquivo de log vazio:</strong> Webhook não está sendo chamado pela ZapSign</li>";
echo "<li><strong>Se erro de conexão:</strong> Verificar arquivo Adm/php/banco.php</li>";
echo "<li><strong>Se tabela não existe:</strong> Executar setup_table_webhook.sql</li>";
echo "<li><strong>Se colunas ausentes:</strong> Atualizar estrutura da tabela</li>";
echo "<li><strong>Se tudo OK mas não grava:</strong> Verificar logs detalhados</li>";
echo "</ol>";

echo "<p><strong>Para habilitar logs detalhados:</strong></p>";
echo "<p>No arquivo webhook_zapsign_config.php, certifique-se que:</p>";
echo "<pre>define('ENABLE_DEBUG_LOGS', true);</pre>";

echo "</div>";

?>

<script>
// Auto-refresh a cada 30 segundos
setTimeout(function() {
    location.reload();
}, 30000);
</script>

</body>
</html> 