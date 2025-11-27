<?php
header('Content-Type: application/json; charset=utf-8');
include "../../php/banco.php";

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$response = array("status" => "ok");

$_codigo   = isset($_POST['C_codigo_empregador']) ? (int)$_POST['C_codigo_empregador'] : 0;
$_usuario  = isset($_POST['C_usuario']) ? trim($_POST['C_usuario']) : "";
$_senha    = isset($_POST['C_senha']) ? trim($_POST['C_senha']) : "";
$operation = isset($_POST['operation']) ? $_POST['operation'] : "";

try {
    // Verificar se o usuário já existe (se não estiver vazio)
    if (!empty($_usuario)) {
        $sql = "SELECT id FROM sind.empregador WHERE usuario = :usuario";
        if ($operation == "Update" && $_codigo > 0) {
            $sql .= " AND id != :codigo";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':usuario', $_usuario, PDO::PARAM_STR);
        if ($operation == "Update" && $_codigo > 0) {
            $stmt->bindParam(':codigo', $_codigo, PDO::PARAM_INT);
        }
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $response["status"] = "erro_usuario";
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Verificar se a senha já existe (se não estiver vazia)
    if (!empty($_senha)) {
        $sql = "SELECT id FROM sind.empregador WHERE senha = :senha";
        if ($operation == "Update" && $_codigo > 0) {
            $sql .= " AND id != :codigo";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':senha', $_senha, PDO::PARAM_STR);
        if ($operation == "Update" && $_codigo > 0) {
            $stmt->bindParam(':codigo', $_codigo, PDO::PARAM_INT);
        }
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $response["status"] = "erro_senha";
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    $response["status"] = "erro";
    $response["message"] = $e->getMessage();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>
