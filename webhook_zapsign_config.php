<?php
/**
 * Configuração do Webhook ZapSign
 * Versão 1.1 - Atualizado para compatibilidade com formato real da ZapSign
 * 
 * INSTRUÇÕES DE INSTALAÇÃO:
 * 
 * 1. Configurar este arquivo com suas credenciais de banco
 * 2. Fazer upload dos arquivos webhook_zapsign.php e este arquivo para seu servidor
 * 3. Configurar o webhook na ZapSign:
 *    - Acesse: https://app.zapsign.com.br/dashboard/api
 *    - Vá em "Webhooks"
 *    - Adicione novo webhook com a URL: https://seudominio.com/webhook_zapsign.php
 *    - Selecione os eventos: "doc_created" (documento criado)
 * 
 * 4. Testar o webhook:
 *    - Crie um documento de teste na ZapSign
 *    - O webhook será acionado na criação (não na assinatura)
 *    - Verifique os logs em webhook_zapsign.log
 *    - Verifique se os dados foram gravados na tabela sind.associados_sasmais
 */

// =============================================================================
// CONFIGURAÇÕES DO BANCO DE DADOS POSTGRESQL
// =============================================================================

// ATENÇÃO: Este webhook usa o arquivo de conexão existente do sistema
// Arquivo: Adm/php/banco.php
// Classe: Banco::conectar_postgres()
// 
// Não é necessário configurar credenciais aqui, pois serão usadas
// as configurações já existentes no seu sistema.

// =============================================================================
// CONFIGURAÇÕES DA API ZAPSIGN
// =============================================================================

// Token de acesso à API ZapSign para consultar dados completos
// Necessário quando o webhook só retorna token sem CPF
define('ZAPSIGN_API_TOKEN', '67fc4b3e-7efe-4921-883b-3b542bd70cce24228a44-8921-4f6f-ab2a-10fa22f5caa4');

// URL base da API ZapSign
define('ZAPSIGN_API_BASE_URL', 'https://api.zapsign.com.br/api/v1');

// =============================================================================
// IMPORTANTE: CONFIGURAÇÃO PARA OBTER CPF DOS SIGNATÁRIOS
// =============================================================================
// 
// Para que o webhook consiga obter o CPF dos signatários, é ESSENCIAL que:
// 
// 1. Na criação dos documentos na ZapSign, configure o signatário com:
//    - "require_cpf": true
// 
// 2. Exemplo de configuração do signatário:
//    {
//        "name": "Nome do Signatário",
//        "email": "email@exemplo.com",
//        "phone_country": "55",
//        "phone_number": "11999999999",
//        "require_cpf": true  <-- ESSENCIAL para coletar CPF
//    }
// 
// 3. O webhook agora consulta DOIS endpoints da ZapSign:
//    - GET /docs/{doc_token}/           (dados do documento)
//    - GET /signers/{signer_token}/     (dados específicos do signatário - pode conter CPF)
// 
// 4. O CPF será coletado quando o signatário completar o processo de assinatura
//    e informar o CPF conforme solicitado pela configuração "require_cpf": true
//
// =============================================================================
// COMO OBTER O SIGNER TOKEN
// =============================================================================
//
// O Signer Token é associado a um signatário específico e pode ser obtido de várias formas:
//
// 1. VIA WEBHOOK (Automático):
//    - O webhook recebe automaticamente o signer token no campo 'token' de cada signatário
//    - Exemplo: $signerToken = $signer['token'] ?? '';
//
// 2. VIA URL DE ASSINATURA (Manual):
//    - URL: https://app.zapsign.com.br/verificar/92b36ec9-a449-4574-8ff0-5cc2c5ab7
//    - Signer Token: 92b36ec9-a449-4574-8ff0-5cc2c5ab7 (parte após "/verificar/")
//
// 3. VIA API (Consulta):
//    - GET /docs/{doc_token}/ retorna array 'signers' com tokens de cada signatário
//    - Cada signatário tem seu próprio token único
//
// O webhook já captura automaticamente o signer token e o utiliza para:
// - Consultar dados específicos do signatário via GET /signers/{signer_token}/
// - Identificar unicamente cada signatário no documento
//
// =============================================================================
// SISTEMA DE REPROCESSAMENTO AUTOMÁTICO
// =============================================================================
//
// PROBLEMA IDENTIFICADO:
// - O webhook recebe "doc_created" quando o documento é criado
// - Neste momento, o signatário ainda não completou: CPF, selfie, documentos, etc.
// - Os dados completos só ficam disponíveis APÓS o signatário finalizar todo o processo
//
// SOLUÇÃO IMPLEMENTADA:
// 1. Webhook inicial registra documento na tabela "sind.documentos_pendentes_zapsign"
// 2. Sistema de reprocessamento consulta periodicamente a API ZapSign
// 3. Quando dados estão disponíveis, cria registro completo em "sind.associados_sasmais"
//
// ENDPOINTS DISPONÍVEIS:
// - webhook_zapsign.php?reprocessar=1  (reprocessar documentos pendentes)
// - auto_reprocessar.php               (script para cron job)
//
// CONFIGURAÇÃO DO CRON (recomendado a cada 5 minutos):
// */5 * * * * wget -q -O /dev/null "https://sas.makecard.com.br/webhook_zapsign.php?reprocessar=1"
//
// OU usando o script dedicado:
// */5 * * * * /usr/bin/php /caminho/para/auto_reprocessar.php >/dev/null 2>&1
//
// FLUXO COMPLETO:
// 1. Usuário assina documento → ZapSign envia webhook "doc_created"
// 2. Webhook registra na tabela "documentos_pendentes_zapsign" 
// 3. Usuário completa processo (CPF, selfie, documentos)
// 4. Cron executa reprocessamento a cada 5 minutos
// 5. Reprocessamento consulta API e obtém dados completos
// 6. Sistema cria registro final em "associados_sasmais" com todos os dados
//
// TEMPOS CONFIGURADOS:
// - Primeira tentativa: 5 minutos após criação do documento
// - Tentativas imediatas: 4 tentativas com 2 minutos entre cada (total ~8 minutos)
// - Entre reprocessamentos: 10 minutos
// - Máximo de tentativas no reprocessamento: 10 (equivale a ~2 horas de tentativas)
//
// ANTI-DUPLICATA:
// - Sistema ATUALIZA registro existente em vez de criar novos
// - Busca por doc_token na tabela sind.associados_sasmais
// - Se encontra registro existente: ATUALIZA com dados completos
// - Se não encontra: cria novo (caso raro)
// - ELIMINA duplicatas desnecessárias na tabela principal
//
// CAMPOS ATUALIZADOS QUANDO CPF É ENCONTRADO:
// - event = 'doc_signed' (marca como documento assinado)
// - has_signed = true (marca signatário como tendo assinado)
// - cel_informado = telefone retornado pelo webhook ZapSign
// - cpf = CPF obtido via API ZapSign
// - email = email obtido via API ZapSign
// - autorizado = true, aceitou_termo = true

