<?PHP
require '../../php/banco.php';

$divisao = isset($_POST['divisao']) ? $_POST['divisao'] : 0;
$bloqueio = isset($_POST['bloqueio']) ? $_POST['bloqueio'] : 0;

$response = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Atualiza todos os empregadores da divisão
    $sql = "UPDATE sind.empregador SET bloqueio = :bloqueio WHERE divisao = :divisao";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':bloqueio', $bloqueio, PDO::PARAM_INT);
    $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    $stmt->execute();
    
    $total_afetados = $stmt->rowCount();
    
    $response->resultado = "sucesso";
    $response->total = $total_afetados;
    $response->mensagem = "Operação realizada com sucesso";
    
} catch (PDOException $erro) {
    $response->resultado = "erro";
    $response->total = 0;
    $response->mensagem = "Erro ao atualizar registros: " . $erro->getMessage();
}

echo json_encode($response);
?>
