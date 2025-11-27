<?php
/**
 * Criar Agendamento de Teste para o Usuário com Subscription
 * Execute UMA VEZ para testar o sistema de notificações
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🧪 Criar Agendamento de Teste</h1>\n";
echo "<pre>\n";

try {
    require_once 'Adm/php/banco.php';
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Usuário que tem subscription ativa (do debug)
    $userCard = '8029774802';
    
    // Data de agendamento para 1 hora no futuro
    $dataAgendada = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    echo "Criando agendamento de teste...\n";
    echo "Usuário: {$userCard}\n";
    echo "Data: {$dataAgendada}\n\n";
    
    // Verificar se já existe agendamento para este usuário
    $checkStmt = $pdo->prepare("
        SELECT id FROM sind.agendamento 
        WHERE cod_associado = :cod_associado 
        AND data_agendada IS NOT NULL
        AND data_agendada > CURRENT_TIMESTAMP
    ");
    $checkStmt->execute([':cod_associado' => $userCard]);
    
    if ($checkStmt->fetch()) {
        echo "⚠️ Já existe agendamento futuro para este usuário.\n";
        echo "🔄 Atualizando data do agendamento existente...\n";
        
        $updateStmt = $pdo->prepare("
            UPDATE sind.agendamento 
            SET data_agendada = :data_agendada,
                profissional = :profissional,
                especialidade = :especialidade,
                cod_convenio = '1'
            WHERE cod_associado = :cod_associado 
            AND data_agendada > CURRENT_TIMESTAMP
        ");
        
        $updateStmt->execute([
            ':cod_associado' => $userCard,
            ':data_agendada' => $dataAgendada,
            ':profissional' => 'Dr. Teste - Push Notification',
            ':especialidade' => 'Teste de Notificação'
        ]);
        
        echo "✅ Agendamento atualizado!\n";
        
    } else {
        echo "📅 Criando novo agendamento...\n";
        
        $insertStmt = $pdo->prepare("
            INSERT INTO sind.agendamento 
            (cod_associado, id_empregador, data_solicitacao, cod_convenio, status, data_agendada, profissional, especialidade)
            VALUES 
            (:cod_associado, 1, CURRENT_TIMESTAMP, '1', 1, :data_agendada, :profissional, :especialidade)
        ");
        
        $insertStmt->execute([
            ':cod_associado' => $userCard,
            ':data_agendada' => $dataAgendada,
            ':profissional' => 'Dr. Teste - Push Notification',
            ':especialidade' => 'Teste de Notificação'
        ]);
        
        echo "✅ Novo agendamento criado!\n";
    }
    
    echo "\n=====================================\n";
    echo "✅ AGENDAMENTO DE TESTE CRIADO!\n";
    echo "=====================================\n";
    echo "Usuário: {$userCard}\n";
    echo "Data: {$dataAgendada}\n";
    echo "Profissional: Dr. Teste - Push Notification\n";
    echo "Especialidade: Teste de Notificação\n\n";
    
    echo "🎯 PRÓXIMOS PASSOS:\n";
    echo "1. Execute: https://sas.makecard.com.br/test_manual_notification.php\n";
    echo "2. Ou aguarde o sistema automático processar (se estiver configurado)\n";
    echo "3. Verifique se a notificação chegou no dispositivo\n\n";
    
    echo "❌ DELETE este arquivo após usar!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n";
}

echo "</pre>\n";
?> 