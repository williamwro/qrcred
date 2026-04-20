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

    $query = "SELECT ant.id, ant.matricula, ant.empregador, ant.mes, ant.data_solicitacao, ant.valor, ant.aprovado, 
		ant.data_aprovacao, ant.celular, ant.valor_taxa, ant.valor_a_descontar, ant.chave_pix, 
		ant.id_divisao, ant.hora, ass.nome as nome_associado, emp.nome as nome_empregador, ass.id as id_associado
                FROM sind.antecipacao ant
                JOIN sind.associado ass ON ass.codigo = ant.matricula
                JOIN sind.empregador emp ON emp.id = ant.empregador
                WHERE ant.matricula = :matricula
                AND ant.empregador = :empregador
                AND ant.id = :id_entecipacao";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':matricula', $_POST['cod_associado']);
    $stmt->bindParam(':empregador', $_POST['empregador']);
    $stmt->bindParam(':id_entecipacao', $_POST['id_entecipacao']);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $std->id               = $row["id"];
        $std->matricula        = $row["matricula"];
        $std->nome             = htmlspecialchars($row["nome_associado"] ?? ''); // Corrigido para nome_associado
        $std->id_empregador    = (int)$row["empregador"];
        $std->nome_empregador  = $row["nome_empregador"];
        $std->mes              = $row["mes"];
        $std->data_solicitacao = date('d/m/Y', strtotime($row["data_solicitacao"]));
        $std->valor            = str_replace('.',',',$row["valor"]);
        $std->valor_taxa       = str_replace('.',',',$row["valor_taxa"]);
        $std->valor_a_descontar = str_replace('.',',',$row["valor_a_descontar"]);
        $std->aprovado         = $row["aprovado"];
        $std->data_aprovacao   = $row["data_aprovacao"];
        $std->celular          = $row["celular"];
        $std->chave_pix        = $row["chave_pix"];
    }
    echo json_encode($std);
}