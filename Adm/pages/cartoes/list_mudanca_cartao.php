<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$codcartao = $_POST["cartao"];
$codempregador = $_POST["codempregador"];

$query = "SELECT c_historico_cartoes.id, c_historico_cartoes.matricula, 
          c_historico_cartoes.cod_verificacao, c_historico_cartoes.cod_situacaocartao, 
          c_historico_cartoes.data, c_historico_cartoes.hora, c_historico_cartoes.usuario, 
          c_historico_cartoes.obs, c_situacaocartao.descri AS descri_situacao
     FROM sind.c_situacaocartao INNER JOIN sind.c_historico_cartoes ON 
          sind.c_situacaocartao.id = c_historico_cartoes.cod_situacaocartao 
    WHERE c_historico_cartoes.cod_verificacao='".$codcartao."' 
    ORDER BY c_historico_cartoes.id DESC";

$someArray = array();
$statment = $pdo->prepare($query);
$statment->execute();
$result = $statment->fetchAll();
$data = array();
$linhas_filtradas = count($result);

foreach ($result as $row){
    $sub_array = array();

    $sub_array["id"]              = $row["id"];
    $sub_array["matricula"]       = $row["matricula"];
    $sub_array["data"]            = $row["data"];
    $sub_array["hora"]            = $row["hora"];
    $sub_array["descri_situacao"] = $row["descri_situacao"];
    $sub_array["operador"]        = $row["usuario"];
    $sub_array["obs"]             = $row["obs"];

    $someArray["data"][] = array_map("utf8_encode",$sub_array);

}
if($linhas_filtradas > 0) {
    $teste = json_encode($someArray);
    echo json_encode($someArray);
}else{
    $someArray = array('data'=>"", 'hora'=>"", 'descri_situacao'=>"",'operador'=>"",'obs'=>"");
    $teste = json_encode($someArray);
    echo json_encode($someArray);
}