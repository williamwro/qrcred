<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
/**
 * Created by PhpStorm.
 * User: William
 * Date: 29/06/2018
 * Time: 16:17
 */

header("Content-type: application/json");
require 'Adm/php/banco.php';
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$lancamento     = $_POST["lancamento"];
$matricula      = $_POST["matricula"];
$valor          = $_POST["valor"];
$data           = $_POST["data"];
$hora           = $_POST["hora"];
$cod_empregador = $_POST["cod_empregador"];
$codigo_convenio   = $_POST["cod_convenio"];
$total_parcelas  = 0;

$sub_array = array();
$someArray = array();
$std = new stdClass();
$sql = $pdo->query("SELECT * FROM sind.conta WHERE lancamento = ".$lancamento);
while ($row_registro = $sql->fetch()) {

    $registrolan        = $row_registro['lancamento'];
    $std->situacao      = 1; /*1 - sucesso*/
    $std->registrolan   = $registrolan;
    $std->matricula     = $row_registro['associado'];
    $matricula          = $row_registro['associado'];
    $parcelas           = $row_registro['parcela'];
    $empregador         = $row_registro['empregador'];
    $std->valorpedido   = $row_registro['valor'];
    $valor              = $row_registro['valor'];
    if($parcelas != null && $parcelas != ""){
        $parcelas       = substr($parcelas, -2);
    }else{
        $parcelas       = 0;
    }
    $std->parcelas      = (int)$parcelas;
    $mes_ponteiro = $row_registro['parcela'];
    if($mes_ponteiro != null && $mes_ponteiro != ""){
        $mes_ponteiro   = explode("/",$mes_ponteiro);
        $mes_ponteiro   = (int)$mes_ponteiro[0];
    }else{
        $mes_ponteiro   = 0;
    }
    $std->mes_ponteiro  = $mes_ponteiro;
    $data               = $row_registro['data'];
    $hora               = $row_registro['hora'];
    //$data2              = new DateTime($data1);
    //$data               = $data2->format('d/m/Y');
    $std->mes_seq       = $row_registro['mes'];
    $std->datacad       = $data;
    $std->hora          = $hora;
    $codigo_convenio    = $row_registro['convenio'];
}
$sql = $pdo->query("SELECT * FROM sind.convenio WHERE codigo = ".$codigo_convenio);
while ($row_convenio = $sql->fetch()) {
    $std->razaosocial   = $row_convenio['razaosocial'];
    $std->nomefantasia  = $row_convenio['nomefantasia'];
    $std->endereco      = $row_convenio['endereco'];
    $std->bairro        = $row_convenio['bairro'];
    $std->cod_convenio  = $codigo_convenio;
    $std->cnpj          = $row_convenio['cnpj'];
    $std->cidade        = $row_convenio['cidade'];
}
$sql = $pdo->query("SELECT codigo, nome, empregador FROM sind.associado WHERE codigo = '" . $matricula . "' and empregador = ".$empregador);
while ($row_associado = $sql->fetch()) {
    $std->nome          = $row_associado['nome'];
}
$sql = $pdo->query("SELECT cod_verificacao, empregador FROM sind.c_cartaoassociado WHERE cod_associado = '" . $matricula . "' and empregador = ".$empregador);
while ($row_cartao = $sql->fetch()) {
    $std->codcarteira   = $row_cartao['cod_verificacao'];
}
$as = 1;
if($parcelas > 0) {
    $sql = $pdo->query("SELECT * FROM sind.conta WHERE associado = '".$matricula."' AND data = '".$data."' AND hora = '".$hora."' AND convenio = ".$codigo_convenio."  AND empregador = ".$cod_empregador. "ORDER BY lancamento");
    while ($row_parcelas = $sql->fetch()) {
        $std->$as = new stdClass();
        $std->$as->numero = $as;
        $std->$as->valor_parcela = $row_parcelas['valor'];
        $std->$as->registrolan = $row_parcelas['lancamento'];
        $std->$as->mes_seq = $row_parcelas['mes'];
        $total_parcelas = $total_parcelas + $row_parcelas['valor'];
        $as++;
    }
    $std->total = $total_parcelas;
}
$resultado = json_encode($std);
echo $resultado;