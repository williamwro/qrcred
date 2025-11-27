<?php
require_once '../../../functions.php';
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Query to get divisions that have convenios registered
    $query = "
        SELECT DISTINCT d.id_divisao, d.nome, d.cidade, d.descricao
        FROM sind.divisao d
        INNER JOIN sind.convenio c ON c.divisao = d.id_divisao
        ORDER BY d.nome
    ";
    
    $statement = $pdo->prepare($query);
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
