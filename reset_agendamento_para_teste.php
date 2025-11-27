<?php
/**
 * Reset Agendamento Para Teste
 * Reseta flags e atualiza data_agendada para testar push notifications
 */

require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>🧪 Reset Agendamento Para Teste</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .urgent { background: #007bff; color: white; padding: 20px; border-radius: 5px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>
<div class='container'>
<h1>🧪 Reset Agendamento Para Teste</h1>";

echo "<div class='urgent'>
🎯 <strong>OBJETIVO:</strong> Resetar agendamento e configurar nova data para testar push notifications<br>
🕒 Timestamp: " . date('Y-m-d H:i:s') . "<br>
📱 Teste completo do sistema de notificações
</div>";

try {
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. LISTAR AGENDAMENTOS DISPONÍVEIS PARA TESTE
    echo "<h2>📋 1. AGENDAMENTOS DISPONÍVEIS</h2>";
    echo "<div class='box'>";
    
    $stmt = $pdo->query("
        SELECT 
            a.id,
            a.cod_associado,
            a.status,
            a.data_agendada,
            a.notification_sent_confirmado,
            s.nome as nome_associado,
            c.cod_verificacao as numero_cartao
        FROM sind.agendamento a
        INNER JOIN sind.associado s ON (
            a.cod_associado = s.codigo 
            AND a.id_empregador = s.empregador
        )
        INNER JOIN sind.c_cartaoassociado c ON (
            s.codigo = c.cod_associado 
            AND s.empregador = c.empregador
        )
        WHERE 
            c.cod_verificacao IS NOT NULL
            AND c.cod_situacaocartao = 1
        ORDER BY a.id DESC
        LIMIT 10
    ");
    
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($agendamentos)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Associado</th><th>Status</th><th>Data Agendada</th><th>Notificado</th><th>Cartão</th><th>Ação</th></tr>";
        
        foreach ($agendamentos as $row) {
            $statusTexto = $row['status'] == 2 ? 'Confirmado' : 'Status ' . $row['status'];
            $notificado = $row['notification_sent_confirmado'] ? 'Sim' : 'Não';
            $dataFormatada = $row['data_agendada'] ? date('d/m/Y H:i', strtotime($row['data_agendada'])) : 'Sem data';
            
            echo "<tr>";
            echo "<td><strong>{$row['id']}</strong></td>";
            echo "<td>{$row['nome_associado']}</td>";
            echo "<td>{$statusTexto}</td>";
            echo "<td>{$dataFormatada}</td>";
            echo "<td>{$notificado}</td>";
            echo "<td>{$row['numero_cartao']}</td>";
            echo "<td><a href='?reset_id={$row['id']}' class='btn btn-warning'>🔄 Resetar</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Nenhum agendamento encontrado com cartão válido</div>";
    }
    
    echo "</div>";
    
    // 2. PROCESSAMENTO DO RESET
    if (isset($_GET['reset_id'])) {
        $agendamento_id = (int)$_GET['reset_id'];
        
        echo "<h2>🔄 2. RESETANDO AGENDAMENTO ID {$agendamento_id}</h2>";
        echo "<div class='box'>";
        
        // Buscar dados atuais
        $stmt = $pdo->prepare("SELECT * FROM sind.agendamento WHERE id = ?");
        $stmt->execute([$agendamento_id]);
        $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($agendamento) {
            // Nova data (amanhã às 14:00)
            $novaData = date('Y-m-d H:i:s', strtotime('+1 day 14:00'));
            
            // Reset das flags e nova data
            $updateSql = "
                UPDATE sind.agendamento 
                SET 
                    notification_sent_confirmado = false,
                    notification_sent_24h = false,
                    notification_sent_1h = false,
                    data_agendada = ?,
                    status = 2
                WHERE id = ?
            ";
            
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([$novaData, $agendamento_id]);
            
            echo "<div class='success'>✅ <strong>AGENDAMENTO RESETADO COM SUCESSO!</strong></div>";
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor Anterior</th><th>Novo Valor</th></tr>";
            echo "<tr><td>data_agendada</td><td>" . ($agendamento['data_agendada'] ?? 'NULL') . "</td><td>{$novaData}</td></tr>";
            echo "<tr><td>status</td><td>{$agendamento['status']}</td><td>2 (Confirmado)</td></tr>";
            echo "<tr><td>notification_sent_confirmado</td><td>" . ($agendamento['notification_sent_confirmado'] ? 'true' : 'false') . "</td><td>false</td></tr>";
            echo "<tr><td>notification_sent_24h</td><td>" . ($agendamento['notification_sent_24h'] ? 'true' : 'false') . "</td><td>false</td></tr>";
            echo "<tr><td>notification_sent_1h</td><td>" . ($agendamento['notification_sent_1h'] ? 'true' : 'false') . "</td><td>false</td></tr>";
            echo "</table>";
            
            echo "<div class='info'>📅 <strong>Nova data agendada:</strong> " . date('d/m/Y H:i:s', strtotime($novaData)) . "</div>";
            
        } else {
            echo "<div class='error'>❌ Agendamento ID {$agendamento_id} não encontrado</div>";
        }
        
        echo "</div>";
        
        // 3. TESTE AUTOMÁTICO
        echo "<h2>🚀 3. EXECUTAR TESTE AUTOMÁTICO</h2>";
        echo "<div class='box'>";
        echo "<a href='check_agendamentos_notifications_final.php' target='_blank' class='btn btn-success'>📱 Enviar Push Notification</a>";
        echo "<a href='?' class='btn btn-info'>🔄 Voltar à Lista</a>";
        echo "</div>";
    }
    
    // 4. INSTRUÇÕES
    if (!isset($_GET['reset_id'])) {
        echo "<h2>📖 3. INSTRUÇÕES</h2>";
        echo "<div class='box'>";
        echo "<h3>🎯 Como usar:</h3>";
        echo "<ol>";
        echo "<li>🔍 <strong>Escolha um agendamento</strong> da lista acima</li>";
        echo "<li>🔄 <strong>Clique em 'Resetar'</strong> para configurar novo teste</li>";
        echo "<li>📅 <strong>Data será definida</strong> para amanhã às 14:00</li>";
        echo "<li>✅ <strong>Status será confirmado</strong> (status = 2)</li>";
        echo "<li>🚫 <strong>Flags serão resetadas</strong> (notification_sent_* = false)</li>";
        echo "<li>📱 <strong>Execute o sistema</strong> para enviar push notification</li>";
        echo "</ol>";
        
        echo "<h3>⚡ Teste rápido:</h3>";
        echo "<a href='check_agendamentos_notifications_final.php' target='_blank' class='btn btn-success'>🚀 Executar Sistema Final Agora</a>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='box error'>";
    echo "<h2>❌ ERRO</h2>";
    echo "Erro: " . $e->getMessage();
    echo "</div>";
}

echo "<div class='box warning'>";
echo "<h3>🗑️ LIMPEZA</h3>";
echo "❌ <strong>DELETE este arquivo após usar!</strong><br>";
echo "📁 Arquivo: reset_agendamento_para_teste.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 