-- ============================================================================
-- ETAPA 2: ESCALABILIDADE - CRIAR ÍNDICES COMPOSTOS
-- ============================================================================
-- Objetivo: Otimizar queries com índices compostos (id_divisao + outros campos)
-- Data: 2026-02-21
-- IMPORTANTE: Execute após criar as Foreign Keys
-- ============================================================================

-- ATENÇÃO: Criação de índices pode demorar em tabelas grandes
-- Recomenda-se executar em horário de baixo uso

BEGIN;

-- ============================================================================
-- FASE 1: REMOVER ÍNDICES ANTIGOS (se existirem)
-- ============================================================================

DROP INDEX IF EXISTS sind.idx_associado_divisao_empregador;
DROP INDEX IF EXISTS sind.idx_associado_divisao_codigo;
DROP INDEX IF EXISTS sind.idx_associado_divisao_nome;
DROP INDEX IF EXISTS sind.idx_conta_divisao_mes;
DROP INDEX IF EXISTS sind.idx_conta_divisao_associado;
DROP INDEX IF EXISTS sind.idx_conta_divisao_data;
DROP INDEX IF EXISTS sind.idx_antecipacao_divisao_mes;
DROP INDEX IF EXISTS sind.idx_antecipacao_divisao_associado;
DROP INDEX IF EXISTS sind.idx_antecipacao_divisao_aprovado;
DROP INDEX IF EXISTS sind.idx_convenio_divisao_codigo;
DROP INDEX IF EXISTS sind.idx_convenio_divisao_status;
DROP INDEX IF EXISTS sind.idx_empregador_divisao_codigo;
DROP INDEX IF EXISTS sind.idx_solicitacao_divisao_situacao;
DROP INDEX IF EXISTS sind.idx_solicitacao_divisao_associado;
DROP INDEX IF EXISTS sind.idx_mes_corrente_divisao;

-- ============================================================================
-- FASE 2: CRIAR ÍNDICES COMPOSTOS PARA TABELA ASSOCIADO
-- ============================================================================

-- Índice: divisao + empregador (consultas por empregador dentro de uma divisão)
CREATE INDEX idx_associado_divisao_empregador 
ON sind.associado (id_divisao, empregador)
INCLUDE (id, nome, codigo);

COMMENT ON INDEX sind.idx_associado_divisao_empregador IS 
'Otimiza consultas de associados por divisão e empregador';

-- Índice: divisao + codigo (busca por matrícula dentro de uma divisão)
CREATE INDEX idx_associado_divisao_codigo 
ON sind.associado (id_divisao, codigo);

COMMENT ON INDEX sind.idx_associado_divisao_codigo IS 
'Otimiza busca de associado por matrícula dentro da divisão';

-- Índice: divisao + nome (busca por nome dentro de uma divisão)
CREATE INDEX idx_associado_divisao_nome 
ON sind.associado (id_divisao, nome);

COMMENT ON INDEX sind.idx_associado_divisao_nome IS 
'Otimiza busca de associado por nome dentro da divisão';

-- ============================================================================
-- FASE 3: CRIAR ÍNDICES COMPOSTOS PARA TABELA CONTA
-- ============================================================================

-- Índice: divisao + mes (consultas de contas por mês dentro de uma divisão)
CREATE INDEX idx_conta_divisao_mes 
ON sind.conta (id_divisao, mes)
INCLUDE (id_associado, valor, data);

COMMENT ON INDEX sind.idx_conta_divisao_mes IS 
'Otimiza consultas de contas por mês dentro da divisão';

-- Índice: divisao + associado (consultas de contas de um associado)
CREATE INDEX idx_conta_divisao_associado 
ON sind.conta (id_divisao, id_associado)
INCLUDE (mes, valor, data);

COMMENT ON INDEX sind.idx_conta_divisao_associado IS 
'Otimiza consultas de contas por associado dentro da divisão';

-- Índice: divisao + data (consultas por período)
CREATE INDEX idx_conta_divisao_data 
ON sind.conta (id_divisao, data DESC);

COMMENT ON INDEX sind.idx_conta_divisao_data IS 
'Otimiza consultas de contas por data dentro da divisão';

-- ============================================================================
-- FASE 4: CRIAR ÍNDICES COMPOSTOS PARA TABELA ANTECIPACAO
-- ============================================================================

-- Índice: divisao + mes (consultas de antecipações por mês)
CREATE INDEX idx_antecipacao_divisao_mes 
ON sind.antecipacao (id_divisao, mes)
INCLUDE (id_associado, valor_solicitado, aprovado);

COMMENT ON INDEX sind.idx_antecipacao_divisao_mes IS 
'Otimiza consultas de antecipações por mês dentro da divisão';

-- Índice: divisao + associado (consultas de antecipações de um associado)
CREATE INDEX idx_antecipacao_divisao_associado 
ON sind.antecipacao (id_divisao, id_associado)
INCLUDE (mes, valor_solicitado, aprovado);

COMMENT ON INDEX sind.idx_antecipacao_divisao_associado IS 
'Otimiza consultas de antecipações por associado dentro da divisão';

