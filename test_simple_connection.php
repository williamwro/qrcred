<?php
/**
 * Teste Simples - Conexão e VAPID
 * Verifica problemas básicos antes da biblioteca web-push
 */

// Incluir configuração VAPID
require_once 'vapid_config.php';

header('Content-Type: application/json');

try {
    // Verificar constantes VAPID
    $vapid_check = [
        'VAPID_PUBLIC_KEY' => defined('VAPID_PUBLIC_KEY'),
        'VAPID_PRIVATE_KEY' => defined('VAPID_PRIVATE_KEY'),
        'VAPID_SUBJECT' => defined('VAPID_SUBJECT')
    ];
    
    // Verificar extensões PHP
    $extensions = [
        'pdo' => extension_loaded('pdo'),
        'pdo_pgsql' => extension_loaded('pdo_pgsql'),
        'curl' => extension_loaded('curl'),
        'openssl' => extension_loaded('openssl'),
        'json' => extension_loaded('json')
    ];
    
    // Tentar conectar ao banco
    $connection_test = ['success' => false, 'error' => null];
    
    try {
        // Incluir conexão com banco
        require_once 'Adm/php/banco.php';
        
        $pdo = Banco::conectar_postgres();
        
        if ($pdo) {
            // Testar query simples
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            
            if ($result && $result['test'] == 1) {
                $connection_test['success'] = true;
                $connection_test['message'] = 'Conexão com banco OK';
            }
        }
        
    } catch (Exception $e) {
        $connection_test['error'] = $e->getMessage();
    }
    
    // Verificar se tabela push_subscriptions existe
    $table_check = ['exists' => false, 'count' => 0, 'error' => null];
    
    if ($connection_test['success']) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM sind.push_subscriptions");
            $result = $stmt->fetch();
            
            $table_check['exists'] = true;
            $table_check['count'] = $result['count'];
            
        } catch (Exception $e) {
            $table_check['error'] = $e->getMessage();
        }
    }
    
    // Verificar biblioteca web-push
    $webpush_check = ['available' => false, 'error' => null];
    
    try {
        require_once 'vendor/autoload.php';
        
        if (class_exists('Minishlink\WebPush\WebPush')) {
            $webpush_check['available'] = true;
            $webpush_check['message'] = 'Biblioteca web-push encontrada';
        } else {
            $webpush_check['error'] = 'Classe WebPush não encontrada';
        }
        
    } catch (Exception $e) {
        $webpush_check['error'] = $e->getMessage();
    }
    
    // Resposta
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'checks' => [
            'vapid_constants' => $vapid_check,
            'php_extensions' => $extensions,
            'database_connection' => $connection_test,
            'push_subscriptions_table' => $table_check,
            'webpush_library' => $webpush_check
        ],
        'summary' => [
            'vapid_ok' => $vapid_check['VAPID_PUBLIC_KEY'] && $vapid_check['VAPID_PRIVATE_KEY'],
            'extensions_ok' => $extensions['pdo'] && $extensions['pdo_pgsql'],
            'database_ok' => $connection_test['success'],
            'table_ok' => $table_check['exists'],
            'webpush_ok' => $webpush_check['available'],
            'ready_for_push' => (
                $vapid_check['VAPID_PUBLIC_KEY'] && 
                $vapid_check['VAPID_PRIVATE_KEY'] && 
                $extensions['pdo'] && 
                $extensions['pdo_pgsql'] && 
                $connection_test['success'] && 
                $table_check['exists'] && 
                $webpush_check['available']
            )
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} 