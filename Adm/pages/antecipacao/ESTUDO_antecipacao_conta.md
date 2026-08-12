# Estudo minucioso — Antecipação × Tabela CONTA

**Projeto:** qrcred · **Tela:** Adm/pages/antecipacao · **Data:** 12/08/2026

**Problema relatado:** ao alterar um registro no modal "ANTECIPAÇÃO Alterando" e mudar o status
(`C_aprovado`) de *Analisando* para *Aprovado*, em alguns eventos o sistema marcou a antecipação
como aprovada **sem gravar o lançamento na tabela `sind.conta`** — etapa que é obrigatória.

---

## 1. Como o fluxo funciona hoje

O botão amarelo da grade (classe `update_antecipacao`) chama `antecipacao_exibe.php`, que busca o
registro em `sind.antecipacao` (com JOIN em `sind.associado` e `sind.empregador`) e preenche o modal
`#ModalEditaAntecipado`, incluindo os campos ocultos `C_id_antecipacao`, `C_associado_id` e
`C_associado_id_divisao`. Ao clicar em **Salvar**, o JavaScript (`js/antecipacao_read_script.js`,
handler `#btnSalvar`) serializa o formulário, acrescenta `divisao` (lido do `sessionStorage`) e
`usuario_cod`, e envia por AJAX para `antecipacao_salvar.php`, que:

1. faz `UPDATE sind.antecipacao` (aprovado, data_aprovacao, chave_pix);
2. faz `UPDATE sind.conta SET aprovado = ...` para os lançamentos `tipo = 'ANTECIPACAO'` do
   associado/mês/empregador/divisão;
3. se o UPDATE não afetou nenhuma linha **e** o status é Aprovado ("2"), **INSERE** os lançamentos:
   convênio **221** (valor a descontar) e convênio **249** (taxa, se maior que zero);
4. se o status é Reprovado ("3"), **EXCLUI** os lançamentos 221/249 do associado/mês;
5. responde o texto `"atualizado"`, que o JavaScript interpreta como sucesso.

## 2. Causas encontradas para "aprovou mas não gravou na conta"

O estudo do código identificou **um defeito estrutural** e vários gatilhos que o exploram. O ponto
central: **as etapas 1 a 4 não eram atômicas e os erros das etapas 2 e 3 eram engolidos**.

**C1 — Erros da gravação na conta eram silenciados (defeito principal).** No arquivo original, o
INSERT dos lançamentos estava dentro de um `try/catch` que apenas gravava o erro no
`debug_antecipacao.log` e seguia adiante (linhas ~377–379); o UPDATE da conta idem (linhas
~387–390). Como o `UPDATE sind.antecipacao` já havia sido executado antes (linha ~118) e cada
comando era confirmado individualmente no banco (autocommit, sem transação), **qualquer falha na
conta deixava a antecipação aprovada, sem lançamento, e o script ainda respondia `"atualizado"`** —
o usuário via "Antecipação atualizada com sucesso!". Este é exatamente o sintoma relatado.
*Reproduzi esse comportamento em laboratório (ver seção 4): forcei uma falha de banco no INSERT da
conta e o código original respondeu "atualizado", com `aprovado = true` e conta vazia.*

**C2 — Gatilhos que faziam o INSERT falhar de verdade.** Entre os motivos reais para a etapa da
conta falhar (e cair no silêncio do C1):
a) **corrida do número de lançamento** — o próximo `lancamento` era calculado com
`MAX(lancamento)+1` sem nenhuma trava; dois salvamentos simultâneos (ou outro módulo lançando na
conta no mesmo instante) geravam o mesmo número e o segundo INSERT estourava por duplicidade;
b) **`divisao` inválida** — o valor vem do `sessionStorage` do navegador; com sessão
expirada/aba nova ele chega como a string `"null"`, que estoura o bind inteiro no PostgreSQL;
c) **`id_associado` não resolvido** — se `C_associado_id` não vinha preenchido e a busca por
matrícula+empregador+divisão não encontrava (por causa do item b, inclusive), o script seguia com
id 0/vazio e o INSERT falhava ou gravava lançamento órfão;
d) **valor vazio/inválido** — `C_valor_a_descontar` vazio virava `''` e quebrava o INSERT numérico;
e) **permissão** — usuário de banco com direito de UPDATE na antecipação mas sem INSERT na conta
(SQLSTATE 42501) caía no mesmo silêncio.

**C3 — O JavaScript escondia qualquer falha restante.** O handler do `#btnSalvar` só tratava três
respostas ("atualizado", "cadastrado", "Seu usuario não tem permissão!"). **Qualquer outra resposta
não mostrava nada** e, em todos os casos, o formulário era resetado, o modal fechado e a tabela
recarregada. Não havia handler `error:` — numa falha HTTP o diálogo "Gravando, aguarde..." ficava
travado. Ou seja: mesmo quando o servidor devolvia um erro, o operador via a tela se comportar como
sucesso.