// =============================================================================
// CONFIGURAÇÕES DE SEGURANÇA (OPCIONAL)
// =============================================================================

// Token de segurança para validar requisições (opcional)
// Deixe vazio para desabilitar validação de token
define('WEBHOOK_SECRET_TOKEN', ''); 

// IPs permitidos para fazer requisições ao webhook (opcional)
// Deixe vazio para permitir qualquer IP
// Formato: ['IP1', 'IP2', '192.168.1.0/24']
define('ALLOWED_IPS', []);

// =============================================================================
// CONFIGURAÇÕES DE LOG
// =============================================================================

// Habilitar logs detalhados (true para desenvolvimento, false para produção)
define('ENABLE_DEBUG_LOGS', true);

// Arquivo de log (caminho relativo ao webhook_zapsign.php)
define('LOG_FILE', __DIR__ . '/webhook_zapsign.log');

// Tamanho máximo do arquivo de log em bytes (0 = sem limite)
define('MAX_LOG_SIZE', 10 * 1024 * 1024); // 10MB

// =============================================================================
// ATUALIZAÇÕES v1.1 - COMPATIBILIDADE COM ZAPSIGN REAL
// =============================================================================

/*
 * MUDANÇAS IMPLEMENTADAS:
 * 
 * 1. MAPEAMENTO AUTOMÁTICO DE CAMPOS:
 *    - ZapSign envia: "event_type" → Webhook mapeia para: "event"
 *    - ZapSign envia: "token" → Webhook mapeia para: "doc_token"
 *    - ZapSign envia: "name" → Webhook mapeia para: "doc_name"
 * 
 * 2. STATUS DE ASSINATURA:
 *    - ZapSign envia: "status": "signed" → Webhook converte para: has_signed = 1
 *    - Mantém compatibilidade com formato antigo: "has_signed": true
 * 
 * 3. CAMPO CEL_INFORMADO:
 *    - Adicionado suporte ao campo cel_informado
 *    - Preenchido com string vazia (celular não disponível no webhook)
 * 
 * 4. FILTRO POR TIPO DE DOCUMENTO:
 *    - Processa documentos de dois tipos:
 *      • "TERMO DE ADESÃO DO CARTÃO CONVÊNIO" (tipo=1) - Token: 4bdad7db-07ae-4505-b8cb-0bee880f6fdd
 *      • "Contrato de Antecipação Salarial" (tipo=2) - Token: 762dbe4c-654b-432b-a7a9-38435966e0aa
 *    - Outros documentos são ignorados automaticamente
 *    - Identificação por token (preferencial) ou nome do documento
 * 
 * 5. LOGS APRIMORADOS:
 *    - Mais informações de debug para troubleshooting
 *    - Mapeamento de campos visível nos logs
 */

