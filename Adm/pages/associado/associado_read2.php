<?PHP
ini_set('display_errors', true);
error_reporting(E_ALL);
/* cSpell:disable */
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
if($_POST['id_situacao'] != "0" ){
    $tipo_sql = " AND id_situacao=".$_POST['id_situacao'];
}else{
    $tipo_sql = "";
}
/* cSpell:enable */
$divisao = $_POST["divisao"];   
$card1 = $_POST["card1"];
$card2 = $_POST["card2"];
$card3 = $_POST["card3"];
$card4 = $_POST["card4"];
$card5 = $_POST["card5"];
$card6 = $_POST["card6"];

$query = "SELECT associado.codigo, 
                 associado.nome, 
                 associado.endereco, 
                 associado.numero, 
                 associado.nascimento, 
                 associado.salario, 
                 associado.limite, 
                 associado.complemento,
                 empregador.nome AS empregador, 
                 empregador.id AS id_empregador, 
                 associado.cep, associado.telres, 
                 associado.telcom, associado.cel, 
                 associado.bairro, associado.rg, 
                 associado.cpf, funcao.nome AS funcao,
                 associado.id_divisao,
                 empregador.abreviacao, 
                 empregador.divisao,
                 situacao_associado.nome as nome_situacao
            FROM sind.empregador 
      RIGHT JOIN (sind.funcao 
      RIGHT JOIN (sind.associado 
      RIGHT JOIN sind.situacao_associado
              ON situacao_associado.codigo = associado.id_situacao) 
              ON funcao.id = associado.funcao) 
              ON empregador.id = associado.empregador 
           WHERE associado.id_divisao = ".$divisao.$tipo_sql ." 
             AND associado.codigo <> '".$card1."' 
             AND associado.codigo <> '".$card2."' 
             AND associado.codigo <> '".$card3."'
             AND associado.codigo <> '".$card4."' 
             AND associado.codigo <> '".$card5."' 
             AND associado.codigo <> '".$card6."'";
$statment = $pdo->prepare($query);

$statment->execute();

$result = $statment->fetchAll();

$data = array();

$linhas_filtradas = $statment->rowCount();

foreach ($result as $row){
    $sub_array = array();

    $sub_array["codigo"]        = $row["codigo"];
    $sub_array["nome"]          = htmlspecialchars($row["nome"]);
    $sub_array["endereco"]      = htmlspecialchars($row["endereco"]);
    $sub_array["numero"]        = $row["numero"];
    if($row["nascimento"] != null){
        $sub_array["nascimento"] = date('d/m/Y', strtotime($row["nascimento"]));
    }else{
        $sub_array["nascimento"] = "";
    }
    $sub_array["salario"]       = $row["salario"];
    $sub_array["limite"]        = $row["limite"];
    $sub_array["empregador"]    = $row["empregador"];
    $sub_array["cep"]           = $row["cep"];
    $sub_array["cpf"]           = $row["cpf"];
    $sub_array["telres"]        = $row["telres"];
    $sub_array["telcom"]        = $row["telcom"];
    $sub_array["cel"]           = $row["cel"];
    $sub_array["complemento"]   = $row["complemento"];
    $sub_array["nome_situacao"] = $row["nome_situacao"];
    $sub_array["bairro"]        = htmlspecialchars($row["bairro"]);
    $sub_array["abreviacao"]    = $row["abreviacao"];
    $sub_array["botao"]         = '<button type="button" name="update_assoc" id="'.$row["codigo"].'" class="btn btn-warning glyphicon glyphicon-edit btn-xs update_assoc" data-toggle="tooltip" data-placement="top" title="Alterar"></button>';
    $sub_array["botaosenha"]    = '<button type="button" name="btnsenha_assoc" id="'.$row["codigo"].'" class="btn btn-facebook glyphicon glyphicon-credit-card btn-xs btnsenha_assoc" data-toggle="tooltip" data-placement="top" title="Senha do cartão"></button>';
    $sub_array["botaoexcluir"]  = '<button type="button" name="btnexcluir" id="'.$row["codigo"].'" class="btn btn-danger glyphicon glyphicon-trash btn-xs btnexcluir" data-toggle="tooltip" data-placement="top" title="Excluir" disabled></button>';
    $sub_array["id_empregador"] = $row["id_empregador"];
    $someArray['data'][] = $sub_array;
}
$pp = json_encode($someArray);
echo json_encode($someArray);