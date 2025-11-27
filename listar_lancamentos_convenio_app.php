<?PHP
// Permitir acesso de qualquer origem
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");
header("Content-Type: application/json");

ini_set('display_errors', true);
error_reporting(E_ALL);

include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Debug: Log da requisição
error_log("=== LISTAR LANÇAMENTOS CONVÊNIO ===");
error_log("GET params: " . print_r($_GET, true));
error_log("POST params: " . print_r($_POST, true));

$response = new stdClass();

try {
    // Obter código do convênio
    $cod_convenio = isset($_GET['cod_convenio']) ? $_GET['cod_convenio'] : (isset($_POST['cod_convenio']) ? $_POST['cod_convenio'] : null);
    
    if (!$cod_convenio) {
        $response->success = false;
        $response->message = "Código do convênio não fornecido";
        echo json_encode($response);
        exit;
    }

    error_log("Código do convênio: " . $cod_convenio);

    // Consulta SQL SEM filtros de data para pegar todos os lançamentos
    $sql = "SELECT 
                c.lancamento,
                c.associado,
                c.convenio,
                c.valor,
                c.data,
                c.hora,
                c.mes,
                c.parcela,
                c.descricao,
                c.data_fatura,
                c.empregador,
                a.nome as nome_associado,
                e.nome as nome_empregador,
                a.cpf as cpf_associado,
                conv.razaosocial,
                conv.cnpj as cnpj
            FROM sind.conta c
            LEFT JOIN sind.associado a ON c.associado = a.codigo AND c.empregador = a.empregador
            INNER JOIN sind.empregador e ON c.empregador = e.id
            INNER JOIN sind.convenio conv ON c.convenio = conv.codigo
            WHERE c.convenio = :cod_convenio
            ORDER BY c.data DESC, c.hora DESC";

    error_log("SQL Query: " . $sql);

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':cod_convenio', $cod_convenio, PDO::PARAM_INT);
    $stmt->execute();

    $lancamentos = [];
    $meses_encontrados = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lancamento = array(
            'lancamento' => intval($row['lancamento']),
            'data' => $row['data'],
            'hora' => $row['hora'],
            'valor' => $row['valor'],
            'associado' => $row['associado'],
            'empregador' => $row['empregador'],
            'mes' => $row['mes'],
            'parcela' => $row['parcela'] ? $row['parcela'] : 0,
            'descricao' => $row['descricao'],
            'data_fatura' => $row['data_fatura'],
            'nome_associado' => $row['nome_associado'],
            'nome_empregador' => $row['nome_empregador'],
            'matricula' => $row['associado'], // Alias para compatibilidade
            'cpf_associado' => $row['cpf_associado'],
            'codigoempregador' => intval($row['empregador']),
            'razaosocial' => $row['razaosocial'],
            'cnpj' => $row['cnpj']
        );
        
        $lancamentos[] = $lancamento;
        
        // Coletar meses únicos para debug
        if ($row['mes'] && !in_array($row['mes'], $meses_encontrados)) {
            $meses_encontrados[] = $row['mes'];
        }
    }

    // Debug: Log dos resultados
    error_log("Total de lançamentos encontrados: " . count($lancamentos));
    error_log("Meses encontrados: " . implode(', ', $meses_encontrados));
    
    // Verificar especificamente se tem AGO/2025
    $tem_ago_2025 = false;
    foreach ($lancamentos as $lanc) {
        if ($lanc['mes'] === 'AGO/2025') {
            $tem_ago_2025 = true;
            break;
        }
    }
    error_log("Tem AGO/2025: " . ($tem_ago_2025 ? 'SIM' : 'NÃO'));

    // Resposta de sucesso
    $response->success = true;
    $response->message = "Lançamentos encontrados com sucesso";
    $response->lancamentos = $lancamentos;
    $response->total = count($lancamentos);
    $response->meses_encontrados = $meses_encontrados;
    $response->debug = array(
        'cod_convenio' => $cod_convenio,
        'total_lancamentos' => count($lancamentos),
        'tem_ago_2025' => $tem_ago_2025,
        'sql_executada' => $sql
    );

} catch (Exception $e) {
    error_log("Erro na consulta: " . $e->getMessage());
    $response->success = false;
    $response->message = "Erro ao buscar lançamentos: " . $e->getMessage();
    $response->error_details = $e->getTrace();
}

echo json_encode($response);