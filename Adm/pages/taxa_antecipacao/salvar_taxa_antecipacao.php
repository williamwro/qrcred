<?PHP
require '../../php/banco.php';
$_codigo = 1;
$_porcentagem = isset($_POST['taxa_app']) ? $_POST['taxa_app'] : 0;
$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "UPDATE sind.taxa_antecipacao SET ";
    $sql .= "id = DEFAULT, ";
    $sql .= "porcentagem = ? ";
   
    try {

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(1, $_porcentagem, PDO::PARAM_STR);
        
        $stmt->execute();

        $response = array("success" => "true", "message" => "atualizado");
        $someArray = array_map("utf8_encode",$response);
        echo json_encode($someArray);

    } catch (PDOException $erro) {
        $response = array("success" => "false", "message" => "Não foi possivel inserir os dados no banco: " . $erro->getMessage());
        $someArray = array_map("utf8_encode",$response);
        echo json_encode($someArray);
    }
