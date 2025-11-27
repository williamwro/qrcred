-- =====================================================
-- Script para remover constraint única do campo 'codigo'
-- Tabela: sind.associados_sasmais
-- =====================================================

-- Verificar se a constraint existe antes de tentar remover
DO $$
BEGIN
    -- Tentar remover a constraint única
    IF EXISTS (
        SELECT 1 
        FROM information_schema.table_constraints 
        WHERE constraint_name = 'uk_associados_sasmais_codigo'
        AND table_schema = 'sind'
        AND table_name = 'associados_sasmais'
    ) THEN
        -- Remover a constraint única
        ALTER TABLE sind.associados_sasmais 
        DROP CONSTRAINT uk_associados_sasmais_codigo;
        
        RAISE NOTICE 'Constraint única "uk_associados_sasmais_codigo" removida com sucesso!';
    ELSE
        RAISE NOTICE 'Constraint única "uk_associados_sasmais_codigo" não encontrada.';
    END IF;
END $$;

-- Verificar outras possíveis constraints únicas no campo codigo
DO $$
DECLARE
    constraint_record RECORD;
BEGIN
    -- Buscar todas as constraints únicas que incluem o campo 'codigo'
    FOR constraint_record IN 
        SELECT 
            tc.constraint_name,
            tc.table_schema,
            tc.table_name
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu 
            ON tc.constraint_name = kcu.constraint_name
            AND tc.table_schema = kcu.table_schema
        WHERE tc.constraint_type = 'UNIQUE'
            AND tc.table_schema = 'sind'
            AND tc.table_name = 'associados_sasmais'
            AND kcu.column_name = 'codigo'
    LOOP
        -- Executar comando para remover a constraint
        EXECUTE format('ALTER TABLE %I.%I DROP CONSTRAINT %I', 
                      constraint_record.table_schema, 
                      constraint_record.table_name, 
                      constraint_record.constraint_name);
                      
        RAISE NOTICE 'Constraint única "%" removida!', constraint_record.constraint_name;
    END LOOP;
END $$;

-- Verificar se ainda existem constraints únicas no campo codigo
SELECT 
    tc.constraint_name,
    tc.constraint_type,
    kcu.column_name
FROM information_schema.table_constraints tc
JOIN information_schema.key_column_usage kcu 
    ON tc.constraint_name = kcu.constraint_name
    AND tc.table_schema = kcu.table_schema
WHERE tc.constraint_type = 'UNIQUE'
    AND tc.table_schema = 'sind'
    AND tc.table_name = 'associados_sasmais'
    AND kcu.column_name = 'codigo';

-- Se a consulta acima retornar registros, ainda existem constraints únicas
-- Se não retornar nada, o campo codigo agora aceita valores duplicados

NOTICE 'Execução concluída! O campo "codigo" da tabela "sind.associados_sasmais" agora aceita valores duplicados.';

-- =====================================================
-- INSTRUCÕES DE USO:
-- =====================================================
-- 1. Execute este script no pgAdmin ou linha de comando do PostgreSQL
-- 2. Certifique-se de ter privilégios para alterar a estrutura da tabela
-- 3. Faça backup da base antes de executar (recomendado)
-- 
-- Comando para executar via psql:
-- psql -d sua_base_de_dados -f remover_constraint_codigo_unico.sql
-- ===================================================== 