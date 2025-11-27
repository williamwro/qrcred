# Automatização de Taxa de Cartão - Sistema QRCred

## 📋 Objetivo

Inserir automaticamente na tabela `sind.conta` o valor da taxa de cartão para todos os associados que tiverem pelo menos um lançamento no mês corrente.

## 🎯 Duas Opções de Implementação

### **Opção 1: TRIGGER (Recomendado) - Totalmente Automático**

**Arquivo:** `trigger_taxa_cartao_automatica.sql`

#### ✅ Vantagens:
- **Totalmente automático**: Dispara sempre que um lançamento é inserido na tabela `sind.conta`
- **Sem intervenção manual**: Não precisa lembrar de executar scripts
- **Inteligente**: Verifica se o associado já tem taxa antes de inserir
- **Auditável**: Gera logs com RAISE NOTICE

#### Como funciona:
1. Quando um lançamento é inserido na tabela `sind.conta`
2. O trigger é disparado automaticamente
3. Verifica se o associado já tem taxa de cartão no mês desse lançamento
4. Se não tiver, busca o valor da taxa na tabela `sind.valor_taxa_cartao`
5. Insere automaticamente a taxa de cartão para o associado (evitando duplicações)

#### Como instalar:
```sql
-- Execute o arquivo no PostgreSQL:
\i c:/xampp2/htdocs/qrcred/sql/trigger_taxa_cartao_automatica.sql

-- Ou copie e cole o conteúdo no pgAdmin/DBeaver
```

#### Como usar:
```sql
-- 1. Configure a taxa de cartão para sua divisão:
INSERT INTO sind.valor_taxa_cartao (valor, descricao, divisao) 
VALUES (5.00, 'Taxa de Cartão', 1);

-- 2. Pronto! Agora sempre que um lançamento for inserido na tabela conta,
--    o trigger verificará automaticamente se o associado precisa ter
--    a taxa de cartão inserida também.

-- Exemplo: Ao inserir um lançamento qualquer...
INSERT INTO sind.conta (associado, convenio, valor, mes, divisao, id_associado, ...)
VALUES ('12345', 100, 50.00, 'JAN2025', 1, 123, ...);

-- O trigger automaticamente inserirá a taxa de cartão para este associado!
```

---

### **Opção 2: SCRIPT MANUAL - Execução Sob Demanda**

**Arquivo:** `inserir_taxa_cartao_manual.sql`

#### ✅ Vantagens:
- **Controle total**: Você decide quando executar
- **Sem triggers**: Não adiciona complexidade ao banco
- **Transparente**: Você vê exatamente o que está sendo feito

#### ⚠️ Desvantagens:
- **Requer execução manual**: Você precisa lembrar de executar
- **Pode ser esquecido**: Risco de não aplicar a taxa em algum mês

#### Como usar:
1. Abra o arquivo `inserir_taxa_cartao_manual.sql`
2. Ajuste os parâmetros no início do script:
   ```sql
   v_divisao INTEGER := 1; -- AJUSTE AQUI
   v_valor_taxa DOUBLE PRECISION := 5.00; -- AJUSTE AQUI
   ```
3. Execute o script no PostgreSQL

---

## 🔍 Estrutura das Tabelas Envolvidas

### **sind.valor_taxa_cartao** (Configuração)
- `valor` (DOUBLE PRECISION) - Valor da taxa a ser cobrada
- `descricao` (VARCHAR) - Descrição da taxa
- `divisao` (INTEGER) - ID da divisão

### **sind.mes_corrente** (Mês Atual)
- `abreviacao` (VARCHAR) - Mês corrente do sistema (ex: "JAN2025")

### **sind.conta** (Lançamentos)
- Onde os lançamentos de taxa de cartão serão inseridos
- `convenio = 249` - Código usado para taxa de cartão
- `mes` - Mês do lançamento
- `valor` - Valor da taxa
- `divisao` - Divisão do associado

### **sind.associado** (Dados dos Associados)
- `id_situacao` - Situação do associado (2=inativo, 3=excluído)
- `id_divisao` - Divisão do associado

---

## 🛡️ Proteções Implementadas

