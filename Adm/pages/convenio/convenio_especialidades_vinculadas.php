<?php
require_once '../../../functions.php';
header('Content-Type: application/json');

include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["cod_convenio"])) {
    try {
        $query = "SELECT ce.id, ce.cod_profissional, p.nome_profissional, e.nome_especialidade, te.nome_tipo
                  FROM sind.convenio_especialidades ce 
                  INNER JOIN sind.profissionais p ON ce.cod_profissional = p.id_profissional
                  LEFT JOIN sind.profissionais_especialidade pe ON p.id_profissional = pe.id_profissional
                  LEFT JOIN sind.especialidade e ON pe.id_especialidade = e.id_especialidade
                  LEFT JOIN sind.tipo_especialidade te ON e.id_tipo_especialidade = te.id_tipo_especialidade
                  WHERE ce.cod_convenio = ? 
                  ORDER BY p.nome_profissional ASC, e.nome_especialidade ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_POST["cod_convenio"]]);
        
        $especialidades = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $especialidades[] = array(
                'id' => $row['id'],
                'cod_profissional' => $row['cod_profissional'],
                'nome_profissional' => $row['nome_profissional'],
                'nome_especialidade' => $row['nome_especialidade'],
                'tipo_especialidade' => $row['nome_tipo']
            );
        }
        
        echo json_encode($especialidades);
        
    } catch(PDOException $e) {
        echo json_encode(array('erro' => $e->getMessage()));
    }
}

?> 