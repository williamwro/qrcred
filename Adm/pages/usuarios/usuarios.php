<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$usuario_global = $_POST["usuario_global"];
$divisao        = $_POST["divisao"];
$usuario_cod    = $_POST["usuario_cod"];
if($usuario_cod == "28"){ // 28 - master pode ver todos independente da divisão
    $query = "SELECT usuarios.*, divisao.nome as nome_divisao,
                CASE 
                    WHEN usuarios.situacao = 1 THEN 'Liberado'
                    WHEN usuarios.situacao = 2 THEN 'Bloqueado'
                    ELSE 'Indefinido'
                END as descri_situacao
                FROM sind.usuarios 
                LEFT JOIN sind.divisao ON usuarios.divisao = divisao.id_divisao";
}elseif($usuario_cod == "1"){ // 1 - adm pode ver todos
    $query = "SELECT usuarios.*, divisao.nome as nome_divisao,
                CASE 
                    WHEN usuarios.situacao = 1 THEN 'Liberado'
                    WHEN usuarios.situacao = 2 THEN 'Bloqueado'
                    ELSE 'Indefinido'
                END as descri_situacao
                FROM sind.usuarios 
                LEFT JOIN sind.divisao ON usuarios.divisao = divisao.id_divisao";
}else{ // outros usuários veem apenas da sua divisão
    $query = "SELECT usuarios.*, divisao.nome as nome_divisao,
                CASE 
                    WHEN usuarios.situacao = 1 THEN 'Liberado'
                    WHEN usuarios.situacao = 2 THEN 'Bloqueado'
                    ELSE 'Indefinido'
                END as descri_situacao
                FROM sind.usuarios 
                LEFT JOIN sind.divisao ON usuarios.divisao = divisao.id_divisao
               WHERE usuarios.divisao = ".$divisao;
}
$statment = $pdo->prepare($query);

$statment->execute();

$result = $statment->fetchAll();

$data = array();

$linhas_filtradas = $statment->rowCount();

foreach ($result as $row){
    // Filtros baseados no código do usuário:
    // - código 28: pode ver todos os usuários (sem filtro)
    // - código 1: não pode ver usuários com código 28
    if ($usuario_cod == 1 && $row["codigo"] == 28) {
        continue; // Usuário código 1 não pode ver registros com código 28
    }
    // Usuário código 28 pode ver todos sem restrições
    
    $sub_array = array();

    $sub_array["status_online"]   = '<span class="status-loading"><i class="fa fa-spinner fa-spin"></i> Carregando...</span>';
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
    $someArray["data"][]          = array_map(function($value) {
        return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
    }, $sub_array);

}
$pp = json_encode($someArray);
echo json_encode($someArray);