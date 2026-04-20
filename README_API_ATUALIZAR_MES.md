# API de Atualização Automática do Mês Corrente por Divisão

## 📋 Descrição

Esta API atualiza automaticamente o campo `abreviacao` na tabela `sind.mes_corrente` quando o dia atual do sistema corresponde ao `dia_mes_renovacao` configurado para cada divisão na tabela `sind.divisao`.

## 📍 Localização

```
c:\xampp2\htdocs\qrcred\api_atualizar_mes_por_divisao.php
```

## 🌐 Como Usar

Acesse a URL:
```
http://seu-servidor/qrcred/api_atualizar_mes_por_divisao.php
```

## 🔄 Funcionamento Passo a Passo

### 1. **Verificação do Dia Atual**
- A API obtém o dia atual do sistema (1 a 31)
- Exemplo: Se hoje é dia 15, `$dia_atual = 15`

### 2. **Busca das Divisões**
- Busca todas as divisões na tabela `sind.divisao`
- Filtra apenas divisões que possuem `dia_mes_renovacao` configurado
- Exemplo de dados:
  ```
  id_divisao | nome      | dia_mes_renovacao
  -----------|-----------|------------------
  1          | Divisão A | 15
  2          | Divisão B | 20
  ```

### 3. **Comparação do Dia**
- Para cada divisão, compara:
  - **Dia atual** vs **dia_mes_renovacao**
- Se forem iguais → Processa a atualização
- Se forem diferentes → Ignora a divisão

### 4. **Busca do Mês Atual**
- Busca o registro atual em `sind.mes_corrente` para a divisão
- Exemplo: `"DEZ/2025"` para `id_divisao = 1`

### 5. **Cálculo do Próximo Mês**
- Calcula o próximo mês seguindo a ordem:
  ```
  JAN → FEV → MAR → ABR → MAI → JUN → 
  JUL → AGO → SET → OUT → NOV → DEZ → JAN (próximo ano)
  ```
- Exemplos de conversão:
  - `DEZ/2025` → `JAN/2026`
  - `JAN/2026` → `FEV/2026`
  - `NOV/2026` → `DEZ/2026`
  - `DEZ/2026` → `JAN/2027`

### 6. **Atualização no Banco**
- Executa UPDATE na tabela `sind.mes_corrente`:
  ```sql
  UPDATE sind.mes_corrente 
  SET abreviacao = 'JAN/2026' 
  WHERE id_divisao = 1
  ```

## 📊 Formato de Resposta JSON

### ✅ Sucesso com Atualizações

```json
{
  "status": "success",
  "dia_atual": 15,
  "message": "Atualização concluída: 1 divisão(ões) atualizada(s), 1 ignorada(s)",
  "divisoes_processadas": [
    {
      "id_divisao": 1,
      "nome": "Divisão A",
      "dia_renovacao": 15,
      "mes_anterior": "DEZ/2025",
      "mes_novo": "JAN/2026",
      "status": "atualizado"
    }
  ],
  "divisoes_ignoradas": [
    {
      "id_divisao": 2,
      "nome": "Divisão B",
      "dia_renovacao": 20,
      "motivo": "Dia atual (15) diferente do dia de renovação (20)"
    }
  ]
}
```

### ⚠️ Nenhuma Atualização Realizada

```json
{
  "status": "success",
  "dia_atual": 10,
  "message": "Nenhuma divisão foi atualizada. Total de divisões ignoradas: 2",
  "divisoes_processadas": [],
  "divisoes_ignoradas": [
    {
      "id_divisao": 1,
      "nome": "Divisão A",
      "dia_renovacao": 15,
      "motivo": "Dia atual (10) diferente do dia de renovação (15)"
    },
    {
      "id_divisao": 2,
      "nome": "Divisão B",
      "dia_renovacao": 20,
      "motivo": "Dia atual (10) diferente do dia de renovação (20)"
    }
  ]
}
```

### ❌ Erro

```json
{
  "status": "error",
  "message": "Erro ao atualizar mês corrente: [mensagem de erro]",
  "dia_atual": "15"
}
```

