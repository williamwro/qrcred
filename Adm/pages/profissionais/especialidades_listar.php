<?php

header('Content-Type: application/json');

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $query = "SELECT id_especialidade, nome_especialidade FROM sind.especialidade ORDER BY nome_especialidade ASC";
    $result = $pdo->query($query);
    
    $especialidades = array();
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $especialidades[] = array(
            'id_especialidade' => $row['id_especialidade'],
            'nome_especialidade' => $row['nome_especialidade']
        );
    }
    
    echo json_encode($especialidades);
    
} catch(PDOException $e) {
    echo json_encode(["erro" => $e->getMessage()]);
}

?> 