<?php
/**
 * Corrigir Estrutura da Tabela push_subscriptions
 * Este script verifica e adiciona as colunas necessárias
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔧 Corrigir Tabela push_subscriptions</h1>\n";
echo "<pre>\n";

try {
    require_once 'Adm/php/banco.php';
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=====================================\n";
    echo "1. VERIFICANDO ESTRUTURA ATUAL\n";
    echo "=====================================\n";
    
    // Verificar colunas da tabela
    $stmt = $pdo->prepare("
        SELECT column_name, data_type, is_nullable 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'push_subscriptions'
        ORDER BY ordinal_position
    ");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Colunas existentes na tabela push_subscriptions:\n";
    foreach ($columns as $column) {
        echo "- {$column['column_name']} ({$column['data_type']}) - Nullable: {$column['is_nullable']}\n";
    }
    
    // Colunas necessárias
    $requiredColumns = [
        'id' => 'SERIAL PRIMARY KEY',
        'user_card' => 'VARCHAR(20) NOT NULL',
        'endpoint' => 'TEXT NOT NULL',
        'p256dh' => 'TEXT NOT NULL',
        'auth' => 'TEXT NOT NULL',
        'settings' => 'TEXT DEFAULT \'{"enabled":true,"agendamentoConfirmado":true,"lembrete24h":true,"lembrete1h":true}\'',
        'is_active' => 'BOOLEAN DEFAULT true',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
    ];
    
    echo "\nColunas necessárias:\n";
    foreach ($requiredColumns as $name => $definition) {
        echo "- {$name}: {$definition}\n";
    }
    
    echo "\n=====================================\n";
    echo "2. ADICIONANDO COLUNAS FALTANTES\n";
    echo "=====================================\n";
    
    $existingColumns = array_column($columns, 'column_name');
    
    foreach ($requiredColumns as $columnName => $definition) {
        if (!in_array($columnName, $existingColumns)) {
            echo "🔧 Adicionando coluna: {$columnName}\n";
            
            // Extrair tipo da definição
            $type = $definition;
            if ($columnName === 'id') {
                // Skip ID - deve ser adicionado como primary key
                continue;
            }
            
            try {
                $alterSql = "ALTER TABLE push_subscriptions ADD COLUMN {$columnName} {$type}";
                echo "SQL: {$alterSql}\n";
                $pdo->exec($alterSql);
                echo "✅ Coluna {$columnName} adicionada!\n\n";
            } catch (Exception $e) {
                echo "❌ Erro ao adicionar {$columnName}: {$e->getMessage()}\n\n";
            }
        } else {
            echo "✅ Coluna {$columnName} já existe\n";
        }
    }
    
    echo "\n=====================================\n";
    echo "3. VERIFICANDO DADOS EXISTENTES\n";
    echo "=====================================\n";
    
    // Verificar registros
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM sind.push_subscriptions");
    $total = $countStmt->fetch()['total'];
    echo "Total de registros: {$total}\n";
    
    if ($total > 0) {
        echo "\n🔍 Primeiros registros:\n";
        $dataStmt = $pdo->query("SELECT * FROM sind.push_subscriptions LIMIT 3");
        $records = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($records as $i => $record) {
            echo "\nRegistro " . ($i + 1) . ":\n";
            foreach ($record as $key => $value) {
                $displayValue = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                echo "  {$key}: {$displayValue}\n";
            }
        }
    }
    
    echo "\n=====================================\n";
    echo "4. VERIFICAÇÃO FINAL\n";
    echo "=====================================\n";
    
    // Verificar novamente as colunas
    $stmt->execute();
    $newColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $newColumnNames = array_column($newColumns, 'column_name');
    
    $missingColumns = [];
    foreach (array_keys($requiredColumns) as $required) {
        if (!in_array($required, $newColumnNames)) {
            $missingColumns[] = $required;
        }
    }
    
    if (empty($missingColumns)) {
        echo "✅ TABELA CORRIGIDA COM SUCESSO!\n";
        echo "✅ Todas as colunas necessárias estão presentes.\n";
        echo "\n🎯 PRÓXIMO PASSO:\n";
        echo "Execute novamente: https://sas.makecard.com.br/test_manual_notification.php\n";
    } else {
        echo "❌ Ainda faltam colunas: " . implode(', ', $missingColumns) . "\n";
        echo "🔧 Pode ser necessário recriar a tabela completamente.\n";
    }
    
    echo "\n❌ DELETE este arquivo após usar!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n";
    echo "\n💡 Se o erro persistir, pode ser necessário recriar a tabela:\n";
    echo "DROP TABLE IF EXISTS push_subscriptions;\n";
    echo "E depois executar o script de criação completo.\n";
}

echo "</pre>\n";
?> 