<?PHP
include "../../php/banco.php";
include "../../php/funcoes.php";
ini_set('display_errors', true);
error_reporting(E_ALL);
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$tem_cadastro_conta = false;
if(isset($_POST["cod_associado"])){
    $std = new stdClass();
    $cod_associado = $_POST["cod_associado"];
    $empregador = $_POST["empregador"];
    $id_entecipacao = $_POST["id_entecipacao"];


    
    $query = "SELECT ant.id, ant.matricula, ass.nome, ant.empregador as id_empregador, 
                     emp.nome as nome_empregador, ant.mes, ant.data_solicitacao, ant.valor, 
                     ant.aprovado, ant.data_aprovacao, ant.celular
                FROM sind.antecipacao ant
                JOIN sind.associado ass ON ass.codigo = ant.matricula
                JOIN sind.empregador emp ON emp.id = ant.empregador
               WHERE ant.matricula = '".$cod_associado."' AND ant.empregador = ".$empregador." AND ant.id = ".$id_entecipacao.";";
    $statment = $pdo->prepare($query);

    $statment->execute();
    $result = $statment->fetchAll();
    $salario='';
    $linha = array();

    foreach ($result as $row){
        $std->id               = $row["id"];
        $std->matricula         = $row["matricula"];
        $std->nome              = htmlspecialchars($row["nome"]);
        $std->id_empregador     = (int)$row["id_empregador"];
        $std->nome_empregador   = $row["nome_empregador"];
        $std->mes               = $row["mes"];
        $std->data_solicitacao  = date('d/m/Y', strtotime($row["data_solicitacao"]));
        $std->valor             = (float)str_replace('.',',',$row["valor"]);
        $std->aprovado          = $row["aprovado"];
        $std->data_aprovacao    = $row["data_aprovacao"];
        $std->celular           = $row["celular"];
    }
    echo json_encode($std);}