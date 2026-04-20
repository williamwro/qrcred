<?PHP
header("Content-type: application/json; charset=utf-8");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Garantir que a conexão use UTF-8
$pdo->exec("SET NAMES 'UTF8'");
$someArray = array();
$i=1;
$divisao = $_GET["divisao"] ?? $_POST["divisao"] ?? null;
$mes = $_GET["mes"] ?? $_POST["mes"] ?? null; // Receber o parâmetro do mês via GET ou POST

// Debug para verificar parâmetros recebidos
error_log("DEBUG producao_empregador.php - Divisao: " . var_export($divisao, true));
error_log("DEBUG producao_empregador.php - Mes: " . var_export($mes, true));
error_log("DEBUG producao_empregador.php - GET params: " . var_export($_GET, true));
error_log("DEBUG producao_empregador.php - POST params: " . var_export($_POST, true));
error_log("DEBUG producao_empregador.php - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("DEBUG producao_empregador.php - REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("DEBUG producao_empregador.php - QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'empty'));

// Se o mês foi fornecido, filtrar apenas empregadores que tenham valores em conta nesse mês
if (!empty($mes)) {
    $sql = $pdo->prepare("
        SELECT DISTINCT e.id, e.nome, e.responsavel, e.telefone, e.abreviacao, e.id_divisao
        FROM sind.empregador e
        INNER JOIN sind.conta c ON c.empregador = e.id
        WHERE e.id_divisao = :divisao 
        AND c.mes = :mes
        AND c.valor > 0
        AND (c.aprovado = true OR c.aprovado IS NULL)
        ORDER BY e.nome
    ");
    $sql->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    $sql->bindParam(':mes', $mes, PDO::PARAM_STR);
} else {
    // Se não há mês especificado, retornar todos os empregadores da divisão (comportamento original)
    $sql = $pdo->prepare("
        SELECT id, nome, responsavel, telefone, abreviacao, id_divisao 
        FROM sind.empregador 
        WHERE id_divisao = :divisao 
        ORDER BY nome
    ");
    $sql->bindParam(':divisao', $divisao, PDO::PARAM_INT);
}

try {
    $sql->execute();
    error_log("DEBUG producao_empregador.php - SQL executado com sucesso");
    
    while($row = $sql->fetch()) {
        $someArray[$i] = array_map(function($value) {
            if (is_string($value)) {
                // Detectar a codificação atual e converter para UTF-8 se necessário
                $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                
                // Debug da codificação (apenas para campo nome)
                if (isset($row['nome']) && $value === $row['nome']) {
                    error_log("DEBUG - Nome original: " . var_export($value, true));
                    error_log("DEBUG - Encoding detectado: " . var_export($encoding, true));
                }
                
                // Tentar conversão se não for UTF-8
                if ($encoding && $encoding !== 'UTF-8') {
                    $converted = mb_convert_encoding($value, 'UTF-8', $encoding);
                    if (isset($row['nome']) && $value === $row['nome']) {
                        error_log("DEBUG - Nome convertido: " . var_export($converted, true));
                    }
                    return $converted;
                }
                
                // Se a detecção falhou ou já é UTF-8, verificar se há caracteres problemáticos
                if (!mb_check_encoding($value, 'UTF-8')) {
                    // Forçar conversão de ISO-8859-1 para UTF-8
                    $converted = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                    if (isset($row['nome']) && $value === $row['nome']) {
                        error_log("DEBUG - Nome forçado ISO->UTF8: " . var_export($converted, true));
                    }
                    return $converted;
                }
                
                return $value; // Já está em UTF-8 válido
            }
            return $value;
        }, $row);
        $i++;
    }
    
    error_log("DEBUG producao_empregador.php - Número de registros encontrados: " . ($i-1));
    
    // Garantir que o JSON seja codificado corretamente com UTF-8
    echo json_encode($someArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    error_log("DEBUG producao_empregador.php - ERRO SQL: " . $e->getMessage());
    echo json_encode(['error' => 'Erro na consulta: ' . $e->getMessage()]);
}