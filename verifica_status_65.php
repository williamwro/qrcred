<?php
/**
 * Verificar Status do Agendamento ID 65
 */

require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔍 Status do Agendamento ID 65</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .button { background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔍 Status do Agendamento ID 65</h1>";

try {
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar agendamento ID 65
    echo "<h2>📋 AGENDAMENTO ID 65</h2>";
    echo "<div class='box'>";
    
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            cod_associado, 
            id_empregador,
            status,
            data_agendada,
            profissional,
            especialidade,
            notification_sent_confirmado
        FROM sind.agendamento 
        WHERE id = 65
    ");
    $stmt->execute();
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($agendamento) {
        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th><th>Status</th></tr>";
        
        $status_desc = '';
        switch ($agendamento['status']) {
            case 0: $status_desc = 'Inativo/Cancelado'; break;
            case 1: $status_desc = 'Ativo'; break;
            case 2: $status_desc = 'CONFIRMADO ✅'; break;
            case 3: $status_desc = 'Em Andamento'; break;
            default: $status_desc = 'Desconhecido'; break;
        }
        
        echo "<tr><td>ID</td><td><strong>{$agendamento['id']}</strong></td><td>-</td></tr>";
        echo "<tr><td>Cod Associado</td><td>{$agendamento['cod_associado']}</td><td>-</td></tr>";
        echo "<tr><td>Empregador</td><td>{$agendamento['id_empregador']}</td><td>-</td></tr>";
        echo "<tr><td>Status</td><td><strong>{$agendamento['status']}</strong></td><td><strong>{$status_desc}</strong></td></tr>";
        echo "<tr><td>Data Agendada</td><td>" . ($agendamento['data_agendada'] ? date('d/m/Y H:i', strtotime($agendamento['data_agendada'])) : 'N/A') . "</td><td>-</td></tr>";
        echo "<tr><td>Profissional</td><td>{$agendamento['profissional']}</td><td>-</td></tr>";
        echo "<tr><td>Notificado</td><td>" . ($agendamento['notification_sent_confirmado'] ? 'Sim' : 'Não') . "</td><td>-</td></tr>";
        echo "</table>";
        
        if ($agendamento['status'] == 2) {
            echo "<div class='success'>✅ <strong>PERFEITO!</strong> Status = 2 (CONFIRMADO) - Sistema deve processar este agendamento!</div>";
        } else {
            echo "<div class='warning'>⚠️ <strong>PROBLEMA:</strong> Status = {$agendamento['status']} ({$status_desc})</div>";
            echo "<div class='info'>💡 <strong>SOLUÇÃO:</strong> Para testar, mude o status para 2 (CONFIRMADO)</div>";
            
            if (isset($_GET['fix']) && $_GET['fix'] == 'true') {
                // Atualizar status para 2
                $updateSql = "UPDATE sind.agendamento SET status = 2 WHERE id = 65";
                $pdo->exec($updateSql);
                echo "<div class='success'>✅ <strong>CORRIGIDO!</strong> Status alterado para 2 (CONFIRMADO)</div>";
                echo "<meta http-equiv='refresh' content='2'>";
            } else {
                echo "<a href='?fix=true' class='button'>🔧 Alterar Status para 2 (CONFIRMADO)</a>";
            }
        }
        
    } else {
        echo "<div class='error'>❌ Agendamento ID 65 não encontrado</div>";
    }
    
    echo "</div>";
    
    // Testar query final com status = 2
    echo "<h2>🧪 TESTE DA QUERY FINAL</h2>";
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
            a.data_agendada IS NOT NULL 
            AND (a.notification_sent_confirmado IS NULL OR a.notification_sent_confirmado = false)
            AND a.status = 2  -- APENAS CONFIRMADOS
            AND c.cod_verificacao IS NOT NULL
            AND c.cod_situacaocartao = 1
        ORDER BY a.id DESC
        LIMIT 5
    ");
    $testAgendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<strong>Agendamentos CONFIRMADOS (status = 2) encontrados:</strong><br>";
    
    if (empty($testAgendamentos)) {
        echo "<div class='warning'>⚠️ Nenhum agendamento CONFIRMADO encontrado</div>";
        echo "<div class='info'>💡 Isso significa que não há agendamentos com status = 2 para notificar</div>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Cod Associado</th><th>Status</th><th>Data Agendada</th><th>Cartão</th><th>Nome</th><th>Notificado</th></tr>";
        
        foreach ($testAgendamentos as $row) {
            $notificado = $row['notification_sent_confirmado'] ? '✅ Sim' : '❌ Não';
            $dataFormatada = date('d/m/Y H:i', strtotime($row['data_agendada']));
            
            echo "<tr>";
            echo "<td><strong>{$row['id']}</strong></td>";
            echo "<td>{$row['cod_associado']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>{$dataFormatada}</td>";
            echo "<td>{$row['numero_cartao']}</td>";
            echo "<td>{$row['nome_associado']}</td>";
            echo "<td>{$notificado}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div class='success'>✅ Encontrados " . count($testAgendamentos) . " agendamentos CONFIRMADOS para notificar!</div>";
        echo "<a href='check_agendamentos_notifications_final.php' class='button' target='_blank'>🚀 Executar Sistema Final</a>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box error'>";
    echo "<h2>❌ ERRO</h2>";
    echo "Erro: " . $e->getMessage();
    echo "</div>";
}

echo "<div class='box warning'>";
echo "<h3>🗑️ LIMPEZA</h3>";
echo "❌ <strong>DELETE este arquivo após usar!</strong><br>";
echo "📁 Arquivo: verifica_status_65.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 