### **Evita Duplicação**
```sql
WHERE NOT EXISTS (
    SELECT 1 
    FROM sind.conta ct
    WHERE ct.id_associado = s.id
      AND ct.mes = v_mes_corrente
      AND ct.convenio = 249
      AND ct.divisao = NEW.divisao
)
```

### **Filtra Associados Ativos**
```sql
WHERE a.id_situacao <> 2  -- Não incluir inativos
  AND a.id_situacao <> 3  -- Não incluir excluídos
```

### **Apenas Associados com Lançamentos no Mês**
```sql
AND a.id IN (
    SELECT DISTINCT c.id_associado
    FROM sind.conta c
    WHERE c.mes = v_mes_corrente
      AND c.divisao = NEW.divisao
)
```

---

## 📊 Consultas Úteis

### Verificar lançamentos de taxa de cartão do mês corrente:
```sql
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
```

### Verificar quantos associados têm lançamentos no mês corrente:
```sql
SELECT 
    COUNT(DISTINCT c.id_associado) as total_associados,
    c.mes,
    c.divisao
FROM sind.conta c
WHERE c.mes = (SELECT abreviacao FROM sind.mes_corrente LIMIT 1)
GROUP BY c.mes, c.divisao;
```

### Verificar se o trigger está instalado:
```sql
SELECT 
    trigger_name,
    event_manipulation,
    event_object_table,
    action_statement
FROM information_schema.triggers
WHERE trigger_schema = 'sind'
  AND trigger_name = 'trg_insere_taxa_cartao_automatica';
```

---

## 🚀 Recomendação

**Use a Opção 1 (TRIGGER)** se você quer uma solução totalmente automática e não quer se preocupar em lembrar de executar scripts manualmente.

**Use a Opção 2 (MANUAL)** se você prefere ter controle total sobre quando a taxa é aplicada ou se não quer adicionar triggers ao banco.

---

## 📝 Notas Importantes

1. **Convenio 249**: O código 249 é usado para identificar lançamentos de taxa de cartão
2. **UUID único**: Cada lançamento recebe um UUID único gerado automaticamente
3. **Mês corrente**: O sistema usa a tabela `sind.mes_corrente` para determinar o mês atual
4. **Divisão**: A taxa é aplicada apenas para associados da mesma divisão configurada
5. **Logs**: O trigger gera logs com RAISE NOTICE para auditoria

---

## ⚙️ Manutenção

### Remover o trigger (se necessário):
```sql
DROP TRIGGER IF EXISTS trg_insere_taxa_cartao_automatica ON sind.valor_taxa_cartao;
DROP FUNCTION IF EXISTS sind.fn_insere_taxa_cartao_automatica();
```

### Recriar o trigger:
```sql
-- Execute novamente o arquivo trigger_taxa_cartao_automatica.sql
```

---

## 🐛 Troubleshooting

### Problema: Taxa não está sendo inserida
**Solução:**
1. Verifique se o mês corrente está configurado:
   ```sql
   SELECT * FROM sind.mes_corrente;
   ```
2. Verifique se existem associados com lançamentos no mês:
   ```sql
   SELECT COUNT(DISTINCT id_associado) 
   FROM sind.conta 
   WHERE mes = (SELECT abreviacao FROM sind.mes_corrente LIMIT 1);
   ```
3. Verifique os logs do PostgreSQL para mensagens do trigger

### Problema: Duplicação de lançamentos
**Solução:**
- O sistema já tem proteção contra duplicação
- Se ainda assim ocorrer, execute:
  ```sql
  DELETE FROM sind.conta 
  WHERE id IN (
      SELECT id FROM (
          SELECT id, ROW_NUMBER() OVER (
              PARTITION BY id_associado, mes, convenio, divisao 
              ORDER BY data DESC, id DESC
          ) as rn
          FROM sind.conta
          WHERE convenio = 249
      ) t
      WHERE rn > 1
  );
  ```

---

## 📧 Suporte

Para dúvidas ou problemas, verifique:
1. Logs do PostgreSQL
2. Mensagens RAISE NOTICE do trigger
3. Consultas de verificação acima
