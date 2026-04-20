<?php
/**
 * API para atualização automática do mês corrente por divisão
 * 
 * Atualiza o campo abreviacao na tabela sind.mes_corrente quando o dia atual
 * corresponde ao dia_mes_renovacao configurado na tabela sind.divisao
 * 
 * Uso: Acessar via URL http://seu-servidor/qrcred/api_atualizar_mes_por_divisao.php
 */

ini_set('display_errors', true);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require 'Adm/php/banco.php';

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtém o dia atual do sistema
    $dia_atual = (int)date('d');
    
    $resultado = array(
        'status' => 'success',
        'dia_atual' => $dia_atual,
        'divisoes_processadas' => array(),
        'divisoes_ignoradas' => array()
    );
    
    // Busca todas as divisões com seus dias de renovação
    $sql_divisoes = "SELECT id_divisao, nome, dia_mes_renovacao 
                     FROM sind.divisao 
                     WHERE dia_mes_renovacao IS NOT NULL
                     ORDER BY id_divisao";
    
    $stmt_divisoes = $pdo->query($sql_divisoes);
    $divisoes = $stmt_divisoes->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($divisoes)) {
        throw new Exception("Nenhuma divisão encontrada com dia_mes_renovacao configurado");
    }
    
    // Processa cada divisão
    foreach ($divisoes as $divisao) {
        $id_divisao = $divisao['id_divisao'];
        $nome_divisao = $divisao['nome'];
        $dia_renovacao = (int)$divisao['dia_mes_renovacao'];
        
        // Verifica se o dia atual corresponde ao dia de renovação
        if ($dia_atual == $dia_renovacao) {
            
            // Busca o mês corrente atual desta divisão
            $sql_mes_atual = "SELECT id, abreviacao 
                             FROM sind.mes_corrente 
                             WHERE id_divisao = :id_divisao";
            
            $stmt_mes = $pdo->prepare($sql_mes_atual);
            $stmt_mes->bindParam(':id_divisao', $id_divisao, PDO::PARAM_INT);
            $stmt_mes->execute();
            $mes_atual = $stmt_mes->fetch(PDO::FETCH_ASSOC);
            
            if (!$mes_atual) {
                $resultado['divisoes_ignoradas'][] = array(
                    'id_divisao' => $id_divisao,
                    'nome' => $nome_divisao,
                    'motivo' => 'Registro não encontrado em mes_corrente'
                );
                continue;
            }
            
            $abreviacao_atual = $mes_atual['abreviacao'];
            
            // Calcula o próximo mês
            $proximo_mes = calcularProximoMes($abreviacao_atual);
            
            if ($proximo_mes === false) {
                $resultado['divisoes_ignoradas'][] = array(
                    'id_divisao' => $id_divisao,
                    'nome' => $nome_divisao,
                    'motivo' => 'Formato de abreviacao inválido: ' . $abreviacao_atual
                );
                continue;
            }
            
            // Atualiza o mês corrente
            $sql_update = "UPDATE sind.mes_corrente 
                          SET abreviacao = :proximo_mes 
                          WHERE id_divisao = :id_divisao";
            
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->bindParam(':proximo_mes', $proximo_mes, PDO::PARAM_STR);
            $stmt_update->bindParam(':id_divisao', $id_divisao, PDO::PARAM_INT);
            $stmt_update->execute();
            
            $resultado['divisoes_processadas'][] = array(
                'id_divisao' => $id_divisao,
                'nome' => $nome_divisao,
                'dia_renovacao' => $dia_renovacao,
                'mes_anterior' => $abreviacao_atual,
                'mes_novo' => $proximo_mes,
                'status' => 'atualizado'
            );
            
        } else {
            $resultado['divisoes_ignoradas'][] = array(
                'id_divisao' => $id_divisao,
                'nome' => $nome_divisao,
                'dia_renovacao' => $dia_renovacao,
                'motivo' => "Dia atual ($dia_atual) diferente do dia de renovação ($dia_renovacao)"
            );
        }
    }
    
    // Define mensagem final
    $total_processadas = count($resultado['divisoes_processadas']);
    $total_ignoradas = count($resultado['divisoes_ignoradas']);
    
    if ($total_processadas > 0) {
        $resultado['message'] = "Atualização concluída: $total_processadas divisão(ões) atualizada(s), $total_ignoradas ignorada(s)";
    } else {
        $resultado['message'] = "Nenhuma divisão foi atualizada. Total de divisões ignoradas: $total_ignoradas";
    }
    
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Erro ao atualizar mês corrente: ' . $e->getMessage(),
        'dia_atual' => date('d')
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * Calcula o próximo mês baseado na abreviação atual
 * 
 * @param string $abreviacao_atual Formato: "MES/ANO" (ex: "DEZ/2025")
 * @return string|false Próximo mês no formato "MES/ANO" ou false se inválido
 */
function calcularProximoMes($abreviacao_atual) {
    // Valida formato
    if (!preg_match('/^([A-Z]{3})\/(\d{4})$/', $abreviacao_atual, $matches)) {
        return false;
    }
    
    $mes_atual = $matches[1];
    $ano_atual = (int)$matches[2];
    
    // Array de meses em ordem
    $meses = array(
        'JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN',
        'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'
    );
    
    // Encontra o índice do mês atual
    $indice_atual = array_search($mes_atual, $meses);
    
    if ($indice_atual === false) {
        return false;
    }
    
    // Calcula o próximo mês
    if ($indice_atual == 11) { // DEZ é o último mês
        $proximo_mes = 'JAN';
        $proximo_ano = $ano_atual + 1;
    } else {
        $proximo_mes = $meses[$indice_atual + 1];
        $proximo_ano = $ano_atual;
    }
    
    return $proximo_mes . '/' . $proximo_ano;
}
?>
