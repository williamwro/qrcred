<?php
require '../../php/banco.php';
$stmtx = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlx = "";
$msg_grava_cad="";
$id = $_POST['id'];
$controle = $_POST['controle'];

$sqlx = "UPDATE sind.cobranca SET enviado = :controle WHERE id = :id";
$msggrava = "atualizado";
    try {
        $stmtx = $pdo->prepare($sqlx);
        $stmtx->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtx->bindParam(':controle', $controle, PDO::PARAM_STR);
        $stmtx->execute();

        $arr = array('resultado'=>$msggrava);
        $someArray = array_map(function($value) {
    return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
}, $arr);
        echo json_encode($someArray);
    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
}