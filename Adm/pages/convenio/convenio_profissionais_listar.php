<?php
require_once '../../../functions.php';
header('Content-Type: application/json');

include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $query = "SELECT 
        p.id_profissional,
        p.nome_profissional,
        STRING_AGG(CONCAT(e.nome_especialidade, '', COALESCE('', 'Sem tipo'), ''), ', ') AS especialidades,
		te.nome_tipo
    FROM 
        sind.profissionais p
    LEFT JOIN 
        sind.profissionais_especialidade pe ON p.id_profissional = pe.id_profissional
    LEFT JOIN 
        sind.especialidade e ON pe.id_especialidade = e.id_especialidade
    LEFT JOIN 
        sind.tipo_especialidade te ON e.id_tipo_especialidade = te.id_tipo_especialidade
    GROUP BY 
        p.id_profissional, p.nome_profissional, te.nome_tipo
    ORDER BY 
        p.nome_profissional";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $profissionais = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $profissionais[] = array(
            'id' => $row['id_profissional'],
            'nome_profissional' => $row['nome_profissional'],
            'especialidades' => $row['especialidades'],
            'nome_tipo' => $row['nome_tipo']
        );
    }
    
    echo json_encode($profissionais);
    
} catch(PDOException $e) {
    echo json_encode(array('erro' => $e->getMessage()));
}

?> 