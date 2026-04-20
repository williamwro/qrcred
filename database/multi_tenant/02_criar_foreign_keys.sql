-- ============================================================================
-- ETAPA 2: ESCALABILIDADE - CRIAR FOREIGN KEYS COM CASCADE
-- ============================================================================
-- Objetivo: Adicionar Foreign Keys em todas as tabelas que referenciam divisão
-- Data: 2026-02-21
-- IMPORTANTE: Execute após a padronização de nomenclatura
-- ============================================================================

-- ATENÇÃO: Faça backup do banco antes de executar este script!
-- pg_dump -U postgres -d qrcred -F c -b -v -f qrcred_backup_antes_fks.backup

BEGIN;

-- ============================================================================
-- FASE 1: REMOVER FOREIGN KEYS ANTIGAS (se existirem)
-- ============================================================================

-- Função auxiliar para dropar FK se existir
CREATE OR REPLACE FUNCTION drop_fk_if_exists(
    p_table_schema TEXT,
    p_table_name TEXT,
    p_constraint_name TEXT
) RETURNS VOID AS $$
BEGIN
    IF EXISTS (
        SELECT 1 
        FROM information_schema.table_constraints 
        WHERE constraint_schema = p_table_schema 
        AND table_name = p_table_name 
        AND constraint_name = p_constraint_name
    ) THEN
        EXECUTE format('ALTER TABLE %I.%I DROP CONSTRAINT %I', 
            p_table_schema, p_table_name, p_constraint_name);
        RAISE NOTICE 'FK % removida de %.%', p_constraint_name, p_table_schema, p_table_name;
    ELSE
        RAISE NOTICE 'FK % não existe em %.%', p_constraint_name, p_table_schema, p_table_name;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Remover FKs antigas (se existirem)
SELECT drop_fk_if_exists('sind', 'associado', 'fk_associado_divisao');
SELECT drop_fk_if_exists('sind', 'conta', 'fk_conta_divisao');
SELECT drop_fk_if_exists('sind', 'antecipacao', 'fk_antecipacao_divisao');
SELECT drop_fk_if_exists('sind', 'convenio', 'fk_convenio_divisao');
SELECT drop_fk_if_exists('sind', 'empregador', 'fk_empregador_divisao');
SELECT drop_fk_if_exists('sind', 'mes_corrente', 'fk_mes_corrente_divisao');
SELECT drop_fk_if_exists('sind', 'valor_taxa_cartao', 'fk_valor_taxa_divisao');
SELECT drop_fk_if_exists('sind', 'solicitacao_bloqueio', 'fk_solicitacao_bloqueio_divisao');

-- ============================================================================
-- FASE 2: CRIAR FOREIGN KEYS COM ON DELETE CASCADE
-- ============================================================================

