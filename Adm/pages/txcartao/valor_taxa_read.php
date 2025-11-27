<?PHP
/**
 * Lista os valores de taxa de cartão cadastrados
 * Retorna: id, divisao, valor, descricao
 */
header("Content-type: application/json");
require '../../php/banco.php';

// Recebe parâmetros
$divisao = isset($_POST['divisao']) ? $_POST['divisao'] : null;

// Log para debug
error_log("valor_taxa_read.php - Divisão recebida: " . $divisao);

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'UTF8'");
    
    // Monta a query (sem JOIN com divisao, pois não precisamos mais exibir o nome)
    $sql = "SELECT 
                vt.id,
                vt.divisao,
                vt.valor,
                vt.descricao
            FROM sind.valor_taxa_cartao vt";
    
    // Adiciona filtro por divisão se fornecido
    if ($divisao !== null) {
        $sql .= " WHERE vt.divisao = :divisao";
    }
    
    $sql .= " ORDER BY vt.divisao, vt.id";
    
    $stmt = $pdo->prepare($sql);
    
    if ($divisao !== null) {
        $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log para debug
    error_log("valor_taxa_read.php - Registros encontrados: " . count($result));
    
    // Formata os dados para JSON
    $data = array();
    foreach ($result as $row) {
        // Tenta detectar o encoding e converter para UTF-8
        $descricao = $row['descricao'];
        if (!mb_check_encoding($descricao, 'UTF-8')) {
            // Se não está em UTF-8, converte de ISO-8859-1 para UTF-8
            $descricao = mb_convert_encoding($descricao, 'UTF-8', 'ISO-8859-1');
        }
        
        $item = array(
            'id' => $row['id'],
            'divisao' => $row['divisao'],
            'valor' => number_format($row['valor'], 2, ',', '.'),
            'valor_decimal' => $row['valor'],
            'descricao' => $descricao
        );
        $data[] = $item;
    }
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $erro) {
    $response = array(
        "success" => false,
        "message" => "Erro ao buscar valores de taxa",
        "erro" => $erro->getMessage()
    );
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>
