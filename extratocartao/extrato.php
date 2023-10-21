<!DOCTYPE HTML>
<html lang="pt-br">
<head>
<TITLE>::QRCRED::</TITLE>

<meta http-equiv="content-type" content="text/html; charset=UTF-8" />

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/4.3.1/flatly/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap3-dialog/1.34.9/css/bootstrap-dialog.min.css"/>
<link rel="stylesheet" type="text/css" href="css/bootstrap-sidermenu.css"/>
<link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css"/>
<link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css"/>
<?PHP
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
            <div class="container" style="margin-top: 10px;">    
                <div class="card" style="width: 100%;margin-bottom: 10px;">

                    <div class="card-header text-center">
                        <img src="../pictures_site-sind/logo.png" style="max-width:140px;max-height: 120px;" alt="">
                        <br><br>EXTRATO SIMPLIFICADO DO CARTAO QRCRED
                    </div>
                    <div class="card-body">

                   
                    
                        <form name="listagem" method="post" action="?leo=x&txtCartao=<?php echo $txtCartao ?>&mes_atual=<?php echo $mes_atual ?>&txtSenhaCartao=<?php echo $txtSenhaCartao ?>&total=<?php echo $total ?>&ct=<?php echo $txtCartao ?>">
                            <input type="hidden" id="txtCartao" name="txtCartao" value="<?php echo $txtCartao ?>"/>
                            <input type="hidden" id="txtSenhaCartao" name="txtSenhaCartao" value="<?php echo $txtSenhaCartao ?>"/>
                            <input type="hidden" id="autorizado" name="autorizado" value="sim"/>
                          
                            
                                <div class="form-group row" style="margin-bottom: 0px;">
                                    <label for="data" class="col-sm-2 col-form-label">Data: </label>
                                    <div id="data" class="col-sm-10" style="padding-top: 6px;"><?php echo date("d/m/y") . "  -  " . date("h:m"); ?></div>
                                </div>
                          
                                <div class="form-group row" style="margin-bottom: 0px;">
                                    <label for="nome" class="col-sm-2 col-form-label">Nome: </label>
                                    <div class="col-sm-10" style="padding-top: 6px;" id="nome"><?php echo $nome; ?></div>
                                </div>
                                <div class="form-group row" style="margin-bottom: 0px;">
                                    <label for="nome" class="col-sm-2 col-form-label">Cartão: </label>
                                    <div class="col-sm-10" style="padding-top: 6px;" id="nome"><?php echo $txtCartao; ?></div>
                                </div>
                                <div class="form-group row" style="margin-bottom: 0px;">
                                    <label for="situacao" class="col-sm-2 col-form-label">Situacao: </label>
                                    <div class="col-sm-10" style="padding-top: 6px;" id="situacao">
                                        <?php if ($bloqueado_uso == "NAO") {
                                                    echo "Normal";
                                                } else {
                                                    echo "<span style='color:red;'>BLOQUEADO</span>";
                                                }
                                                ?>
                                    </div>
                                </div>
                                <div class="form-group row" style="margin-bottom: 0px;">
                                    <label for="mes_atual" class="col-sm-2 col-form-label">Mes: </label>
                                    <select name="mes_atual" class="col-sm-2" id="mes_atual" onchange="submit()">
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
                                </div>
                                <div class="form-group row" style="margin-bottom: 0px;">
                                    <label for="limite" class="col-sm-2 col-form-label">Limite: </label>
                                    <div class="col-sm-10" style="padding-top: 6px;" id="limite"> 
                                        <?php if ($bloqueado_uso == "NAO") {
                                                echo number_format($Limite, '2', ',', '.');
                                              } else {
                                                echo "<span class='titulo_campo'>BLOQUEADO</span>";
                                              }
                                        ?>
                                    </div>
                                </div>
                                <div class="form-group row" style="margin-bottom: 0px;">
                                    <input type="button" name="ImprimirRelatorio" value="Imprimir" class="btn btn-primary" style="margin-left: 10px;" onclick="parent.print()"/>
                                    <input type="button" name="retornar" class="btn btn-secondary" onclick="javascript:history.go(-1);" style="margin-left: 10px;" value="Voltar"/>    
                                </div>
                        </form>
                    </div>
                </div>    
                <table class="table table-striped table-hover table-bordered">
                    <thead>
                        <tr>
                            <td class="font-weight-bold">DATA</td>
                            <td class="font-weight-bold">HORA</td>
                            <td class="font-weight-bold">CONV&Ecirc;NIO</td>
                            <td class="font-weight-bold">M&Ecirc;S</td>
                            <td class="font-weight-bold">PARCELA</td>
                            <td class="font-weight-bold">VALOR</td>
                        </tr>
                    </thead>
                    <tbody>
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
                                <td><?PHP echo organiza_dt($aVet1[0]) ?></td>
                                <td><?PHP echo $sql_extrato_cartao['hora'] ?></td>
                                <td><?PHP echo $sql_extrato_cartao['razaosocial'] ?></td>
                                <td><?PHP echo $mes_seq_x ?></td>
                                <td><?PHP
                                    if ($sql_extrato_cartao['parcela'] == null) {
                                        echo ".";
                                    } else {
                                        echo $sql_extrato_cartao['parcela'];
                                    } ?>
                                </td>
                                <td><p><?PHP echo number_format($sql_extrato_cartao['valor'], '2', ',', '.') ?></p></td>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr>
                            <td colspan="5">
                                <div>Total gasto</div>
                            </td>
                            <td><p><?PHP echo number_format($total, "2", ",", ".") ?>
                                </p></td>
                        </tr>
                        <tr>
                            <td colspan="5">
                                <div>Saldo</div>
                            </td>
                            <td><p><?PHP echo number_format(($Limite - $total), '2', ',', '.'); ?>
                                </p></td>
                        </tr>
                    
                    </tbody>
                </table>
            </div>
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
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-fallr-2.0.1.js"></script> 
    <script type="text/javascript" src="../js/maskmoney.js"></script>
    <script type="text/javascript" src="../js/jquery.redirect.js"></script>
    <script type="text/javascript" src="../js/sweetalert2.all.js"></script>
    <script type="text/javascript" src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/6.0.0/bootbox.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script type="text/javascript" src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
    <!-- The core Firebase JS SDK is always required and must be listed first -->
    <script type="text/javascript" src="https://www.gstatic.com/firebasejs/7.15.1/firebase-app.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/firebasejs/7.15.1/firebase-database.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/firebasejs/7.15.1/firebase-firestore.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap3-dialog/1.34.9/js/bootstrap-dialog.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-waitingfor/1.2.8/bootstrap-waitingfor.min.js"></script>
    <script type="text/javascript" src="//cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <!-- : Add SDKs for Firebase products that you want to use
     https://firebase.google.com/docs/web/setup#config-web-app -->
    <script type="text/javascript" src="../app.js"></script>
    <script type="text/javascript" src="../js/printThis.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
    <script type="text/javascript" src="https://kit.fontawesome.com/9a646532a8.js" crossorigin="anonymous"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/gh/bgaze/bootstrap4-dialogs@2/dist/bootstrap4-dialogs.min.js"></script>
</body>
</html>





