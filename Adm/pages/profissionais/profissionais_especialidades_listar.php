<?php

header('Content-Type: application/json');

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["id_profissional"])) {
    try {
        $query = "SELECT pe.id_especialidade, e.nome_especialidade 
                  FROM sind.profissionais_especialidade pe
                  INNER JOIN sind.especialidade e ON pe.id_especialidade = e.id_especialidade
                  WHERE pe.id_profissional = ?
                  ORDER BY e.nome_especialidade ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_POST["id_profissional"]]);
        
        $especialidades = array();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $especialidades[] = array(
                'id_especialidade' => $row['id_especialidade'],
                'nome_especialidade' => $row['nome_especialidade']
            );
        }
        
        echo json_encode($especialidades);
        
    } catch(PDOException $e) {
        echo json_encode(["erro" => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}

?> 