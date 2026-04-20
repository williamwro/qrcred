<?php
/**
 * ============================================================================
 * ETAPA 2: ESCALABILIDADE - CONNECTION POOLING POR DIVISÃO
 * ============================================================================
 * Objetivo: Implementar pool de conexões otimizado por divisão
 * Data: 2026-02-21
 * 
 * BENEFÍCIOS:
 * - Reutilização de conexões (reduz overhead)
 * - Isolamento de recursos por divisão
 * - Melhor performance em ambientes multi-tenant
 * - Controle de limites de conexão por cliente
 * ============================================================================
 */

class ConnectionPoolManager {
    
    private static $instance = null;
    private $pools = [];
    private $config = [
        'max_connections_per_division' => 10,  // Máximo de conexões por divisão
        'connection_timeout' => 30,            // Timeout em segundos
        'idle_timeout' => 300,                 // Tempo máximo de conexão ociosa (5 min)
        'enable_persistent' => true            // Usar conexões persistentes
    ];
    
    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Construtor privado para Singleton
    }
    
    /**
     * Obter conexão para uma divisão específica
     * 
     * @param int $divisao_id ID da divisão
     * @return PDO Conexão PDO
     */
    public function getConnection($divisao_id) {
        $divisao_id = (int)$divisao_id;
        
        if ($divisao_id <= 0) {
            throw new Exception("ID de divisão inválido: {$divisao_id}");
        }
        
        // Verificar se já existe pool para esta divisão
        if (!isset($this->pools[$divisao_id])) {
            $this->pools[$divisao_id] = [
                'connections' => [],
                'active_count' => 0,
                'total_created' => 0,
                'last_cleanup' => time()
            ];
        }
        
        // Tentar reutilizar conexão existente
        $connection = $this->getAvailableConnection($divisao_id);
        
        if ($connection === null) {
            // Criar nova conexão se não atingiu o limite
            if ($this->pools[$divisao_id]['active_count'] < $this->config['max_connections_per_division']) {
                $connection = $this->createNewConnection($divisao_id);
            } else {
                throw new Exception("Limite de conexões atingido para divisão {$divisao_id}");
            }
        }
        
        // Marcar conexão como ativa
        $this->pools[$divisao_id]['active_count']++;
        
        // Definir divisão no contexto da sessão
        $this->setDivisionContext($connection, $divisao_id);
        
        return $connection;
    }
    
    /**
     * Buscar conexão disponível no pool
     */
    private function getAvailableConnection($divisao_id) {
        if (empty($this->pools[$divisao_id]['connections'])) {
            return null;
        }
        
        foreach ($this->pools[$divisao_id]['connections'] as $key => $conn_data) {
            // Verificar se conexão está disponível e válida
            if (!$conn_data['in_use'] && $this->isConnectionValid($conn_data['connection'])) {
                $this->pools[$divisao_id]['connections'][$key]['in_use'] = true;
                $this->pools[$divisao_id]['connections'][$key]['last_used'] = time();
                return $conn_data['connection'];
            }
        }
        
        return null;
    }
    
    /**
     * Criar nova conexão para a divisão
     */
    private function createNewConnection($divisao_id) {
        try {
            // Configurações de conexão
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '5432';
            $dbname = getenv('DB_NAME') ?: 'qrcred';
            $user = getenv('DB_USER') ?: 'postgres';
            $password = getenv('DB_PASSWORD') ?: '';
            
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => $this->config['connection_timeout']
            ];
            
            // Usar conexão persistente se habilitado
            if ($this->config['enable_persistent']) {
                $options[PDO::ATTR_PERSISTENT] = true;
            }
            
            $pdo = new PDO($dsn, $user, $password, $options);
            
            // Adicionar ao pool
            $this->pools[$divisao_id]['connections'][] = [
                'connection' => $pdo,
                'in_use' => false,
                'created_at' => time(),
                'last_used' => time()
            ];
            
            $this->pools[$divisao_id]['total_created']++;
            
            return $pdo;
            
        } catch (PDOException $e) {
            throw new Exception("Erro ao criar conexão para divisão {$divisao_id}: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar se conexão está válida
     */
    private function isConnectionValid($pdo) {
        try {
            $pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Definir contexto da divisão na sessão do PostgreSQL
     * Útil para Row Level Security (RLS) e auditorias
     */
    private function setDivisionContext($pdo, $divisao_id) {
        try {
            // Definir variável de sessão com a divisão atual
            $stmt = $pdo->prepare("SET SESSION app.current_division = :divisao_id");
            $stmt->execute([':divisao_id' => $divisao_id]);
            
            // Opcional: Definir search_path para schema da divisão (se usar schemas separados)
            // $stmt = $pdo->prepare("SET search_path TO sind, public");
            // $stmt->execute();
            
        } catch (PDOException $e) {
            // Log do erro, mas não interrompe a execução
            error_log("Aviso: Não foi possível definir contexto da divisão: " . $e->getMessage());
        }
    }
    
    /**
     * Liberar conexão de volta ao pool
     */
    public function releaseConnection($divisao_id, $pdo) {
        $divisao_id = (int)$divisao_id;
        
        if (!isset($this->pools[$divisao_id])) {
            return;
        }
        
        foreach ($this->pools[$divisao_id]['connections'] as $key => $conn_data) {
            if ($conn_data['connection'] === $pdo) {
                $this->pools[$divisao_id]['connections'][$key]['in_use'] = false;
                $this->pools[$divisao_id]['connections'][$key]['last_used'] = time();
                $this->pools[$divisao_id]['active_count']--;
                break;
            }
        }
    }
    
    /**
     * Limpar conexões ociosas
     */
    public function cleanupIdleConnections() {
        $current_time = time();
        
        foreach ($this->pools as $divisao_id => &$pool) {
            // Executar limpeza a cada 60 segundos
            if ($current_time - $pool['last_cleanup'] < 60) {
                continue;
            }
            
            foreach ($pool['connections'] as $key => $conn_data) {
                // Remover conexões ociosas há mais tempo que o configurado
                if (!$conn_data['in_use'] && 
                    ($current_time - $conn_data['last_used']) > $this->config['idle_timeout']) {
                    
                    unset($pool['connections'][$key]);
                }
            }
            
            $pool['last_cleanup'] = $current_time;
        }
    }
    
    /**
     * Obter estatísticas do pool
     */
    public function getPoolStats($divisao_id = null) {
        if ($divisao_id !== null) {
            $divisao_id = (int)$divisao_id;
            if (isset($this->pools[$divisao_id])) {
                return [
                    'divisao_id' => $divisao_id,
                    'total_connections' => count($this->pools[$divisao_id]['connections']),
                    'active_connections' => $this->pools[$divisao_id]['active_count'],
                    'total_created' => $this->pools[$divisao_id]['total_created']
                ];
            }
            return null;
        }
        
        // Retornar estatísticas de todas as divisões
        $stats = [];
        foreach ($this->pools as $div_id => $pool) {
            $stats[$div_id] = [
                'divisao_id' => $div_id,
                'total_connections' => count($pool['connections']),
                'active_connections' => $pool['active_count'],
                'total_created' => $pool['total_created']
            ];
        }
        return $stats;
    }
    
    /**
     * Fechar todas as conexões de uma divisão
     */
    public function closeAllConnections($divisao_id) {
        $divisao_id = (int)$divisao_id;
        
        if (isset($this->pools[$divisao_id])) {
            unset($this->pools[$divisao_id]);
        }
    }
    
    /**
     * Destrutor - limpar todas as conexões
     */
    public function __destruct() {
        $this->pools = [];
    }
}

/**
 * ============================================================================
 * EXEMPLO DE USO
 * ============================================================================
 */

/*
// 1. Obter instância do pool manager
$poolManager = ConnectionPoolManager::getInstance();

// 2. Obter conexão para divisão específica
$divisao_id = 1;
try {
    $pdo = $poolManager->getConnection($divisao_id);
    
    // 3. Usar a conexão normalmente
    $stmt = $pdo->prepare("SELECT * FROM sind.associado WHERE id_divisao = :divisao LIMIT 10");
    $stmt->execute([':divisao' => $divisao_id]);
    $results = $stmt->fetchAll();
    
    // 4. Liberar conexão de volta ao pool (opcional, mas recomendado)
    $poolManager->releaseConnection($divisao_id, $pdo);
    
} catch (Exception $e) {
    error_log("Erro ao usar connection pool: " . $e->getMessage());
}

// 5. Ver estatísticas do pool
$stats = $poolManager->getPoolStats($divisao_id);
print_r($stats);

// 6. Limpar conexões ociosas (executar periodicamente)
$poolManager->cleanupIdleConnections();
*/

/**
 * ============================================================================
 * INTEGRAÇÃO COM CLASSE Banco EXISTENTE
 * ============================================================================
 */

/*
// Modificar a classe Banco para usar o pool:

class Banco {
    public static function conectar_postgres($divisao_id = null) {
        if ($divisao_id !== null) {
            // Usar connection pooling
            $poolManager = ConnectionPoolManager::getInstance();
            return $poolManager->getConnection($divisao_id);
        } else {
            // Conexão tradicional (para compatibilidade)
            return self::conectar_postgres_tradicional();
        }
    }
    
    private static function conectar_postgres_tradicional() {
        // Código existente...
    }
}
*/
?>
