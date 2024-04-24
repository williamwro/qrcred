<?PHP
require '../../php/banco.php';
$_codigo = 1;
$_email  = isset($_POST['email']) ? $_POST['email'] : "";
$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad="";

    $sql = "UPDATE sind.email_antecipacao SET ";
    $sql .= "id = DEFAULT, ";
    $sql .= "email = ? ";
   
    try {

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(1, $_email, PDO::PARAM_STR);
        
        $stmt->execute();

        
        $response = array("success" => "true", "message" => "atualizado");
        $someArray = array_map("utf8_encode",$response);
        echo json_encode($someArray);

    } catch (PDOException $erro) {
        $response = array("success" => "false", "message" => "Não foi possivel inserir os dados no banco: " . $erro->getMessage());
        $someArray = array_map("utf8_encode",$response);
        echo json_encode($someArray);
    }
