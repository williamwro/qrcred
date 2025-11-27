-- =====================================================================
-- TRIGGER PARA INSERÇÃO AUTOMÁTICA DE TAXA DE CARTÃO NA TABELA CONTA
-- =====================================================================
-- 
-- OBJETIVO:
-- Inserir automaticamente na tabela sind.conta o valor da taxa de cartão
-- para todos os associados que tiverem pelo menos um lançamento no mês corrente.
--
-- FUNCIONAMENTO:
-- 1. Trigger é disparado quando há INSERT na tabela sind.conta
-- 2. Verifica se o associado já tem taxa de cartão no mês do lançamento
-- 3. Se não tiver, busca o valor da taxa na tabela sind.valor_taxa_cartao
-- 4. Insere automaticamente a taxa de cartão para o associado
--
-- TABELAS ENVOLVIDAS:
-- - sind.conta - tabela onde o trigger é disparado (AFTER INSERT)
-- - sind.valor_taxa_cartao (valor, descricao, divisao) - configuração da taxa
-- - sind.associado - dados dos associados
-- =====================================================================

-- Remove o trigger se já existir
DROP TRIGGER IF EXISTS trg_insere_taxa_cartao_automatica ON sind.conta;
DROP FUNCTION IF EXISTS sind.fn_insere_taxa_cartao_automatica();

-- Cria a função que será executada pelo trigger
CREATE OR REPLACE FUNCTION sind.fn_insere_taxa_cartao_automatica()
RETURNS TRIGGER AS $$
DECLARE
    v_valor_taxa DOUBLE PRECISION;
    v_descricao_taxa VARCHAR(255);
    v_ja_tem_taxa BOOLEAN;
    v_codigo_associado VARCHAR(50);
    v_empregador INTEGER;
BEGIN
    -- Ignora se o lançamento já é uma taxa de cartão (convenio = 249)
    IF NEW.convenio = 249 THEN
        RETURN NEW;
    END IF;
    
    -- Verifica se o associado já tem taxa de cartão neste mês
    SELECT EXISTS(
        SELECT 1 
        FROM sind.conta 
        WHERE id_associado = NEW.id_associado 
          AND mes = NEW.mes 
          AND convenio = 249 
          AND divisao = NEW.divisao
    ) INTO v_ja_tem_taxa;
    
    -- Se já tem taxa, não faz nada
    IF v_ja_tem_taxa THEN
        RETURN NEW;
    END IF;
    
    -- Busca o valor e descrição da taxa para esta divisão
    SELECT valor, descricao 
    INTO v_valor_taxa, v_descricao_taxa
    FROM sind.valor_taxa_cartao 
    WHERE divisao = NEW.divisao 
    LIMIT 1;
    
    -- Se não encontrou configuração de taxa, não faz nada
    IF v_valor_taxa IS NULL THEN
        RETURN NEW;
    END IF;
    
    -- Busca dados do associado
    SELECT codigo, empregador 
    INTO v_codigo_associado, v_empregador
    FROM sind.associado 
    WHERE id = NEW.id_associado;
    
    -- Insere a taxa de cartão para este associado
    INSERT INTO sind.conta (
        associado,
        convenio,
        valor,
        data,
        hora,
        descricao,
        mes,
        empregador,
        divisao,
        id_associado,
        uuid_conta
    )
    VALUES (
        v_codigo_associado,
        249, -- Código do convênio para taxa de cartão
        v_valor_taxa,
        CURRENT_DATE,
        CURRENT_TIME,
        v_descricao_taxa,
        NEW.mes,
        v_empregador,
        NEW.divisao,
        NEW.id_associado,
        (
            substring(md5(random()::text || clock_timestamp()::text), 1, 8) || '-' ||
            substring(md5(random()::text || clock_timestamp()::text), 9, 4) || '-' ||
            substring(md5(random()::text || clock_timestamp()::text), 13, 4) || '-' ||
            substring(md5(random()::text || clock_timestamp()::text), 17, 4) || '-' ||
            substring(md5(random()::text || clock_timestamp()::text), 21, 12)
        )::uuid
    );
    
    -- Log da operação
    RAISE NOTICE 'Taxa de cartão inserida automaticamente para associado % no mês % (divisão %)', 
                 v_codigo_associado, NEW.mes, NEW.divisao;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Cria o trigger que dispara APÓS INSERT na tabela conta
CREATE TRIGGER trg_insere_taxa_cartao_automatica
    AFTER INSERT ON sind.conta
    FOR EACH ROW
    EXECUTE FUNCTION sind.fn_insere_taxa_cartao_automatica();

-- =====================================================================
-- COMENTÁRIOS E OBSERVAÇÕES
-- =====================================================================
-- 
-- 1. O trigger é disparado APÓS INSERT na tabela sind.conta
-- 2. Ignora se o lançamento já é uma taxa de cartão (convenio = 249)
-- 3. Verifica se o associado já tem taxa de cartão no mês do lançamento
-- 4. Se não tiver, busca o valor e descrição da taxa na sind.valor_taxa_cartao
-- 5. Insere automaticamente a taxa de cartão para o associado
-- 6. Evita duplicação verificando antes de inserir
-- 7. Usa convenio = 249 (código padrão para taxa de cartão)
-- 8. Gera UUID único para cada lançamento
-- 9. Registra log com RAISE NOTICE para auditoria
--
-- FUNCIONAMENTO AUTOMÁTICO:
-- Sempre que um lançamento é inserido na tabela sind.conta, o trigger verifica
-- se o associado precisa ter a taxa de cartão inserida também.
-- Não é necessário executar nenhum comando manualmente!
--
-- =====================================================================

COMMENT ON FUNCTION sind.fn_insere_taxa_cartao_automatica() IS 
'Função trigger que insere automaticamente taxa de cartão na tabela conta para associados com lançamentos no mês corrente';

COMMENT ON TRIGGER trg_insere_taxa_cartao_automatica ON sind.conta IS 
'Trigger que dispara a inserção automática de taxa de cartão quando um novo lançamento é inserido na tabela conta';
