<?php
/**
 * Manage Push Subscriptions
 * Gerencia subscriptions de push notifications
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir conexão com banco
require_once 'Adm/php/banco.php';

try {
    // Conectar ao banco PostgreSQL
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obter ação
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'register':
            registerSubscription($pdo);
            break;
            
        case 'update':
            updateSubscription($pdo);
            break;
            
        case 'deactivate':
            deactivateSubscription($pdo);
            break;
            
        case 'list':
            listSubscriptions($pdo);
            break;
            
        default:
            throw new Exception('Ação inválida');
    }
    
} catch (Exception $e) {
    error_log("Erro em manage_push_subscriptions_app.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage()
    ]);
}

/**
 * Registrar nova subscription
 */
function registerSubscription($pdo) {
    $userCard = $_POST['user_card'] ?? '';
    $endpoint = $_POST['endpoint'] ?? '';
    $p256dhKey = $_POST['p256dh_key'] ?? '';
    $authKey = $_POST['auth_key'] ?? '';
    $settings = $_POST['settings'] ?? '{}';
    
    // Validar dados obrigatórios
    if (empty($userCard) || empty($endpoint) || empty($p256dhKey) || empty($authKey)) {
        throw new Exception('Dados obrigatórios faltando: user_card, endpoint, p256dh_key, auth_key');
    }
    
    // Log dos dados recebidos
    error_log("Registrando subscription - User: {$userCard}, Endpoint: " . substr($endpoint, 0, 50) . ", P256dh: " . substr($p256dhKey, 0, 20) . ", Auth: " . substr($authKey, 0, 20));
    
    try {
        // VERIFICAR SE JÁ EXISTE SUBSCRIPTION COM MESMO ENDPOINT
        $stmt = $pdo->prepare("
            SELECT id, is_active 
            FROM sind.push_subscriptions 
            WHERE user_card = ? AND endpoint = ?
            LIMIT 1
        ");
        $stmt->execute([$userCard, $endpoint]);
        $existingSubscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingSubscription) {
            // JÁ EXISTE - APENAS REATIVAR E ATUALIZAR
            $subscriptionId = $existingSubscription['id'];
            
            $stmt = $pdo->prepare("
                UPDATE sind.push_subscriptions 
                SET 
                    p256dh_key = ?,
                    auth_key = ?,
                    p256dh = ?,
                    auth = ?,
                    settings = ?,
                    is_active = true,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([
                $p256dhKey,
                $authKey,
                $p256dhKey,
                $authKey,
                $settings,
                $subscriptionId
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Subscription reativada com sucesso',
                'subscription_id' => $subscriptionId,
                'user_card' => $userCard,
                'action' => 'reactivated'
            ]);
            
            error_log("Subscription reativada - ID: {$subscriptionId}, User: {$userCard}");
            
        } else {
            // NÃO EXISTE - DESATIVAR OUTRAS E INSERIR NOVA
            
            // Desativar outras subscriptions ativas do usuário (endpoints diferentes)
            $stmt = $pdo->prepare("
                UPDATE sind.push_subscriptions 
                SET is_active = false, updated_at = CURRENT_TIMESTAMP
                WHERE user_card = ? AND is_active = true
            ");
            $stmt->execute([$userCard]);
            
            // Inserir nova subscription
            $stmt = $pdo->prepare("
                INSERT INTO sind.push_subscriptions (
                    user_card, 
                    endpoint, 
                    p256dh_key, 
                    auth_key, 
                    p256dh, 
                    auth, 
                    settings, 
                    is_active,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                RETURNING id
            ");
            
            $stmt->execute([
                $userCard,
                $endpoint,
                $p256dhKey,
                $authKey,
                $p256dhKey,
                $authKey,
                $settings
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $subscriptionId = $result['id'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Subscription registrada com sucesso',
                'subscription_id' => $subscriptionId,
                'user_card' => $userCard,
                'action' => 'created'
            ]);
            
            error_log("Subscription criada - ID: {$subscriptionId}, User: {$userCard}");
        }
        
    } catch (Exception $e) {
        error_log("Erro ao registrar subscription: " . $e->getMessage());
        throw new Exception('Erro ao registrar subscription: ' . $e->getMessage());
    }
}

/**
 * Atualizar subscription existente
 */
function updateSubscription($pdo) {
    $userCard = $_POST['user_card'] ?? '';
    $settings = $_POST['settings'] ?? '{}';
    
    if (empty($userCard)) {
        throw new Exception('user_card é obrigatório');
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE sind.push_subscriptions 
            SET settings = ?, updated_at = CURRENT_TIMESTAMP
            WHERE user_card = ? AND is_active = true
        ");
        
        $stmt->execute([$settings, $userCard]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Settings atualizados com sucesso'
            ]);
        } else {
            throw new Exception('Nenhuma subscription ativa encontrada para este usuário');
        }
        
    } catch (Exception $e) {
        throw new Exception('Erro ao atualizar subscription: ' . $e->getMessage());
    }
}

/**
 * Desativar subscription
 */
function deactivateSubscription($pdo) {
    $userCard = $_POST['user_card'] ?? '';
    
    if (empty($userCard)) {
        throw new Exception('user_card é obrigatório');
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE sind.push_subscriptions 
            SET is_active = false, updated_at = CURRENT_TIMESTAMP
            WHERE user_card = ? AND is_active = true
        ");
        
        $stmt->execute([$userCard]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Subscription desativada com sucesso',
            'deactivated_count' => $stmt->rowCount()
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Erro ao desativar subscription: ' . $e->getMessage());
    }
}

/**
 * Listar subscriptions do usuário
 */
function listSubscriptions($pdo) {
    $userCard = $_POST['user_card'] ?? '';
    
    if (empty($userCard)) {
        throw new Exception('user_card é obrigatório');
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, endpoint, p256dh, auth, settings, is_active, created_at, updated_at
            FROM sind.push_subscriptions 
            WHERE user_card = ?
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([$userCard]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'subscriptions' => $subscriptions,
            'count' => count($subscriptions)
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Erro ao listar subscriptions: ' . $e->getMessage());
    }
}
?> 