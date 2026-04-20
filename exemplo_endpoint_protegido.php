<?php
/**
 * ============================================================================
 * EXEMPLO DE ENDPOINT PROTEGIDO COM TENANT SECURITY
 * ============================================================================
 * Este arquivo demonstra como proteger um endpoint com o middleware
 * Use como referência para proteger outros endpoints do sistema
 * ============================================================================
 */

// 1. Iniciar sessão (se ainda não iniciada)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Incluir dependências
require_once 'Adm/php/banco.php';
require_once 'Adm/php/tenant_security.php';

// 3. Inicializar middleware de segurança
$tenantSec = new TenantSecurity();

// 4. Obter divisão de forma segura (valida automaticamente)
// ANTES: $divisao = $_POST['divisao'];
// DEPOIS:
$divisao = $tenantSec->getSecureDivisao($_POST['divisao'] ?? null);

// 5. Se chegou até aqui, o acesso foi validado!
// Agora pode prosseguir com a lógica do endpoint

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Exemplo: Buscar associados da divisão validada
    $sql = "SELECT 
                id,
                codigo,
                nome,
                cpf,
                salario,
                limite
            FROM sind.associado
            WHERE id_divisao = :divisao
            ORDER BY nome
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':divisao' => $divisao]);
    $associados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retornar resposta JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'success',
        'divisao_validada' => $divisao,
        'total_registros' => count($associados),
        'dados' => $associados
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'mensagem' => 'Erro ao processar requisição',
        'erro' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * ============================================================================
 * COMO APLICAR EM OUTROS ENDPOINTS
 * ============================================================================
 * 
 * PASSO 1: Adicionar no início do arquivo (após session_start se houver)
 * -----------------------------------------------------------------------
 * require_once 'Adm/php/tenant_security.php';
 * $tenantSec = new TenantSecurity();
 * 
 * 
 * PASSO 2: Substituir $_POST['divisao'] por validação segura
 * -----------------------------------------------------------------------
 * ANTES:
 * $divisao = $_POST['divisao'];
 * 
 * DEPOIS:
 * $divisao = $tenantSec->getSecureDivisao($_POST['divisao']);
 * 
 * 
 * PASSO 3: Usar $divisao normalmente nas queries
 * -----------------------------------------------------------------------
 * SELECT * FROM sind.tabela WHERE id_divisao = :divisao
 * 
 * 
 * ENDPOINTS PRIORITÁRIOS PARA PROTEGER:
 * ============================================================================
 * 
 * 🔴 CRÍTICOS (Dados sensíveis):
 * 1. Adm/pages/associado/associado_read2.php
 * 2. Adm/pages/conta/conta_list_mes.php
 * 3. Adm/pages/antecipacao/antecipacao_read2.php
 * 4. Adm/pages/convenio/convenio_read2.php
 * 5. Adm/pages/empregador/empregador_read2.php
 * 
 * 🟡 IMPORTANTES:
 * 6. Adm/pages/producao/producao_read2_totais.php
 * 7. Adm/pages/cheques/cheques_read2_totais.php
 * 8. Adm/pages/cobranca/cobranca_read2_totais.php
 * 
 * 
 * VALIDAÇÃO AVANÇADA (Opcional):
 * ============================================================================
 * 
 * Se quiser lançar exceção em caso de acesso negado:
 * try {
 *     if (!$tenantSec->validateAccess($_POST['divisao'], true)) {
 *         // Não chegará aqui, pois validateAccess lança exceção
 *     }
 * } catch (Exception $e) {
 *     header('Content-Type: application/json; charset=utf-8');
 *     http_response_code(403);
 *     echo json_encode(['status' => 'error', 'mensagem' => $e->getMessage()]);
 *     exit;
 * }
 * 
 * ============================================================================
 */
