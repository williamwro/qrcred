# Sistema de Cadastro de Taxa de Cartão

## Descrição
Sistema para lançamento automático de taxas de cartão para todos os associados que possuem valores lançados na tabela `sind.conta` no mês selecionado.

## Arquivos Criados

### 1. `txcartao_cadastro.html`
Interface HTML com formulário para cadastro de taxas de cartão.

**Campos do formulário:**
- **Mês**: Select com meses disponíveis (carregado dinamicamente, mês corrente pré-selecionado)
- **Valor**: Campo numérico com valor padrão de R$ 7,50
- **Descrição**: Campo texto com valor padrão "Tarifa Cartao / Manutenção"

### 2. `meses_conta.php`
Script PHP que busca os meses disponíveis para cadastro.

**Funcionalidades:**
- Busca meses da tabela `sind.meses_conta` com `status_cadastro = 1`
- Retorna o mês corrente da tabela `sind.mes_corrente`
- Retorna dados em formato JSON

### 3. `gravar_taxa_cartao.php`
Script PHP que processa a gravação das taxas no banco de dados.

**Funcionalidades:**
- Recebe: mês, valor, descrição, divisão e usuário
- Valida todos os campos obrigatórios
- Converte valor de formato brasileiro (0,00) para decimal
- Insere registros na tabela `sind.conta` para todos os associados elegíveis
- Utiliza transação para garantir integridade dos dados
- Retorna quantidade de registros inseridos

**Critérios de seleção dos associados:**
- Associados com `id_situacao <> 2` e `id_situacao <> 3` (ativos)
- Que possuem lançamentos na tabela `sind.conta` no mês selecionado

### 4. `txcartao_cadastro.js`
Script JavaScript com toda a lógica do formulário.

**Funcionalidades:**
- Carrega dados da sessão (divisão, usuário)
- Carrega meses disponíveis via AJAX
- Seleciona automaticamente o mês corrente
- Valida campos do formulário
- Formata campo de valor (aceita apenas números e vírgula)
- Exibe confirmação antes de gravar
- Processa gravação via AJAX
- Exibe mensagens de sucesso/erro com SweetAlert2
- Mostra quantidade de registros inseridos

## Fluxo de Funcionamento

1. **Carregamento da página:**
   - Sistema carrega dados da sessão (divisão, usuário)
   - Busca meses disponíveis do banco de dados
   - Seleciona automaticamente o mês corrente
   - Exibe valores padrão (R$ 7,50 e descrição)

2. **Preenchimento do formulário:**
   - Usuário pode alterar o mês (se necessário)
   - Usuário pode alterar o valor
   - Usuário pode alterar a descrição

3. **Gravação:**
   - Sistema valida todos os campos
   - Exibe confirmação com resumo dos dados
   - Processa gravação via AJAX
   - Exibe resultado (sucesso ou erro)
   - Mostra quantidade de associados que receberam a taxa

## SQL Utilizado

```sql
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
    249,                    -- Convênio fixo (Taxa Cartão)
    :valor,                 -- Valor informado no formulário
    DATE :data,             -- Data atual
    TIME :hora,             -- Hora atual
    :descricao,             -- Descrição informada
    :mes,                   -- Mês selecionado
    s.empregador,
    :divisao,
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
      AND a.id IN (
          SELECT DISTINCT c.id_associado
          FROM sind.conta c
          WHERE c.mes = :mes
      )
) s;
```

## Dependências

- **jQuery**: Para manipulação DOM e AJAX
- **SweetAlert2**: Para exibição de alertas e confirmações
- **Bootstrap**: Para estilização (classes CSS)
- **Font Awesome**: Para ícones

## Integração com o Sistema

Para integrar esta funcionalidade ao menu do sistema, adicione a seguinte entrada no arquivo de menu:

```javascript
{
    "titulo": "Taxa Cartão",
    "icone": "fa-credit-card",
    "url": "../Adm/pages/txcartao/txcartao_cadastro.html"
}
```

## Observações Importantes

1. **Convênio 249**: O sistema utiliza o convênio fixo 249 para identificar taxas de cartão
2. **UUID único**: Cada lançamento recebe um UUID único gerado via MD5
3. **Transação**: A gravação utiliza transação para garantir integridade
4. **Validação**: Sistema valida associados ativos e com lançamentos no mês
5. **Formato de valor**: Aceita formato brasileiro (0,00) e converte automaticamente

## Segurança

- Validação de campos obrigatórios
- Uso de prepared statements (PDO) para prevenir SQL Injection
- Transação para garantir integridade dos dados
- Tratamento de erros com try/catch
- Confirmação antes de executar operação crítica

## Autor
Sistema QRCred - Módulo de Taxa de Cartão
Data: Outubro 2025
