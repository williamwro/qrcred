-- ============================================================================
-- ETAPA 2: ESCALABILIDADE - PADRONIZAÇÃO DE NOMENCLATURA
-- ============================================================================
-- Objetivo: Padronizar todas as colunas para usar 'id_divisao'
-- Data: 2026-02-21
-- IMPORTANTE: Execute a auditoria primeiro para confirmar as tabelas afetadas
-- ============================================================================

-- ATENÇÃO: Faça backup do banco antes de executar este script!
-- pg_dump -U postgres -d qrcred -F c -b -v -f qrcred_backup_antes_padronizacao.backup

BEGIN;

-- ============================================================================
-- FASE 1: RENOMEAR COLUNAS PARA PADRONIZAR NOMENCLATURA
-- ============================================================================

-- Tabela: sind.conta (divisao -> id_divisao)
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'sind' 
        AND table_name = 'conta' 
        AND column_name = 'divisao'
    ) THEN
        ALTER TABLE sind.conta RENAME COLUMN divisao TO id_divisao;
        RAISE NOTICE 'Coluna sind.conta.divisao renomeada para id_divisao';
    ELSE
        RAISE NOTICE 'Coluna sind.conta.divisao já foi renomeada ou não existe';
    END IF;
END $$;

-- Tabela: sind.antecipacao (divisao -> id_divisao)
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'sind' 
        AND table_name = 'antecipacao' 
        AND column_name = 'divisao'
    ) THEN
        ALTER TABLE sind.antecipacao RENAME COLUMN divisao TO id_divisao;
        RAISE NOTICE 'Coluna sind.antecipacao.divisao renomeada para id_divisao';
    ELSE
        RAISE NOTICE 'Coluna sind.antecipacao.divisao já foi renomeada ou não existe';
    END IF;
END $$;

-- Tabela: sind.convenio (divisao -> id_divisao)
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'sind' 
        AND table_name = 'convenio' 
        AND column_name = 'divisao'
    ) THEN
        ALTER TABLE sind.convenio RENAME COLUMN divisao TO id_divisao;
        RAISE NOTICE 'Coluna sind.convenio.divisao renomeada para id_divisao';
    ELSE
        RAISE NOTICE 'Coluna sind.convenio.divisao já foi renomeada ou não existe';
    END IF;
END $$;

-- Tabela: sind.empregador (divisao -> id_divisao)
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'sind' 
        AND table_name = 'empregador' 
        AND column_name = 'divisao'
    ) THEN
        ALTER TABLE sind.empregador RENAME COLUMN divisao TO id_divisao;
        RAISE NOTICE 'Coluna sind.empregador.divisao renomeada para id_divisao';
    ELSE
        RAISE NOTICE 'Coluna sind.empregador.divisao já foi renomeada ou não existe';
    END IF;
END $$;

-- Tabela: sind.valor_taxa_cartao (divisao -> id_divisao)
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'sind' 
        AND table_name = 'valor_taxa_cartao' 
        AND column_name = 'divisao'
    ) THEN
        ALTER TABLE sind.valor_taxa_cartao RENAME COLUMN divisao TO id_divisao;
        RAISE NOTICE 'Coluna sind.valor_taxa_cartao.divisao renomeada para id_divisao';
    ELSE
        RAISE NOTICE 'Coluna sind.valor_taxa_cartao.divisao já foi renomeada ou não existe';
    END IF;
END $$;

-- Tabela: sind.mes_corrente (id_divisao já está correto, apenas verificar)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'sind' 
        AND table_name = 'mes_corrente' 
        AND column_name = 'id_divisao'
    ) THEN
        RAISE WARNING 'ATENÇÃO: Tabela sind.mes_corrente não possui coluna id_divisao!';
    ELSE
        RAISE NOTICE 'Tabela sind.mes_corrente.id_divisao já está correta';
    END IF;
END $$;

-- Tabela: sind.associado (id_divisao já está correto, apenas verificar)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'sind' 
        AND table_name = 'associado' 
        AND column_name = 'id_divisao'
    ) THEN
        RAISE WARNING 'ATENÇÃO: Tabela sind.associado não possui coluna id_divisao!';
    ELSE
        RAISE NOTICE 'Tabela sind.associado.id_divisao já está correta';
    END IF;
END $$;

-- ============================================================================
-- FASE 2: ADICIONAR COMENTÁRIOS NAS COLUNAS (Documentação)
-- ============================================================================

COMMENT ON COLUMN sind.associado.id_divisao IS 'FK para sind.divisao - Identifica a divisão/cliente do associado';
COMMENT ON COLUMN sind.conta.id_divisao IS 'FK para sind.divisao - Identifica a divisão/cliente da conta';
COMMENT ON COLUMN sind.antecipacao.id_divisao IS 'FK para sind.divisao - Identifica a divisão/cliente da antecipação';
COMMENT ON COLUMN sind.convenio.id_divisao IS 'FK para sind.divisao - Identifica a divisão/cliente do convênio';
COMMENT ON COLUMN sind.empregador.id_divisao IS 'FK para sind.divisao - Identifica a divisão/cliente do empregador';
COMMENT ON COLUMN sind.mes_corrente.id_divisao IS 'FK para sind.divisao - Identifica a divisão/cliente do mês corrente';
COMMENT ON COLUMN sind.valor_taxa_cartao.id_divisao IS 'FK para sind.divisao - Identifica a divisão/cliente da taxa';
COMMENT ON COLUMN sind.solicitacao_bloqueio.id_divisao IS 'FK para sind.divisao - Identifica a divisão/cliente da solicitação';

-- ============================================================================
-- FASE 3: VERIFICAÇÃO FINAL
-- ============================================================================

-- Listar todas as colunas id_divisao criadas/renomeadas
SELECT 
    table_name,
    column_name,
    data_type,
    is_nullable
FROM information_schema.columns
WHERE table_schema = 'sind'
  AND column_name = 'id_divisao'
ORDER BY table_name;

COMMIT;

-- ============================================================================
-- ROLLBACK EM CASO DE ERRO
-- ============================================================================
-- Se algo der errado, execute: ROLLBACK;
-- E restaure o backup: pg_restore -U postgres -d qrcred qrcred_backup_antes_padronizacao.backup
-- ============================================================================

RAISE NOTICE '✅ Padronização de nomenclatura concluída com sucesso!';
