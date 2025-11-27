<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $matricula = $_POST['matricula'] ?? '';
    $empregador = $_POST['empregador'] ?? '';
    $id_associado = $_POST['id_associado'] ?? '';
    $id_divisao = $_POST['id_divisao'] ?? '';
    $acao = $_POST['acao'] ?? '';
    
    if ($acao !== 'limpar_cache') {
        echo json_encode(['erro' => 'Ação inválida']);
        exit;
    }
    
    if (empty($matricula) || empty($empregador) || empty($id_associado) || empty($id_divisao)) {
        echo json_encode(['erro' => 'Parâmetros obrigatórios não informados']);
        exit;
    }
    
    // Limpar cache de proteção contra duplicação
    $chaves_para_limpar = [
        "antecipacao_processing_{$matricula}_{$empregador}_{$id_associado}_{$id_divisao}",
        "antecipacao_lock_{$matricula}",
        "request_lock_{$matricula}",
        "submission_protection_{$matricula}",
        "rate_limit_{$matricula}",
        "{$matricula}_{$empregador}_{$id_associado}_{$id_divisao}"
    ];
    
    $cache_limpo = [];
    
    // Se usar Redis ou Memcached
    if (class_exists('Redis')) {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            
            foreach ($chaves_para_limpar as $chave) {
                if ($redis->del($chave)) {
                    $cache_limpo[] = $chave;
                }
            }
            
            $redis->close();
        } catch (Exception $e) {
            // Redis não disponível, continuar
        }
    }
    
    // Limpar cache de arquivos (se existir)
    $cache_dir = '/tmp/antecipacao_cache/';
    if (is_dir($cache_dir)) {
        foreach ($chaves_para_limpar as $chave) {
            $arquivo_cache = $cache_dir . md5($chave) . '.cache';
            if (file_exists($arquivo_cache)) {
                unlink($arquivo_cache);
                $cache_limpo[] = $chave;
            }
        }
    }
    
    // Limpar sessões relacionadas
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    foreach ($chaves_para_limpar as $chave) {
        if (isset($_SESSION[$chave])) {
            unset($_SESSION[$chave]);
            $cache_limpo[] = "session_{$chave}";
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cache de proteção limpo com sucesso',
        'chaves_limpas' => $cache_limpo,
        'total_limpas' => count($cache_limpo),
        'dados' => [
            'matricula' => $matricula,
            'empregador' => $empregador,
            'id_associado' => $id_associado,
            'id_divisao' => $id_divisao
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'erro' => 'Erro ao limpar cache: ' . $e->getMessage()
    ]);
}