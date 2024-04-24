<?PHP
ini_set('display_errors', true);
error_reporting(E_ALL);
/* cSpell:disable */
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
if($_POST['id_situacao'] == "true" || $_POST['id_situacao'] == "false" ){
    $tipo_sql = "WHERE ant.aprovado = ".$_POST['id_situacao'];
}else if($_POST['id_situacao'] == "null" || $_POST['id_situacao'] == "" ){
    $tipo_sql = "WHERE ant.aprovado isnull;";
}else{
    $tipo_sql = "";
}
/* cSpell:enable */
//$divisao = $_POST["divisao"];   


$query = "SELECT ant.id, ant.matricula, ass.nome, ant.empregador as id_empregador, emp.nome as nome_empregador, ant.mes, ant.data_solicitacao, ant.valor, ant.aprovado, ant.data_aprovacao, ant.celular
            FROM sind.antecipacao ant
            JOIN sind.associado ass ON ass.codigo = ant.matricula
            JOIN sind.empregador emp ON emp.id = ant.empregador
            ".$tipo_sql ."";

$statment = $pdo->prepare($query);

$statment->execute();

$result = $statment->fetchAll();

$data = array();

$linhas_filtradas = $statment->rowCount();

foreach ($result as $row){
    $sub_array = array();

    $sub_array["id"]              = $row["id"];
    $sub_array["matricula"]       = $row["matricula"];
    $sub_array["nome"]            = htmlspecialchars($row["nome"]);
    $sub_array["id_empregador"]   = $row["id_empregador"];
    $sub_array["nome_empregador"] = $row["nome_empregador"];
    $sub_array["mes"]             = $row["mes"];
    if($row["data_solicitacao"] != null){
        $sub_array["data_solicitacao"] = date('d/m/Y', strtotime($row["data_solicitacao"]));
    }else{
        $sub_array["data_solicitacao"] = "";
    }
    $sub_array["valor"]           = $row["valor"];
    if($row["aprovado"] === null){
        $sub_array["aprovado"]    = "Analisando";
    }else if($row["aprovado"] == 1){
        $sub_array["aprovado"]    = "Aprovado";
    }else{
        $sub_array["aprovado"]    = "Reprovado";
    }
    $sub_array["data_aprovacao"]  = $row["data_aprovacao"];
    $sub_array["celular"]         = $row["celular"];
    $sub_array["botao"]           = '<button type="button" name="update_antecipacao" id="'.$row["matricula"].'" class="btn btn-warning glyphicon glyphicon-edit btn-xs update_antecipacao" data-toggle="tooltip" data-placement="top" title="Alterar"></button>';
    $sub_array["botaoexcluir"]    = '<button type="button" name="btnexcluir" id="'.$row["matricula"].'" class="btn btn-danger glyphicon glyphicon-trash btn-xs btnexcluir" data-toggle="tooltip" data-placement="top" title="Excluir" disabled></button>';
    $someArray['data'][] = $sub_array;
}
$pp = json_encode($someArray);
echo json_encode($someArray);