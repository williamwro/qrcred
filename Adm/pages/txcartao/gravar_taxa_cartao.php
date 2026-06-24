<?PHP
header("Content-type: application/json");
require '../../php/banco.php';

// Recebe os parâmetros do POST
$mes = isset($_POST['mes']) ? $_POST['mes'] : '';
$valor = isset($_POST['valor']) ? $_POST['valor'] : '';
$descricao = isset($_POST['descricao']) ? $_POST['descricao'] : '';
$divisao = isset($_POST['divisao']) ? $_POST['divisao'] : 1;
$usuario_cod = isset($_POST['usuario_cod']) ? $_POST['usuario_cod'] : '';

// Validações básicas
if (empty($mes) || empty($valor) || empty($descricao)) {
    $response = array(
        "success" => false, 
        "message" => "Todos os campos são obrigatórios"
    );
    echo json_encode($response);
    exit;
}

// Converte valor de formato brasileiro para decimal
$valor_decimal = str_replace(',', '.', str_replace('.', '', $valor));

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Inicia transação
    $pdo->beginTransaction();
    
    // Prepara a data e hora atuais
    $data_atual = date('Y-m-d');
    $hora_atual = date('H:i:s');
    
    // SQL baseado no script fornecido
    $sql = "INSERT INTO sind.conta (
        associado,
        convenio,
        valor,
        data,
        hora,
        descricao,
        mes,
        empregador,
        divisao,
        id_associado,
        uuid_conta,
        aprovado
    )
    SELECT
        s.codigo::varchar,
        249,
        :valor,
        :data::date,
        :hora::time,
        :descricao,
        :mes,
        s.empregador,
        :divisao,
        s.id,
        (
            substring(s.h, 1, 8) || '-' ||
            substring(s.h, 9, 4) || '-' ||
            substring(s.h, 13, 4) || '-' ||
            substring(s.h, 17, 4) || '-' ||
            substring(s.h, 21, 12)
        )::uuid,
        TRUE
    FROM (
        SELECT a.*, md5(random()::text || clock_timestamp()::text) AS h
        FROM sind.associado a
        WHERE a.id_situacao <> 2
          AND a.id_situacao <> 3
          AND a.id IN (
              SELECT DISTINCT c.id_associado
              FROM sind.conta c
              WHERE c.mes = :mes
          )
    ) s";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind dos parâmetros
    $stmt->bindParam(':valor', $valor_decimal, PDO::PARAM_STR);
    $stmt->bindParam(':data', $data_atual, PDO::PARAM_STR);
    $stmt->bindParam(':hora', $hora_atual, PDO::PARAM_STR);
    $stmt->bindParam(':descricao', $descricao, PDO::PARAM_STR);
    $stmt->bindParam(':mes', $mes, PDO::PARAM_STR);
    $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    
    // Executa a query
    $stmt->execute();
    
    // Obtém o número de registros inseridos
    $registros_inseridos = $stmt->rowCount();
    
    // Commit da transação
    $pdo->commit();
    
    // Retorna sucesso
    $response = array(
        "success" => true,
        "message" => "Taxa de cartão lançada com sucesso!",
        "registros_inseridos" => $registros_inseridos,
        "detalhes" => "Foram lançadas taxas para $registros_inseridos associados no mês de $mes"
    );
    
    // Converte para UTF-8 se necessário
    $response_encoded = array();
    foreach ($response as $key => $value) {
        $response_encoded[$key] = is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
    }
    echo json_encode($response_encoded);
    
} catch (PDOException $erro) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $response = array(
        "success" => false,
        "message" => "Erro ao gravar taxa de cartão",
        "erro_detalhes" => $erro->getMessage()
    );
    
    // Converte para UTF-8 se necessário
    $response_encoded = array();
    foreach ($response as $key => $value) {
        $response_encoded[$key] = is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
    }
    echo json_encode($response_encoded);
}
?>
