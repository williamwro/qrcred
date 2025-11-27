<?PHP
/**
 * Salva ou atualiza valores de taxa de cartão
 * Operações: INSERT ou UPDATE
 */
header("Content-type: application/json");
require '../../php/banco.php';

// Recebe parâmetros
$operation = isset($_POST['operation']) ? $_POST['operation'] : '';
$id = isset($_POST['id']) ? $_POST['id'] : null;
$divisao = isset($_POST['divisao']) ? $_POST['divisao'] : null;
$valor = isset($_POST['valor']) ? $_POST['valor'] : '';
$descricao = isset($_POST['descricao']) ? $_POST['descricao'] : '';

// Garante que a descrição está em UTF-8
if (!mb_check_encoding($descricao, 'UTF-8')) {
    $descricao = mb_convert_encoding($descricao, 'UTF-8', 'ISO-8859-1');
}

// Validações
if (empty($divisao)) {
    echo json_encode(array("success" => false, "message" => "Divisão é obrigatória"));
    exit;
}

if (empty($valor)) {
    echo json_encode(array("success" => false, "message" => "Valor é obrigatório"));
    exit;
}

if (empty($descricao)) {
    echo json_encode(array("success" => false, "message" => "Descrição é obrigatória"));
    exit;
}

// Converte valor de formato brasileiro para decimal
$valor_decimal = str_replace(',', '.', str_replace('.', '', $valor));

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'UTF8'");
    
    if ($operation === 'insert') {
        // Verifica se já existe taxa para esta divisão
        $sql_check = "SELECT id FROM sind.valor_taxa_cartao WHERE divisao = :divisao";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':divisao', $divisao, PDO::PARAM_INT);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            echo json_encode(array(
                "success" => false, 
                "message" => "Já existe uma taxa cadastrada para esta divisão. Use a opção de editar."
            ));
            exit;
        }
        
        // INSERT
        $sql = "INSERT INTO sind.valor_taxa_cartao (divisao, valor, descricao) 
                VALUES (:divisao, :valor, :descricao)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
        $stmt->bindParam(':valor', $valor_decimal, PDO::PARAM_STR);
        $stmt->bindParam(':descricao', $descricao, PDO::PARAM_STR);
        $stmt->execute();
        
        echo json_encode(array(
            "success" => true,
            "message" => "Taxa cadastrada com sucesso!",
            "operation" => "insert"
        ));
        
    } elseif ($operation === 'update') {
        // Validação adicional para UPDATE
        if (empty($id)) {
            echo json_encode(array("success" => false, "message" => "ID é obrigatório para atualização"));
            exit;
        }
        
        // UPDATE
        $sql = "UPDATE sind.valor_taxa_cartao 
                SET valor = :valor, 
                    descricao = :descricao
                WHERE id = :id AND divisao = :divisao";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':valor', $valor_decimal, PDO::PARAM_STR);
        $stmt->bindParam(':descricao', $descricao, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(array(
                "success" => true,
                "message" => "Taxa atualizada com sucesso!",
                "operation" => "update"
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Nenhum registro foi atualizado. Verifique os dados."
            ));
        }
        
    } else {
        echo json_encode(array("success" => false, "message" => "Operação inválida"));
    }
    
} catch (PDOException $erro) {
    echo json_encode(array(
        "success" => false,
        "message" => "Erro ao salvar taxa de cartão",
        "erro" => $erro->getMessage()
    ));
}
?>
