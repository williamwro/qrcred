-- ============================================================================
-- SISTEMA DE SEGURANÇA MULTI-TENANT - QRCred
-- ============================================================================
-- Criação de tabelas para auditoria e controle de acesso por divisão
-- Data: 2026-02-12
-- Versão: 1.0
-- ============================================================================

-- 1. Tabela de Log de Acesso Cross-Tenant (tentativas de acesso indevido)
CREATE TABLE IF NOT EXISTS sind.tenant_security_log (
    id BIGSERIAL PRIMARY KEY,
    data_hora TIMESTAMP DEFAULT NOW(),
    codigo_usuario INTEGER,
    username VARCHAR(100),
    divisao_usuario INTEGER,
    divisao_tentada INTEGER,
    endpoint VARCHAR(500),
    ip_address VARCHAR(45),
    user_agent TEXT,
    metodo_http VARCHAR(10),
    parametros_post TEXT,
    parametros_get TEXT,
    session_id VARCHAR(100),
    bloqueado BOOLEAN DEFAULT false,
    motivo VARCHAR(500),
    stack_trace TEXT
);

-- Índices para performance
CREATE INDEX IF NOT EXISTS idx_tenant_security_log_usuario 
    ON sind.tenant_security_log(codigo_usuario);
CREATE INDEX IF NOT EXISTS idx_tenant_security_log_data 
    ON sind.tenant_security_log(data_hora DESC);
CREATE INDEX IF NOT EXISTS idx_tenant_security_log_bloqueado 
    ON sind.tenant_security_log(bloqueado) WHERE bloqueado = true;
CREATE INDEX IF NOT EXISTS idx_tenant_security_log_divisao 
    ON sind.tenant_security_log(divisao_usuario, divisao_tentada);

-- 2. Tabela de Configuração de Segurança por Divisão
CREATE TABLE IF NOT EXISTS sind.tenant_security_config (
    id_divisao INTEGER PRIMARY KEY REFERENCES sind.divisao(id_divisao),
    rls_enabled BOOLEAN DEFAULT false,
    require_session_validation BOOLEAN DEFAULT true,
    max_failed_attempts INTEGER DEFAULT 5,
    lockout_duration_minutes INTEGER DEFAULT 30,
    allow_cross_tenant_admin BOOLEAN DEFAULT false,
    ip_whitelist TEXT[], -- Array de IPs permitidos
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- 3. Tabela de Mapeamento Usuário-Divisão (para validação)
CREATE TABLE IF NOT EXISTS sind.usuario_divisao_permitida (
    id BIGSERIAL PRIMARY KEY,
    codigo_usuario INTEGER REFERENCES sind.usuarios(codigo),
    id_divisao INTEGER REFERENCES sind.divisao(id_divisao),
    is_admin BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT NOW(),
    created_by INTEGER,
    UNIQUE(codigo_usuario, id_divisao)
);

-- Índice para validação rápida
CREATE INDEX IF NOT EXISTS idx_usuario_divisao_permitida 
    ON sind.usuario_divisao_permitida(codigo_usuario, id_divisao);

-- 4. Tabela de Estatísticas de Acesso por Divisão
CREATE TABLE IF NOT EXISTS sind.tenant_access_stats (
    id BIGSERIAL PRIMARY KEY,
    id_divisao INTEGER REFERENCES sind.divisao(id_divisao),
    data_referencia DATE DEFAULT CURRENT_DATE,
    total_acessos INTEGER DEFAULT 0,
    total_usuarios_unicos INTEGER DEFAULT 0,
    total_tentativas_bloqueadas INTEGER DEFAULT 0,
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(id_divisao, data_referencia)
);

-- 5. Popular configuração padrão para divisões existentes
INSERT INTO sind.tenant_security_config (id_divisao, rls_enabled, require_session_validation)
SELECT id_divisao, false, true 
FROM sind.divisao
WHERE id_divisao NOT IN (SELECT id_divisao FROM sind.tenant_security_config)
ON CONFLICT (id_divisao) DO NOTHING;

-- 6. Popular mapeamento usuário-divisão baseado em dados existentes
INSERT INTO sind.usuario_divisao_permitida (codigo_usuario, id_divisao, is_admin)
SELECT codigo, divisao, false
FROM sind.usuarios
WHERE divisao IS NOT NULL
ON CONFLICT (codigo_usuario, id_divisao) DO NOTHING;

-- 7. Função para atualizar estatísticas
CREATE OR REPLACE FUNCTION sind.update_tenant_access_stats(
    p_id_divisao INTEGER,
    p_codigo_usuario INTEGER,
    p_bloqueado BOOLEAN
) RETURNS VOID AS $$
BEGIN
    INSERT INTO sind.tenant_access_stats (id_divisao, data_referencia, total_acessos, total_usuarios_unicos, total_tentativas_bloqueadas)
    VALUES (p_id_divisao, CURRENT_DATE, 1, 1, CASE WHEN p_bloqueado THEN 1 ELSE 0 END)
    ON CONFLICT (id_divisao, data_referencia) 
    DO UPDATE SET 
        total_acessos = sind.tenant_access_stats.total_acessos + 1,
        total_tentativas_bloqueadas = sind.tenant_access_stats.total_tentativas_bloqueadas + CASE WHEN p_bloqueado THEN 1 ELSE 0 END,
        updated_at = NOW();
END;
$$ LANGUAGE plpgsql;

-- 8. Comentários nas tabelas
COMMENT ON TABLE sind.tenant_security_log IS 'Log de tentativas de acesso cross-tenant e violações de segurança';
COMMENT ON TABLE sind.tenant_security_config IS 'Configurações de segurança por divisão/tenant';
COMMENT ON TABLE sind.usuario_divisao_permitida IS 'Mapeamento de quais divisões cada usuário pode acessar';
COMMENT ON TABLE sind.tenant_access_stats IS 'Estatísticas de acesso por divisão para monitoramento';

-- ============================================================================
-- FIM DA CRIAÇÃO DAS TABELAS
-- ============================================================================
