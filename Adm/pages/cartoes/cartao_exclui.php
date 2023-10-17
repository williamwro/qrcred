<?php
header("Content-type: application/json");
require '../../php/banco.php';
$msg='';
if (isset($_POST['cartao'])) {
    $stmt = new stdClass();
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $_cartao = $_POST['cartao'];

    $sql = "DELETE FROM sind.c_cartaoassociado WHERE cod_verificacao = :cartao";

    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':cartao', $_cartao, PDO::PARAM_STR);

    $stmt->execute();

    $msg = 'excluido';

    $arr = array('cartao' => $_cartao, 'resultado' => $msg);
    $someArray = array_map("utf8_encode", $arr);
}
echo json_encode($someArray);





