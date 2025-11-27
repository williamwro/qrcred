<?PHP
// Configurar cabeçalho para JSON
header('Content-Type: application/json; charset=utf-8');

include "../../php/banco.php";
include "../../php/funcoes.php";
// Suprimir warnings para não quebrar o JSON response
ini_set('display_errors', false);
error_reporting(E_ERROR | E_PARSE);
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["id_agendamento"])){
    $std = new stdClass();
    $id_agendamento = $_POST["id_agendamento"];

    $query = "SELECT ag.id, ag.cod_associado, ag.id_empregador, ag.data_solicitacao, ag.data_agendada, ag.cod_convenio, ag.status, ag.profissional, ag.especialidade, ag.convenio_nome,
                     assoc.nome as nome_associado, assoc.empregador as empregador_id,
                     emp.nome as nome_empregador, emp.abreviacao as abreviacao_empregador
                FROM sind.agendamento ag
                LEFT JOIN sind.associado assoc ON ag.cod_associado = assoc.codigo AND ag.id_empregador = assoc.empregador
                LEFT JOIN sind.empregador emp ON assoc.empregador = emp.id
                WHERE ag.id = :id_agendamento";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id_agendamento', $id_agendamento, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $std->id               = $row["id"];
        $std->cod_associado    = $row["cod_associado"];
        $std->id_empregador    = $row["id_empregador"];
        
        // Dados do associado
        $std->nome_associado = $row["nome_associado"] ?? '';
        
        // Dados do empregador
        $std->nome_empregador = $row["nome_empregador"] ?? '';
        $std->abreviacao_empregador = $row["abreviacao_empregador"] ?? '';
        
        // Formatação da data de solicitação
        if($row["data_solicitacao"] != null){
            $std->data_solicitacao = date('Y-m-d\TH:i', strtotime($row["data_solicitacao"]));
        }else{
            $std->data_solicitacao = "";
        }
        
        // Formatação da data agendada
        if($row["data_agendada"] != null){
            $std->data_agendada = date('Y-m-d\TH:i', strtotime($row["data_agendada"]));
        }else{
            $std->data_agendada = "";
        }
        
        $std->cod_convenio     = $row["cod_convenio"];
        $std->status           = $row["status"];
        $std->profissional     = htmlspecialchars($row["profissional"] ?? '');
        $std->especialidade    = htmlspecialchars($row["especialidade"] ?? '');
        $std->convenio_nome    = htmlspecialchars($row["convenio_nome"] ?? '');
    }
    echo json_encode($std);
}
?> 