<?php
header('Content-Type: application/json; charset=utf-8');

require 'Adm/php/banco.php';
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cpf = isset($_GET['cpf']) ? preg_replace('/\D/', '', $_GET['cpf']) : '';

$response = new stdClass();
$response->success = false;
$response->data = null;

if ($cpf != '') {
    $sql = "SELECT id, codigo, nome, celular, data_hora, autorizado, aceitou_termo, event, doc_token, doc_name, signed_at, name, email, cpf, has_signed, cel_informado, limite, valor_aprovado, data_pgto, chave_pix, reprovado
            FROM sind.associados_sasmais
            WHERE cpf = :cpf";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':cpf', $cpf);
    $stmt->execute();
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dados) {
        $response->success = true;
        $response->data = $dados;
    }
}

echo json_encode($response);