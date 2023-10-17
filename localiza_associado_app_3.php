<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 24/06/2019
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
if(isset($_POST['empregador'])) {
    $empregador = $_POST['empregador'];
}else{
    $empregador = "";
}
$std = new stdClass();

$contador=0;

$row_assoc = $pdo->query("SELECT associado.codigo,associado.nome,
                                    associado.empregador,associado.limite,
                                    associado.salario,associado.parcelas_permitidas,
                                    associado.endereco,associado.numero, 
                                    associado.cpf,associado.email,
                                    associado.cel,associado.cep,
                                    associado.bairro,associado.cidade,
                                    associado.uf,associado.celwatzap,
                                    c_cartaoassociado.cod_situacaocartao,
                                    c_cartaoassociado.cod_verificacao,associado.email,
                                    associado.cel,associado.cpf
                               FROM sind.associado 
                         INNER JOIN sind.c_cartaoassociado 
                                 ON associado.codigo = c_cartaoassociado.cod_associado 
                              WHERE associado.codigo='".$codigo."' AND associado.empregador=".$empregador)->fetch();
if ($row_assoc) {
	
	$std->nome = $row_assoc['nome'];
	$std->cod_cart = $row_assoc['cod_verificacao'];
	$std->matricula = $row_assoc['codigo'];
	$std->empregador = $row_assoc['empregador'];
	$std->parcelas_permitidas = $row_assoc["parcelas_permitidas"];
	$std->limite = number_format(($row_assoc["limite"]), 2, '.', '');
	$std->email = $row_assoc["email"];
	$std->cpf = $row_assoc["cpf"];
	$std->cel = $row_assoc["cel"];
    $std->endereco = $row_assoc["endereco"];
    $std->numero = $row_assoc["numero"];
    $std->bairro = $row_assoc["bairro"];
    $std->cep = $row_assoc["cep"];
    $std->cidade = $row_assoc["cidade"];
    $std->uf = $row_assoc["uf"];
    $std->celwatzap = $row_assoc["celwatzap"];
}
echo json_encode($std);