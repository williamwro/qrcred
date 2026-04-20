<?php
/**
 * ============================================================================
 * TENANT SECURITY LOGGER - Sistema de Log Multi-tenant
 * ============================================================================
 * Responsável por registrar tentativas de acesso cross-tenant e violações
 * Data: 2026-02-12
 * Versão: 1.0
 * ============================================================================
 */

class TenantLogger {
    
    private $pdo;
    
    public function __construct($pdo = null) {
        if ($pdo === null) {
            require_once __DIR__ . '/banco.php';
            $this->pdo = Banco::conectar_postgres();
        } else {
            $this->pdo = $pdo;
        }
    }
    
    /**
     * Registra tentativa de acesso cross-tenant
     */
    public function logCrossTenantAttempt($params) {
        try {
            $sql = "INSERT INTO sind.tenant_security_log (
                codigo_usuario, username, divisao_usuario, divisao_tentada,
                endpoint, ip_address, user_agent, metodo_http,
                parametros_post, parametros_get, session_id,
                bloqueado, motivo, stack_trace
            ) VALUES (
                :codigo_usuario, :username, :divisao_usuario, :divisao_tentada,
                :endpoint, :ip_address, :user_agent, :metodo_http,
                :parametros_post, :parametros_get, :session_id,
                :bloqueado, :motivo, :stack_trace
            )";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':codigo_usuario' => $params['codigo_usuario'] ?? null,
                ':username' => $params['username'] ?? null,
                ':divisao_usuario' => $params['divisao_usuario'] ?? null,
                ':divisao_tentada' => $params['divisao_tentada'] ?? null,
                ':endpoint' => $params['endpoint'] ?? $_SERVER['REQUEST_URI'] ?? null,
                ':ip_address' => $this->getClientIP(),
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ':metodo_http' => $_SERVER['REQUEST_METHOD'] ?? null,
                ':parametros_post' => json_encode($_POST, JSON_UNESCAPED_UNICODE),
                ':parametros_get' => json_encode($_GET, JSON_UNESCAPED_UNICODE),
                ':session_id' => session_id() ?: null,
                ':bloqueado' => $params['bloqueado'] ?? false,
                ':motivo' => $params['motivo'] ?? null,
                ':stack_trace' => isset($params['stack_trace']) ? json_encode($params['stack_trace'], JSON_UNESCAPED_UNICODE) : null
            ]);
            
            // Atualizar estatísticas
            $this->updateStats(
                $params['divisao_usuario'] ?? 0,
                $params['codigo_usuario'] ?? 0,
                $params['bloqueado'] ?? false
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("ERRO TenantLogger: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza estatísticas de acesso
     */
    private function updateStats($id_divisao, $codigo_usuario, $bloqueado) {
        try {
            $sql = "SELECT sind.update_tenant_access_stats(:id_divisao, :codigo_usuario, :bloqueado)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id_divisao' => $id_divisao,
                ':codigo_usuario' => $codigo_usuario,
                ':bloqueado' => $bloqueado ? 'true' : 'false'
            ]);
        } catch (Exception $e) {
            error_log("ERRO ao atualizar stats: " . $e->getMessage());
        }
    }
    
    /**
     * Obtém IP real do cliente (considerando proxies)
     */
    private function getClientIP() {
        $ip = 'unknown';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
    
    /**
     * Verifica se usuário está bloqueado por excesso de tentativas
     */
    public function isUserBlocked($codigo_usuario, $id_divisao) {
        try {
            // Buscar configuração da divisão
            $sqlConfig = "SELECT max_failed_attempts, lockout_duration_minutes 
                          FROM sind.tenant_security_config 
                          WHERE id_divisao = :id_divisao";
            $stmtConfig = $this->pdo->prepare($sqlConfig);
            $stmtConfig->execute([':id_divisao' => $id_divisao]);
            $config = $stmtConfig->fetch(PDO::FETCH_ASSOC);
            
            if (!$config) {
                return false; // Sem configuração = não bloqueia
            }
            
            $maxAttempts = $config['max_failed_attempts'];
            $lockoutMinutes = $config['lockout_duration_minutes'];
            
            // Contar tentativas bloqueadas recentes
            $sql = "SELECT COUNT(*) as total 
                    FROM sind.tenant_security_log 
                    WHERE codigo_usuario = :codigo_usuario 
                    AND bloqueado = true 
                    AND data_hora > NOW() - INTERVAL '$lockoutMinutes minutes'";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':codigo_usuario' => $codigo_usuario]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ($result['total'] >= $maxAttempts);
            
        } catch (Exception $e) {
            error_log("ERRO ao verificar bloqueio: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém estatísticas de segurança
     */
    public function getSecurityStats($id_divisao = null, $dias = 7) {
        try {
            $sql = "SELECT 
                        id_divisao,
                        data_referencia,
                        total_acessos,
                        total_usuarios_unicos,
                        total_tentativas_bloqueadas
                    FROM sind.tenant_access_stats
                    WHERE data_referencia >= CURRENT_DATE - INTERVAL '$dias days'";
            
            if ($id_divisao !== null) {
                $sql .= " AND id_divisao = :id_divisao";
            }
            
            $sql .= " ORDER BY data_referencia DESC, id_divisao";
            
            $stmt = $this->pdo->prepare($sql);
            
            if ($id_divisao !== null) {
                $stmt->execute([':id_divisao' => $id_divisao]);
            } else {
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("ERRO ao obter stats: " . $e->getMessage());
            return [];
        }
    }
}