// =============================================================================
// CONFIGURAÇÕES DA TABELA
// =============================================================================

// Nome da tabela onde serão gravados os dados
define('TABLE_NAME', 'sind.associados_sasmais');

// Mapeamento de campos ZapSign → Formato Interno (automático no webhook)
define('FIELD_MAPPING', [
    // ZapSign → Interno
    'event_type' => 'event',
    'token' => 'doc_token', 
    'name' => 'doc_name',
    'signed_at' => 'signed_at',
    'signer_name' => 'name',
    'signer_email' => 'email',
    'signer_cpf' => 'cpf',
    'status_signed' => 'has_signed', // "signed" → true
    'cel_informado' => 'cel_informado' // vazio - não disponível no webhook
]);

// =============================================================================
// EXEMPLO DE TESTE DO WEBHOOK
// =============================================================================

/**
 * Para testar o webhook manualmente, você pode usar este comando curl:
 * 
 * curl -X POST https://seudominio.com/webhook_zapsign.php \
 *   -H "Content-Type: application/json" \
 *   -d '{
 *     "event_type": "doc_created",
 *     "token": "be5f6334-30fd-4d88-81b2-cf6aac9e5a64",
 *     "name": "TERMO DE ADESÃO DO CARTÃO CONVÊNIO",
 *     "signed_at": "2025-07-08T13:47:40.599551Z",
 *     "signers": [
 *       {
 *         "name": "William ribeiro de oliveira",
 *         "email": "william@makecard.com.br",
 *         "cpf": "023.995.136-06",
 *         "status": "pending",
 *         "signed_at": "2025-07-08T13:47:40.599551Z",
 *         "token": "18d0163b-5493-4cea-a75a-fbbf8a0670a5"
 *       }
 *     ]
 *   }'
 * 
 * NOTA: Este exemplo usa o formato real enviado pela ZapSign.
 * O webhook automaticamente mapeia os campos:
 * - event_type → event
 * - token → doc_token  
 * - name → doc_name
 * - status: "signed" → has_signed: true
 */

// =============================================================================
// VERIFICAÇÃO DA ESTRUTURA DA TABELA
// =============================================================================

/**
 * A tabela sind.associados_sasmais deve ter pelo menos estas colunas:
 * 
 * CREATE TABLE IF NOT EXISTS sind.associados_sasmais (
 *     id SERIAL PRIMARY KEY,
 *     codigo VARCHAR(50),
 *     nome VARCHAR(255),
 *     celular VARCHAR(20),
 *     data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *     autorizado INTEGER DEFAULT 0,
 *     aceitou_termo INTEGER DEFAULT 0,
 *     event VARCHAR(50),
 *     doc_token VARCHAR(255),
 *     doc_name VARCHAR(500),
 *     signed_at TIMESTAMP,
 *     name VARCHAR(255),
 *     email VARCHAR(255),
 *     cpf VARCHAR(11),
 *     has_signed INTEGER DEFAULT 0,
 *     cel_informado VARCHAR(20)
 * );
 */

// =============================================================================
// MONITORAMENTO E ALERTAS (OPCIONAL)
// =============================================================================

// Email para receber alertas de erro (opcional)
define('ALERT_EMAIL', '');

// Webhook para notificações (ex: Slack, Discord) (opcional) 
define('ALERT_WEBHOOK_URL', '');

/**
 * Função para enviar alertas (implementar conforme necessário)
 */
function sendAlert($message, $level = 'info') {
    if (ALERT_EMAIL && $level === 'error') {
        // Implementar envio de email
        // mail(ALERT_EMAIL, 'Webhook ZapSign - Erro', $message);
    }
    
    if (ALERT_WEBHOOK_URL && $level === 'error') {
        // Implementar notificação via webhook
        // file_get_contents(ALERT_WEBHOOK_URL, false, stream_context_create([
        //     'http' => [
        //         'method' => 'POST',
        //         'header' => 'Content-Type: application/json',
        //         'content' => json_encode(['text' => $message])
        //     ]
        // ]));
    }
}

// =============================================================================
// STATUS DO WEBHOOK
// =============================================================================

/**
 * Para verificar o status do webhook, acesse:
 * https://seudominio.com/webhook_zapsign.php?status
 * 
 * Isso retornará informações sobre:
 * - Versão do webhook (v1.1)
 * - Configuração atual
 * - Status da conexão com banco
 * - Método HTTP detectado
 * - Arquivo de configuração usado
 */
?> 