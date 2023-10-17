<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$query = "SELECT id_divisao, 
                 nome, 
                 cidade 
            FROM sind.divisao ORDER BY id_divisao";
$statment = $pdo->prepare($query);
$statment->execute();
$result = $statment->fetchAll();
$data = array();
$linhas_filtradas = $statment->rowCount();
foreach ($result as $row){
    $sub_array = array();
    $sub_array["id_divisao"]   = $row["id_divisao"];
    $sub_array["nome"]         = $row["nome"];
    $sub_array["cidade"]       = $row["cidade"];
    $sub_array["botao"]        = '<button type="button" name="update_divisao" id="'.$row["id_divisao"].'" class="btn btn-warning btn-xs update_divisao">Alterar</button>';
    $sub_array["botaoexcluir"] = '<button type="button" name="btnexcluir" id="'.$row["id_divisao"].'" class="btn btn-danger btn-xs btnexcluir">Excluir</button>';
    $someArray["data"][] = array_map("utf8_encode",$sub_array);
}
$pp = json_encode($someArray);
echo json_encode($someArray);