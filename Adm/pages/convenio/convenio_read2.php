<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$query = 'SELECT codigo,razaosocial,nomefantasia,endereco,bairro,telefone,data_cadastro,cidade,cnpj,email,contato,registro,cpf,cel,contrato,desativado,divulga,aceita_parce_individ FROM sind.convenio';
$statment = $pdo->prepare($query);

$statment->execute();

$result = $statment->fetchAll();

$data = array();

$linhas_filtradas = $statment->rowCount();

foreach ($result as $row){
    $sub_array = array();

    $sub_array["codigo"]        = $row["codigo"];
    $sub_array["razaosocial"]   = htmlspecialchars(substr($row["razaosocial"],0,30));
    $sub_array["nomefantasia"]  = htmlspecialchars(substr($row["nomefantasia"],0,30));
    $sub_array["endereco"]      = htmlspecialchars(substr($row["endereco"],0,30));
    $sub_array["bairro"]        = htmlspecialchars($row["bairro"]);
    $sub_array["telefone"]      = $row["telefone"];
    $sub_array["data_cadastro"] = $row["data_cadastro"];
    $sub_array["cidade"]        = htmlspecialchars($row["cidade"]);
    $sub_array["cnpj"]          = $row["cnpj"];
    $sub_array["email"]         = $row["email"];
    $sub_array["contato"]       = htmlspecialchars($row["contato"]);
    $sub_array["registro"]      = $row["registro"];
    $sub_array["cpf"]           = $row["cpf"];
    $sub_array["cel"]           = $row["cel"];
    $sub_array["contrato"]      = $row["contrato"];
    $sub_array["divulga"]       = $row["divulga"];
    $sub_array["desativado"]    = $row["desativado"];
    $sub_array["aceita_parce_individ"]    = $row["aceita_parce_individ"];
    /*if($row["divulga"] == 'S'){
        $sub_array["divulga"]       = '<input type="checkbox" checked="checked" name="chkdivulga" id="'.$row["codigo"].'" class="form-check-input chkdivulga" data-toggle="tooltip" data-placement="top" title="Divulga"></button>';
    }else if($row["divulga"] == 'N'){
        $sub_array["divulga"]       = '<input type="checkbox" name="chkdivulga" id="'.$row["codigo"].'" class="form-check-input chkdivulga" data-toggle="tooltip" data-placement="top" title="Divulga"></button>';
    }
    if($row["desativado"] == true){
        $sub_array["desativado"]       = '<input type="checkbox" checked="checked" name="chkdesativado" id="'.$row["codigo"].'" class="form-check-input chkdesativado" data-toggle="tooltip" data-placement="top" title="Desativado"></button>';
    }else if($row["desativado"] == false){
        $sub_array["desativado"]       = '<input type="checkbox" name="chkdesativado" id="'.$row["codigo"].'" class="form-check-input chkdesativado" data-toggle="tooltip" data-placement="top" title="Desativado"></button>';
    }*/ 
    $sub_array["botaover"]      = '<button type="button" name="btnvisualiza" id="'.$row["codigo"].'" class="btn btn-primary glyphicon glyphicon-eye-open btn-xs btnvisualiza" data-toggle="tooltip" data-placement="top" title="Visualizar"></button>';
    $sub_array["botao"]         = '<button type="button" name="updateconvenio" id="'.$row["codigo"].'" class="btn btn-warning glyphicon glyphicon-edit btn-xs updateconvenio" data-toggle="tooltip" data-placement="top" title="Alterar"></button>';
    $sub_array["botaosenha"]    = '<button type="button" name="btnsenha" id="'.$row["codigo"].'" class="btn btn-facebook glyphicon glyphicon-credit-card btn-xs btnsenha" data-toggle="tooltip" data-placement="top" title="Senha do cartão"></button>';
    $sub_array["botaobanco"]    = '<button type="button" name="btnbanco" id="'.$row["codigo"].'" class="btn btn-info glyphicon glyphicon-home btn-xs btnbanco" data-toggle="tooltip" data-placement="top" title="Banco"></button>';
    $someArray["data"][] = $sub_array;

}

echo json_encode($someArray);