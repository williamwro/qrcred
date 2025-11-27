<?php
/**
 * Criar Tabela notification_log
 * Script simples para resolver o erro de log
 */

require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Criar Tabela notification_log</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔧 Criar Tabela notification_log</h1>";

try {
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='box'>
    <h2>📊 Criando tabela notification_log...</h2>";
    
    // Criar tabela notification_log
    $sql = "
    CREATE TABLE IF NOT EXISTS notification_log (
        id SERIAL PRIMARY KEY,
        user_card VARCHAR(50) NOT NULL,
        subscription_id INTEGER,
        notification_type VARCHAR(50) NOT NULL,
        payload TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT 'sent',
        error_message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    
    $pdo->exec($sql);
    echo "✅ Tabela notification_log criada com sucesso!<br>";
    
    // Criar índices
    $indices = [
        "CREATE INDEX IF NOT EXISTS idx_notification_log_user_card ON notification_log(user_card);",
        "CREATE INDEX IF NOT EXISTS idx_notification_log_sent_at ON notification_log(sent_at);"
    ];
    
    foreach ($indices as $indice) {
        $pdo->exec($indice);
    }
    echo "✅ Índices criados com sucesso!<br>";
    
    // Verificar se foi criada
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notification_log");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Tabela verificada: {$count} registros<br>";
    
    echo "</div>";
    
    echo "<div class='box success'>";
    echo "<h2>🎉 SUCESSO!</h2>";
    echo "A tabela notification_log foi criada com sucesso!<br>";
    echo "Agora os logs das notificações funcionarão corretamente.<br>";
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h3>🧪 PRÓXIMO PASSO:</h3>";
    echo "Execute o teste final novamente para confirmar que não há mais erros:<br>";
    echo "<a href='test_push_final.php' target='_blank'>🚀 test_push_final.php</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box error'>";
    echo "<h2>❌ ERRO</h2>";
    echo "Erro: " . $e->getMessage();
    echo "</div>";
}

echo "<div class='box' style='margin-top: 20px; background: #fff3cd; border: 1px solid #ffeaa7;'>";
echo "<h3>🗑️ LIMPEZA</h3>";
echo "❌ <strong>DELETE este arquivo após usar!</strong><br>";
echo "📁 Arquivo: create_notification_log_table.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 