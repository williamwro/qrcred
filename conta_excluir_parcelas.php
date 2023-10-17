<?php
header("Content-type: application/json");
require 'Adm/php/banco.php';
date_default_timezone_set('America/Sao_Paulo');
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg='';
$data2          = new DateTime();
$data           = $data2->format('Y-m-d');
$hora           = date('H:i:s');
$lancamento     = 0;
$data_filtrada  = "";
$valor          = 0;
$uuid_filtrado  = "";
$mes            = "";
$empregador     = 0;
$uuid_filtrado  = "";
$nregistros     = count($_POST['data']);
$nome_operador  = $_POST['nome_operador'];
if($nregistros  > 0)
{
    for ($i = 1; $i < count($_POST['data'])+1; $i++)
    {
        $lancamento    = $_POST['data'][$i]['lancamento'];
        $data_filtrada = $_POST['data'][$i]['data'];
        $valor         = $_POST['data'][$i]['valor'];
        $mes           = $_POST['data'][$i]['mes'];
        $empregador    = $_POST['data'][$i]['empregador'];
        $uuid_filtrado = $_POST['data'][$i]['uuid_conta'];

        $sql = "DELETE FROM sind.conta 
                WHERE uuid_conta = :uuid_conta 
                AND lancamento = :lancamento 
                AND data = :data_filtrada 
                AND valor = :valor 
                AND mes = :mes 
                AND empregador = :empregador";
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':uuid_conta', $uuid_filtrado, PDO::PARAM_STR);
        $stmt->bindParam(':lancamento', $lancamento, PDO::PARAM_INT);
        $stmt->bindParam(':data_filtrada', $data_filtrada, PDO::PARAM_STR);
        $stmt->bindParam(':valor', $valor, PDO::PARAM_STR);
        $stmt->bindParam(':mes', $mes, PDO::PARAM_STR);
        $stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);

        $qtde_deletados = $stmt->execute();
        $msg = 'excluido';
        // ATUALIZA DATA ESTORNO
        $sql2 = "UPDATE sind.estornos SET 
                        data_estorno = :data_estorno,
                        hora_estorno = :hora_estorno,
                        descricao = :nome_operador
                WHERE uuid_conta = :uuid_conta
                    AND lancamento = :lancamento";
        $stmt = $pdo->prepare($sql2);
        $stmt->bindParam(':uuid_conta', $uuid_filtrado, PDO::PARAM_STR);
        $stmt->bindParam(':data_estorno', $data, PDO::PARAM_STR);
        $stmt->bindParam(':hora_estorno', $hora, PDO::PARAM_STR);
        $stmt->bindParam(':lancamento', $lancamento, PDO::PARAM_INT);
        $stmt->bindParam(':nome_operador', $nome_operador, PDO::PARAM_STR);

        $qtde_deletados = $stmt->execute();
    }
    $msg = 'excluido';
    $arr = array('Resultado'=>$msg);
    $someArray = array_map("utf8_encode",$arr);

    echo json_encode($someArray);
} else {
    $msg = 'nao excluido';
    $arr = array('Resultado'=>$msg);
    $someArray = array_map("utf8_encode",$arr);

    echo json_encode($someArray);
}