-- Índice: divisao + aprovado (filtro por situação de aprovação)
CREATE INDEX idx_antecipacao_divisao_aprovado 
ON sind.antecipacao (id_divisao, aprovado)
WHERE aprovado IS NOT NULL;

COMMENT ON INDEX sind.idx_antecipacao_divisao_aprovado IS 
'Otimiza filtros por situação de aprovação dentro da divisão';

-- ============================================================================
-- FASE 5: CRIAR ÍNDICES COMPOSTOS PARA TABELA CONVENIO
-- ============================================================================

-- Índice: divisao + codigo (busca por código de convênio)
CREATE INDEX idx_convenio_divisao_codigo 
ON sind.convenio (id_divisao, codigo)
INCLUDE (razaosocial, nomefantasia);

COMMENT ON INDEX sind.idx_convenio_divisao_codigo IS 
'Otimiza busca de convênio por código dentro da divisão';

-- Índice: divisao + status (filtro por convênios ativos/inativos)
CREATE INDEX idx_convenio_divisao_status 
ON sind.convenio (id_divisao, status)
WHERE status = 1;

COMMENT ON INDEX sind.idx_convenio_divisao_status IS 
'Otimiza filtro de convênios ativos dentro da divisão';

-- ============================================================================
-- FASE 6: CRIAR ÍNDICES COMPOSTOS PARA TABELA EMPREGADOR
-- ============================================================================

-- Índice: divisao + codigo (busca por código de empregador)
CREATE INDEX idx_empregador_divisao_codigo 
ON sind.empregador (id_divisao, codigo)
INCLUDE (nome, abreviacao);

COMMENT ON INDEX sind.idx_empregador_divisao_codigo IS 
'Otimiza busca de empregador por código dentro da divisão';

-- ============================================================================
-- FASE 7: CRIAR ÍNDICES COMPOSTOS PARA TABELA SOLICITACAO_BLOQUEIO
-- ============================================================================

-- Índice: divisao + situacao (filtro por situação da solicitação)
CREATE INDEX idx_solicitacao_divisao_situacao 
ON sind.solicitacao_bloqueio (id_divisao, id_situacao)
INCLUDE (id_associado, cod_verificacao, data_hora);

COMMENT ON INDEX sind.idx_solicitacao_divisao_situacao IS 
'Otimiza filtro de solicitações por situação dentro da divisão';

-- Índice: divisao + associado (consultas de solicitações de um associado)
CREATE INDEX idx_solicitacao_divisao_associado 
ON sind.solicitacao_bloqueio (id_divisao, id_associado)
INCLUDE (id_situacao, data_hora);

COMMENT ON INDEX sind.idx_solicitacao_divisao_associado IS 
'Otimiza consultas de solicitações por associado dentro da divisão';

-- ============================================================================
-- FASE 8: CRIAR ÍNDICE ÚNICO PARA TABELA MES_CORRENTE
-- ============================================================================

-- Índice único: apenas um mês corrente por divisão
CREATE UNIQUE INDEX idx_mes_corrente_divisao 
ON sind.mes_corrente (id_divisao);

COMMENT ON INDEX sind.idx_mes_corrente_divisao IS 
'Garante que cada divisão tenha apenas um mês corrente';

-- ============================================================================
-- FASE 9: ANÁLISE E ESTATÍSTICAS
-- ============================================================================

-- Atualizar estatísticas das tabelas para otimizar o planner
ANALYZE sind.associado;
ANALYZE sind.conta;
ANALYZE sind.antecipacao;
ANALYZE sind.convenio;
ANALYZE sind.empregador;
ANALYZE sind.solicitacao_bloqueio;
ANALYZE sind.mes_corrente;

-- ============================================================================
-- FASE 10: VERIFICAÇÃO FINAL
-- ============================================================================

-- Listar todos os índices criados
SELECT
    schemaname,
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname = 'sind'
  AND indexname LIKE 'idx_%'
ORDER BY tablename, indexname;

-- Tamanho dos índices criados
SELECT
    schemaname,
    tablename,
    indexname,
    pg_size_pretty(pg_relation_size(schemaname||'.'||indexname)) as index_size
FROM pg_indexes
WHERE schemaname = 'sind'
  AND indexname LIKE 'idx_%'
ORDER BY pg_relation_size(schemaname||'.'||indexname) DESC;

COMMIT;

-- ============================================================================
-- MANUTENÇÃO DOS ÍNDICES
-- ============================================================================
-- Para manutenção periódica, execute:
-- 
-- REINDEX SCHEMA sind; -- Reconstruir todos os índices
-- VACUUM ANALYZE sind.associado; -- Atualizar estatísticas
-- 
-- Ou configure autovacuum no postgresql.conf:
-- autovacuum = on
-- autovacuum_analyze_scale_factor = 0.05
-- autovacuum_vacuum_scale_factor = 0.1
-- ============================================================================

RAISE NOTICE '✅ Índices compostos criados com sucesso!';
RAISE NOTICE 'Total de índices criados: 15';
RAISE NOTICE 'Execute EXPLAIN ANALYZE nas queries principais para verificar uso dos índices';
