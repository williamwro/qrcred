<?php
header('Content-Type: application/json; charset=utf-8');

try {
    include "../../php/banco.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obter parâmetros do POST (enviados pelo JavaScript)
    $usuario_cod = isset($_POST['usuario_cod']) ? $_POST['usuario_cod'] : null;
    $divisao_post = isset($_POST['divisao']) ? $_POST['divisao'] : null;
    
    if ($usuario_cod) {
        $stmt = $pdo->prepare("SELECT divisao, nome FROM sind.usuarios WHERE codigo = :usuario_cod");
        $stmt->bindParam(':usuario_cod', $usuario_cod, PDO::PARAM_INT);
        $stmt->execute();
        $user_data = $stmt->fetch();
        
        if ($user_data) {
            $response = array(
                'success' => true,
                'divisao' => $user_data['divisao'],
                'nome' => $user_data['nome']
            );
        } else {
            // Fallback para divisão enviada via POST
            $divisao_fallback = $divisao_post ? $divisao_post : 2;
            $response = array(
                'success' => true,
                'divisao' => $divisao_fallback,
                'nome' => 'Usuário não encontrado - usando divisão do POST'
            );
        }
    } else {
        // Fallback para divisão enviada via POST
        $divisao_fallback = $divisao_post ? $divisao_post : 2;
        $response = array(
            'success' => true,
            'divisao' => $divisao_fallback,
            'nome' => 'usuario_cod não fornecido - usando divisão do POST'
        );
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}
