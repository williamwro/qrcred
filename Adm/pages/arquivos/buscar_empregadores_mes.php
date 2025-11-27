<?php
header("Content-type: application/json; charset=utf-8");
error_reporting(E_ALL ^ E_NOTICE);

include "../../php/banco.php";
include "../../php/funcoes.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'UTF8'");

    $mes_atual = $_POST['mes_atual'] ?? '';
    $divisao = $_POST['divisao'] ?? '';
    $tipo = $_POST['tipo'] ?? null;

    if (empty($mes_atual) || empty($divisao)) {
        echo json_encode(['error' => 'Parâmetros obrigatórios não fornecidos']);
        exit;
    }

    // Buscar todos os empregadores que têm dados no mês selecionado
    $sql = "SELECT DISTINCT e.id, e.nome 
            FROM sind.empregador e
            INNER JOIN sind.qrelatoriofinal q ON q.empregador = e.id
            WHERE e.divisao = :divisao 
            AND (q.aprovado = true OR q.aprovado IS NULL)
            AND q.mes = :mes_atual";

    $params = [
        ':divisao' => $divisao,
        ':mes_atual' => $mes_atual
    ];

    if (!empty($tipo)) {
        $sql .= " AND q.tipoconvenio = :tipo";
        $params[':tipo'] = $tipo;
    }

    $sql .= " ORDER BY e.nome";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $empregadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Processar encoding dos nomes
    foreach ($empregadores as &$empregador) {
        if (isset($empregador['nome'])) {
            $encoding = mb_detect_encoding($empregador['nome'], ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $empregador['nome'] = mb_convert_encoding($empregador['nome'], 'UTF-8', $encoding);
            }
            if (!mb_check_encoding($empregador['nome'], 'UTF-8')) {
                $empregador['nome'] = mb_convert_encoding($empregador['nome'], 'UTF-8', 'ISO-8859-1');
            }
        }
    }

    echo json_encode($empregadores, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    error_log("Erro em buscar_empregadores_mes.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>
