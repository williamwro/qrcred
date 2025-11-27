<?php

header('Content-Type: application/json');

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$query = "SELECT DISTINCT ON (UPPER(TRIM(p.nome_profissional))) 
                 p.id_profissional, p.nome_profissional, p.contato_nome1, p.cel_telefone1, p.contato_nome2, p.cel_telefone2
          FROM sind.profissionais p
          ORDER BY UPPER(TRIM(p.nome_profissional)) ASC, p.id_profissional ASC";
$result = $pdo->query($query);

$data = array();

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $sub_array = array();
    $sub_array['id_profissional'] = $row['id_profissional'];
    $sub_array['nome_profissional'] = $row['nome_profissional'];
    $sub_array['botao'] = '<button type="button" name="update" id="'.$row['id_profissional'].'" class="btn btn-warning btn-xs update_profissional">Update</button>';
    $sub_array['botaoexcluir'] = '<button type="button" name="delete" id="'.$row['id_profissional'].'" class="btn btn-danger btn-xs delete_profissional">Delete</button>';
    $data[] = $sub_array;
}

$output = array(
    "data" => $data
);

echo json_encode($output);

?> 