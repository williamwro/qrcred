-- ============================================================================
-- AUDITORIA: Antecipações APROVADAS sem lançamento correspondente na CONTA
-- Projeto qrcred — tela Adm/pages/antecipacao — 12/08/2026
-- ----------------------------------------------------------------------------
-- Use esta consulta para localizar os eventos antigos em que o status foi
-- alterado para Aprovado mas o lançamento (convênio 221) não foi gravado na
-- tabela sind.conta. Com o antecipacao_salvar.php corrigido, novos casos não
-- devem mais aparecer — recomenda-se rodar antes de cada fechamento mensal.
-- ============================================================================

-- 1) Antecipações aprovadas SEM lançamento principal (convênio 221) aprovado
SELECT a.id,
       a.matricula,
       a.empregador,
       a.mes,
       a.valor,
       a.valor_taxa,
       a.valor_a_descontar,
       a.data_solicitacao,
       a.data_aprovacao
  FROM sind.antecipacao a
 WHERE a.aprovado = true
   AND NOT EXISTS (
         SELECT 1
           FROM sind.conta c
          WHERE c.associado  = a.matricula
            AND c.mes        = a.mes
            AND c.empregador = a.empregador
            AND c.tipo       = 'ANTECIPACAO'
            AND c.convenio   = 221
            AND c.aprovado   = true)
 ORDER BY a.data_aprovacao NULLS FIRST, a.mes, a.matricula;

-- 2) Antecipações aprovadas COM taxa > 0 mas SEM o lançamento da taxa (convênio 249)
SELECT a.id,
       a.matricula,
       a.empregador,
       a.mes,
       a.valor_taxa,
       a.data_aprovacao
  FROM sind.antecipacao a
 WHERE a.aprovado = true
   AND COALESCE(a.valor_taxa, 0) > 0
   AND NOT EXISTS (
         SELECT 1
           FROM sind.conta c
          WHERE c.associado  = a.matricula
            AND c.mes        = a.mes
            AND c.empregador = a.empregador
            AND c.convenio   = 249)
 ORDER BY a.data_aprovacao NULLS FIRST, a.mes, a.matricula;

-- 3) Situação inversa: lançamentos de ANTECIPACAO na conta cuja antecipação
--    não está aprovada. REGRA (12/08/2026): lançamento só permanece na conta
--    enquanto a antecipação está APROVADA — qualquer linha aqui é inconsistência.
SELECT c.lancamento,
       c.associado,
       c.mes,
       c.empregador,
       c.convenio,
       c.valor,
       c.aprovado AS aprovado_conta,
       a.aprovado AS aprovado_antecipacao
  FROM sind.conta c
  LEFT JOIN sind.antecipacao a
         ON a.matricula  = c.associado
        AND a.mes        = c.mes
        AND a.empregador = c.empregador
 WHERE c.tipo = 'ANTECIPACAO'
   AND (a.id IS NULL OR a.aprovado IS DISTINCT FROM true)
 ORDER BY c.mes, c.associado;

-- 4) REGRA "aprovado sempre true" (12/08/2026): lançamentos de antecipação com
--    aprovado = false ou NULL NÃO deveriam existir. Os listados aqui são legado
--    do comportamento antigo ("Analisando" gravava false). Eles são corrigidos
--    automaticamente na próxima aprovação do registro — ou de uma vez pelo
--    UPDATE comentado abaixo (confira antes com a antecipação correspondente:
--    se ela NÃO estiver aprovada, o correto é EXCLUIR o lançamento, não aprovar).
SELECT c.lancamento,
       c.associado,
       c.mes,
       c.empregador,
       c.convenio,
       c.valor,
       c.aprovado AS aprovado_conta,
       a.aprovado AS aprovado_antecipacao
  FROM sind.conta c
  LEFT JOIN sind.antecipacao a
         ON a.matricula  = c.associado
        AND a.mes        = c.mes
        AND a.empregador = c.empregador
 WHERE (c.tipo = 'ANTECIPACAO' OR c.convenio IN (221, 249))
   AND c.aprovado IS DISTINCT FROM true
 ORDER BY c.mes, c.associado;

-- 4a) Correção em massa do legado (rode a consulta 4 antes e revise!):
--     - antecipação APROVADA  -> corrigir o lançamento para true:
-- UPDATE sind.conta c
--    SET aprovado = true
--   FROM sind.antecipacao a
--  WHERE a.matricula = c.associado AND a.mes = c.mes AND a.empregador = c.empregador
--    AND (c.tipo = 'ANTECIPACAO' OR c.convenio IN (221, 249))
--    AND c.aprovado IS DISTINCT FROM true
--    AND a.aprovado = true;
--     - antecipação NÃO aprovada -> excluir o lançamento órfão:
-- DELETE FROM sind.conta c
--  USING sind.antecipacao a
--  WHERE a.matricula = c.associado AND a.mes = c.mes AND a.empregador = c.empregador
--    AND (c.tipo = 'ANTECIPACAO' OR c.convenio IN (221, 249))
--    AND c.aprovado IS DISTINCT FROM true
--    AND a.aprovado IS DISTINCT FROM true;

-- ============================================================================
-- (Opcional, recomendação do estudo) Blindagem definitiva contra duplicidade:
-- impede dois lançamentos 221 de antecipação para o mesmo associado/mês.
-- Rode primeiro a consulta 1 e limpe eventuais duplicatas antes de criar.
-- ----------------------------------------------------------------------------
-- CREATE UNIQUE INDEX IF NOT EXISTS ux_conta_antecipacao_unica
--     ON sind.conta (associado, mes, empregador, divisao, id_associado, convenio)
--  WHERE tipo = 'ANTECIPACAO';
-- ============================================================================
