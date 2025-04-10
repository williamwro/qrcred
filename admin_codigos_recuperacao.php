<?php
// gerencia_codigo_recuperacao.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-type: application/json");

// Se for uma solicitação OPTIONS, retorne apenas os cabeçalhos
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include "Adm/php/banco.php";

// Verificar autenticação (exemplo simplificado)
// Em produção, implemente autenticação adequada
$autenticado = false;

// Permitir acesso em desenvolvimento ou com credenciais válidas
if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1') {
    $autenticado = true;
} elseif (isset($_GET['admin_token']) && $_GET['admin_token'] == 'chave_segura_123') {
    $autenticado = true;
} elseif (isset($_POST['admin_token']) && $_POST['admin_token'] == 'chave_segura_123') {
    $autenticado = true;
}

if (!$autenticado) {
    http_response_code(403);
    echo json_encode(['status' => 'erro', 'erro' => 'Acesso não autorizado']);
    exit;
}

try {
    // Conexão com o banco de dados 
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Listar códigos
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Limpar códigos expirados
        $stmtLimpar = $pdo->prepare("SELECT sind.limpar_codigos_expirados()");
        $stmtLimpar->execute();
        
        // Buscar todos os códigos ativos
        $stmt = $pdo->prepare("
            SELECT * FROM sind.codigos_recuperacao 
            WHERE expirado = FALSE
            ORDER BY timestamp DESC
        ");
        $stmt->execute();
        
        $codigos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'sucesso', 'codigos' => $codigos]);
    } 
    // Operações POST (inserir, atualizar)
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['cartao'])) {
            echo json_encode(['status' => 'erro', 'erro' => 'Número do cartão é obrigatório']);
            exit;
        }
        
        $cartao = $_POST['cartao'];
        $operacao = isset($_POST['operacao']) ? $_POST['operacao'] : 'inserir';
        
        // Operação de inserção
        if ($operacao === 'inserir') {
            if (!isset($_POST['codigo'])) {
                echo json_encode(['status' => 'erro', 'erro' => 'Código de verificação é obrigatório']);
                exit;
            }
            
            $codigo = $_POST['codigo'];
            
            // Verificar se já existe um código para este cartão
            $stmtVerificar = $pdo->prepare("
                SELECT id FROM sind.codigos_recuperacao 
                WHERE cartao = :cartao AND expirado = FALSE
            ");
            $stmtVerificar->bindParam(':cartao', $cartao);
            $stmtVerificar->execute();
            
            if ($stmtVerificar->rowCount() > 0) {
                // Já existe um código para este cartão, atualizar
                $stmtUpdate = $pdo->prepare("
                    UPDATE sind.codigos_recuperacao
                    SET codigo = :codigo, timestamp = NOW(), usado = FALSE, expirado = FALSE
                    WHERE cartao = :cartao
                ");
                $stmtUpdate->bindParam(':codigo', $codigo);
                $stmtUpdate->bindParam(':cartao', $cartao);
                $stmtUpdate->execute();
                
                echo json_encode(['status' => 'sucesso', 'mensagem' => 'Código atualizado com sucesso']);
            } else {
                // Não existe código, inserir novo
                $stmtInsert = $pdo->prepare("
                    INSERT INTO sind.codigos_recuperacao (cartao, codigo, timestamp, usado, expirado)
                    VALUES (:cartao, :codigo, NOW(), FALSE, FALSE)
                ");
                $stmtInsert->bindParam(':cartao', $cartao);
                $stmtInsert->bindParam(':codigo', $codigo);
                $stmtInsert->execute();
                
                echo json_encode(['status' => 'sucesso', 'mensagem' => 'Código inserido com sucesso']);
            }
        }
        // Operação de atualização
        elseif ($operacao === 'atualizar') {
            if (!isset($_POST['codigo'])) {
                echo json_encode(['status' => 'erro', 'erro' => 'Código de verificação é obrigatório']);
                exit;
            }
            
            $codigo = $_POST['codigo'];
            
            $stmtUpdate = $pdo->prepare("
                UPDATE sind.codigos_recuperacao
                SET codigo = :codigo, timestamp = NOW(), usado = FALSE, expirado = FALSE
                WHERE cartao = :cartao
            ");
            $stmtUpdate->bindParam(':codigo', $codigo);
            $stmtUpdate->bindParam(':cartao', $cartao);
            $stmtUpdate->execute();
            
            if ($stmtUpdate->rowCount() > 0) {
                echo json_encode(['status' => 'sucesso', 'mensagem' => 'Código atualizado com sucesso']);
            } else {
                echo json_encode(['status' => 'erro', 'erro' => 'Código não encontrado para atualização']);
            }
        } else {
            echo json_encode(['status' => 'erro', 'erro' => 'Operação inválida']);
        }
    }
    // Excluir código
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['cartao'])) {
            echo json_encode(['status' => 'erro', 'erro' => 'Número do cartão é obrigatório']);
            exit;
        }
        
        $cartao = $_GET['cartao'];
        
        $stmt = $pdo->prepare("
            DELETE FROM sind.codigos_recuperacao 
            WHERE cartao = :cartao
        ");
        $stmt->bindParam(':cartao', $cartao);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'sucesso', 'mensagem' => 'Código removido com sucesso']);
        } else {
            echo json_encode(['status' => 'erro', 'erro' => 'Código não encontrado']);
        }
    } else {
        echo json_encode(['status' => 'erro', 'erro' => 'Método não permitido']);
    }
} catch (PDOException $e) {
    error_log("Erro de banco de dados: " . $e->getMessage());
    echo json_encode(['status' => 'erro', 'erro' => "Erro no servidor: " . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    echo json_encode(['status' => 'erro', 'erro' => "Erro inesperado: " . $e->getMessage()]);
}
?>