## 🗄️ Tabelas Utilizadas

### 1. **sind.divisao**
```sql
SELECT nome, cidade, id_divisao, descricao, dia_mes_renovacao
FROM sind.divisao;
```

**Campos importantes:**
- `id_divisao`: Identificador único da divisão
- `nome`: Nome da divisão
- `dia_mes_renovacao`: Dia do mês para renovação (1 a 31)

### 2. **sind.mes_corrente**
```sql
SELECT abreviacao, id_divisao, status, id
FROM sind.mes_corrente;
```

**Campos importantes:**
- `id`: Identificador único do registro
- `id_divisao`: Referência à divisão
- `abreviacao`: Mês atual no formato "MES/ANO"
- `status`: Status do registro

## 🔐 Regras de Validação

1. **Formato da Abreviação**: Deve ser `MES/ANO` (ex: `DEZ/2025`)
2. **Meses Válidos**: JAN, FEV, MAR, ABR, MAI, JUN, JUL, AGO, SET, OUT, NOV, DEZ
3. **Dia de Renovação**: Deve estar configurado na tabela `sind.divisao`
4. **Correspondência de Dia**: Dia atual = dia_mes_renovacao

## 📅 Exemplos de Uso

### Cenário 1: Dia 15 - Divisão A atualiza
```
Dia atual: 15
Divisão A: dia_renovacao = 15, mes_atual = "DEZ/2025"
Resultado: Atualiza para "JAN/2026" ✅

Divisão B: dia_renovacao = 20
Resultado: Ignora (dia diferente) ⏭️
```

### Cenário 2: Dia 20 - Divisão B atualiza
```
Dia atual: 20
Divisão A: dia_renovacao = 15
Resultado: Ignora (dia diferente) ⏭️

Divisão B: dia_renovacao = 20, mes_atual = "DEZ/2025"
Resultado: Atualiza para "JAN/2026" ✅
```

### Cenário 3: Dia 10 - Nenhuma atualiza
```
Dia atual: 10
Divisão A: dia_renovacao = 15
Resultado: Ignora (dia diferente) ⏭️

Divisão B: dia_renovacao = 20
Resultado: Ignora (dia diferente) ⏭️
```

## ⚙️ Configuração Necessária

Para que a API funcione, certifique-se de que:

1. ✅ A tabela `sind.divisao` possui o campo `dia_mes_renovacao` preenchido
2. ✅ A tabela `sind.mes_corrente` possui registros para cada divisão
3. ✅ O formato da abreviação está correto: `MES/ANO`

### Exemplo de Configuração

```sql
-- Configurar dia de renovação para divisão 1 (dia 15)
UPDATE sind.divisao 
SET dia_mes_renovacao = 15 
WHERE id_divisao = 1;

-- Configurar dia de renovação para divisão 2 (dia 20)
UPDATE sind.divisao 
SET dia_mes_renovacao = 20 
WHERE id_divisao = 2;
```

## 🤖 Automação

Para executar automaticamente todos os dias, configure um **CRON JOB**:

```bash
# Executa todos os dias às 00:01
1 0 * * * curl http://seu-servidor/qrcred/api_atualizar_mes_por_divisao.php
```

Ou use o **Agendador de Tarefas do Windows**:
```
Programa: curl.exe
Argumentos: http://seu-servidor/qrcred/api_atualizar_mes_por_divisao.php
Gatilho: Diariamente às 00:01
```

## 🔍 Logs e Debug

A API retorna informações detalhadas sobre:
- ✅ Divisões que foram atualizadas
- ⏭️ Divisões que foram ignoradas (com motivo)
- ❌ Erros que ocorreram

Todas as respostas são em formato JSON com charset UTF-8.

## 📝 Notas Importantes

1. A API processa **todas as divisões** em uma única execução
2. Cada divisão só é atualizada se o dia atual corresponder ao seu `dia_mes_renovacao`
3. O cálculo do próximo mês é automático e respeita a virada de ano
4. A API é segura contra SQL Injection (usa prepared statements)
5. Retorna JSON formatado para fácil leitura e debug
