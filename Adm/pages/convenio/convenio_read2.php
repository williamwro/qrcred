<?PHP
require_once '../../../functions.php';
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
include "../../php/tenant_security.php";

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// SEGURANÇA MULTI-TENANT: Validar divisão
$tenantSec = new TenantSecurity($pdo);

$someArray = array();

// Debug: Log all received parameters
error_log("convenio_read2.php - POST data: " . print_r($_POST, true));
error_log("convenio_read2.php - GET data: " . print_r($_GET, true));

// Capture division parameter from POST or GET request - COM VALIDAÇÃO DE SEGURANÇA
$divisao = null;
$divisao_nome = null;

if (isset($_POST['divisao']) && !empty($_POST['divisao'])) {
    $divisao = $tenantSec->getSecureDivisao($_POST['divisao']);
    error_log("convenio_read2.php - Found divisao in POST (validated): " . $divisao);
}
if (isset($_GET['divisao']) && !empty($_GET['divisao'])) {
    $divisao = $tenantSec->getSecureDivisao($_GET['divisao']);
    error_log("convenio_read2.php - Found divisao in GET (validated): " . $divisao);
}
if (isset($_POST['divisao_nome']) && !empty($_POST['divisao_nome'])) {
    $divisao_nome = $_POST['divisao_nome'];
    error_log("convenio_read2.php - Found divisao_nome in POST: " . $divisao_nome);
}
if (isset($_GET['divisao_nome']) && !empty($_GET['divisao_nome'])) {
    $divisao_nome = $_GET['divisao_nome'];
    error_log("convenio_read2.php - Found divisao_nome in GET: " . $divisao_nome);
}

// Build query with optional division filter
$query = 'SELECT codigo,razaosocial,nomefantasia,endereco,bairro,telefone,data_cadastro,cidade,cnpj,email,contato,registro,cpf,cel,contrato,desativado,divulga,aceita_parce_individ,divisao FROM sind.convenio';

if ($divisao !== null && $divisao !== '') {
    $query .= ' WHERE divisao = :divisao';
}

$statment = $pdo->prepare($query);

if ($divisao !== null && $divisao !== '') {
    $statment->bindParam(':divisao', $divisao);
}

$statment->execute();

$result = $statment->fetchAll();

$data = array();

$linhas_filtradas = $statment->rowCount();

// Verificar se não há dados para a divisão informada
if ($linhas_filtradas == 0 && $divisao !== null && $divisao !== '') {
    // Buscar nome da divisão para mensagem personalizada
    $divisao_query = "SELECT nome FROM sind.divisao WHERE id_divisao = :divisao";
    $divisao_stmt = $pdo->prepare($divisao_query);
    $divisao_stmt->bindParam(':divisao', $divisao);
    $divisao_stmt->execute();
    $divisao_info = $divisao_stmt->fetch();
    
    $divisao_nome_msg = $divisao_info ? $divisao_info['nome'] : "Divisão " . $divisao;
    
    // Retornar resposta com mensagem personalizada
    $someArray = array(
        "data" => array(),
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "message" => "A " . $divisao_nome_msg . " não possui estabelecimentos cadastrados.",
        "empty_division" => true
    );
    
    echo json_encode($someArray);
    exit;
}

foreach ($result as $row){
    $sub_array = array();

    $sub_array["codigo"]        = $row["codigo"];
    $sub_array["razaosocial"]   = htmlspecialchars(substr($row["razaosocial"] ?? '',0,50));
    $sub_array["nomefantasia"]  = htmlspecialchars(substr($row["nomefantasia"] ?? '',0,50));
    $sub_array["endereco"]      = htmlspecialchars(substr($row["endereco"] ?? '',0,30));
    $sub_array["bairro"]        = htmlspecialchars($row["bairro"] ?? '');
    $sub_array["telefone"]      = $row["telefone"];
    $sub_array["data_cadastro"] = $row["data_cadastro"];
    $sub_array["cidade"]        = htmlspecialchars($row["cidade"] ?? '');
    $sub_array["cnpj"]          = $row["cnpj"];
    $sub_array["email"]         = $row["email"];
    $sub_array["contato"]       = htmlspecialchars($row["contato"] ?? '');
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