**C4 — Riscos menores anotados (não alterados, ver seção 6):** duas antecipações do mesmo
associado no mesmo mês compartilham as mesmas chaves na conta (associado+mês+empregador+divisão),
então a segunda aprovação reaproveita/atualiza o lançamento existente em vez de criar outro; o
fluxo "Cadastrando" (`btnInserir`) posta para o mesmo arquivo sem um INSERT correspondente; e o
`antecipacao_salvar.php` não valida sessão/tenant como o `antecipacao_read2.php` faz.

## 3. O que foi corrigido

### `antecipacao_salvar.php` (reescrito, mantendo as regras de negócio e as respostas)

1. **Transação única (BEGIN/COMMIT/ROLLBACK).** O UPDATE da antecipação e TODAS as operações na
   conta acontecem na mesma transação. Falhou qualquer etapa → `ROLLBACK` → **é impossível existir
   antecipação aprovada sem o lançamento na conta**.
2. **Validação prévia.** Antes de gravar qualquer coisa ao aprovar, o script exige matrícula,
   empregador, mês, divisão, `id_associado` e valor > 0. Faltou algo → erro claro, nada é alterado.
3. **Banco como fonte da verdade.** Os dados-chave são lidos do próprio registro de
   `sind.antecipacao` (com trava `FOR UPDATE`), com fallback para `sind.associado` e só então para
   o formulário. Sessão expirada, campo vazio no modal ou máscara de moeda deixam de derrubar a
   gravação.
4. **Verificação final obrigatória.** Antes do COMMIT, o script **confere no banco** que o
   lançamento aprovado (convênio 221, tipo ANTECIPACAO) existe — e o da taxa (249), quando há taxa.
   Se não encontrar, cancela tudo e avisa. É a materialização do "esta etapa não pode falhar".
5. **Fim dos erros engolidos.** Toda falha responde `"ERRO: ..."` com o motivo; permissão 42501
   continua respondendo `"Seu usuario não tem permissão!"`. Tudo fica registrado no
   `debug_antecipacao.log` (mantido), inclusive o ROLLBACK.
6. **Concorrência tratada.** `FOR UPDATE` na linha da antecipação (dois operadores não processam o
   mesmo registro ao mesmo tempo) e `pg_advisory_xact_lock` + retry com SAVEPOINT na geração do
   número de lançamento (elimina a corrida do MAX+1).
7. **Resposta blindada.** Buffer de saída + `Content-Type: text/plain` garantem que o JavaScript
   receba exatamente a string esperada, sem avisos ou espaços de includes.

### `js/antecipacao_read_script.js` (apenas o handler do botão Salvar)

O modal agora **só fecha com sucesso confirmado** pelo servidor. Resposta de erro → SweetAlert com a
mensagem, modal aberto, formulário preservado e tabela recarregada com o estado real do banco. Foi
adicionado handler `error:` para falha de comunicação (o "Gravando, aguarde..." não trava mais). No
sucesso de uma aprovação, a mensagem passa a dizer "Antecipação APROVADA e gravada na conta com
sucesso!". Nada mais foi alterado no arquivo.

## 4. Provas executadas (banco PostgreSQL de laboratório com as tabelas sind.*)

Foram executados 11 cenários contra o código novo, e um teste A/B contra o original:

| # | Cenário | Resultado |
|---|---------|-----------|
| 1 | Aprovar (POST completo) | "atualizado"; conta recebe 221 + 249 aprovados; antecipação aprovada |
| 2 | Reaprovar | idempotente, sem duplicar lançamentos |
| 3 | Voltar para Analisando | antecipação NULL; lançamentos ficam `aprovado = false` |
| 4 | Aprovar de novo | reaproveita lançamentos, sem duplicar |
| 5 | Reprovar | lançamentos 221/249 excluídos da conta |
| 6 | Registro antigo sem id_associado/divisão + sessão expirada (`divisao="null"`) | resolve pelo banco e grava certo |
| 7 | Antecipação órfã (associado inexistente) | "ERRO: ... NADA foi alterado"; status permanece Analisando |
| 8 | **Falha de banco forçada no INSERT da conta** | "ERRO ..." + **ROLLBACK: aprovação desfeita, conta íntegra** |
| 9 | Mesma operação sem a falha | volta a funcionar normalmente |
| 10 | Status inválido / id ausente | bloqueado antes de tocar no banco |
| 11 | Duas aprovações simultâneas | ambas gravam, lançamentos sequenciais, zero duplicidade |

