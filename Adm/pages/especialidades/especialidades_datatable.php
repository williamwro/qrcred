<?php
require_once '../../../functions.php';

header('Content-Type: application/json');

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$query = "SELECT e.id_especialidade, e.nome_especialidade, COALESCE(t.nome_tipo, 'Não informado') as nome_tipo 
          FROM sind.especialidade e 
          LEFT JOIN sind.tipo_especialidade t ON e.id_tipo_especialidade = t.id_tipo_especialidade 
          ORDER BY e.nome_especialidade ASC";
$result = $pdo->query($query);

$data = array();

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $sub_array = array();
    $sub_array['id_especialidade'] = $row['id_especialidade'];
    $sub_array['nome_especialidade'] = $row['nome_especialidade'];
    $sub_array['nome_tipo'] = $row['nome_tipo'];
    $sub_array['botao'] = '<button type="button" name="update" id="'.$row['id_especialidade'].'" class="btn btn-warning btn-xs update_especialidade">Update</button>';
    $sub_array['botaoexcluir'] = '<button type="button" name="delete" id="'.$row['id_especialidade'].'" class="btn btn-danger btn-xs delete_especialidade">Delete</button>';
    $data[] = $sub_array;
}

$output = array(
    "data" => $data
);

echo json_encode($output);

?> 