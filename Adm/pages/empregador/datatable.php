<?PHP
header('Content-Type: application/json; charset=utf-8');
include "../../php/banco.php";
include "../../php/tenant_security.php";

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// SEGURANÇA MULTI-TENANT: Validar divisão
$tenantSec = new TenantSecurity($pdo);
$divisao = $tenantSec->getSecureDivisao($_POST['divisao'] ?? null);

$someArray = array();
$query = "SELECT empregador.id, 
                 empregador.nome, 
                 empregador.responsavel,
                 empregador.telefone,
                 empregador.abreviacao,
                 empregador.id_divisao,
                 empregador.bloqueio,
                 divisao.nome as nome_divisao,
                 divisao.cidade
            FROM sind.empregador INNER JOIN sind.divisao 
              ON empregador.id_divisao = divisao.id_divisao
           WHERE empregador.id_divisao = ".$divisao." ORDER BY id";
$statment = $pdo->prepare($query);
$statment->execute();
$result = $statment->fetchAll();
$data = array();
$linhas_filtradas = $statment->rowCount();
foreach ($result as $row){
    $sub_array = array();
    $sub_array["id"]           = $row["id"];
    $sub_array["nome"]         = htmlspecialchars($row["nome"], ENT_QUOTES, 'UTF-8');
    $sub_array["responsavel"]  = htmlspecialchars($row["responsavel"], ENT_QUOTES, 'UTF-8');
    $sub_array["telefone"]     = $row["telefone"];
    $sub_array["abreviacao"]   = htmlspecialchars($row["abreviacao"], ENT_QUOTES, 'UTF-8');
    $sub_array["nome_divisao"] = htmlspecialchars($row["nome_divisao"], ENT_QUOTES, 'UTF-8');
    $sub_array["cidade"]       = htmlspecialchars($row["cidade"], ENT_QUOTES, 'UTF-8');
    $sub_array["bloqueio"]     = $row["bloqueio"] == 't' || $row["bloqueio"] == '1' ? 
        '<div class="text-center"><i class="fa fa-lock text-danger" title="Bloqueado"></i></div>' : 
        '<div class="text-center"><i class="fa fa-unlock text-success" title="Liberado"></i></div>';
    $sub_array["botao"]        = '<button type="button" name="update_emp" id="'.$row["id"].'" class="btn btn-warning btn-xs update_emp">Alterar</button>';
    $someArray["data"][] = $sub_array;
}
echo json_encode($someArray, JSON_UNESCAPED_UNICODE);