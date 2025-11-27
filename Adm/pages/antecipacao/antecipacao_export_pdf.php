<?php
session_start();
header("Content-type: application/json; charset=utf-8");
include "../../php/banco.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $situacao = $_POST["situacao"] ?? "null";
    $divisao = $_SESSION["divisao"] ?? 1;
    
    // Construir query baseada na situação selecionada (usando estrutura correta da tabela)
    $sql = "SELECT DISTINCT
                ant.id,
                ant.matricula,
                (SELECT a.nome 
                FROM sind.associado a 
                WHERE a.codigo = ant.matricula AND a.empregador = ant.empregador
                LIMIT 1) AS nome,
                ant.empregador AS id_empregador,
                emp.nome AS nome_empregador,
                ant.mes,
                ant.data_solicitacao,
                ant.valor,
                ant.valor_taxa,
                ant.valor_a_descontar,
                ant.aprovado,
                ant.data_aprovacao AS data_conclusao,
                ant.celular,
                ant.chave_pix
            FROM sind.antecipacao ant
            JOIN sind.empregador emp ON emp.id = ant.empregador
            WHERE emp.divisao = :divisao";
    
    // Adicionar filtro baseado na situação
    if ($situacao === "true") {
        $sql .= " AND ant.aprovado = true";
    } elseif ($situacao === "false") {
        $sql .= " AND ant.aprovado = false";
    } elseif ($situacao === "null") {
        $sql .= " AND ant.aprovado IS NULL";
    }
    // Se situacao == "0" (Todos), não adiciona filtro
    
    $sql .= " ORDER BY ant.data_solicitacao DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    $stmt->execute();
    
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatar dados para o PDF
    $dadosFormatados = [];
    foreach ($dados as $linha) {
        $dadosFormatados[] = [
            'matricula' => $linha['matricula'],
            'nome' => $linha['nome'],
            'id_empregador' => $linha['id_empregador'],
            'nome_empregador' => $linha['nome_empregador'],
            'mes' => $linha['mes'],
            'data_solicitacao' => $linha['data_solicitacao'] ? date('d/m/Y', strtotime($linha['data_solicitacao'])) : '',
            'valor' => $linha['valor'] ? 'R$ ' . number_format($linha['valor'], 2, ',', '.') : '',
            'valor_taxa' => $linha['valor_taxa'] ? 'R$ ' . number_format($linha['valor_taxa'], 2, ',', '.') : '',
            'valor_a_descontar' => $linha['valor_a_descontar'] ? 'R$ ' . number_format($linha['valor_a_descontar'], 2, ',', '.') : '',
            'aprovado' => $linha['aprovado'],
            'data_conclusao' => $linha['data_conclusao'] ? date('d/m/Y', strtotime($linha['data_conclusao'])) : '',
            'celular' => $linha['celular'],
            'chave_pix' => $linha['chave_pix']
        ];
    }
    
    echo json_encode($dadosFormatados, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao buscar dados: ' . $e->getMessage(),
        'debug_info' => [
            'situacao' => $situacao ?? 'não definido',
            'divisao' => $divisao ?? 'não definido',
            'sql' => $sql ?? 'query não definida'
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