**Teste A/B (a prova do bug):** com a mesma falha forçada do cenário 8, o **código original**
respondeu `"atualizado"`, deixou `aprovado = true` e **zero lançamentos na conta** — reproduzindo
fielmente o evento relatado. O código novo, no mesmo cenário, desfaz tudo e avisa o operador.

## 5. Como localizar os casos antigos (auditoria)

O arquivo `auditoria_antecipacao_conta.sql` (entregue junto) lista as antecipações **aprovadas sem
lançamento correspondente na conta**, para correção manual dos eventos passados:

```sql
SELECT a.id, a.matricula, a.empregador, a.mes, a.valor_a_descontar, a.valor_taxa, a.data_aprovacao
FROM sind.antecipacao a
WHERE a.aprovado = true
  AND NOT EXISTS (
        SELECT 1 FROM sind.conta c
         WHERE c.associado = a.matricula
           AND c.mes       = a.mes
           AND c.empregador = a.empregador
           AND c.tipo      = 'ANTECIPACAO'
           AND c.convenio  = 221
           AND c.aprovado  = true)
ORDER BY a.data_aprovacao, a.mes, a.matricula;
```

Para corrigir um caso encontrado, basta abrir o registro na tela, voltar o status para *Analisando*,
salvar, e aprovar novamente — o código novo criará os lançamentos (e agora não deixa passar falha).

## 6. Recomendações complementares (não aplicadas, para avaliação)

1. **Chave única parcial na conta** para blindar contra duplicidade também por fora da tela:
   `CREATE UNIQUE INDEX ... ON sind.conta (associado, mes, empregador, divisao, id_associado, convenio) WHERE tipo = 'ANTECIPACAO';`
2. **Vincular o lançamento à antecipação** (coluna `id_antecipacao` em `sind.conta`) — hoje duas
   antecipações do mesmo associado no mesmo mês disputam o mesmo lançamento (C4).
3. **Validação de sessão/tenant no `antecipacao_salvar.php`**, como já existe no
   `antecipacao_read2.php` (TenantSecurity).
4. Rodar a auditoria da seção 5 **mensalmente antes do fechamento**, ou agendar um alerta.
5. O fluxo "Cadastrando" do modal (`btnInserir`) posta para o mesmo arquivo sem um INSERT de
   antecipação — hoje é inócuo; se for usado, precisa ser implementado.

## 7. Adendo (12/08/2026) — Regra adicional: `conta.aprovado` é SEMPRE `true`

Por definição do William, o campo booleano `aprovado` da `sind.conta` **deve ser gravado sempre com
o valor `true`** — o sistema nunca deve gravar `false`. O `antecipacao_salvar.php` foi ajustado
para incorporar essa regra como invariante:

1. **Analisando (status 1) não grava mais `aprovado = false`.** Ao voltar de Aprovado para
   Analisando, os lançamentos 221/249 são **excluídos** da conta (mesmo tratamento do Reprovado).
   O lançamento passa a existir na conta **somente** enquanto a antecipação está aprovada — e
   sempre com `aprovado = true`. Ao aprovar de novo, os lançamentos são recriados.
2. **Aprovar força `true` em todos os lançamentos da antecipação** (tipo ANTECIPACAO e convênios
   221/249 das mesmas chaves), inclusive registros antigos que tenham ficado com `false` pelo
   comportamento anterior — a aprovação **autocorrige o legado**.
3. **Nova verificação final:** antes do COMMIT de uma aprovação, o script confere que **nenhum**
   lançamento da antecipação ficou com `aprovado` diferente de `true`; e, após Analisando/Reprovado,
   que nenhum lançamento 221/249 restou na conta. Falhou → ROLLBACK + mensagem de erro.
4. **Reteste completo (7 cenários):** cura do estado do bug histórico (aprovada com conta vazia);
   Analisando excluindo lançamentos; reaprovação recriando com `true`; correção de `false` legado
   plantado manualmente; Reprovado excluindo; falha forçada no DELETE gerando erro + rollback
   íntegro; e varredura global confirmando **zero** lançamentos de antecipação com
   `aprovado ≠ true` e **zero** duplicidades.

Para o legado já existente em produção, a consulta 4 do `auditoria_antecipacao_conta.sql` lista os
lançamentos de antecipação com `aprovado ≠ true`; eles são corrigidos automaticamente na próxima
aprovação do registro, ou podem ser ajustados de uma vez pelo UPDATE comentado no arquivo.

## 8. Arquivos entregues

`antecipacao_salvar.php` (reescrito) · `js/antecipacao_read_script.js` (handler do Salvar) ·
`auditoria_antecipacao_conta.sql` · este estudo. Os originais foram preservados como
`*.bak_20260812` na mesma pasta.
