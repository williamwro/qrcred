<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$usuario_global = $_POST["usuario_global"];
$divisao        = $_POST["divisao"];
if($usuario_global  != 'wrox'){
    $query = "SELECT *
                FROM sind.qusuarios
               WHERE qusuarios.divisao = ".$divisao;
}else{
    $query = "SELECT *
                FROM sind.qusuarios";
}
$statment = $pdo->prepare($query);

$statment->execute();

$result = $statment->fetchAll();

$data = array();

$linhas_filtradas = $statment->rowCount();

foreach ($result as $row){
    $sub_array = array();

    $sub_array["codigo"]          = $row["codigo"];
    $sub_array["username"]        = $row["username"];
    $sub_array["password"]        = $row["password"];
    $sub_array["senha"]           = $row["senha"];
    $sub_array["email"]           = $row["email"];
    $sub_array["lastname"]        = $row["lastname"];
    $sub_array["situacao"]        = $row["situacao"];
    $sub_array["nome"]            = $row["nome"];
    $sub_array["divisao"]         = $row["divisao"];
    $sub_array["descri_situacao"] = $row["descri_situacao"];
    $sub_array["nome_divisao"]    = $row["nome_divisao"];

    if($row["situacao"] ==  1){
        $sub_array["badges"]      = '<span class="badge badge-pill badge-success" style="background-color: green">Liberado</span>';
    }else{
        $sub_array["badges"]      = '<span class="badge badge-pill badge-danger" style="background-color: red">Bloqueado</span>';
    }
    $sub_array["botao"]           = '<button type="button" name="update" id="'.$row["codigo"].'" class="btn btn-warning btn-xs update">Alterar</button>';
    $sub_array["botaoexcluir"]    = '<button type="button" name="btnexcluir" id="'.$row["codigo"].'" class="btn btn-danger btn-xs btnexcluir">Excluir</button>';
    $someArray["data"][]          = array_map("utf8_encode",$sub_array);

}
$pp = json_encode($someArray);
echo json_encode($someArray);