<?PHP
// Permitir acesso de qualquer origem
header("Access-Control-Allow-Origin: *");

// Ou para permitir apenas de origens específicas:
// header("Access-Control-Allow-Origin: http://localhost:3000");

// Definir métodos HTTP permitidos
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

// Permitir headers específicos
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Definir por quanto tempo (em segundos) o navegador pode armazenar em cache os resultados da preflight request
header("Access-Control-Max-Age: 86400");

//header("Content-type: application/json");

$std = new stdClass();
include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Buscar o mês corrente
    $query = $pdo->query("SELECT id, abreviacao, id_divisao, status 
                         FROM sind.mes_corrente 
                         WHERE status = 1 
                         ORDER BY id DESC 
                         LIMIT 1");
    
    $mesCorrente = $query->fetch(PDO::FETCH_ASSOC);

    if ($mesCorrente) {
        // Buscar a taxa de antecipação
        $taxa_query = $pdo->query("SELECT porcentagem FROM sind.taxa_antecipacao ORDER BY id DESC LIMIT 1");
        $taxa = $taxa_query->fetch(PDO::FETCH_ASSOC);

        // Buscar o email de antecipação
        $email_query = $pdo->query("SELECT email FROM sind.email_antecipacao ORDER BY id DESC LIMIT 1");
        $email = $email_query->fetch(PDO::FETCH_ASSOC);

        // Preencher o objeto de resposta
        $std->id = $mesCorrente['id'];
        $std->abreviacao = $mesCorrente['abreviacao'];
        $std->id_divisao = $mesCorrente['id_divisao'];
        $std->status = $mesCorrente['status'];
        $std->porcentagem = $taxa ? $taxa['porcentagem'] : null;
        $std->email = $email ? $email['email'] : null;
    } else {
        // Se não encontrar mês corrente
        $std->error = 'Nenhum mês corrente encontrado';
    }
} catch (PDOException $e) {
    // Em caso de erro na conexão ou consulta
    $std->error = 'Erro ao conectar com o banco de dados: ' . $e->getMessage();
}

echo json_encode($std);