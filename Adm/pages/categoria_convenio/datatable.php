<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$query = "SELECT codigo, 
                 nome 
            FROM sind.categoriaconvenio";
$statment = $pdo->prepare($query);
$statment->execute();
$result = $statment->fetchAll();
$data = array();
$linhas_filtradas = $statment->rowCount();
foreach ($result as $row){
    $sub_array = array();
    $sub_array["codigo"]       = $row["codigo"];
    $sub_array["nome"]         = $row["nome"];
    $sub_array["botao"]        = '<button type="button" name="update_categoria" id="'.$row["codigo"].'" class="btn btn-warning btn-xs update_categoria">Alterar</button>';
    $someArray["data"][] = array_map("utf8_encode",$sub_array);
}
$pp = json_encode($someArray);
echo json_encode($someArray);