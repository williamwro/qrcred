<?PHP
ini_set('display_errors', true);
error_reporting(E_ALL);

// Configurar cabeçalho para JSON
header('Content-Type: application/json; charset=utf-8');

/* cSpell:disable */
include "../../php/banco.php";
include "../../php/funcoes.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Inicializar array de resposta com estrutura válida para DataTables
    $someArray = array('data' => array());

// Filtros baseados na situação selecionada
// DEBUG: Log do valor recebido
error_log("DEBUG AGENDAMENTO - id_situacao recebido: " . $_POST['id_situacao']);

if($_POST['id_situacao'] == "1"){
    $tipo_sql = "WHERE ag.status = 1";
    error_log("DEBUG AGENDAMENTO - Filtro aplicado: status = 1 (Pendentes)");
}else if($_POST['id_situacao'] == "2"){
    $tipo_sql = "WHERE ag.status = 2";
    error_log("DEBUG AGENDAMENTO - Filtro aplicado: status = 2 (Confirmados)");
}else if($_POST['id_situacao'] == "0"){
    // RadioTodos - mostrar pendentes e confirmados (status 1 e 2)
    $tipo_sql = "WHERE ag.status IN (1, 2)";
    error_log("DEBUG AGENDAMENTO - Filtro RadioTodos aplicado: status 1 e 2 (Pendentes e Confirmados)");
}else{
    // Filtro padrão - mostrar pendentes e confirmados
    $tipo_sql = "WHERE ag.status IN (1, 2)";
    error_log("DEBUG AGENDAMENTO - Filtro padrão aplicado: status 1 e 2");
}
/* cSpell:enable */

$query = "SELECT ag.id, ag.cod_associado, ag.id_empregador, ag.data_solicitacao, ag.data_agendada, ag.cod_convenio, ag.status, ag.profissional, ag.especialidade, ag.convenio_nome,
                 assoc.nome as nome_associado, assoc.empregador as empregador_id,
                 emp.nome as nome_empregador, emp.abreviacao as abreviacao_empregador
            FROM sind.agendamento ag
            LEFT JOIN sind.associado assoc ON ag.cod_associado = assoc.codigo AND ag.id_empregador = assoc.empregador
            LEFT JOIN sind.empregador emp ON assoc.empregador = emp.id
            ".$tipo_sql ."
            ORDER BY ag.id DESC";

// DEBUG: Log da query completa
error_log("DEBUG AGENDAMENTO - Query executada: " . $query);

$statment = $pdo->prepare($query);
$statment->execute();
$result = $statment->fetchAll();

// DEBUG: Log da quantidade de registros retornados
error_log("DEBUG AGENDAMENTO - Registros encontrados: " . count($result));

// DEBUG: Verificar campos disponíveis no primeiro registro
if (count($result) > 0) {
    $primeiro_registro = $result[0];
    error_log("DEBUG AGENDAMENTO - Campos disponíveis no primeiro registro: " . implode(", ", array_keys($primeiro_registro)));
}

foreach ($result as $row) {
    // Preparar array de dados para cada linha
    $data_row = array();
    
    // ID
    $data_row[] = $row["id"];
    
    // Código Associado
    $data_row[] = htmlspecialchars($row["cod_associado"] ?? '');
    
    // Nome Associado
    $data_row[] = htmlspecialchars($row["nome_associado"] ?? '');
    
    // ID Empregador
    $data_row[] = $row["id_empregador"] ?? '';
    
    // Nome Empregador
    $nome_empregador = $row["nome_empregador"] ?? '';
    $abreviacao_empregador = $row["abreviacao_empregador"] ?? '';
    // Mostrar abreviação se existir, senão nome completo
    $empregador_display = !empty($abreviacao_empregador) ? $abreviacao_empregador : $nome_empregador;
    $data_row[] = htmlspecialchars($empregador_display);
    
    // Data Solicitação formatada
    if($row["data_solicitacao"] != null){
        $data_row[] = date('d/m/Y H:i', strtotime($row["data_solicitacao"]));
    }else{
        $data_row[] = "";
    }
    
    // Data Agendada formatada
    if($row["data_agendada"] != null){
        $data_row[] = date('d/m/Y H:i', strtotime($row["data_agendada"]));
    }else{
        $data_row[] = "";
    }
    
    // Código Convênio
    $data_row[] = htmlspecialchars($row["cod_convenio"] ?? '');
    
    // Status com formatação baseado em números
    $status = $row["status"] ?? '';
    $status_class = '';
    $status_texto = '';
    switch($status) {
        case '1':
        case 1:
            $status_class = 'label label-warning';
            $status_texto = 'Pendente';
            break;
        case '2':
        case 2:
            $status_class = 'label label-success';
            $status_texto = 'Confirmado';
            break;
        default:
            $status_class = 'label label-default';
            $status_texto = 'Indefinido';
    }
    $data_row[] = '<span class="'.$status_class.'">'.$status_texto.'</span>';
    
    // Profissional
    $data_row[] = htmlspecialchars($row["profissional"] ?? '');
    
    // Especialidade
    $data_row[] = htmlspecialchars($row["especialidade"] ?? '');
    
    // Convênio Nome
    $data_row[] = htmlspecialchars($row["convenio_nome"] ?? '');
    
    // Botão de Ações
    $id_agendamento = $row["id"];
    $botao_acoes = '';
    
    // Determinar cor e texto do botão baseado no status numérico
    switch($status) {
        case '1':
        case 1:
            $botao_acoes = '<button type="button" class="btn btn-warning btn-xs editar_agendamento" data-id="'.$id_agendamento.'" title="Agendamento pendente - Clique para editar">
                                <span class="glyphicon glyphicon-edit"></span> Pendente
                            </button>';
            break;
        case '2':
        case 2:
            $botao_acoes = '<button type="button" class="btn btn-success btn-xs editar_agendamento" data-id="'.$id_agendamento.'" title="Agendamento confirmado - Clique para editar">
                                <span class="glyphicon glyphicon-ok"></span> Confirmado
                            </button>';
            break;
        default:
            $botao_acoes = '<button type="button" class="btn btn-default btn-xs editar_agendamento" data-id="'.$id_agendamento.'" title="Editar agendamento">
                                <span class="glyphicon glyphicon-edit"></span> Editar
                            </button>';
    }
    
    $data_row[] = $botao_acoes;
    
    // Adicionar linha ao array final
    $someArray['data'][] = $data_row;
}

// DEBUG: Log da quantidade de linhas processadas
error_log("DEBUG AGENDAMENTO - Linhas processadas para DataTables: " . count($someArray['data']));

} catch (Exception $e) {
    error_log("ERRO AGENDAMENTO - Exception: " . $e->getMessage());
    // Em caso de erro, retornar estrutura vazia válida para DataTables
    $someArray = array(
        'data' => array(),
        'error' => 'Erro ao carregar dados: ' . $e->getMessage()
    );
}

// Retornar JSON válido para DataTables
echo json_encode($someArray, JSON_UNESCAPED_UNICODE);
?> 