<?PHP
// Headers CORS e Content-Type
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");
header("Content-Type: application/json; charset=utf-8");

try {
    include "Adm/php/banco.php";
    
    if (!class_exists('Banco')) {
        throw new Exception('Classe Banco não encontrada');
    }
    
    $pdo = Banco::conectar_postgres();
    
    if (!$pdo) {
        throw new Exception('Falha na conexão com o banco de dados');
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Receber parâmetro divisao
    $divisao = isset($_POST['divisao']) ? $_POST['divisao'] : (isset($_GET['divisao']) ? $_GET['divisao'] : null);
    
    if (!$divisao || !is_numeric($divisao)) {
        // ESTRUTURA ORIGINAL para compatibilidade
        $std = new stdClass();
        $std->error = 'Parâmetro divisao é obrigatório e deve ser numérico';
        echo json_encode($std);
        exit;
    }
    
    // Buscar mês corrente
    $query = $pdo->prepare("SELECT id, abreviacao, id_divisao, status 
                           FROM sind.mes_corrente 
                           WHERE status = 1 AND id_divisao = :divisao
                           ORDER BY id DESC 
                           LIMIT 1");
    $query->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    $query->execute();
    
    $mesCorrente = $query->fetch(PDO::FETCH_ASSOC);

    if (!$mesCorrente) {
        // ESTRUTURA ORIGINAL para compatibilidade
        $std = new stdClass();
        $std->error = 'Nenhum mês corrente encontrado para a divisão ' . $divisao;
        echo json_encode($std);
        exit;
    }

    // Buscar dados adicionais com tratamento de erro
    $porcentagem = null;
    $email = null;
    
    try {
        $taxa_query = $pdo->query("SELECT porcentagem FROM sind.taxa_antecipacao ORDER BY id DESC LIMIT 1");
        $taxa = $taxa_query->fetch(PDO::FETCH_ASSOC);
        $porcentagem = $taxa ? $taxa['porcentagem'] : null;
    } catch (PDOException $e) {
        error_log('Erro ao buscar taxa de antecipação: ' . $e->getMessage());
    }

    try {
        $email_query = $pdo->query("SELECT email FROM sind.email_antecipacao ORDER BY id DESC LIMIT 1");
        $email_result = $email_query->fetch(PDO::FETCH_ASSOC);
        $email = $email_result ? $email_result['email'] : null;
    } catch (PDOException $e) {
        error_log('Erro ao buscar email de antecipação: ' . $e->getMessage());
    }

    // ESTRUTURA ORIGINAL para manter compatibilidade
    $std = new stdClass();
    $std->id = (int)$mesCorrente['id'];
    $std->abreviacao = $mesCorrente['abreviacao'];
    $std->id_divisao = (int)$mesCorrente['id_divisao'];
    $std->status = (int)$mesCorrente['status'];
    $std->porcentagem = $porcentagem;
    $std->email = $email;

} catch (PDOException $e) {
    $std = new stdClass();
    $std->error = 'Erro de banco de dados: ' . $e->getMessage();
    error_log('Erro PDO em mes_corrente_app.php: ' . $e->getMessage());
} catch (Exception $e) {
    $std = new stdClass();
    $std->error = 'Erro interno: ' . $e->getMessage();
    error_log('Erro geral em mes_corrente_app.php: ' . $e->getMessage());
}

echo json_encode($std, JSON_UNESCAPED_UNICODE);
?>