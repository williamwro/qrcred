<?php
require '../../php/banco.php';
$stmtx = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
date_default_timezone_set('America/Sao_Paulo');
$sqlx = "";
$msg_grava_cad="";
$codconvenio = $_POST['codconvenio'];
$controle = $_POST['controle'];
if ($controle == "true"){
    $data2 = new DateTime();
    $data  = $data2->format('Y-m-d');
}else{
    $data = null;
}

$sqlx = "UPDATE sind.convenio SET desativado = :controle WHERE codigo = :codconvenio";

try {
    $stmtx = $pdo->prepare($sqlx);
    $stmtx->bindParam(':codconvenio', $codconvenio, PDO::PARAM_INT);
    $stmtx->bindParam(':controle', $controle, PDO::PARAM_STR);
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