<?PHP
header('Content-Type: application/json; charset=utf-8');
include "../../php/banco.php";
include "../../php/funcoes.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id_associado  = isset($_POST['id_associado']) ? (int)$_POST['id_associado'] : 0;
    $id_divisao    = isset($_POST['id_divisao']) ? (int)$_POST['id_divisao'] : 0;
    $id_empregador = isset($_POST['id_empregador']) ? (int)$_POST['id_empregador'] : 0;
    $cpf           = isset($_POST['cpf']) ? $_POST['cpf'] : '';

    if (!$id_associado || !$id_divisao || !$id_empregador || !$cpf) {
        echo json_encode([ 'success' => false, 'message' => 'Parâmetros inválidos' ]);
        exit;
    }

    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);

    // Atualizar CPF no cadastro do associado
    $sql = "UPDATE sind.associado
               SET cpf = :cpf
             WHERE id = :id
               AND id_divisao = :divisao
               AND empregador = :empregador";

    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        ':cpf' => $cpf_limpo,
        ':id' => $id_associado,
        ':divisao' => $id_divisao,
        ':empregador' => $id_empregador
    ]);

    if ($ok && $stmt->rowCount() > 0) {
        echo json_encode([ 'success' => true ]);
    } else {
        echo json_encode([ 'success' => false, 'message' => 'Nenhuma linha atualizada. Verifique os filtros.' ]);
    }
} catch (PDOException $e) {
    echo json_encode([ 'success' => false, 'message' => 'Erro de banco: ' . $e->getMessage() ]);
} catch (Exception $e) {
    echo json_encode([ 'success' => false, 'message' => 'Erro geral: ' . $e->getMessage() ]);
}
?>


