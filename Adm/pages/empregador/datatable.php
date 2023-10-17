<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$divisao = $_POST['divisao'];
$query = "SELECT empregador.id, 
                 empregador.nome, 
                 empregador.responsavel,
                 empregador.telefone,
                 empregador.abreviacao,
                 empregador.divisao,
                 divisao.nome as nome_divisao,
                 divisao.cidade
            FROM sind.empregador INNER JOIN sind.divisao 
              ON empregador.divisao = divisao.id_divisao
           WHERE empregador.divisao = ".$divisao." ORDER BY id";
$statment = $pdo->prepare($query);
$statment->execute();
$result = $statment->fetchAll();
$data = array();
$linhas_filtradas = $statment->rowCount();
foreach ($result as $row){
    $sub_array = array();
    $sub_array["id"]           = $row["id"];
    $sub_array["nome"]         = $row["nome"];
    $sub_array["responsavel"]  = $row["responsavel"];
    $sub_array["telefone"]     = $row["telefone"];
    $sub_array["abreviacao"]   = $row["abreviacao"];
    $sub_array["nome_divisao"] = $row["nome_divisao"];
    $sub_array["cidade"]       = $row["cidade"];
    $sub_array["botao"]        = '<button type="button" name="update_emp" id="'.$row["id"].'" class="btn btn-warning btn-xs update_emp">Alterar</button>';
    $sub_array["botaoexcluir"] = '<button type="button" name="btnexcluir" id="'.$row["id"].'" class="btn btn-danger btn-xs btnexcluir">Excluir</button>';
    $someArray["data"][] = array_map("utf8_encode",$sub_array);
}
$pp = json_encode($someArray);
echo json_encode($someArray);