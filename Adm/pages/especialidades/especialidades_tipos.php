<?php
require_once '../../../functions.php';

header('Content-Type: application/json');

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $query = "SELECT id_tipo_especialidade, nome_tipo FROM sind.tipo_especialidade ORDER BY nome_tipo ASC";
    $result = $pdo->query($query);
    
    $data = array();
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $data[] = array(
            'id_tipo_especialidade' => $row['id_tipo_especialidade'],
            'nome_tipo' => $row['nome_tipo']
        );
    }
    
    echo json_encode($data);
    
} catch(PDOException $e) {
    echo json_encode(["erro" => $e->getMessage()]);
}

?> 