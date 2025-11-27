<?php
header('Content-Type: application/json; charset=utf-8');
include "Adm/php/banco.php";

$response = array();

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    if (empty($usuario) || empty($senha)) {
        $response['status'] = 'erro';
        $response['message'] = 'Usuário e senha são obrigatórios.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Buscar empregador pelo usuário e senha
    $sql = "SELECT id, nome, divisao, usuario, bloqueio 
            FROM sind.empregador 
            WHERE usuario = :usuario AND senha = :senha";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
    $stmt->bindParam(':senha', $senha, PDO::PARAM_STR);
    $stmt->execute();
    
    $empregador = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($empregador) {
        // Verificar se está bloqueado
        if ($empregador['bloqueio'] == 1) {
            $response['status'] = 'erro';
            $response['message'] = 'Acesso bloqueado. Entre em contato com o administrador.';
        } else {
            $response['status'] = 'sucesso';
            $response['id'] = $empregador['id'];
            $response['nome'] = $empregador['nome'];
            $response['divisao'] = $empregador['divisao'];
            $response['usuario'] = $empregador['usuario'];
            $response['message'] = 'Login realizado com sucesso!';
        }
    } else {
        $response['status'] = 'erro';
        $response['message'] = 'Usuário ou senha incorretos.';
    }

} catch (PDOException $e) {
    $response['status'] = 'erro';
    $response['message'] = 'Erro ao processar login: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
