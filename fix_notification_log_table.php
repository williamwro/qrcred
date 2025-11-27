<?php
/**
 * Corrigir Estrutura da Tabela notification_log
 * Adiciona colunas faltantes como sent_at
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔧 Corrigir Tabela notification_log</h1>\n";
echo "<pre>\n";

try {
    require_once 'Adm/php/banco.php';
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=====================================\n";
    echo "1. VERIFICANDO TABELA notification_log\n";
    echo "=====================================\n";
    
    // Verificar se a tabela existe
    $tableStmt = $pdo->prepare("
        SELECT COUNT(*) as exists 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'notification_log'
    ");
    $tableStmt->execute();
    $tableExists = $tableStmt->fetch()['exists'] > 0;
    
    if (!$tableExists) {
        echo "❌ Tabela notification_log não existe. Criando...\n";
        
        $createSQL = "
        CREATE TABLE notification_log (
            id SERIAL PRIMARY KEY,
            user_card VARCHAR(20) NOT NULL,
            agendamento_id INTEGER,
            tipo_notificacao VARCHAR(50) NOT NULL,
            titulo VARCHAR(255),
            mensagem TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            response_data TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($createSQL);
        echo "✅ Tabela notification_log criada!\n";
        
        // Criar índices
        $indices = [
            "CREATE INDEX idx_notification_log_user_card ON notification_log(user_card)",
            "CREATE INDEX idx_notification_log_sent_at ON notification_log(sent_at)",
            "CREATE INDEX idx_notification_log_status ON notification_log(status)"
        ];
        
        foreach ($indices as $indexSQL) {
            $pdo->exec($indexSQL);
            echo "✅ Índice criado\n";
        }
        
    } else {
        echo "✅ Tabela notification_log existe.\n";
        
        // Verificar colunas existentes
        $columnsStmt = $pdo->prepare("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'notification_log'
            ORDER BY ordinal_position
        ");
        $columnsStmt->execute();
        $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "Colunas existentes:\n";
        foreach ($columns as $column) {
            echo "- {$column}\n";
        }
        
        // Colunas necessárias
        $requiredColumns = [
            'id' => 'SERIAL PRIMARY KEY',
            'user_card' => 'VARCHAR(20) NOT NULL',
            'agendamento_id' => 'INTEGER',
            'tipo_notificacao' => 'VARCHAR(50) NOT NULL',
            'titulo' => 'VARCHAR(255)',
            'mensagem' => 'TEXT',
            'status' => 'VARCHAR(20) DEFAULT \'pending\'',
            'sent_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'response_data' => 'TEXT',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ];
        
        echo "\n=====================================\n";
        echo "2. ADICIONANDO COLUNAS FALTANTES\n";
        echo "=====================================\n";
        
        foreach ($requiredColumns as $columnName => $definition) {
            if (!in_array($columnName, $columns)) {
                echo "🔧 Adicionando coluna: {$columnName}\n";
                
                if ($columnName === 'id') {
                    continue; // Skip primary key
                }
                
                try {
                    $alterSQL = "ALTER TABLE notification_log ADD COLUMN {$columnName} {$definition}";
                    echo "SQL: {$alterSQL}\n";
                    $pdo->exec($alterSQL);
                    echo "✅ Coluna {$columnName} adicionada!\n\n";
                } catch (Exception $e) {
                    echo "❌ Erro ao adicionar {$columnName}: {$e->getMessage()}\n\n";
                }
            } else {
                echo "✅ Coluna {$columnName} já existe\n";
            }
        }
    }
    
    echo "\n=====================================\n";
    echo "3. VERIFICAÇÃO FINAL\n";
    echo "=====================================\n";
    
    // Verificar estrutura final
    $finalStmt = $pdo->prepare("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'notification_log'
        ORDER BY ordinal_position
    ");
    $finalStmt->execute();
    $finalColumns = $finalStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Estrutura final da tabela notification_log:\n";
    foreach ($finalColumns as $column) {
        echo "  - {$column['column_name']} ({$column['data_type']})\n";
    }
    
    // Verificar dados
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM notification_log");
    $total = $countStmt->fetch()['total'];
    echo "\n📊 Total de registros: {$total}\n";
    
    echo "\n=====================================\n";
    echo "✅ TABELA notification_log CORRIGIDA!\n";
    echo "=====================================\n";
    echo "🎯 PRÓXIMOS PASSOS:\n";
    echo "1. Configure a chave VAPID privada no send_push_notification_app.php\n";
    echo "2. Reative as notificações no app móvel\n";
    echo "3. Execute novamente: test_manual_notification.php\n";
    echo "\n❌ DELETE este arquivo após usar!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n";
}

echo "</pre>\n";
?> 