-- 1. ASSOCIADO -> DIVISAO
ALTER TABLE sind.associado
ADD CONSTRAINT fk_associado_divisao 
FOREIGN KEY (id_divisao) 
REFERENCES sind.divisao(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

COMMENT ON CONSTRAINT fk_associado_divisao ON sind.associado IS 
'FK com CASCADE: Ao deletar divisão, todos os associados são deletados';

-- 2. CONTA -> DIVISAO
ALTER TABLE sind.conta
ADD CONSTRAINT fk_conta_divisao 
FOREIGN KEY (id_divisao) 
REFERENCES sind.divisao(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

COMMENT ON CONSTRAINT fk_conta_divisao ON sind.conta IS 
'FK com CASCADE: Ao deletar divisão, todas as contas são deletadas';

-- 3. ANTECIPACAO -> DIVISAO
ALTER TABLE sind.antecipacao
ADD CONSTRAINT fk_antecipacao_divisao 
FOREIGN KEY (id_divisao) 
REFERENCES sind.divisao(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

COMMENT ON CONSTRAINT fk_antecipacao_divisao ON sind.antecipacao IS 
'FK com CASCADE: Ao deletar divisão, todas as antecipações são deletadas';

-- 4. CONVENIO -> DIVISAO
ALTER TABLE sind.convenio
ADD CONSTRAINT fk_convenio_divisao 
FOREIGN KEY (id_divisao) 
REFERENCES sind.divisao(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

COMMENT ON CONSTRAINT fk_convenio_divisao ON sind.convenio IS 
'FK com CASCADE: Ao deletar divisão, todos os convênios são deletados';

-- 5. EMPREGADOR -> DIVISAO
ALTER TABLE sind.empregador
ADD CONSTRAINT fk_empregador_divisao 
FOREIGN KEY (id_divisao) 
REFERENCES sind.divisao(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

COMMENT ON CONSTRAINT fk_empregador_divisao ON sind.empregador IS 
'FK com CASCADE: Ao deletar divisão, todos os empregadores são deletados';

-- 6. MES_CORRENTE -> DIVISAO
ALTER TABLE sind.mes_corrente
ADD CONSTRAINT fk_mes_corrente_divisao 
FOREIGN KEY (id_divisao) 
REFERENCES sind.divisao(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

COMMENT ON CONSTRAINT fk_mes_corrente_divisao ON sind.mes_corrente IS 
'FK com CASCADE: Ao deletar divisão, configurações de mês corrente são deletadas';

-- 7. VALOR_TAXA_CARTAO -> DIVISAO
ALTER TABLE sind.valor_taxa_cartao
ADD CONSTRAINT fk_valor_taxa_divisao 
FOREIGN KEY (id_divisao) 
REFERENCES sind.divisao(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

COMMENT ON CONSTRAINT fk_valor_taxa_divisao ON sind.valor_taxa_cartao IS 
'FK com CASCADE: Ao deletar divisão, configurações de taxa são deletadas';

-- 8. SOLICITACAO_BLOQUEIO -> DIVISAO
ALTER TABLE sind.solicitacao_bloqueio
ADD CONSTRAINT fk_solicitacao_bloqueio_divisao 
FOREIGN KEY (id_divisao) 
REFERENCES sind.divisao(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

COMMENT ON CONSTRAINT fk_solicitacao_bloqueio_divisao ON sind.solicitacao_bloqueio IS 
'FK com CASCADE: Ao deletar divisão, solicitações de bloqueio são deletadas';

-- ============================================================================
-- FASE 3: FOREIGN KEYS ADICIONAIS (Relacionamentos entre tabelas)
-- ============================================================================

-- CONTA -> ASSOCIADO (com CASCADE)
SELECT drop_fk_if_exists('sind', 'conta', 'fk_conta_associado');
ALTER TABLE sind.conta
ADD CONSTRAINT fk_conta_associado 
FOREIGN KEY (id_associado) 
REFERENCES sind.associado(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- ANTECIPACAO -> ASSOCIADO (com CASCADE)
SELECT drop_fk_if_exists('sind', 'antecipacao', 'fk_antecipacao_associado');
ALTER TABLE sind.antecipacao
ADD CONSTRAINT fk_antecipacao_associado 
FOREIGN KEY (id_associado) 
REFERENCES sind.associado(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- SOLICITACAO_BLOQUEIO -> ASSOCIADO (com CASCADE)
SELECT drop_fk_if_exists('sind', 'solicitacao_bloqueio', 'fk_solicitacao_bloqueio_associado');
ALTER TABLE sind.solicitacao_bloqueio
ADD CONSTRAINT fk_solicitacao_bloqueio_associado 
FOREIGN KEY (id_associado) 
REFERENCES sind.associado(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- SOLICITACAO_BLOQUEIO -> EMPREGADOR (com CASCADE)
SELECT drop_fk_if_exists('sind', 'solicitacao_bloqueio', 'fk_solicitacao_bloqueio_empregador');
ALTER TABLE sind.solicitacao_bloqueio
ADD CONSTRAINT fk_solicitacao_bloqueio_empregador 
FOREIGN KEY (id_empregador) 
REFERENCES sind.empregador(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- ASSOCIADO -> EMPREGADOR (com RESTRICT - não deletar empregador se tiver associados)
SELECT drop_fk_if_exists('sind', 'associado', 'fk_associado_empregador');
ALTER TABLE sind.associado
ADD CONSTRAINT fk_associado_empregador 
FOREIGN KEY (empregador) 
REFERENCES sind.empregador(id)
ON DELETE RESTRICT
ON UPDATE CASCADE;

-- ============================================================================
-- FASE 4: VERIFICAÇÃO FINAL
-- ============================================================================

-- Listar todas as Foreign Keys criadas
SELECT
    tc.table_name, 
    tc.constraint_name,
    kcu.column_name,
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
  AND tc.constraint_name LIKE 'fk_%'
ORDER BY tc.table_name, tc.constraint_name;

-- Limpar função auxiliar
DROP FUNCTION IF EXISTS drop_fk_if_exists(TEXT, TEXT, TEXT);

COMMIT;

-- ============================================================================
-- TESTE DE CASCADE (NÃO EXECUTE EM PRODUÇÃO!)
-- ============================================================================
-- Para testar se o CASCADE está funcionando:
-- 
-- BEGIN;
-- -- Criar divisão de teste
-- INSERT INTO sind.divisao (id, nome, status) VALUES (999, 'TESTE_CASCADE', 1);
-- 
-- -- Criar associado de teste
-- INSERT INTO sind.associado (id_divisao, nome, codigo) VALUES (999, 'Teste', 'TEST001');
-- 
-- -- Deletar divisão (deve deletar associado automaticamente)
-- DELETE FROM sind.divisao WHERE id = 999;
-- 
-- -- Verificar se associado foi deletado
-- SELECT * FROM sind.associado WHERE codigo = 'TEST001'; -- Deve retornar vazio
-- ROLLBACK;
-- ============================================================================

RAISE NOTICE '✅ Foreign Keys com CASCADE criadas com sucesso!';
