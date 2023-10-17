<?php
require '../../php/banco.php';
$stmtx = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
date_default_timezone_set('America/Sao_Paulo');
$sqlx = "";
$msg_grava_cad="";
$id = $_POST['id'];
$controle = $_POST['controle'];
$data2      = new DateTime();
$data       = $data2->format('Y-m-d');
$sqlx = "UPDATE sind.cobranca SET pago = :controle, data_pgto = :data_pgto WHERE id = :id";

try {
    $stmtx = $pdo->prepare($sqlx);
    $stmtx->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtx->bindParam(':controle', $controle, PDO::PARAM_STR);
    $stmtx->bindParam(':data_pgto', $data, PDO::PARAM_STR);
    $stmtx->execute();
    $msggrava = "atualizado";
    $arr = array('resultado'=>$msggrava);

} catch (PDOException $erro) {
    //echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    $msggrava = "nao atualizado";
    $arr = array('resultado'=>$msggrava);
}
$someArray = array_map("utf8_encode",$arr);
echo json_encode($someArray);