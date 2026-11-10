<?php
header('Content-Type: application/json; charset=utf-8');
include "../../php/banco.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $mes_atual = isset($_POST['mes']) ? $_POST['mes'] : (isset($_POST['mes_atual']) ? $_POST['mes_atual'] : '');
    $divisao = isset($_POST['divisao']) ? $_POST['divisao'] : '';
    $parcela = isset($_POST['parcela']) ? $_POST['parcela'] : '';

    if ($mes_atual === '' || $divisao === '') {
        echo json_encode(array('data' => array(), 'total' => 0, 'error' => 'Parâmetros obrigatórios não fornecidos'), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "SELECT DISTINCT
                c.codigo,
                c.razaosocial,
                c.nomefantasia
            FROM sind.convenio c
            INNER JOIN sind.conta ct ON c.codigo = ct.convenio
            INNER JOIN sind.empregador e ON ct.empregador = e.id
            WHERE ct.mes = :mes_atual
              AND e.id_divisao = :divisao
              AND (ct.aprovado = true OR ct.aprovado IS NULL)";

    if ($parcela !== '') {
        $sql .= " AND LEFT(ct.parcela, 2) = :parcela";
    }

    $sql .= " ORDER BY c.razaosocial ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':mes_atual', $mes_atual, PDO::PARAM_STR);
    $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    if ($parcela !== '') {
        $stmt->bindParam(':parcela', $parcela, PDO::PARAM_STR);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(array('data' => $rows, 'total' => count($rows)), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('data' => array(), 'total' => 0, 'error' => $e->getMessage()), JSON_UNESCAPED_UNICODE);
}
