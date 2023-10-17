<!DOCTYPE HTML>
<html lang="pt-br">
<head>
<TITLE>::MAKECARD::</TITLE>

<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<link href="../style.css" rel="stylesheet" type="text/css"><?PHP
include "../Adm/php/banco.php";
include "../Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$mes_atual = "";
$n_cartao = "";
$n_senha = "";
if (isset($_POST['autorizado'])){
    if (isset($_POST['total'])) {
        $total = $_POST['total'];
    } else {
        $total = 0;
    }
    if (isset($_POST['cod_cart'])) {
        $txtCartao = $_POST['cod_cart'];
    } else {
        if (isset($_POST['txtCartao'])) {
            $txtCartao = $_POST['txtCartao'];
        }else{
            $txtCartao = 0;
        }

    }
    if (isset($_POST['senhacartao'])) {
        $txtSenhaCartao = $_POST['senhacartao'];
    } else {
        if (isset($_POST['txtSenhaCartao'])){
            $txtSenhaCartao = $_POST['txtSenhaCartao'];
        }else{
            $txtSenhaCartao = 0;
        }
    }
    if($txtSenhaCartao != 0){

        $dia = date("d");
        $dia = intval($dia);
        $m = 1;
        if ($dia >= 4) {
            $mes_atual = somames(date("m/Y"), $m + 1);
        } else if ($dia >= 1 && $dia <= 3) {
            $mes_atual = somames(date("m/Y"), $m);
        }
        //if (isset($_POST['cod_cart'])) {
        //    $n_cartao = $_POST['cod_cart'];
        //}
        $sql_associadox = $pdo->query("SELECT associado.codigo, associado.nome, associado.empregador, 
                                                      associado.limite, associado.salario, 
                                                      c_cartaoassociado.cod_situacaocartao, 
                                                      c_cartaoassociado.cod_verificacao 
                                                      FROM sind.associado 
                                                      INNER JOIN sind.c_cartaoassociado 
                                                      ON associado.codigo = c_cartaoassociado.cod_associado 
                                                      AND associado.empregador = c_cartaoassociado.empregador
                                                      WHERE ((c_cartaoassociado.cod_verificacao)='$txtCartao')");
        while ($row_assoc = $sql_associadox->fetch()) {
            $Codigo = $row_assoc['codigo'];
            $nome = $row_assoc['nome'];
            $Empregador = $row_assoc['empregador'];
            $Limite = $row_assoc['limite'];
            $Salario = $row_assoc['salario'];
            $cod_situacaocartao = $row_assoc['cod_situacaocartao'];
            $n_associado = $row_assoc['codigo'];
        }
        $sql_pede_senhax = $pdo->query("SELECT * FROM sind.c_senhaassociado WHERE cod_associado = '" . $Codigo . "' and id_empregador=".$Empregador);
        while ($sql_associado_senha = $sql_pede_senhax->fetch()) {
            $n_senha = $sql_associado_senha['senha'];
            //$txtSenhaCartao = $sql_associado_senha['senha'];
        }
        if($txtSenhaCartao == $n_senha){


            if ($cod_situacaocartao == "1" or $cod_situacaocartao == "5" or $cod_situacaocartao == "6" or $cod_situacaocartao == "7" or $cod_situacaocartao == "4" or $Codigo == "172561" or $Codigo == "270435") {
                $bloqueado_uso = 'NAO';
            } else {
                //$bloqueado_uso = 'SIM'; //ESTAVA FUNCIONADO ASSIM BLOQUADO PARA QUEM TEM CARTAO BLOQUEADO
                $bloqueado_uso = 'SIM';
            }
            $sql_senha_associadox = $pdo->query("SELECT cod_associado, senha FROM sind.c_senhaassociado WHERE cod_associado = '$n_associado' AND senha = '$txtSenhaCartao'");
            while ($sql_senha_associado = $sql_senha_associadox->fetch()) {

                if ($total == 0) {
                    $total = 0;
                }
            }
            ?>
            </head>
            <body>
            <table width="500" class="conteudorel" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#BCBCBC">
                <form name="listagem" method="post"
                      action="?leo=x&txtCartao=<?php echo $txtCartao ?>&mes_atual=<?php echo $mes_atual ?>&txtSenhaCartao=<?php echo $txtSenhaCartao ?>&total=<?php echo $total ?>&ct=<?php echo $txtCartao ?>">
                    <input type="hidden" id="txtCartao" name="txtCartao" value="<?php echo $txtCartao ?>"/>
                    <input type="hidden" id="txtSenhaCartao" name="txtSenhaCartao" value="<?php echo $txtSenhaCartao ?>"/>
                    <input type="hidden" id="autorizado" name="autorizado" value="sim"/>
                    <tr>
                        <td colspan="6" bordercolor="E9E9E9"><p align="center"
                                                                style="font-family: Verdana, arial; font-size:14px; font-weight:bold;">
                                EXTRATO SIMPLIFICADO DO CARTAO MAKECARD <br/>
                            </p>
                            <p align="left">
                            <div align="right"><?php echo date("d/m/y") . "  -  " . date("h:m"); ?></div>
                            <br>
                            <table>
                                <tr>
                                    <td align="right">
                                        <span class="titulo_campo">associado:</span>
                                    </td>
                                    <td colspan="4">
                                        <?php echo $nome; ?>
                                </tr>
                                </tr>
                                <tr>
                                    <td align="right">
                                        <span class="titulo_campo"> Numero cartao : </span>
                                    </td>
                                    <td colspan="4">
                                        <?php echo $txtCartao ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right">
                                        <span class="titulo_campo"> Situacao : </span>
                                    </td>
                                    <td colspan="4">
                                        <?php if ($bloqueado_uso == "NAO") {
                                            echo "Normal";
                                        } else {
                                            echo "<span style='color:red;'>BLOQUEADO</span>";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right">
                                        <span class="titulo_campo">Mes de Desconto:</span>
                                    </td>
                                    <td colspan="4">
                                        <select name="mes_atual" class="campo" id="mes_atual" onchange="submit()">
                                            <?php
                                            $res = explode("/", $mes_atual);
                                            $res[0] = intval($res[0]) - 2;            //inicia com dois meses anteriores ao mes atual
                                            if ($res[0] == 0) {    // se o mes for fevereiro = 2 (-2) = 0 portanto 0 corresponde a dezembro = 12
                                                $res[0] = 12;       // $res[0] recebe 12 o mes de dezembro
                                                $res[1]--;        // volta um ano
                                            } elseif ($res[0] < 0) { // se o mes $res igual a 1 (1 - 2 =  -1) corresponde ao mes de novembro
                                                $res[0] = 11;       // $res[0] recebe 11 o mes de novembro
                                                $res[1]--;        // volta um ano
                                            }
                                            $mes_inicial = implode("/", $res);

                                            for ($m = 0; $m <= 20; $m++) {
                                                if ($m > 0) {      //somando os meses
                                                    $res[0]++;
                                                }
                                                if ($res[0] > 12) { // se chegar em dezembro inicia em janeiro = 01
                                                    $res[0] = 1;
                                                    $res[1]++;    // acrescenta um ano
                                                }
                                                if ($res[0] < 10) {
                                                    $caracter = "0$res[0]"; //formata mes com dois caracters
                                                } else {
                                                    $caracter = $res[0];
                                                }
                                                $res[0] = $caracter;
                                                $mes_seq = implode("/", $res);
                                                $c = '';
                                                $mes_seq = somames($mes_seq, 0);
                                                if (isset($_POST['mes_atual'])) {
                                                    $mes_atual = $_POST['mes_atual'];
                                                }
                                                if ($mes_seq == $mes_atual) {
                                                    $c = " selected ";
                                                }
                                                echo "<option $c value='$mes_seq'>$mes_seq</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right">
                                        <span class="titulo_campo">Limite:</span>
                                    </td>
                                    <td>
                                        <?php if ($bloqueado_uso == "NAO") {
                                            echo number_format($Limite, '2', ',', '.');
                                        } else {
                                            echo "<span class='titulo_campo'>BLOQUEADO</span>";
                                        }
                                        ?>
                                    </td>
                                    <td align="right">
                                        <input type="button" name="ImprimirRelatorio" value="Imprimir"
                                               class="zpButtonWinxpContainer, zpButtonImageDefaultContainer, zpButtonImageWinxpContainer"
                                               onclick="parent.print()"/>
                                    </td>
                                    <td align="right">
                                        <input type="button" name="retornar" onclick="javascript:history.go(-1);" value="Voltar"/>
                                    </td>
                                </tr>

                            </table>
                            <div align="right">


                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td align='center'>DATA</td>
                        <td align='center'>HORA</td>
                        <td align='left'>CONV&Ecirc;NIO</td>
                        <td align='center'>M&Ecirc;S</td>
                        <td align='center'>PARCELA</td>
                        <td align='right'>VALOR</td>
                    </tr>
                    <?PHP
                    if (isset($_POST['mes_atual'])) {
                        $mes_seq_x = $_POST['mes_atual'];
                    } else {
                        $mes_seq_x = $mes_atual;
                    }

                    $item = 0;
                    $sql_extrato_cartaox = $pdo->query("SELECT data, hora, valor, parcela, razaosocial, 
                                                                       cod_verificacao, mes, nome FROM sind.qextratocartao 
                                                                 WHERE cod_verificacao = '$txtCartao' 
                                                                   AND mes = '$mes_seq_x'  
                                                                   AND valor > 0 
                                                              ORDER BY data");
                    while ($sql_extrato_cartao = $sql_extrato_cartaox->fetch()) {
                        $item++;
                        $aVet1 = $sql_extrato_cartao['data'];
                        $aVet1 = explode(" ", $aVet1);
                        $aux = $sql_extrato_cartao['valor'];
                        $total = $total +  floatval($aux);
                        ?>
                        <tr>
                            <td align='center'><?PHP echo organiza_dt($aVet1[0]) ?></td>
                            <td align='center'><?PHP echo $sql_extrato_cartao['hora'] ?></td>
                            <td align='left'><?PHP echo $sql_extrato_cartao['razaosocial'] ?></td>
                            <td align='center'><?PHP echo $mes_seq_x ?></td>
                            <td align='center'><?PHP
                                if ($sql_extrato_cartao['parcela'] == null) {
                                    echo ".";
                                } else {
                                    echo $sql_extrato_cartao['parcela'];
                                } ?>
                            </td>
                            <td><p align="right"><?PHP echo number_format($sql_extrato_cartao['valor'], '2', ',', '.') ?></p></td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr>
                        <td colspan="5">
                            <div align="right">Total gasto</div>
                        </td>
                        <td><p align="right"><?PHP echo number_format($total, "2", ",", ".") ?>
                            </p></td>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <div align="right">Saldo</div>
                        </td>
                        <td><p align="right"><?PHP echo number_format(($Limite - $total), '2', ',', '.'); ?>
                            </p></td>
                    </tr>
                </form>
            </table>
            <?php
        }else{
                echo "<table class='conteudorel' align='center'>";
                echo "   <tr>";
                echo "<td align='center'>Senha errada!</td>";
                echo "    </tr>";
                echo "</table>";
        }
    }else{
            echo "<table class='conteudorel' align='center'>";
            echo "   <tr>";
            echo "<td align='center'>Informe a senha!</td>";
            echo "    </tr>";
            echo "</table>";
    }
}else{
    echo "<table class='conteudorel' align='center'>";
    echo "   <tr>";
    echo "<td align='center'>Não não está logado!</td>";
    echo "    </tr>";
    echo "</table>";
    }
?>

</body>
</html>





