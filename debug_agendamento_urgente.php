<?php
/**
 * Debug Agendamento Urgente
 * Diagnosticar por que push não chegou após gravar data_agendada
 */

require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>🚨 DEBUG URGENTE - Push Não Chegou</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .urgent { background: #ff6b6b; color: white; padding: 20px; border-radius: 5px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .button { background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #0056b3; }
        .code { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🚨 DEBUG URGENTE - Push Não Chegou</h1>";

echo "<div class='urgent'>
⏰ <strong>SITUAÇÃO:</strong> Usuário gravou data_agendada mas push não chegou no celular!<br>
🕒 Timestamp: " . date('Y-m-d H:i:s') . "<br>
🔍 Investigando causa...
</div>";

try {
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. VERIFICAR ÚLTIMOS AGENDAMENTOS
    echo "<h2>📋 1. ÚLTIMOS AGENDAMENTOS COM DATA_AGENDADA</h2>";
    echo "<div class='box'>";
    
    $stmt = $pdo->query("
        SELECT 
            id, 
            cod_associado, 
            id_empregador,
            data_agendada,
            data_solicitacao,
            profissional,
            especialidade,
            status,
            notification_sent_confirmado
        FROM sind.agendamento 
        WHERE data_agendada IS NOT NULL
        ORDER BY data_solicitacao DESC 
        LIMIT 5
    ");
    $ultimosAgendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ultimosAgendamentos)) {
        echo "<div class='error'>❌ Nenhum agendamento com data_agendada encontrado!</div>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Cod Associado</th><th>Empregador</th><th>Data Agendada</th><th>Profissional</th><th>Status</th><th>Notificado</th><th>Solicitado</th></tr>";
        
        foreach ($ultimosAgendamentos as $row) {
            $notificado = $row['notification_sent_confirmado'] ? '✅ Sim' : '❌ Não';
            $status = $row['status'] == 1 ? '✅ Ativo' : '❌ Inativo';
            $dataFormatada = $row['data_agendada'] ? date('d/m/Y H:i', strtotime($row['data_agendada'])) : 'N/A';
            $solicitadoEm = $row['data_solicitacao'] ? date('d/m/Y H:i:s', strtotime($row['data_solicitacao'])) : 'N/A';
            
            echo "<tr>";
            echo "<td><strong>{$row['id']}</strong></td>";
            echo "<td>{$row['cod_associado']}</td>";
            echo "<td>{$row['id_empregador']}</td>";
            echo "<td>{$dataFormatada}</td>";
            echo "<td>{$row['profissional']}</td>";
            echo "<td>{$status}</td>";
            echo "<td>{$notificado}</td>";
            echo "<td>{$solicitadoEm}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $maisRecente = $ultimosAgendamentos[0];
        if (!$maisRecente['notification_sent_confirmado']) {
            echo "<div class='warning'>⚠️ <strong>AGENDAMENTO MAIS RECENTE (ID: {$maisRecente['id']}) NÃO FOI NOTIFICADO!</strong></div>";
            $idParaTestar = $maisRecente['id'];
        } else {
            echo "<div class='info'>ℹ️ Agendamento mais recente já foi notificado.</div>";
            $idParaTestar = null;
        }
    }
    echo "</div>";
    
    // 2. TESTAR JOIN TRIPLO COM AGENDAMENTO ESPECÍFICO
    if (!empty($ultimosAgendamentos)) {
        echo "<h2>🔍 2. TESTE JOIN TRIPLO - NÚMERO DO CARTÃO</h2>";
        echo "<div class='box'>";
        
        $testId = $ultimosAgendamentos[0]['id'];
        
        $sqlJoin = "
            SELECT 
                a.id, 
                a.cod_associado, 
                a.id_empregador, 
                a.data_agendada, 
                a.profissional, 
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
            WHERE a.id = ?
        ";
        
        try {
            $stmt = $pdo->prepare($sqlJoin);
            $stmt->execute([$testId]);
            $joinResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($joinResult) {
                echo "<div class='success'>✅ JOIN TRIPLO funcionando!</div>";
                echo "<table>";
                echo "<tr><th>Campo</th><th>Valor</th></tr>";
                echo "<tr><td>ID Agendamento</td><td><strong>{$joinResult['id']}</strong></td></tr>";
                echo "<tr><td>Cod Associado</td><td>{$joinResult['cod_associado']}</td></tr>";
                echo "<tr><td>Empregador</td><td>{$joinResult['id_empregador']}</td></tr>";
                echo "<tr><td>Nome Associado</td><td>{$joinResult['nome_associado']}</td></tr>";
                echo "<tr><td><strong>Número do Cartão</strong></td><td><strong style='color: #007bff;'>{$joinResult['numero_cartao']}</strong></td></tr>";
                echo "<tr><td>Status Cartão</td><td>" . ($joinResult['cod_situacaocartao'] == 1 ? '✅ Ativo' : '❌ Inativo') . "</td></tr>";
                echo "<tr><td>Notificado</td><td>" . ($joinResult['notification_sent_confirmado'] ? '✅ Sim' : '❌ Não') . "</td></tr>";
                echo "</table>";
                
                if ($joinResult['cod_situacaocartao'] != 1) {
                    echo "<div class='error'>❌ <strong>PROBLEMA:</strong> Cartão está INATIVO! Status: {$joinResult['cod_situacaocartao']}</div>";
                }
                
                if (!$joinResult['numero_cartao']) {
                    echo "<div class='error'>❌ <strong>PROBLEMA:</strong> Número do cartão está VAZIO!</div>";
                }
                
            } else {
                echo "<div class='error'>❌ <strong>PROBLEMA:</strong> JOIN TRIPLO falhou! Associado não tem cartão ou dados inconsistentes.</div>";
                
                // Testar cada JOIN separadamente
                echo "<h3>🔍 Debug dos JOINs:</h3>";
                
                // Teste JOIN com associado
                $sqlAssociado = "
                    SELECT a.*, s.nome, s.codigo, s.empregador
                    FROM sind.agendamento a
                    INNER JOIN sind.associado s ON (
                        a.cod_associado = s.codigo 
                        AND a.id_empregador = s.empregador
                    )
                    WHERE a.id = ?
                ";
                $stmt = $pdo->prepare($sqlAssociado);
                $stmt->execute([$testId]);
                $associadoResult = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($associadoResult) {
                    echo "<div class='success'>✅ JOIN com sind.associado OK</div>";
                    
                    // Teste se existe cartão para este associado
                    $sqlCartao = "
                        SELECT * FROM sind.c_cartaoassociado 
                        WHERE cod_associado = ? AND empregador = ?
                    ";
                    $stmt = $pdo->prepare($sqlCartao);
                    $stmt->execute([$associadoResult['codigo'], $associadoResult['empregador']]);
                    $cartaoResult = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($cartaoResult) {
                        echo "<div class='success'>✅ Cartão encontrado para este associado</div>";
                        echo "<strong>Detalhes do cartão:</strong><br>";
                        echo "Número: <strong>{$cartaoResult['cod_verificacao']}</strong><br>";
                        echo "Status: " . ($cartaoResult['cod_situacaocartao'] == 1 ? '✅ Ativo' : '❌ Inativo') . "<br>";
                    } else {
                        echo "<div class='error'>❌ <strong>PROBLEMA:</strong> Associado não tem cartão na tabela sind.c_cartaoassociado!</div>";
                    }
                    
                } else {
                    echo "<div class='error'>❌ <strong>PROBLEMA:</strong> JOIN com sind.associado falhou!</div>";
                }
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro no JOIN: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
    }
    
    // 3. TESTAR SISTEMA FINAL AGORA
    echo "<h2>🚀 3. EXECUTAR SISTEMA FINAL AGORA</h2>";
    echo "<div class='box'>";
    
    echo "<a href='check_agendamentos_notifications_final.php' class='button' target='_blank'>
          🔄 Executar Sistema Final Agora
          </a><br><br>";
    
    // Executar via cURL
    $url = 'https://sas.makecard.com.br/check_agendamentos_notifications_final.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Debug-Urgente/1.0'
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($result !== false) {
        echo "<strong>Resultado da execução (HTTP {$httpCode}):</strong><br>";
        
        $response = json_decode($result, true);
        if ($response) {
            echo "<div class='code'>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</div>";
            
            if ($response['success']) {
                if ($response['results']['notifications_sent'] > 0) {
                    echo "<div class='success'>✅ <strong>{$response['results']['notifications_sent']} notificações enviadas!</strong></div>";
                    
                    if (isset($response['results']['details'])) {
                        echo "<h4>📋 Detalhes das notificações:</h4>";
                        echo "<table>";
                        echo "<tr><th>ID</th><th>Associado</th><th>Cartão Usado</th><th>Nome</th><th>Status</th></tr>";
                        
                        foreach ($response['results']['details'] as $detail) {
                            $status = $detail['success'] ? '✅ Enviado' : '❌ Erro';
                            echo "<tr>";
                            echo "<td>{$detail['agendamento_id']}</td>";
                            echo "<td>{$detail['cod_associado']}</td>";
                            echo "<td><strong>{$detail['user_card']}</strong></td>";
                            echo "<td>{$detail['nome_associado']}</td>";
                            echo "<td>{$status}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                    
                } else {
                    echo "<div class='warning'>⚠️ Nenhuma notificação enviada</div>";
                    echo "<strong>Possíveis motivos:</strong><br>";
                    echo "1. Agendamento já foi notificado<br>";
                    echo "2. Status do agendamento não é 1<br>";
                    echo "3. Cartão inativo<br>";
                    echo "4. Problemas no JOIN<br>";
                }
                
            } else {
                echo "<div class='error'>❌ Erro na execução: {$response['message']}</div>";
            }
            
        } else {
            echo "<div class='warning'>⚠️ Resposta não é JSON válido:</div>";
            echo "<div class='code'>" . htmlspecialchars($result) . "</div>";
        }
        
    } else {
        echo "<div class='error'>❌ Falha ao executar o sistema final</div>";
    }
    
    echo "</div>";
    
    // 4. PRÓXIMOS PASSOS
    echo "<h2>⚡ 4. AÇÕES URGENTES</h2>";
    echo "<div class='box'>";
    
    if (isset($idParaTestar) && $idParaTestar) {
        echo "<div class='warning'>🎯 <strong>AGENDAMENTO PENDENTE:</strong> ID {$idParaTestar}</div>";
        echo "<a href='?force_send={$idParaTestar}' class='button'>🔥 Forçar Envio para ID {$idParaTestar}</a><br><br>";
    }
    
    echo "<strong>Se ainda não funcionou:</strong><br>";
    echo "1. 🔍 Verifique se o arquivo <code>send_push_fixed.php</code> existe<br>";
    echo "2. 📱 Confirme se o app tem notificações ativadas<br>";
    echo "3. 🔑 Verifique se as chaves VAPID ainda estão válidas<br>";
    echo "4. 📊 Confira se o número do cartão é o correto no app<br>";
    echo "<br>";
    
    echo "<a href='/app/api/check-agendamentos-notifications' class='button' target='_blank'>
          🔗 Testar via Next.js API
          </a>";
    
    echo "<a href='reset_notifications_for_test.php' class='button'>
          🔄 Reset Flags para Reenvio
          </a>";
    
    echo "</div>";
    
    // 5. FORÇAR ENVIO SE SOLICITADO
    if (isset($_GET['force_send']) && !empty($_GET['force_send'])) {
        $forceId = (int)$_GET['force_send'];
        
        echo "<h2>🔥 5. FORÇAR ENVIO PARA ID {$forceId}</h2>";
        echo "<div class='box'>";
        
        // Resetar flag de notificação
        $resetSql = "UPDATE sind.agendamento SET notification_sent_confirmado = false WHERE id = ?";
        $stmt = $pdo->prepare($resetSql);
        $stmt->execute([$forceId]);
        
        echo "<div class='info'>🔄 Flag resetada para ID {$forceId}</div>";
        
        // Executar sistema novamente
        $result = file_get_contents('https://sas.makecard.com.br/check_agendamentos_notifications_final.php');
        
        if ($result) {
            $response = json_decode($result, true);
            if ($response && $response['success'] && $response['results']['notifications_sent'] > 0) {
                echo "<div class='success'>✅ <strong>FORÇADO COM SUCESSO!</strong> {$response['results']['notifications_sent']} notificação(ões) enviada(s)!</div>";
            } else {
                echo "<div class='error'>❌ Forçar envio falhou</div>";
                echo "<div class='code'>" . json_encode($response, JSON_PRETTY_PRINT) . "</div>";
            }
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='box error'>";
    echo "<h2>❌ ERRO CRÍTICO</h2>";
    echo "Erro: " . $e->getMessage();
    echo "</div>";
}

echo "<div class='box warning'>";
echo "<h3>🗑️ LIMPEZA</h3>";
echo "❌ <strong>DELETE este arquivo após resolver!</strong><br>";
echo "📁 Arquivo: debug_agendamento_urgente.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 