<?php
/**
 * Setup Agendamento Notifications
 * Adiciona colunas de controle de notificação na tabela agendamento
 */

require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup Agendamento Notifications</title>
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
<h1>⚙️ Setup Sistema de Notificações Agendamento</h1>";

try {
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='box'>
    <h2>🔧 Configurando estrutura da tabela...</h2>";
    
    // Lista das colunas para adicionar
    $columns = [
        [
            'name' => 'notification_sent_confirmado',
            'definition' => 'BOOLEAN DEFAULT FALSE',
            'description' => 'Controla se notificação de agendamento confirmado foi enviada'
        ],
        [
            'name' => 'notification_sent_24h',
            'definition' => 'BOOLEAN DEFAULT FALSE',
            'description' => 'Controla se lembrete de 24h foi enviado'
        ],
        [
            'name' => 'notification_sent_1h',
            'definition' => 'BOOLEAN DEFAULT FALSE',
            'description' => 'Controla se lembrete de 1h foi enviado'
        ],
        [
            'name' => 'updated_at',
            'definition' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'description' => 'Timestamp da última atualização'
        ]
    ];
    
    $alterations = 0;
    $errors = 0;
    
    foreach ($columns as $column) {
        try {
            // Verificar se coluna já existe
            $checkSql = "
                SELECT column_name 
                FROM information_schema.columns 
                WHERE table_schema = 'sind' 
                AND table_name = 'agendamento' 
                AND column_name = ?
            ";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$column['name']]);
            $exists = $checkStmt->fetch();
            
            if (!$exists) {
                // Adicionar coluna
                $alterSql = "ALTER TABLE sind.agendamento ADD COLUMN {$column['name']} {$column['definition']}";
                $pdo->exec($alterSql);
                
                echo "✅ Adicionada coluna: <strong>{$column['name']}</strong><br>";
                echo "&nbsp;&nbsp;&nbsp;{$column['description']}<br>";
                $alterations++;
            } else {
                echo "⚪ Coluna já existe: <strong>{$column['name']}</strong><br>";
            }
            
        } catch (Exception $e) {
            echo "❌ Erro ao adicionar {$column['name']}: " . $e->getMessage() . "<br>";
            $errors++;
        }
    }
    
    echo "</div>";
    
    // Verificar estrutura final
    echo "<div class='box'>";
    echo "<h3>🔍 Verificação Final</h3>";
    
    $stmt = $pdo->query("
        SELECT column_name, data_type, column_default, is_nullable
        FROM information_schema.columns 
        WHERE table_schema = 'sind' AND table_name = 'agendamento' 
        AND column_name IN ('notification_sent_confirmado', 'notification_sent_24h', 'notification_sent_1h', 'updated_at')
        ORDER BY column_name
    ");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($finalColumns) == 4) {
        echo "<div class='success'>✅ Todas as colunas estão configuradas!</div>";
        echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
        echo "<tr><th>Coluna</th><th>Tipo</th><th>Default</th><th>Nullable</th></tr>";
        
        foreach ($finalColumns as $col) {
            echo "<tr>";
            echo "<td>{$col['column_name']}</td>";
            echo "<td>{$col['data_type']}</td>";
            echo "<td>{$col['column_default']}</td>";
            echo "<td>{$col['is_nullable']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Algumas colunas não foram criadas corretamente</div>";
    }
    
    echo "</div>";
    
    // Criar trigger para atualizar updated_at automaticamente
    echo "<div class='box'>";
    echo "<h3>🔄 Configurando Trigger</h3>";
    
    try {
        // Função para trigger
        $functionSql = "
        CREATE OR REPLACE FUNCTION update_agendamento_updated_at()
        RETURNS TRIGGER AS $$
        BEGIN
            NEW.updated_at = CURRENT_TIMESTAMP;
            RETURN NEW;
        END;
        $$ language 'plpgsql';
        ";
        
        $pdo->exec($functionSql);
        echo "✅ Função update_agendamento_updated_at criada<br>";
        
        // Trigger
        $triggerSql = "
        DROP TRIGGER IF EXISTS trigger_update_agendamento_updated_at ON sind.agendamento;
        CREATE TRIGGER trigger_update_agendamento_updated_at
            BEFORE UPDATE ON sind.agendamento
            FOR EACH ROW
            EXECUTE FUNCTION update_agendamento_updated_at();
        ";
        
        $pdo->exec($triggerSql);
        echo "✅ Trigger de update automático criado<br>";
        
    } catch (Exception $e) {
        echo "⚠️ Aviso: Não foi possível criar trigger: " . $e->getMessage() . "<br>";
    }
    
    echo "</div>";
    
    // Resumo
    echo "<div class='box success'>";
    echo "<h2>🎉 CONFIGURAÇÃO CONCLUÍDA!</h2>";
    echo "<strong>Resumo:</strong><br>";
    echo "📊 Colunas adicionadas: {$alterations}<br>";
    echo "❌ Erros: {$errors}<br>";
    echo "<br>";
    echo "<strong>Próximos passos:</strong><br>";
    echo "1. ✅ Execute o teste: <a href='test_agendamento_notifications.php'>test_agendamento_notifications.php</a><br>";
    echo "2. 🔄 Configure o monitoramento: <a href='check_agendamentos_notifications.php'>check_agendamentos_notifications.php</a><br>";
    echo "3. ⚙️ Configure cron job para automação<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box error'>";
    echo "<h2>❌ ERRO</h2>";
    echo "Erro: " . $e->getMessage();
    echo "</div>";
}

echo "<div class='box' style='background: #fff3cd; border: 1px solid #ffeaa7;'>";
echo "<h3>🗑️ LIMPEZA</h3>";
echo "❌ <strong>DELETE este arquivo após usar!</strong><br>";
echo "📁 Arquivo: setup_agendamento_notifications.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 