<?php
require_once '../../../functions.php';
header('Content-Type: application/json');

include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $query = "SELECT id_especialidade, nome_especialidade FROM sind.especialidade ORDER BY nome_especialidade ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $especialidades = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $especialidades[] = array(
            'id' => $row['id_especialidade'],
            'nome_especialidade' => $row['nome_especialidade']
        );
    }
    
    echo json_encode($especialidades);
    
} catch(PDOException $e) {
    echo json_encode(array('erro' => $e->getMessage()));
}

?> 