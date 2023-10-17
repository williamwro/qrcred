<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$card1 = $_POST["card1"];
$card2 = $_POST["card2"];
$card3 = $_POST["card3"];
$card4 = $_POST["card4"];
$card5 = $_POST["card5"];
$card6 = $_POST["card6"];

if($_POST["todos"] === "") {
    if (isset($_POST["parcela"]) and $_POST["parcela"] != "") {
        if (isset($_POST["cod_convenio"]) and $_POST["cod_convenio"] != "") {
            if (isset($_POST["empregador"]) and $_POST["empregador"] != "") {
                $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
                            FROM sind.associado 
                            RIGHT JOIN (sind.empregador 
                            RIGHT JOIN (sind.convenio 
                            RIGHT JOIN sind.conta ON convenio.codigo = conta.convenio) 
                            ON empregador.id = conta.empregador) 
                            ON associado.codigo = conta.associado and associado.empregador = conta.empregador 
                            WHERE convenio.codigo = " . $_POST["cod_convenio"] . " AND conta.mes = '" . $_POST["mes"] . "'
                            AND empregador.id =" . $_POST["empregador"] . " AND left(conta.parcela,2) ='" . $_POST["parcela"] . "' OR
                            convenio.codigo = " . $_POST["cod_convenio"] . " AND conta.mes = '" . $_POST["mes"] . "'
                            AND empregador.id =" . $_POST["empregador"] . " AND empregador.divisao =" . $_POST["divisao"] . "
                               
                            AND conta.parcela ISNULL;";
            } else {
                $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
                            FROM sind.associado 
                            RIGHT JOIN (sind.empregador 
                            RIGHT JOIN (sind.convenio 
                            RIGHT JOIN sind.conta 
                            ON convenio.codigo = conta.convenio) 
                            ON empregador.id = conta.empregador) 
                            ON associado.codigo = conta.associado and associado.empregador = conta.empregador  
                            WHERE convenio.codigo = " . $_POST["cod_convenio"] . " AND conta.mes = '" . $_POST["mes"] . "'
                            AND left(conta.parcela,2) ='" . $_POST["parcela"] . "' OR
                            convenio.codigo = " . $_POST["cod_convenio"] . " AND conta.mes = '" . $_POST["mes"] . "' AND empregador.divisao =" . $_POST["divisao"] . "
                           
                            AND conta.parcela ISNULL;";
            }
        } else {
            if (isset($_POST["empregador"]) and $_POST["empregador"] != "") {
                $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
                            FROM sind.associado 
                            RIGHT JOIN (sind.empregador 
                            RIGHT JOIN (sind.convenio 
                            RIGHT JOIN sind.conta 
                            ON convenio.codigo = conta.convenio) 
                            ON empregador.id = conta.empregador) 
                            ON associado.codigo = conta.associado and associado.empregador = conta.empregador 
                            WHERE empregador.id =" . $_POST["empregador"] . " AND conta.mes = '" . $_POST["mes"] . "'
                            AND left(conta.parcela,2) ='" . $_POST["parcela"] . "' OR 
                            AND conta.mes = '" . $_POST["mes"] . "'
                            AND empregador.id =" . $_POST["empregador"] . " AND empregador.divisao =" . $_POST["divisao"] . "
                            
                            AND conta.parcela ISNULL;";
            } else {
                $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
                            FROM sind.associado 
                            RIGHT JOIN (sind.empregador 
                            RIGHT JOIN (sind.convenio 
                            RIGHT JOIN sind.conta 
                            ON convenio.codigo = conta.convenio) 
                            ON empregador.id = conta.empregador) 
                            ON associado.codigo = conta.associado and associado.empregador = conta.empregador 
                            WHERE conta.mes = '" . $_POST["mes"] . "'
                            AND left(conta.parcela,2) ='" . $_POST["parcela"] . "' OR
                            AND conta.mes = '" . $_POST["mes"] . "' AND empregador.divisao =" . $_POST["divisao"] . "
                            
                            AND conta.parcela ISNULL;";
            }
        }
    } else {
        if (isset($_POST["cod_convenio"]) and $_POST["cod_convenio"] != "") {
            if ($_POST["empregador"] == "") {
                $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
                            FROM sind.associado 
                            RIGHT JOIN (sind.empregador 
                            RIGHT JOIN (sind.convenio 
                            RIGHT JOIN sind.conta 
                            ON convenio.codigo = conta.convenio) 
                            ON empregador.id = conta.empregador) 
                            ON associado.codigo = conta.associado and associado.empregador = conta.empregador 
                            WHERE convenio.codigo = " . $_POST["cod_convenio"] . " AND conta.mes = '" . $_POST["mes"] . "'
                            
                            AND empregador.divisao =" . $_POST["divisao"] . ";";

            } else {
                $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
                            FROM sind.associado 
                            RIGHT JOIN (sind.empregador 
                            RIGHT JOIN (sind.convenio 
                            RIGHT JOIN sind.conta 
                            ON convenio.codigo = conta.convenio) 
                            ON empregador.id = conta.empregador) 
                            ON associado.codigo = conta.associado  and associado.empregador = conta.empregador 
                            WHERE convenio.codigo = " . $_POST["cod_convenio"] . " AND conta.mes = '" . $_POST["mes"] . "'
                          
                            AND empregador.id =" . $_POST["empregador"] . " AND empregador.divisao =" . $_POST["divisao"] . ";";
            }
        } else {
            if (isset($_POST["empregador"]) and $_POST["empregador"] != "") {
                $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
                            FROM sind.associado 
                            RIGHT JOIN (sind.empregador 
                            RIGHT JOIN (sind.convenio 
                            RIGHT JOIN sind.conta 
                            ON convenio.codigo = conta.convenio) 
                            ON empregador.id = conta.empregador) 
                            ON associado.codigo = conta.associado and associado.empregador = conta.empregador 
                            WHERE empregador.id =" . $_POST["empregador"] . "
                          
                            AND conta.mes = '" . $_POST["mes"] . "' AND empregador.divisao =" . $_POST["divisao"] . ";";
            } else {
                /*$query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
                            FROM sind.associado
                            RIGHT JOIN (sind.empregador
                            RIGHT JOIN (sind.convenio
                            RIGHT JOIN sind.conta
                            ON convenio.codigo = conta.convenio)
                            ON empregador.id = conta.empregador)
                            ON associado.codigo = conta.associado and associado.empregador = conta.empregador
                            WHERE conta.mes = '" . $_POST["mes"] . "'

                            AND empregador.divisao =" . $_POST["divisao"] . ";";*/
                $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
                FROM sind.associado 
                RIGHT JOIN (sind.empregador 
                RIGHT JOIN (sind.convenio 
                RIGHT JOIN sind.conta 
                ON convenio.codigo = conta.convenio) 
                ON empregador.id = conta.empregador) 
                ON associado.codigo = conta.associado and associado.empregador = conta.empregador 
             WHERE conta.mes = 'XXXXXXXXXXXXXXXXX'
               
               AND empregador.divisao =" . $_POST["divisao"] . ";";
            }
        }
    }
}else{
    $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
                FROM sind.associado 
                RIGHT JOIN (sind.empregador 
                RIGHT JOIN (sind.convenio 
                RIGHT JOIN sind.conta 
                ON convenio.codigo = conta.convenio) 
                ON empregador.id = conta.empregador) 
                ON associado.codigo = conta.associado and associado.empregador = conta.empregador 
             WHERE conta.mes = 'XXXXXXXXXXXXXXXXX'
               
               AND empregador.divisao =" . $_POST["divisao"] . ";";
}
/*AND associado.codigo <> '".$card1."'
AND associado.codigo <> '".$card2."'
AND associado.codigo <> '".$card3."' */
$i=1;

