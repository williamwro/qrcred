<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$query = "SELECT id_divisao, 
                 nome, 
                 cidade,
                 dia_mes_renovacao 
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
    $sub_array["dia_mes_renovacao"] = $row["dia_mes_renovacao"];
    $sub_array["botao"]        = '<button type="button" name="update_divisao" id="'.$row["id_divisao"].'" class="btn btn-warning btn-xs update_divisao">Alterar</button>';
    $sub_array["botaoexcluir"] = '<button type="button" name="btnexcluir" id="'.$row["id_divisao"].'" class="btn btn-danger btn-xs btnexcluir">Excluir</button>';
    $someArray["data"][] = array_map(function($value) {
        return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
    }, $sub_array);
}
$pp = json_encode($someArray);
echo json_encode($someArray);