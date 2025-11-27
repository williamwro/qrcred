-- =====================================================================
-- SCRIPT PARA INSERÇÃO MANUAL DE TAXA DE CARTÃO
-- =====================================================================
-- 
-- Este script pode ser executado manualmente quando necessário
-- Não usa triggers - é uma alternativa para execução sob demanda
-- =====================================================================

-- Parâmetros que você deve ajustar antes de executar:
-- :divisao - ID da divisão (exemplo: 1)
-- :valor_taxa - Valor da taxa de cartão (exemplo: 5.00)

DO $$
DECLARE
    v_mes_corrente VARCHAR(10);
    v_divisao INTEGER := 1; -- AJUSTE AQUI: ID da divisão
    v_valor_taxa DOUBLE PRECISION := 5.00; -- AJUSTE AQUI: Valor da taxa
    v_descricao VARCHAR(255) := 'Taxa de Cartão'; -- AJUSTE AQUI: Descrição da taxa
    v_registros_inseridos INTEGER;
    v_data_atual DATE;
    v_hora_atual TIME;
BEGIN
    -- Busca o mês corrente
    SELECT abreviacao INTO v_mes_corrente 
    FROM sind.mes_corrente 
    LIMIT 1;
    
    -- Se não encontrou mês corrente, aborta
    IF v_mes_corrente IS NULL THEN
        RAISE EXCEPTION 'Mês corrente não encontrado. Verifique a tabela sind.mes_corrente';
    END IF;
    
    RAISE NOTICE 'Mês corrente identificado: %', v_mes_corrente;
    
    -- Define data e hora atuais
    v_data_atual := CURRENT_DATE;
    v_hora_atual := CURRENT_TIME;
    
    -- Insere a taxa de cartão
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
    SELECT
        s.codigo::varchar,
        249, -- Código do convênio para taxa de cartão
        v_valor_taxa,
        v_data_atual,
        v_hora_atual,
        v_descricao, -- Descrição configurável
        v_mes_corrente,
        s.empregador,
        v_divisao,
        s.id,
        (
            substring(s.h, 1, 8) || '-' ||
            substring(s.h, 9, 4) || '-' ||
            substring(s.h, 13, 4) || '-' ||
            substring(s.h, 17, 4) || '-' ||
            substring(s.h, 21, 12)
        )::uuid
    FROM (
        SELECT a.*, md5(random()::text || clock_timestamp()::text) AS h
        FROM sind.associado a
        WHERE a.id_situacao <> 2
          AND a.id_situacao <> 3
          AND a.id_divisao = v_divisao
          AND a.id IN (
              SELECT DISTINCT c.id_associado
              FROM sind.conta c
              WHERE c.mes = v_mes_corrente
                AND c.divisao = v_divisao
          )
    ) s
    WHERE NOT EXISTS (
        SELECT 1 
        FROM sind.conta ct
        WHERE ct.id_associado = s.id
          AND ct.mes = v_mes_corrente
          AND ct.convenio = 249
          AND ct.divisao = v_divisao
    );
    
    GET DIAGNOSTICS v_registros_inseridos = ROW_COUNT;
    
    RAISE NOTICE '========================================';
    RAISE NOTICE 'TAXA DE CARTÃO INSERIDA COM SUCESSO!';
    RAISE NOTICE '========================================';
    RAISE NOTICE 'Mês: %', v_mes_corrente;
    RAISE NOTICE 'Divisão: %', v_divisao;
    RAISE NOTICE 'Valor da taxa: R$ %', v_valor_taxa;
    RAISE NOTICE 'Registros inseridos: %', v_registros_inseridos;
    RAISE NOTICE '========================================';
END $$;

-- =====================================================================
-- CONSULTA PARA VERIFICAR OS LANÇAMENTOS INSERIDOS
-- =====================================================================

-- Descomente e execute para ver os lançamentos de taxa de cartão do mês corrente:
/*
SELECT 
    c.id,
    c.associado,
    a.nome,
    c.valor,
    c.mes,
    c.data,
    c.divisao
FROM sind.conta c
INNER JOIN sind.associado a ON a.id = c.id_associado
WHERE c.convenio = 249
  AND c.mes = (SELECT abreviacao FROM sind.mes_corrente LIMIT 1)
ORDER BY c.data DESC, a.nome;
*/
