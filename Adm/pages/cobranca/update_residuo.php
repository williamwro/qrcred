<?php
require '../../php/banco.php';
$_id = $_POST['id'];
$_valor = $_POST['valor'];
$valor_porcentagem = $_POST['valor_porcentagem'];
$acrescimo         = $_POST['acrescimo'];
$total = $_valor + $valor_porcentagem + $acrescimo;
$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad="";


$sql = "UPDATE sind.cobranca SET ";
$sql .= "residuo = :valor, ";
$sql .= "val_cob = :total_cobranca ";
$sql .= "WHERE id = :id";
$msg_grava_cad = "atualizado";

try {

    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':id', $_id, PDO::PARAM_INT);
    $stmt->bindParam(':valor', $_valor, PDO::PARAM_STR);
    $stmt->bindParam(':total_cobranca', $total, PDO::PARAM_STR);

    $stmt->execute();

    $arr = array('resultado'=>$msg_grava_cad);
    $someArray = array_map("utf8_encode",$arr);
    echo json_encode($someArray);
} catch (PDOException $erro) {
    echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
}