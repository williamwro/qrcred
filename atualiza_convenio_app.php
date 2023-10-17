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
    $codigo = $_POST['codigo'];
}else{
    $codigo = "";
}
if(isset($_POST['nomefantasia'])) {
    $nomefantasia = $_POST['nomefantasia'];
}else{
    $nomefantasia = "";
}
if(isset($_POST['razaosocial'])) {
    $razaosocial = $_POST['razaosocial'];
}else{
    $razaosocial = "";
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
    $cidade = ucfirst(strtolower($_POST['cidade']));
}else{
    $cidade = "";
}
if(isset($_POST['estado'])) {
    $estado = $_POST['estado'];
}else{
    $estado = "";
}
if(isset($_POST['cnpj'])) {
    $cnpj = $_POST['cnpj'];
}else{
    $cnpj = "";
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
if(isset($_POST['tel'])) {
    $tel = $_POST['tel'];
}else{
    $tel = "";
}
if(isset($_POST['cel'])) {
    $cel = $_POST['cel'];
}else{
    $cel = "";
}
if(isset($_POST['contato'])) {
    $contato = $_POST['contato'];
}else{
    $contato = "";
}
if(isset($_POST['id_categoria'])) {
    $id_categoria = $_POST['id_categoria'];
}else{
    $id_categoria = 0;
}
$sql = "UPDATE sind.convenio SET ";
$sql .= "razaosocial = :razaosocial, ";
$sql .= "nomefantasia = :nomefantasia, ";
$sql .= "endereco = :endereco, ";
$sql .= "numero = :numero, ";
$sql .= "bairro = :bairro, ";
$sql .= "cidade = :cidade, ";
$sql .= "uf = :uf, ";
$sql .= "cep = :cep, ";
$sql .= "telefone = :telefone, ";
$sql .= "cel = :cel, ";
$sql .= "contato = :contato, ";
$sql .= "cnpj = :cnpj, ";
$sql .= "cpf = :cpf, ";
$sql .= "email = :email, ";
$sql .= "id_categoria = :id_categoria ";
$sql .= "WHERE codigo = :codigo";

$stmt = $pdo->prepare($sql);

$stmt->bindParam(':codigo', $codigo, PDO::PARAM_INT);
$stmt->bindParam(':razaosocial', $razaosocial, PDO::PARAM_STR);
$stmt->bindParam(':nomefantasia', $nomefantasia, PDO::PARAM_STR);
$stmt->bindParam(':endereco', $endereco, PDO::PARAM_STR);
$stmt->bindParam(':numero', $numero, PDO::PARAM_STR);
$stmt->bindParam(':bairro', $bairro, PDO::PARAM_STR);
$stmt->bindParam(':cidade', $cidade, PDO::PARAM_STR);
$stmt->bindParam(':uf', $estado, PDO::PARAM_STR);
$stmt->bindParam(':cep', $cep, PDO::PARAM_STR);
$stmt->bindParam(':telefone', $tel, PDO::PARAM_STR);
$stmt->bindParam(':cel', $cel, PDO::PARAM_STR);
$stmt->bindParam(':contato', $contato, PDO::PARAM_STR);
$stmt->bindParam(':cnpj', $cnpj, PDO::PARAM_STR);
$stmt->bindParam(':cpf', $cpf, PDO::PARAM_STR);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);

$count = $stmt->execute();

if ($count == 1) {
    echo "gravou";
}else{
    echo "nao gravou";
}