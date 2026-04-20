-- ============================================================================
-- ETAPA 2: ESCALABILIDADE - AUDITORIA DE NOMENCLATURA
-- ============================================================================
-- Objetivo: Identificar todas as colunas relacionadas a divisão no banco
-- Data: 2026-02-21
-- ============================================================================

-- 1. LISTAR TODAS AS COLUNAS QUE REFERENCIAM DIVISÃO
SELECT 
    table_schema,
    table_name,
    column_name,
    data_type,
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_schema = 'sind'
  AND (
    column_name LIKE '%divisao%' 
    OR column_name LIKE '%div%'
  )
ORDER BY table_name, column_name;

-- 2. VERIFICAR FOREIGN KEYS EXISTENTES PARA DIVISÃO
SELECT
    tc.table_schema, 
    tc.constraint_name, 
    tc.table_name, 
    kcu.column_name,
    ccu.table_schema AS foreign_table_schema,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name,
    rc.delete_rule,
    rc.update_rule
FROM information_schema.table_constraints AS tc 
JOIN information_schema.key_column_usage AS kcu
  ON tc.constraint_name = kcu.constraint_name
  AND tc.table_schema = kcu.table_schema
JOIN information_schema.constraint_column_usage AS ccu
  ON ccu.constraint_name = tc.constraint_name
  AND ccu.table_schema = tc.table_schema
JOIN information_schema.referential_constraints AS rc
  ON rc.constraint_name = tc.constraint_name
  AND rc.constraint_schema = tc.table_schema
WHERE tc.constraint_type = 'FOREIGN KEY' 
  AND tc.table_schema = 'sind'
  AND (kcu.column_name LIKE '%divisao%' OR kcu.column_name LIKE '%div%')
ORDER BY tc.table_name;

-- 3. VERIFICAR ÍNDICES EXISTENTES EM COLUNAS DE DIVISÃO
SELECT
    schemaname,
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname = 'sind'
  AND (indexdef LIKE '%divisao%' OR indexdef LIKE '%div%')
ORDER BY tablename, indexname;

-- 4. ANÁLISE DE TABELAS PRINCIPAIS (baseado no conhecimento do sistema)
-- Verificar estrutura das tabelas principais:

\d+ sind.associado
\d+ sind.conta
\d+ sind.antecipacao
\d+ sind.convenio
\d+ sind.empregador
\d+ sind.solicitacao_bloqueio
\d+ sind.mes_corrente
\d+ sind.divisao

-- 5. CONTAR REGISTROS POR DIVISÃO (para entender distribuição)
SELECT 
    'associado' as tabela,
    id_divisao,
    COUNT(*) as total
FROM sind.associado
GROUP BY id_divisao
UNION ALL
SELECT 
    'conta' as tabela,
    divisao as id_divisao,
    COUNT(*) as total
FROM sind.conta
GROUP BY divisao
UNION ALL
SELECT 
    'antecipacao' as tabela,
    divisao as id_divisao,
    COUNT(*) as total
FROM sind.antecipacao
GROUP BY divisao
UNION ALL
SELECT 
    'convenio' as tabela,
    divisao as id_divisao,
    COUNT(*) as total
FROM sind.convenio
GROUP BY divisao
ORDER BY tabela, id_divisao;

-- ============================================================================
-- RESULTADO ESPERADO:
-- Este script irá gerar um relatório completo mostrando:
-- 1. Todas as colunas relacionadas a divisão e suas nomenclaturas
-- 2. Foreign Keys existentes e suas regras de CASCADE
-- 3. Índices existentes
-- 4. Estrutura detalhada das tabelas principais
-- 5. Distribuição de dados por divisão
-- ============================================================================