$someArray = array();
$statment = $pdo->query($query);
$matricula_aux = "";
while($row = $statment->fetch()) {


    if( $row["matricula"] <> $card1 && $row["matricula"] <> $card2 && $row["matricula"] <> $card3 && $row["matricula"] <> $card4 && $row["matricula"] <> $card5 && $row["matricula"] <> $card6 ){
        $sub_array = array();
        $sub_array["lancamento"]  = $row["lancamento"];
        /*$row["matricula"] == '123139' ||*/
        $matricula_aux = $row["matricula"];
        if( $matricula_aux == '800030' ) {
            $sub_array["matricula"] = '166863';
            $sub_array["associado"] = 'MARIA APARECIDA DIAS';
        }else{
            $sub_array["matricula"] = $row["matricula"];
            $sub_array["associado"] = $row["associado"];
        }
        $sub_array["valor"]       = $row["valor"];
        //$sub_array["valor"]     = number_format((real)$row["valor"],2,",",".");
        $sub_array["data"]        = date('d/m/Y', strtotime($row["data"]));
        $sub_array["hora"]        = $row["hora"];
        $sub_array["mes"]         = $row["mes"];
        $sub_array["empregador"]  = $row["empregador"];
        $sub_array["convenio"]    = $row["convenio"];
        $sub_array["funcionario"] = $row["funcionario"];
        $sub_array["parcela"]     = $row["parcela"];
        $sub_array["descricao"]   = $row["descricao"];
        $sub_array["botao"]       = '<button type="button" name="update" id="'.$row["lancamento"].'" class="btn btn-warning btn-xs update">Alterar</button>';
        $sub_array["botaosenha"]  = '<button type="button" name="btnsenha" id="'.$row["lancamento"].'" class="btn btn-facebook btn-xs btnsenha">Senha</button>';

        $someArray["data"][] = array_map("utf8_encode",$sub_array);
    }else{}
}
$aux = json_encode($someArray);
echo $aux;