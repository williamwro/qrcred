<?php
/**
 * API para atualizar o mês corrente na tabela sind.mes_corrente
 * 
 * Atualiza o campo abreviacao para o próximo mês seguindo o formato:
 * JAN/2025, FEV/2025, MAR/2025, etc.
 * 
 * Quando chegar em DEZ, incrementa o ano para JAN do próximo ano
 * 
 * Uso: Acesse http://seu-servidor/qrcred/api_atualizar_mes_corrente.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'Adm/php/banco.php';

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar todos os registros da tabela mes_corrente
    $sql_select = "SELECT id, abreviacao, id_divisao, status FROM sind.mes_corrente ORDER BY id";
    $stmt_select = $pdo->prepare($sql_select);
    $stmt_select->execute();
    $registros = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($registros)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Nenhum registro encontrado na tabela sind.mes_corrente'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Array com os meses em ordem
    $meses = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
    
    $registros_atualizados = [];
    
    foreach ($registros as $registro) {
        $abreviacao_atual = $registro['abreviacao'];
        $id = $registro['id'];
        
        // Separar mês e ano (formato: "NOV/2025")
        $partes = explode('/', $abreviacao_atual);
        
        if (count($partes) !== 2) {
            $registros_atualizados[] = [
                'id' => $id,
                'status' => 'error',
                'message' => "Formato inválido: $abreviacao_atual"
            ];
            continue;
        }
        
        $mes_atual = trim($partes[0]);
        $ano_atual = (int)trim($partes[1]);
        
        // Encontrar o índice do mês atual
        $indice_mes_atual = array_search($mes_atual, $meses);
        
        if ($indice_mes_atual === false) {
            $registros_atualizados[] = [
                'id' => $id,
                'status' => 'error',
                'message' => "Mês inválido: $mes_atual"
            ];
            continue;
        }
        
        // Calcular o próximo mês
        $indice_proximo_mes = $indice_mes_atual + 1;
        
        // Se passou de dezembro, volta para janeiro e incrementa o ano
        if ($indice_proximo_mes >= 12) {
            $indice_proximo_mes = 0;
            $ano_atual++;
        }
        
        $proximo_mes = $meses[$indice_proximo_mes];
        $nova_abreviacao = "$proximo_mes/$ano_atual";
        
        // Atualizar o registro
        $sql_update = "UPDATE sind.mes_corrente SET abreviacao = :nova_abreviacao WHERE id = :id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindParam(':nova_abreviacao', $nova_abreviacao, PDO::PARAM_STR);
        $stmt_update->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_update->execute();
        
        $registros_atualizados[] = [
            'id' => $id,
            'id_divisao' => $registro['id_divisao'],
            'status' => 'success',
            'abreviacao_anterior' => $abreviacao_atual,
            'abreviacao_nova' => $nova_abreviacao
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Mês corrente atualizado com sucesso',
        'total_registros' => count($registros_atualizados),
        'registros' => $registros_atualizados
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao atualizar mês corrente',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
