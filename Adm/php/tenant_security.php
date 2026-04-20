<?php
/**
 * ============================================================================
 * TENANT SECURITY MIDDLEWARE - Validação Multi-tenant
 * ============================================================================
 * Middleware para validar acesso por divisão e prevenir cross-tenant access
 * Data: 2026-02-12
 * Versão: 1.0
 * 
 * USO:
 * require_once 'Adm/php/tenant_security.php';
 * $tenantSec = new TenantSecurity();
 * $divisao = $tenantSec->getSecureDivisao($_POST['divisao']);
 * ============================================================================
 */

require_once __DIR__ . '/tenant_logger.php';

class TenantSecurity {
    
    private $pdo;
    private $logger;
    private $usuarioAutenticado;
    private $divisaoAutenticada;
    
    /**
     * Construtor - Inicializa conexão e logger
     */
    public function __construct($pdo = null) {
        if ($pdo === null) {
            require_once __DIR__ . '/banco.php';
            $this->pdo = Banco::conectar_postgres();
        } else {
            $this->pdo = $pdo;
        }
        
        $this->logger = new TenantLogger($this->pdo);
        $this->loadAuthenticatedUser();
    }
    
    /**
     * Carrega dados do usuário autenticado da sessão PHP
     */
    private function loadAuthenticatedUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Buscar dados do usuário na sessão
        $this->usuarioAutenticado = [
            'codigo' => $_SESSION['usuario_cod'] ?? null,
            'username' => $_SESSION['user_name'] ?? null,
            'divisao' => $_SESSION['divisao'] ?? null
        ];
        
        // Se não estiver em $_SESSION, tentar buscar do banco via session_id
        if (!$this->usuarioAutenticado['codigo']) {
            $this->loadUserFromSessionTable();
        }
        
        $this->divisaoAutenticada = $this->usuarioAutenticado['divisao'];
    }
    
    /**
     * Busca usuário da tabela sessoes_ativas
     */
    private function loadUserFromSessionTable() {
        try {
            $sessionId = session_id();
            
            $sql = "SELECT u.codigo, u.username, u.divisao
                    FROM sind.sessoes_ativas sa
                    INNER JOIN sind.usuarios u ON sa.codigo_usuario = u.codigo
                    WHERE sa.session_id = :session_id 
                    AND sa.is_active = true
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':session_id' => $sessionId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $this->usuarioAutenticado = [
                    'codigo' => $user['codigo'],
                    'username' => $user['username'],
                    'divisao' => $user['divisao']
                ];
                
                // Armazenar em $_SESSION para próximas requisições
                $_SESSION['usuario_cod'] = $user['codigo'];
                $_SESSION['user_name'] = $user['username'];
                $_SESSION['divisao'] = $user['divisao'];
            }
            
        } catch (Exception $e) {
            error_log("ERRO ao carregar usuário da sessão: " . $e->getMessage());
        }
    }
    
    /**
     * MÉTODO PRINCIPAL: Valida se o acesso à divisão é permitido
     * 
     * @param int $divisaoTentada - Divisão que o usuário está tentando acessar
     * @param bool $throwException - Se deve lançar exceção ou retornar false
     * @return bool - true se permitido, false se negado
     */
    public function validateAccess($divisaoTentada, $throwException = false) {
        
        // 1. Verificar se usuário está autenticado
        if (!$this->usuarioAutenticado['codigo']) {
            $this->logViolation($divisaoTentada, 'Usuário não autenticado');
            
            if ($throwException) {
                throw new Exception('Acesso negado: usuário não autenticado');
            }
            return false;
        }
        
        // 2. Verificar se usuário está bloqueado
        if ($this->logger->isUserBlocked($this->usuarioAutenticado['codigo'], $this->divisaoAutenticada)) {
            $this->logViolation($divisaoTentada, 'Usuário bloqueado por excesso de tentativas');
            
            if ($throwException) {
                throw new Exception('Acesso bloqueado temporariamente. Tente novamente mais tarde.');
            }
            return false;
        }
        
        // 3. Validar se divisão tentada corresponde à divisão autenticada
        if ($divisaoTentada != $this->divisaoAutenticada) {
            
            // Verificar se usuário tem permissão multi-divisão
            if (!$this->hasMultiDivisionPermission($divisaoTentada)) {
                $this->logViolation($divisaoTentada, 'Tentativa de acesso cross-tenant', true);
                
                if ($throwException) {
                    throw new Exception('Acesso negado: você não tem permissão para acessar esta divisão');
                }
                return false;
            }
        }
        
        // 4. Acesso permitido - registrar log de sucesso
        $this->logAccess($divisaoTentada, false);
        
        return true;
    }
    
    /**
     * Verifica se usuário tem permissão para acessar múltiplas divisões
     */
    private function hasMultiDivisionPermission($divisaoTentada) {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM sind.usuario_divisao_permitida 
                    WHERE codigo_usuario = :codigo_usuario 
                    AND id_divisao = :id_divisao";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':codigo_usuario' => $this->usuarioAutenticado['codigo'],
                ':id_divisao' => $divisaoTentada
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['total'] > 0);
            
        } catch (Exception $e) {
            error_log("ERRO ao verificar permissão multi-divisão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra violação de segurança
     */
    private function logViolation($divisaoTentada, $motivo, $bloqueado = false) {
        $this->logger->logCrossTenantAttempt([
            'codigo_usuario' => $this->usuarioAutenticado['codigo'],
            'username' => $this->usuarioAutenticado['username'],
            'divisao_usuario' => $this->divisaoAutenticada,
            'divisao_tentada' => $divisaoTentada,
            'bloqueado' => $bloqueado,
            'motivo' => $motivo,
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ]);
    }
    
    /**
     * Registra acesso bem-sucedido
     */
    private function logAccess($divisao, $bloqueado = false) {
        $this->logger->logCrossTenantAttempt([
            'codigo_usuario' => $this->usuarioAutenticado['codigo'],
            'username' => $this->usuarioAutenticado['username'],
            'divisao_usuario' => $this->divisaoAutenticada,
            'divisao_tentada' => $divisao,
            'bloqueado' => $bloqueado,
            'motivo' => 'Acesso permitido'
        ]);
    }
    
    /**
     * Retorna divisão autenticada do usuário
     */
    public function getDivisaoAutenticada() {
        return $this->divisaoAutenticada;
    }
    
    /**
     * Retorna dados do usuário autenticado
     */
    public function getUsuarioAutenticado() {
        return $this->usuarioAutenticado;
    }
    
    /**
     * Método helper para validar e retornar divisão segura
     * USO RECOMENDADO: $divisao = $tenantSec->getSecureDivisao($_POST['divisao']);
     */
    public function getSecureDivisao($divisaoFromPost = null) {
        $divisaoTentada = $divisaoFromPost ?? $_POST['divisao'] ?? null;
        
        if ($this->validateAccess($divisaoTentada)) {
            return $divisaoTentada;
        }
        
        // Se falhar, retornar divisão autenticada (fallback seguro)
        return $this->divisaoAutenticada;
    }
}
