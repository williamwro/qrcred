<?PHP
include 'Adm/php/banco.php';
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_GET['data_inicial'])){
    $data_inicial = substr($_GET['data_inicial'], 6, 4) . '-' . substr($_GET['data_inicial'], 3, 2) . '-' . substr($_GET['data_inicial'], 0, 2);
}else if(isset($_POST['data_inicial'])){
    $data_inicial = substr($_POST['data_inicial'], 6, 4) . '-' . substr($_POST['data_inicial'], 3, 2) . '-' . substr($_POST['data_inicial'], 0, 2);
}
if(isset($_GET['data_final'])){
    $data_final = substr($_GET['data_final'], 6, 4) . '-' . substr($_GET['data_final'], 3, 2) . '-' . substr($_GET['data_final'], 0, 2);
}else if(isset($_POST['data_final'])){
    $data_final = substr($_POST['data_final'], 6, 4) . '-' . substr($_POST['data_final'], 3, 2) . '-' . substr($_POST['data_final'], 0, 2);
}
if(isset($_GET['cod_convenio'])){
    $cod_convenio = $_GET['cod_convenio'];
}else if(isset($_POST['cod_convenio'])){
    $cod_convenio = $_POST['cod_convenio'];
}
//$mes_atual = 'OUT/2017';
//$cod_convenio = 99;

$item  = 0;
$total = 0;
$someArray = array();
$query = "SELECT conta.lancamento, 
                     conta.associado AS matricula, 
                     conta.valor, 
                     conta.data, 
                     conta.hora, 
                     conta.mes, 
                     empregador.nome AS empregador, 
                     empregador.id AS codigo_empregador, 
                     convenio.razaosocial AS convenio, 
                     convenio.codigo AS cod_convenio, 
                     associado.nome AS associado, 
                     conta.funcionario, 
                     conta.parcela, 
                     conta.descricao
                FROM sind.associado 
          RIGHT JOIN (sind.empregador 
          RIGHT JOIN (sind.convenio 
          RIGHT JOIN sind.conta 
          ON convenio.codigo = conta.convenio) 
          ON empregador.id = conta.empregador) 
          ON associado.codigo = conta.associado AND associado.empregador = conta.empregador 
          WHERE conta.data between '" . $data_inicial . "' AND '" . $data_final . "' AND convenio.codigo = ".$cod_convenio." ORDER BY conta.lancamento DESC";

$sql_conv_vendas = $pdo->query($query);

while($row = $sql_conv_vendas->fetch()) {
    $sub_array = array();

    if ($row['parcela'] != "" && $row['parcela'] != null ) {
        $parcela    = substr($row['parcela'], 0, 2);
        $qtde       = substr($row['parcela'], 3, 2);
        if($parcela === "01"){
            $val_parcela = $row['valor'];
            $total_aux = $val_parcela * $qtde;
            $sub_array["valor_total"]   = $total_aux;
            $total_aux = 0;
            $sub_array["lancamento"]    = $row['lancamento'];
            $sub_array["associado"]     = $row['associado'];
            $sub_array["data"]          = $row['data'];
            $sub_array["hora"]          = $row['hora'];
            $sub_array["mes"]           = $row['mes'];
            $sub_array["valor_parcela"] = $row['valor'];
            $sub_array["parcela"]       = '('.$row['parcela'].')';
            $someArray["data"][]        = array_map("utf8_encode",$sub_array);
        }
    }else{
        $sub_array["valor_total"]   = $row['valor'];
        $sub_array["lancamento"]    = $row['lancamento'];
        $sub_array["associado"]     = $row['associado'];
        $sub_array["data"]          = $row['data'];
        $sub_array["hora"]          = $row['hora'];
        $sub_array["mes"]           = $row['mes'];
        $sub_array["valor_parcela"] = $row['valor'];
        $sub_array["parcela"]       = '';
        $someArray["data"][]        = array_map("utf8_encode",$sub_array);
    }
}
echo json_encode($someArray);


