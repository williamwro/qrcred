<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 23/08/2018
 * Time: 14:02
 */
include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST['codigo'])) {
    $matricula = $_POST['codigo'];
}else{
    $matricula = "";
}
if(isset($_POST['empregador'])) {
    $empregador = $_POST['empregador'];
}else{
    $empregador = "";
}
if(isset($_POST['cel'])) {
    $cel = $_POST['cel'];
}else{
    $cel = "";
}
if(isset($_POST['cpf'])) {
    $cpf = $_POST['cpf'];
}else{
    $cpf = "";
}
if(isset($_POST['email'])) {
    $email = $_POST['email'];
}else{
    $email = "";
}
if(isset($_POST['cep'])) {
    $cep = $_POST['cep'];
}else{
    $cep = "";
}
if(isset($_POST['endereco'])) {
    $endereco = $_POST['endereco'];
}else{
    $endereco = "";
}
if(isset($_POST['numero'])) {
    $numero = $_POST['numero'];
}else{
    $numero = "";
}
if(isset($_POST['bairro'])) {
    $bairro = $_POST['bairro'];
}else{
    $bairro = "";
}
if(isset($_POST['cidade'])) {
    $cidade = $_POST['cidade'];
}else{
    $cidade = "";
}
if(isset($_POST['estado'])) {
    $estado = $_POST['estado'];
}else{
    $estado = "";
}
if(isset($_POST['celzap'])) {
    if ($_POST['celzap'] === "true") {
        $celzap = "true";
    }else{
        $celzap = "false";
    }
}else{
    $celzap = "";
}
$sql = "UPDATE sind.associado SET 
               email = :email,
               cel = :cel,
               cpf = :cpf,
               cep = :cep, 
               endereco = :endereco,
               numero = :numero,
               bairro = :bairro,
               cidade = :cidade,
               uf = :estado,
               celwatzap = :celzap
               WHERE codigo = :codigo AND empregador = :empregador";
$stmt = $pdo->prepare($sql);

$stmt->bindParam(':codigo', $matricula, PDO::PARAM_STR);
$stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->bindParam(':cel', $cel, PDO::PARAM_STR);
$stmt->bindParam(':cpf', $cpf, PDO::PARAM_STR);
$stmt->bindParam(':cep', $cep, PDO::PARAM_STR);
$stmt->bindParam(':endereco', $endereco, PDO::PARAM_STR);
$stmt->bindParam(':numero', $numero, PDO::PARAM_STR);
$stmt->bindParam(':bairro', $bairro, PDO::PARAM_STR);
$stmt->bindParam(':cidade', $cidade, PDO::PARAM_STR);
$stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
$stmt->bindParam(':celzap', $celzap, PDO::PARAM_STR);
$count = $stmt->execute();
if ($count == 1) {
	echo "gravou";
}else{
	echo "nao gravou";
}
