<?php
session_start();

header('Content-Type: application/json');

// Incluir conexões necessárias
include_once "../../php/banco.php";

class UserHeartbeat {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Banco::conectar_postgres();
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'heartbeat':
                return $this->heartbeat();
            case 'verify':
                return $this->verifySession();
            case 'logout':
                return $this->logout();
            case 'close_session':
                return $this->closeSession();
            case 'close_all_user_sessions':
                return $this->closeAllUserSessions();
            case 'get_online_users':
                return $this->getOnlineUsers();
            case 'cleanup_sessions':
                return $this->cleanupSessions();
            case 'update_activity':
                return $this->updateActivity();
            default:
                return ['success' => false, 'error' => 'Ação inválida'];
        }
    }
    
    private function heartbeat() {
        $usuario_cod = $_POST['usuario_cod'] ?? null;
        $last_activity = $_POST['last_activity'] ?? time() * 1000;
        
        if (!$usuario_cod) {
            return ['success' => false, 'session_valid' => false];
        }
        
        // Atualizar último acesso no banco
        try {
            // Tentar atualizar, se falhar, as colunas podem não existir
            $stmt = $this->pdo->prepare("
                UPDATE sind.usuarios 
                SET ultimo_acesso = NOW(), ip_ultimo_acesso = :ip
                WHERE codigo = :codigo
            ");
            
            $stmt->execute([
                ':codigo' => $usuario_cod,
                ':ip' => $this->getUserIP()
            ]);
            
            return [
                'success' => true,
                'session_valid' => true,
                'timestamp' => time()
            ];
            
        } catch (Exception $e) {
            // Se der erro, pode ser que as colunas não existem
            // Tentar criar as colunas e tentar novamente
            try {
                $this->pdo->exec("ALTER TABLE sind.usuarios ADD COLUMN IF NOT EXISTS ultimo_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                $this->pdo->exec("ALTER TABLE sind.usuarios ADD COLUMN IF NOT EXISTS ip_ultimo_acesso VARCHAR(45)");
                
                // Tentar novamente
                $stmt = $this->pdo->prepare("
                    UPDATE sind.usuarios 
                    SET ultimo_acesso = NOW(), ip_ultimo_acesso = :ip
                    WHERE codigo = :codigo
                ");
                
                $stmt->execute([
                    ':codigo' => $usuario_cod,
                    ':ip' => $this->getUserIP()
                ]);
                
                return [
                    'success' => true,
                    'session_valid' => true,
                    'timestamp' => time()
                ];
                
            } catch (Exception $e2) {
                error_log("Erro no heartbeat: " . $e2->getMessage());
                return ['success' => false, 'session_valid' => false, 'error' => $e2->getMessage()];
            }
        }
    }
    
    private function verifySession() {
        $usuario_cod = $_POST['usuario_cod'] ?? null;
        
        if (!$usuario_cod) {
            return ['session_valid' => false];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT codigo FROM sind.usuarios 
                WHERE codigo = :codigo
            ");
            
            $stmt->execute([':codigo' => $usuario_cod]);
            $user = $stmt->fetch();
            
            return ['session_valid' => (bool)$user];
            
        } catch (Exception $e) {
            return ['session_valid' => false];
        }
    }
    
    private function logout() {
        session_destroy();
        return ['success' => true];
    }
    
    
    private function getUserIP() {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
               $_SERVER['HTTP_CLIENT_IP'] ?? 
               $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    private function getOnlineUsers() {
        try {
            // Primeiro, verificar se a tabela de sessões ativas existe
            $checkTable = $this->pdo->prepare("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = 'sind' 
                AND table_name = 'sessoes_ativas'
            ");
            $checkTable->execute();
            $tableExists = $checkTable->fetch();
            
            if (!$tableExists) {
                // Criar tabela de sessões ativas
                $this->pdo->exec("
                    CREATE TABLE sind.sessoes_ativas (
                        id SERIAL PRIMARY KEY,
                        codigo_usuario INTEGER NOT NULL,
                        session_id VARCHAR(255) NOT NULL,
                        ip_address VARCHAR(45),
                        user_agent TEXT,
                        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        is_active BOOLEAN DEFAULT true,
                        FOREIGN KEY (codigo_usuario) REFERENCES sind.usuarios(codigo) ON DELETE CASCADE
                    )
                ");
                
                // Criar índices para performance
                $this->pdo->exec("CREATE INDEX idx_sessoes_ativas_usuario ON sind.sessoes_ativas(codigo_usuario)");
                $this->pdo->exec("CREATE INDEX idx_sessoes_ativas_session ON sind.sessoes_ativas(session_id)");
                $this->pdo->exec("CREATE INDEX idx_sessoes_ativas_activity ON sind.sessoes_ativas(last_activity)");
            }
            
            // REMOVIDO: Limpeza automática (agora é feita separadamente)
            // A limpeza estava interferindo com sessões recém-criadas
            
            // Buscar usuários com sessões ativas (tempo limite ainda mais generoso para produção)
            $tempo_limite_exibicao = date('Y-m-d H:i:s', strtotime('-5 minutes'));
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT u.codigo, u.username, u.nome, 
                       MAX(s.last_activity) as ultimo_acesso,
                       COUNT(s.id) as sessoes_ativas
                FROM sind.usuarios u
                INNER JOIN sind.sessoes_ativas s ON u.codigo = s.codigo_usuario
                WHERE s.is_active = true
                GROUP BY u.codigo, u.username, u.nome
                ORDER BY MAX(s.last_activity) DESC
                LIMIT 50
            ");
            
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug: Primeiro verificar TODAS as sessões ativas sem filtro de tempo
            $debug_stmt = $this->pdo->prepare("
                SELECT DISTINCT u.codigo, u.username, u.nome, s.last_activity, s.is_active
                FROM sind.usuarios u
                INNER JOIN sind.sessoes_ativas s ON u.codigo = s.codigo_usuario
                WHERE s.is_active = true
                ORDER BY s.last_activity DESC
            ");
            $debug_stmt->execute();
            $all_active = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("=== DEBUG USUARIOS ONLINE ===");
            error_log("Total usuários com sessões ativas (sem filtro tempo): " . count($all_active));
            foreach ($all_active as $user) {
                error_log("- Usuário " . $user['codigo'] . " (" . $user['username'] . ") - Última atividade: " . $user['last_activity'] . " - Ativo: " . ($user['is_active'] ? 'true' : 'false'));
            }
            
            // Log detalhado dos resultados
            error_log("Tempo limite aplicado: " . $tempo_limite_exibicao);
            error_log("Usuários retornados pela consulta final: " . count($users));
            foreach ($users as $user) {
                error_log("- Resultado: Usuário " . $user['codigo'] . " (" . $user['username'] . ") - Último acesso: " . $user['ultimo_acesso']);
            }
            error_log("=== FIM DEBUG ===");
            
            return [
                'success' => true,
                'users' => $users,
                'count' => count($users),
                'timestamp' => time(),
                'tempo_limite' => $tempo_limite_exibicao
            ];
            
        } catch (Exception $e) {
            // Log do erro para debug
            error_log("Erro em getOnlineUsers: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'users' => []
            ];
        }
    }
    
    private function updateActivity() {
        $usuario_cod = $_POST['usuario_cod'] ?? null;
        $session_id = $_POST['session_id'] ?? session_id();
        
        // Logs removidos - sistema funcionando
        
        if (!$usuario_cod) {
            return ['success' => false, 'error' => 'Código do usuário não informado'];
        }
        
        try {
            $ip = $this->getUserIP();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Verificar se as colunas existem antes de usar
            $checkColumn = $this->pdo->prepare("
                SELECT column_name 
                FROM information_schema.columns 
                WHERE table_schema = 'sind' 
                AND table_name = 'usuarios' 
                AND column_name = 'ultimo_acesso'
            ");
            $checkColumn->execute();
            $columnExists = $checkColumn->fetch();
            
            if (!$columnExists) {
                $this->pdo->exec("ALTER TABLE sind.usuarios ADD COLUMN ultimo_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                $this->pdo->exec("ALTER TABLE sind.usuarios ADD COLUMN ip_ultimo_acesso VARCHAR(45)");
            }
            
            // Verificar se a sessão já existe
            $checkSession = $this->pdo->prepare("
                SELECT id FROM sind.sessoes_ativas 
                WHERE codigo_usuario = :codigo AND session_id = :session_id AND is_active = true
            ");
            $checkSession->execute([
                ':codigo' => $usuario_cod,
                ':session_id' => $session_id
            ]);
            
            if ($checkSession->fetch()) {
                // Atualizar sessão existente
                $stmt = $this->pdo->prepare("
                    UPDATE sind.sessoes_ativas 
                    SET last_activity = NOW(), ip_address = :ip
                    WHERE codigo_usuario = :codigo AND session_id = :session_id AND is_active = true
                ");
                $stmt->execute([
                    ':codigo' => $usuario_cod,
                    ':session_id' => $session_id,
                    ':ip' => $ip
                ]);
            } else {
                // Verificar se existe uma sessão ativa para este usuário (possível session_id diferente)
                $checkUserSession = $this->pdo->prepare("
                    SELECT session_id FROM sind.sessoes_ativas 
                    WHERE codigo_usuario = :codigo AND is_active = true 
                    ORDER BY last_activity DESC LIMIT 1
                ");
                $checkUserSession->execute([':codigo' => $usuario_cod]);
                $existingSession = $checkUserSession->fetch();
                
                if ($existingSession && $existingSession['session_id'] !== $session_id) {
                    // Apenas atualizar se for o mesmo session_id ou se a sessão existente for muito antiga
                    $checkTime = $this->pdo->prepare("
                        SELECT last_activity FROM sind.sessoes_ativas 
                        WHERE codigo_usuario = :codigo AND session_id = :session_id AND is_active = true
                    ");
                    $checkTime->execute([
                        ':codigo' => $usuario_cod,
                        ':session_id' => $existingSession['session_id']
                    ]);
                    $sessionData = $checkTime->fetch();
                    
                    if ($sessionData && strtotime($sessionData['last_activity']) < strtotime('-2 minutes')) {
                        // Sessão existente é antiga, substituir
                        $stmt = $this->pdo->prepare("
                            UPDATE sind.sessoes_ativas 
                            SET last_activity = NOW(), ip_address = :ip, session_id = :new_session_id
                            WHERE codigo_usuario = :codigo AND session_id = :old_session_id AND is_active = true
                        ");
                        $stmt->execute([
                            ':codigo' => $usuario_cod,
                            ':new_session_id' => $session_id,
                            ':old_session_id' => $existingSession['session_id'],
                            ':ip' => $ip
                        ]);
                    } else {
                        // Criar nova sessão pois a existente ainda está ativa
                        $stmt = $this->pdo->prepare("
                            INSERT INTO sind.sessoes_ativas 
                            (codigo_usuario, session_id, ip_address, user_agent, login_time, last_activity, is_active)
                            VALUES (:codigo, :session_id, :ip, :user_agent, NOW(), NOW(), true)
                        ");
                        $stmt->execute([
                            ':codigo' => $usuario_cod,
                            ':session_id' => $session_id,
                            ':ip' => $ip,
                            ':user_agent' => $user_agent
                        ]);
                    }
                } else {
                    // Criar nova sessão se não existe nenhuma
                    $stmt = $this->pdo->prepare("
                        INSERT INTO sind.sessoes_ativas 
                        (codigo_usuario, session_id, ip_address, user_agent, login_time, last_activity, is_active)
                        VALUES (:codigo, :session_id, :ip, :user_agent, NOW(), NOW(), true)
                    ");
                    $stmt->execute([
                        ':codigo' => $usuario_cod,
                        ':session_id' => $session_id,
                        ':ip' => $ip,
                        ':user_agent' => $user_agent
                    ]);
                }
            }
            
            // Também atualizar o campo ultimo_acesso na tabela usuarios (compatibilidade)
            $stmt = $this->pdo->prepare("
                UPDATE sind.usuarios 
                SET ultimo_acesso = NOW(), ip_ultimo_acesso = :ip
                WHERE codigo = :codigo
            ");
            $stmt->execute([
                ':codigo' => $usuario_cod,
                ':ip' => $ip
            ]);
            
            return [
                'success' => true,
                'message' => 'Atividade atualizada',
                'session_id' => $session_id,
                'timestamp' => time()
            ];
            
        } catch (Exception $e) {
            error_log("Erro em updateActivity: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function closeSession() {
        $usuario_cod = $_POST['usuario_cod'] ?? null;
        $session_id = $_POST['session_id'] ?? null;
        
        if (!$usuario_cod) {
            return ['success' => false, 'error' => 'Código do usuário não informado'];
        }
        
        try {
            if ($session_id) {
                // Marcar sessão específica como inativa
                $stmt = $this->pdo->prepare("
                    UPDATE sind.sessoes_ativas 
                    SET is_active = false 
                    WHERE codigo_usuario = :codigo AND session_id = :session_id
                ");
                $stmt->execute([
                    ':codigo' => $usuario_cod,
                    ':session_id' => $session_id
                ]);
                
                $affected = $stmt->rowCount();
                error_log("Sessão finalizada: usuário $usuario_cod, session $session_id, linhas afetadas: $affected");
            } else {
                // Se não há session_id, marcar todas as sessões do usuário como inativas
                $stmt = $this->pdo->prepare("
                    UPDATE sind.sessoes_ativas 
                    SET is_active = false 
                    WHERE codigo_usuario = :codigo AND is_active = true
                ");
                $stmt->execute([':codigo' => $usuario_cod]);
                
                $affected = $stmt->rowCount();
                error_log("Todas as sessões finalizadas para usuário $usuario_cod, linhas afetadas: $affected");
            }
            
            return [
                'success' => true,
                'message' => 'Sessão encerrada',
                'affected_rows' => $affected ?? 0
            ];
            
        } catch (Exception $e) {
            error_log("Erro em closeSession: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function closeAllUserSessions() {
        $usuario_cod = $_POST['usuario_cod'] ?? null;
        $session_id = $_POST['session_id'] ?? null;
        
        if (!$usuario_cod) {
            return ['success' => false, 'error' => 'Código do usuário não informado'];
        }
        
        try {
            // Marcar TODAS as sessões ativas do usuário como inativas
            $stmt = $this->pdo->prepare("
                UPDATE sind.sessoes_ativas 
                SET is_active = false 
                WHERE codigo_usuario = :codigo AND is_active = true
            ");
            $stmt->execute([':codigo' => $usuario_cod]);
            
            $affected = $stmt->rowCount();
            error_log("TODAS as sessões finalizadas para usuário $usuario_cod, linhas afetadas: $affected" . ($session_id ? " (sessão atual: $session_id)" : ""));
            
            return [
                'success' => true,
                'message' => 'Todas as sessões do usuário foram encerradas',
                'affected_rows' => $affected,
                'user_code' => $usuario_cod
            ];
            
        } catch (Exception $e) {
            error_log("Erro em closeAllUserSessions: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function cleanupSessions() {
        try {
            // Limpar sessões antigas (mais de 10 minutos de inatividade - ainda mais conservador para produção)
            $tempo_limite = date('Y-m-d H:i:s', strtotime('-10 minutes'));
            $stmt_cleanup = $this->pdo->prepare("
                UPDATE sind.sessoes_ativas 
                SET is_active = false 
                WHERE last_activity < :tempo_limite AND is_active = true
            ");
            $stmt_cleanup->execute([':tempo_limite' => $tempo_limite]);
            $desativadas = $stmt_cleanup->rowCount();
            
            // Remover sessões muito antigas (mais de 24 horas)
            $tempo_remover = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $stmt_remove = $this->pdo->prepare("
                DELETE FROM sind.sessoes_ativas 
                WHERE login_time < :tempo_remover
            ");
            $stmt_remove->execute([':tempo_remover' => $tempo_remover]);
            $removidas = $stmt_remove->rowCount();
            
            error_log("Limpeza executada: $desativadas sessões desativadas, $removidas sessões removidas");
            
            return [
                'success' => true,
                'message' => 'Limpeza executada',
                'desativadas' => $desativadas,
                'removidas' => $removidas
            ];
            
        } catch (Exception $e) {
            error_log("Erro na limpeza de sessões: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

$heartbeat = new UserHeartbeat();
echo json_encode($heartbeat->handleRequest());
