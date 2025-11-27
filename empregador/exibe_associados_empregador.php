<?php
header('Content-Type: application/json; charset=utf-8');
include "../Adm/php/banco.php";

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$response = array();
$response['data'] = array();

try {
    $divisao = isset($_POST['divisao']) ? (int)$_POST['divisao'] : 0;
    $empregador_id = isset($_POST['empregador_id']) ? (int)$_POST['empregador_id'] : 0;

    if ($empregador_id == 0) {
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Buscar associados do empregador
    $sql = "SELECT 
                a.id as id_associado,
                a.id_divisao,
                a.codigo as matricula,
                a.nome,
                a.endereco,
                a.numero,
                a.bairro,
                a.nascimento,
                e.nome as empregador,
                e.abreviacao,
                e.id as codempregador
            FROM sind.associado a
            LEFT JOIN sind.empregador e ON a.empregador = e.id
            WHERE a.empregador = :empregador_id
            ORDER BY a.nome ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':empregador_id', $empregador_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result as $row) {
        $response['data'][] = array(
            'id_associado' => $row['id_associado'],
            'id_divisao' => $row['id_divisao'],
            'matricula' => $row['matricula'],
            'nome' => $row['nome'],
            'endereco' => $row['endereco'] ? $row['endereco'] : '',
            'numero' => $row['numero'] ? $row['numero'] : '',
            'bairro' => $row['bairro'] ? $row['bairro'] : '',
            'nascimento' => $row['nascimento'] ? date('d/m/Y', strtotime($row['nascimento'])) : '',
            'empregador' => $row['empregador'],
            'abreviacao' => $row['abreviacao'],
            'codempregador' => $row['codempregador']
        );
    }

} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
