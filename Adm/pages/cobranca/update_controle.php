<?php
require '../../php/banco.php';
$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad="";
$razaosocial = $_POST['razaosocial'];
$codigo = $_POST['codigo'];
$controle = $_POST['controle'];

    $sql = "UPDATE sind.convenio SET ";
    $sql .= "controle = :controle ";
    $sql .= "WHERE codigo = :codigo ";
    $sql .= "AND razaosocial = :razaosocial";

    $msg_grava_cad = "atualizado";

    try {

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':codigo', $codigo, PDO::PARAM_INT);
        $stmt->bindParam(':razaosocial', $razaosocial, PDO::PARAM_STR);
        $stmt->bindParam(':controle', $controle, PDO::PARAM_STR);

        $stmt->execute();

        $arr = array('resultado'=>$msg_grava_cad);
        $someArray = array_map(function($value) {
    return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
}, $arr);
        echo json_encode($someArray);
    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
}