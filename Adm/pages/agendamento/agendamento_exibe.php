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

// Log de debug
error_log("========== AGENDAMENTO_EXIBE.PHP ==========");
error_log("POST recebido: " . print_r($_POST, true));

if(isset($_POST["id_agendamento"])){
    $std = new stdClass();
    $id_agendamento = $_POST["id_agendamento"];
    error_log("ID do agendamento recebido: " . $id_agendamento);

    $query = "SELECT ag.id, ag.cod_associado, ag.id_empregador, ag.data_solicitacao, ag.data_agendada, ag.data_pretendida, ag.cod_convenio, ag.status, ag.profissional, ag.especialidade, ag.convenio_nome,
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
        error_log("Dados encontrados no banco: " . print_r($row, true));
        error_log("DEBUG - Valor bruto data_agendada: [" . var_export($row["data_agendada"], true) . "]");
        error_log("DEBUG - Tipo de data_agendada: " . gettype($row["data_agendada"]));
        error_log("DEBUG - is_null: " . (is_null($row["data_agendada"]) ? 'SIM' : 'NÃO'));
        error_log("DEBUG - empty: " . (empty($row["data_agendada"]) ? 'SIM' : 'NÃO'));
        
        $std->id               = $row["id"];
        $std->cod_associado    = $row["cod_associado"];
        $std->id_empregador    = $row["id_empregador"];
        
        // Dados do associado
        $std->nome_associado = $row["nome_associado"] ?? '';
        
        // Dados do empregador
        $std->nome_empregador = $row["nome_empregador"] ?? '';
        $std->abreviacao_empregador = $row["abreviacao_empregador"] ?? '';
        
        // Formatação da data de solicitação (TIMESTAMP WITH TIME ZONE)
        if($row["data_solicitacao"] != null && $row["data_solicitacao"] != ''){
            // Remover timezone se existir para evitar problemas de conversão
            $data_limpa = preg_replace('/([+-]\d{2}:\d{2}|\.\d+)$/', '', $row["data_solicitacao"]);
            
            try {
                $datetime = new DateTime($data_limpa);
                $std->data_solicitacao = $datetime->format('Y-m-d\TH:i');
                error_log("Data solicitação formatada: " . $std->data_solicitacao);
            } catch (Exception $e) {
                error_log("Erro ao formatar data_solicitacao: " . $e->getMessage());
                $std->data_solicitacao = "";
            }
        }else{
            $std->data_solicitacao = "";
        }
        
        // Formatação da data agendada
        if($row["data_agendada"] != null && $row["data_agendada"] != ''){
            try {
                // Converter para DateTime e formatar para datetime-local (Y-m-d\TH:i)
                $datetime = new DateTime($row["data_agendada"]);
                $std->data_agendada = $datetime->format('Y-m-d\TH:i');
                error_log("Data agendada original: " . $row["data_agendada"]);
                error_log("Data agendada formatada: " . $std->data_agendada);
            } catch (Exception $e) {
                error_log("Erro ao formatar data_agendada: " . $e->getMessage());
                error_log("Valor recebido: " . $row["data_agendada"]);
                $std->data_agendada = "";
            }
        }else{
            $std->data_agendada = "";
            error_log("Data agendada está NULL ou vazia no banco");
        }
        
        // Formatação da data pretendida
        if($row["data_pretendida"] != null && $row["data_pretendida"] != ''){
            try {
                // Converter para DateTime e formatar para datetime-local (Y-m-d\TH:i)
                $datetime = new DateTime($row["data_pretendida"]);
                $std->data_pretendida = $datetime->format('Y-m-d\TH:i');
                error_log("Data pretendida formatada: " . $std->data_pretendida);
            } catch (Exception $e) {
                error_log("Erro ao formatar data_pretendida: " . $e->getMessage());
                $std->data_pretendida = "";
            }
        }else{
            $std->data_pretendida = "";
        }
        
        $std->cod_convenio     = $row["cod_convenio"];
        $std->status           = $row["status"];
        $std->profissional     = htmlspecialchars($row["profissional"] ?? '');
        $std->especialidade    = htmlspecialchars($row["especialidade"] ?? '');
        $std->convenio_nome    = htmlspecialchars($row["convenio_nome"] ?? '');
        
        error_log("Objeto final a ser retornado: " . json_encode($std));
    } else {
        error_log("ERRO: Nenhum registro encontrado para ID: " . $id_agendamento);
    }
    echo json_encode($std);
}
?> 