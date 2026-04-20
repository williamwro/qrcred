<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$divisao = $_POST["divisao"];

if($_POST["mes"] == "Todos") {
    $sql = "WHERE est.id_divisao = ".$divisao;
}else{
    $sql = "WHERE est.mes = '".$_POST["mes"]."' AND est.id_divisao = ".$divisao;
}
$query = "SELECT DISTINCT est.lancamento,
                 est.associado AS matricula,
                 associado.nome,
                 convenio.codigo AS cod_convenio,
                 convenio.razaosocial,
                 est.empregador AS cod_empregador,
                 empregador.nome AS nome_empregador,
                 est.valor,
                 est.data,
                 est.hora,
                 est.descricao,
                 est.mes,
                 est.parcela,
                 est.funcionario,
                 user1.username,
                 est.data_estorno,
                 est.hora_estorno,
                 empregador.id_divisao AS id_divisao_emp,
                 est.id_divisao,
                 user2.username AS username_estornado
          FROM sind.estornos est
          JOIN sind.associado ON est.associado::text = associado.codigo::text AND est.empregador = associado.empregador
          JOIN sind.convenio ON est.convenio = convenio.codigo
          JOIN sind.empregador ON est.empregador = empregador.id
          LEFT JOIN sind.usuarios user1 ON est.funcionario = user1.codigo
          LEFT JOIN sind.usuarios user2 ON est.func_estorno = user2.codigo
          " . $sql ." 
          ORDER BY est.lancamento DESC";

$someArray = array();
$statment = $pdo->query($query);
while($row = $statment->fetch()) {
    $sub_array = array();
    $sub_array["lancamento"]      = $row["lancamento"];
    $sub_array["matricula"]       = $row["matricula"];
    $sub_array["nome"]            = $row["nome"];
    $sub_array["razaosocial"]     = $row["razaosocial"];
    $sub_array["nome_empregador"] = $row["nome_empregador"];
    $sub_array["valor"]           = $row["valor"];
    $sub_array["data"]            = $row["data"];
    $sub_array["hora"]            = $row["hora"];
    $sub_array["mes"]             = $row["mes"];
    $sub_array["parcela"]         = $row["parcela"];
    $sub_array["username"]        = $row["username"];
    $sub_array["data_estorno"]    = $row["data_estorno"];
    $sub_array["hora_estorno"]    = $row["hora_estorno"];
    $sub_array["username_estornado"]    = $row["username_estornado"];
    $sub_array["botaocancelar"]   = '<button type="button" name="btncancelarestorno" id="'.$row["lancamento"].'" class="btn btn-warning glyphicon glyphicon-open btn-xs btncancelarestorno" data-toggle="tooltip" data-placement="top" title="Cancelar estorno"></button>';
    $someArray["data"][] = array_map(function($value) {
        return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
    }, $sub_array);
}
$aux = json_encode($someArray);
echo $aux;