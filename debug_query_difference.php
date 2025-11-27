<?php
/**
 * Debug Query Difference
 * Comparar queries para identificar por que uma funciona e outra não
 */

require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔍 Debug Query Difference</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .urgent { background: #ff6b6b; color: white; padding: 20px; border-radius: 5px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; }
        .code { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: monospace; font-size: 11px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔍 Debug Query Difference</h1>";

echo "<div class='urgent'>
🚨 <strong>PROBLEMA:</strong> Script de verificação encontra 1 agendamento, mas sistema final encontra 0!<br>
🕒 Timestamp: " . date('Y-m-d H:i:s') . "<br>
🔍 Investigando diferenças nas queries...
</div>";

try {
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. QUERY DO SCRIPT DE VERIFICAÇÃO (que funcionou)
    echo "<h2>🧪 1. QUERY DO SCRIPT DE VERIFICAÇÃO (FUNCIONOU)</h2>";
    echo "<div class='box'>";
    
    $queryVerificacao = "
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
    ";
    
    echo "<div class='code'>" . $queryVerificacao . "</div>";
    
    $stmt = $pdo->query($queryVerificacao);
    $resultadosVerificacao = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<strong>Resultados:</strong> " . count($resultadosVerificacao) . " agendamentos encontrados<br>";
    
    if (!empty($resultadosVerificacao)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Cod Associado</th><th>Status</th><th>Data Agendada</th><th>Cartão</th><th>Nome</th><th>Notificado</th></tr>";
        
        foreach ($resultadosVerificacao as $row) {
            $notificado = $row['notification_sent_confirmado'] ? 'Sim' : 'Não';
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
        echo "<div class='success'>✅ Query de verificação encontrou agendamentos!</div>";
    } else {
        echo "<div class='error'>❌ Query de verificação não encontrou nada</div>";
    }
    
    echo "</div>";
    
    // 2. QUERY DO SISTEMA FINAL (que não funcionou)
    echo "<h2>🚀 2. QUERY DO SISTEMA FINAL (NÃO FUNCIONOU)</h2>";
    echo "<div class='box'>";
    
    $queryFinal = "
        SELECT 
            a.id, 
            a.cod_associado, 
            a.id_empregador, 
            a.data_solicitacao, 
            a.cod_convenio, 
            a.status, 
            a.profissional, 
            a.especialidade, 
            a.convenio_nome, 
            a.data_agendada, 
            a.notification_sent_confirmado,
            a.notification_sent_24h, 
            a.notification_sent_1h,
            s.nome as nome_associado,
            s.email,
            s.cel,
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
            AND a.status = 2
            AND c.cod_verificacao IS NOT NULL
            AND c.cod_situacaocartao = 1
        ORDER BY a.data_agendada ASC
        LIMIT 50
    ";
    
    echo "<div class='code'>" . $queryFinal . "</div>";
    
    $stmt = $pdo->query($queryFinal);
    $resultadosFinal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<strong>Resultados:</strong> " . count($resultadosFinal) . " agendamentos encontrados<br>";
    
    if (!empty($resultadosFinal)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Cod Associado</th><th>Status</th><th>Data Agendada</th><th>Cartão</th><th>Nome</th><th>Notificado</th></tr>";
        
        foreach ($resultadosFinal as $row) {
            $notificado = $row['notification_sent_confirmado'] ? 'Sim' : 'Não';
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
        echo "<div class='success'>✅ Query final encontrou agendamentos!</div>";
    } else {
        echo "<div class='error'>❌ Query final NÃO encontrou nada</div>";
    }
    
    echo "</div>";
    
    // 3. VERIFICAR STATUS ATUAL DO AGENDAMENTO 65
    echo "<h2>🎯 3. STATUS ATUAL DO AGENDAMENTO 65</h2>";
    echo "<div class='box'>";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            cod_associado,
            status,
            data_agendada,
            notification_sent_confirmado,
            notification_sent_24h,
            notification_sent_1h
        FROM sind.agendamento 
        WHERE id = 65
    ");
    $stmt->execute();
    $agendamento65 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($agendamento65) {
        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th><th>Observação</th></tr>";
        echo "<tr><td>ID</td><td>{$agendamento65['id']}</td><td>-</td></tr>";
        echo "<tr><td>Cod Associado</td><td>{$agendamento65['cod_associado']}</td><td>-</td></tr>";
        echo "<tr><td>Status</td><td>{$agendamento65['status']}</td><td>" . ($agendamento65['status'] == 2 ? 'Confirmado ✅' : 'Não confirmado ❌') . "</td></tr>";
        echo "<tr><td>Data Agendada</td><td>" . ($agendamento65['data_agendada'] ? date('d/m/Y H:i:s', strtotime($agendamento65['data_agendada'])) : 'NULL') . "</td><td>" . ($agendamento65['data_agendada'] ? 'Preenchida ✅' : 'Vazia ❌') . "</td></tr>";
        echo "<tr><td>notification_sent_confirmado</td><td>" . ($agendamento65['notification_sent_confirmado'] ? 'true' : 'false/NULL') . "</td><td>" . ($agendamento65['notification_sent_confirmado'] ? 'JÁ NOTIFICADO ❌' : 'Pode notificar ✅') . "</td></tr>";
        echo "<tr><td>notification_sent_24h</td><td>" . ($agendamento65['notification_sent_24h'] ? 'true' : 'false/NULL') . "</td><td>-</td></tr>";
        echo "<tr><td>notification_sent_1h</td><td>" . ($agendamento65['notification_sent_1h'] ? 'true' : 'false/NULL') . "</td><td>-</td></tr>";
        echo "</table>";
        
        if ($agendamento65['notification_sent_confirmado']) {
            echo "<div class='warning'>⚠️ <strong>CAUSA ENCONTRADA!</strong> Agendamento já foi marcado como notificado!</div>";
            echo "<div class='info'>💡 <strong>SOLUÇÃO:</strong> Resetar flag de notificação para testar novamente</div>";
        } else {
            echo "<div class='success'>✅ Agendamento ainda não foi notificado - deveria ser processado</div>";
        }
        
    } else {
        echo "<div class='error'>❌ Agendamento ID 65 não encontrado</div>";
    }
    
    echo "</div>";
    
    // 4. TESTAR QUERY ESPECÍFICA PARA ID 65
    echo "<h2>🔬 4. TESTE ESPECÍFICO PARA ID 65</h2>";
    echo "<div class='box'>";
    
    $queryEspecifica = "
        SELECT 
            a.id, 
            a.cod_associado, 
            a.status,
            a.data_agendada, 
            a.notification_sent_confirmado,
            s.nome as nome_associado,
            c.cod_verificacao as numero_cartao,
            c.cod_situacaocartao
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
            a.id = 65
    ";
    
    echo "<div class='code'>" . $queryEspecifica . "</div>";
    
    $stmt = $pdo->query($queryEspecifica);
    $resultado65 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado65) {
        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th><th>Condição</th><th>Status</th></tr>";
        echo "<tr><td>data_agendada IS NOT NULL</td><td>" . ($resultado65['data_agendada'] ?? 'NULL') . "</td><td>IS NOT NULL</td><td>" . ($resultado65['data_agendada'] ? '✅ OK' : '❌ FALHA') . "</td></tr>";
        echo "<tr><td>notification_sent_confirmado</td><td>" . ($resultado65['notification_sent_confirmado'] ? 'true' : 'false/NULL') . "</td><td>IS NULL OR = false</td><td>" . (!$resultado65['notification_sent_confirmado'] ? '✅ OK' : '❌ FALHA') . "</td></tr>";
        echo "<tr><td>status</td><td>{$resultado65['status']}</td><td>= 2</td><td>" . ($resultado65['status'] == 2 ? '✅ OK' : '❌ FALHA') . "</td></tr>";
        echo "<tr><td>cod_verificacao</td><td>{$resultado65['numero_cartao']}</td><td>IS NOT NULL</td><td>" . ($resultado65['numero_cartao'] ? '✅ OK' : '❌ FALHA') . "</td></tr>";
        echo "<tr><td>cod_situacaocartao</td><td>{$resultado65['cod_situacaocartao']}</td><td>= 1</td><td>" . ($resultado65['cod_situacaocartao'] == 1 ? '✅ OK' : '❌ FALHA') . "</td></tr>";
        echo "</table>";
        
        $todasCondicoes = 
            $resultado65['data_agendada'] && 
            !$resultado65['notification_sent_confirmado'] && 
            $resultado65['status'] == 2 && 
            $resultado65['numero_cartao'] && 
            $resultado65['cod_situacaocartao'] == 1;
        
        if ($todasCondicoes) {
            echo "<div class='success'>✅ <strong>TODAS AS CONDIÇÕES ATENDIDAS!</strong> Agendamento deveria ser processado!</div>";
        } else {
            echo "<div class='error'>❌ <strong>ALGUMA CONDIÇÃO FALHOU!</strong> Por isso não está sendo processado.</div>";
        }
        
    } else {
        echo "<div class='error'>❌ Query específica para ID 65 falhou - problema no JOIN</div>";
    }
    
    echo "</div>";
    
    // 5. RESET PARA TESTE (SE NECESSÁRIO)
    if (isset($_GET['reset']) && $_GET['reset'] == 'true') {
        echo "<h2>🔄 5. RESETANDO FLAGS PARA TESTE</h2>";
        echo "<div class='box'>";
        
        $resetSql = "UPDATE sind.agendamento SET notification_sent_confirmado = false WHERE id = 65";
        $pdo->exec($resetSql);
        
        echo "<div class='success'>✅ <strong>FLAGS RESETADAS!</strong> Agendamento 65 pode ser notificado novamente.</div>";
        echo "<a href='check_agendamentos_notifications_final.php' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px;'>🚀 Testar Sistema Final Agora</a>";
        
        echo "</div>";
    } else {
        echo "<h2>🔄 5. AÇÕES</h2>";
        echo "<div class='box'>";
        echo "<a href='?reset=true' style='background: #ffc107; color: black; padding: 10px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px;'>🔄 Resetar Flags de Notificação</a>";
        echo "<a href='check_agendamentos_notifications_final.php' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px;'>🚀 Executar Sistema Final</a>";
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
echo "📁 Arquivo: debug_query_difference.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 