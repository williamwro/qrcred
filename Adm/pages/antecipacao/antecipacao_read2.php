<?PHP
session_start();
ini_set('display_errors', true);
error_reporting(E_ALL);
/* cSpell:disable */
include "../../php/banco.php";
include "../../php/funcoes.php";
include "../../php/tenant_security.php";

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// SEGURANÇA MULTI-TENANT: Validar divisão
$tenantSec = new TenantSecurity($pdo);
$divisao = $tenantSec->getDivisaoAutenticada();

$someArray = array();

// Construir filtros
$filtros = array();
$filtros[] = "emp.id_divisao = :divisao";

if($_POST['id_situacao'] == "true" || $_POST['id_situacao'] == "false" ){
    $filtros[] = "ant.aprovado = ".$_POST['id_situacao'];
}else if($_POST['id_situacao'] == "null" || $_POST['id_situacao'] == "" ){
    $filtros[] = "ant.aprovado IS NULL";
}

// Filtro por mês
if(isset($_POST['mes_filtro']) && $_POST['mes_filtro'] != 'todos' && !empty($_POST['mes_filtro'])){
    $filtros[] = "ant.mes = :mes_filtro";
}

$tipo_sql = "WHERE " . implode(" AND ", $filtros);
/* cSpell:enable */
//$divisao = $_POST["divisao"];   


$query = "SELECT DISTINCT
                ant.id,
                ant.matricula,
                (SELECT a.nome 
                FROM sind.associado a 
                WHERE a.codigo = ant.matricula AND a.empregador = ant.empregador
                LIMIT 1) AS nome_associado,
                (SELECT a.id 
                FROM sind.associado a 
                WHERE a.codigo = ant.matricula AND a.empregador = ant.empregador
                LIMIT 1) AS associado_id,
                (SELECT a.id_divisao 
                FROM sind.associado a 
                WHERE a.codigo = ant.matricula AND a.empregador = ant.empregador
                LIMIT 1) AS associado_id_divisao,
                (SELECT a.limite 
                FROM sind.associado a 
                WHERE a.codigo = ant.matricula AND a.empregador = ant.empregador
                LIMIT 1) AS limite,
                (SELECT a.salario 
                FROM sind.associado a 
                WHERE a.codigo = ant.matricula AND a.empregador = ant.empregador
                LIMIT 1) AS salario,
                (SELECT a.cpf 
                FROM sind.associado a 
                WHERE a.codigo = ant.matricula AND a.empregador = ant.empregador
                LIMIT 1) AS cpf,
                (SELECT a.rg 
                FROM sind.associado a 
                WHERE a.codigo = ant.matricula AND a.empregador = ant.empregador
                LIMIT 1) AS rg,
                (SELECT a.cidade 
                FROM sind.associado a 
                WHERE a.codigo = ant.matricula AND a.empregador = ant.empregador
                LIMIT 1) AS cidade,
                (SELECT a.uf 
                FROM sind.associado a 
                WHERE a.codigo = ant.matricula AND a.empregador = ant.empregador
                LIMIT 1) AS uf,
                (SELECT a.telres 
                FROM sind.associado a 
                WHERE a.codigo = ant.matricula AND a.empregador = ant.empregador
                LIMIT 1) AS telres,
                (SELECT a.telcom 
                FROM sind.associado a 
                WHERE a.codigo = ant.matricula AND a.empregador = ant.empregador
                LIMIT 1) AS telcom,
                (SELECT a.cel 
                FROM sind.associado a 
                WHERE a.codigo = ant.matricula AND a.empregador = ant.empregador
                LIMIT 1) AS cel,
                ant.empregador AS id_empregador,
                emp.nome AS nome_empregador,
                ant.mes,
                ant.data_solicitacao,
                ant.valor,
                ant.valor_taxa,
                ant.valor_a_descontar,
                ant.aprovado,
                ant.data_aprovacao,
                ant.celular,
                ant.chave_pix,
                ant.hora,
                ant.id_associado
            FROM sind.antecipacao ant
            JOIN sind.empregador emp 
            ON emp.id = ant.empregador
            ".$tipo_sql;

$statment = $pdo->prepare($query);
$statment->bindParam(':divisao', $divisao, PDO::PARAM_INT);

// Bind do parâmetro do mês se foi aplicado o filtro
if(isset($_POST['mes_filtro']) && $_POST['mes_filtro'] != 'todos' && !empty($_POST['mes_filtro'])){
    $statment->bindParam(':mes_filtro', $_POST['mes_filtro'], PDO::PARAM_STR);
}

$statment->execute();

$result = $statment->fetchAll();

$data = array();

$linhas_filtradas = $statment->rowCount();

foreach ($result as $row){
    $sub_array = array();

    $sub_array["id"]              = $row["id"];
    $sub_array["matricula"]       = $row["matricula"];
    $sub_array["nome"]            = htmlspecialchars($row["nome_associado"] ?? '');
    $sub_array["id_empregador"]   = $row["id_empregador"];
    $sub_array["nome_empregador"] = $row["nome_empregador"];
    $sub_array["mes"]             = $row["mes"];
    if($row["data_solicitacao"] != null){
        $sub_array["data_solicitacao"] = date('d/m/Y', strtotime($row["data_solicitacao"]));
    }else{
        $sub_array["data_solicitacao"] = "";
    }
    $sub_array["valor"]           = $row["valor"];
    $sub_array["valor_taxa"]      = $row["valor_taxa"];
    $sub_array["valor_a_descontar"] = $row["valor_a_descontar"];
    if($row["aprovado"] === null){
        $sub_array["aprovado"]    = "Analisando";
    }else if($row["aprovado"] == 1){
        $sub_array["aprovado"]    = "Aprovado";
    }else{
        $sub_array["aprovado"]    = "Reprovado";
    }
    $sub_array["data_aprovacao"]  = $row["data_aprovacao"];
    $sub_array["celular"]         = $row["celular"];
    $sub_array["chave_pix"]       = $row["chave_pix"];
    $sub_array["associado_id"]    = $row["associado_id"];
    $sub_array["associado_id_divisao"] = $row["associado_id_divisao"];
    $sub_array["limite"]          = $row["limite"];
    $sub_array["salario"]         = $row["salario"];
    $sub_array["cpf"]             = $row["cpf"];
    $sub_array["rg"]              = $row["rg"];
    $sub_array["cidade"]          = $row["cidade"];
    $sub_array["uf"]              = $row["uf"];
    $sub_array["telres"]          = $row["telres"];
    $sub_array["telcom"]          = $row["telcom"];
    $sub_array["cel"]             = $row["cel"];
    $sub_array["hora"]            = $row["hora"];
    $sub_array["botao"]           = '<button type="button" name="update_antecipacao" id="'.$row["matricula"].'" class="btn btn-warning glyphicon glyphicon-edit btn-xs update_antecipacao" data-toggle="tooltip" data-placement="top" title="Alterar"></button>';
    $sub_array["botaoexcluir"]    = '<button type="button" name="btnexcluir" data-id="'.$row["id"].'" data-matricula="'.$row["matricula"].'" data-empregador="'.$row["id_empregador"].'" data-mes="'.$row["mes"].'" class="btn btn-danger glyphicon glyphicon-trash btn-xs btnexcluir" data-toggle="tooltip" data-placement="top" title="Excluir" disabled></button>';
    $someArray['data'][] = $sub_array;
}
$pp = json_encode($someArray);
echo json_encode($someArray);