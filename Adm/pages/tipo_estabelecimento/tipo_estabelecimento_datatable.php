<?php

header('Content-Type: application/json');

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$query = "SELECT id_tipo_estabelecimento, nome_tipo FROM sind.tipo_estabelecimento ORDER BY nome_tipo ASC";
$result = $pdo->query($query);

$data = array();

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $sub_array = array();
    $sub_array['id'] = $row['id_tipo_estabelecimento'];
    $sub_array['nome_tipo'] = $row['nome_tipo'];
    $sub_array['botao'] = '<button type="button" name="update" id="'.$row['id_tipo_estabelecimento'].'" class="btn btn-warning btn-xs update_tipo_estabelecimento">Update</button>';
    $sub_array['botaoexcluir'] = '<button type="button" name="delete" id="'.$row['id_tipo_estabelecimento'].'" class="btn btn-danger btn-xs delete_tipo_estabelecimento">Delete</button>';
    $data[] = $sub_array;
}

$output = array(
    "data" => $data
);

echo json_encode($output);

?> 