<?PHP
header("Content-type: application/json");
require '../../php/banco.php';
ini_set('display_errors', true);
error_reporting(E_ALL);

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
date_default_timezone_set('America/Sao_Paulo');

// Obtém o ID da divisão
$divisao = isset($_POST['divisao']) ? $_POST['divisao'] : 0;

// Status padrão para novos cartões
$_codsituacao = 4;

// Data atual formatada para gravação
$data2 = new DateTime();
$data3 = $data2->format('d-m-Y');
$data4 = new DateTime($data3);
$data = $data4->format('d/m/Y');
$data = converte_data($data);

// Função para converter data para formato do banco
function converte_data($date) {
    return substr($date,6,4).'-'.substr($date,3,2).'-'.substr($date,0,2).' 00:00:00';
}

// Função para gerar número de cartão único
function gerarNumeroCartao($pdo) {
    $existe_cartao = "sim";
    $tentativas = 0;
    
    while ($existe_cartao === "sim" && $tentativas < 100) {
        // Gera um número aleatório de 10 dígitos
        $cartao = mt_rand(1111111111, 9999999999);
        
        // Verifica se o número já existe
        $query = "SELECT COUNT(*) as total FROM sind.c_cartaoassociado WHERE cod_verificacao = :cartao";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':cartao', $cartao, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] == 0) {
            $existe_cartao = "nao";
            return strval($cartao);
        }
        
        $tentativas++;
    }
    
    // Se não conseguir gerar em 100 tentativas, retorna erro
    return false;
}

try {
    // Inicia transação
    $pdo->beginTransaction();
    
    // Busca todos os associados que não possuem cartão
    $sql = "SELECT a.codigo, a.nome, a.empregador, a.id_divisao 
            FROM sind.associado a 
            WHERE a.id_divisao = :divisao 
            AND NOT EXISTS (
                SELECT 1 FROM sind.c_cartaoassociado c 
                WHERE c.cod_associado = a.codigo 
                AND c.empregador = a.empregador
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    $stmt->execute();
    $associados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contadores
    $total_associados = count($associados);
    $cartoes_gerados = 0;
    $erros = 0;
    
    // SQL para inserção de cartões
    $sql_insert = "INSERT INTO sind.c_cartaoassociado(
                      cod_situacaocartao, 
                      cod_associado, 
                      cod_verificacao, 
                      empregador, 
                      data_pedido, 
                      cod_situacao2, 
                      id_divisao
                   ) VALUES (
                      :cod_situacaocartao,
                      :cod_associado,
                      :cod_verificacao,
                      :empregador,
                      :data_pedido,
                      :cod_situacao2,
                      :id_divisao
                   )";
    
    $stmt_insert = $pdo->prepare($sql_insert);
    
    // Percorre cada associado e gera um cartão
    foreach ($associados as $associado) {
        // Gera número de cartão único
        $cartao = gerarNumeroCartao($pdo);
        
        if ($cartao) {
            // Insere o novo cartão
            $stmt_insert->bindParam(':cod_situacaocartao', $_codsituacao, PDO::PARAM_INT);
            $stmt_insert->bindParam(':cod_associado', $associado['codigo'], PDO::PARAM_STR);
            $stmt_insert->bindParam(':cod_verificacao', $cartao, PDO::PARAM_STR);
            $stmt_insert->bindParam(':empregador', $associado['empregador'], PDO::PARAM_INT);
            $stmt_insert->bindParam(':data_pedido', $data, PDO::PARAM_STR);
            $stmt_insert->bindParam(':cod_situacao2', $_codsituacao, PDO::PARAM_INT);
            $stmt_insert->bindParam(':id_divisao', $associado['id_divisao'], PDO::PARAM_INT);
            
            $stmt_insert->execute();
            $cartoes_gerados++;
        } else {
            $erros++;
        }
    }
    
    // Commit da transação
    $pdo->commit();
    
    // Resposta com status e contadores
    echo json_encode([
        'status' => 'success',
        'mensagem' => 'Cartões gerados com sucesso!',
        'total_associados' => $total_associados,
        'cartoes_gerados' => $cartoes_gerados,
        'erros' => $erros
    ]);
    
} catch (PDOException $e) {
    // Rollback em caso de erro
    $pdo->rollBack();
    
    echo json_encode([
        'status' => 'error',
        'mensagem' => 'Erro ao gerar cartões: ' . $e->getMessage()
    ]);
} 