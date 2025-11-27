# Gerenciamento de Taxa de Cartão - QRCred

## 📋 Visão Geral

Sistema para gerenciar os valores de taxa de cartão por divisão na tabela `sind.valor_taxa_cartao`.

---

## 📁 Arquivos Criados

### **1. valor_taxa_gerenciar.html**
Interface principal para gerenciar as taxas de cartão.

**Funcionalidades:**
- ✅ Listar taxas cadastradas por divisão
- ✅ Cadastrar nova taxa
- ✅ Editar taxa existente
- ✅ Interface responsiva e moderna

**Campos do formulário:**
- **Divisão**: Preenchido automaticamente (readonly)
- **Valor**: Campo monetário formatado (R$ 0,00)
- **Descrição**: Texto descritivo da taxa

---

### **2. valor_taxa_gerenciar.js**
Script JavaScript para gerenciar a interface.

**Funções principais:**
- `carregarTaxas()` - Lista as taxas cadastradas
- `salvarTaxa()` - Salva ou atualiza uma taxa
- `editarTaxa()` - Prepara o formulário para edição
- `limparFormulario()` - Limpa o formulário
- `formatarValor()` - Formata o campo de valor monetário

**Integração:**
- Usa `sessionStorage` para obter dados do usuário logado
- Comunicação via AJAX com backend PHP
- Alertas com SweetAlert2

---

### **3. valor_taxa_read.php**
Backend PHP para listar as taxas cadastradas.

**Query SQL:**
```sql
SELECT 
    vt.id,
    vt.divisao,
    vt.valor,
    vt.descricao,
    d.nome as divisao_nome
FROM sind.valor_taxa_cartao vt
LEFT JOIN sind.divisao d ON d.id = vt.divisao
WHERE vt.divisao = :divisao
ORDER BY vt.divisao, vt.id
```

**Retorno JSON:**
```json
[
  {
    "id": 1,
    "divisao": 1,
    "divisao_nome": "Divisão Principal",
    "valor": "5,00",
    "valor_decimal": 5.00,
    "descricao": "Taxa de Cartão Mensal"
  }
]
```

---

### **4. valor_taxa_salvar.php**
Backend PHP para cadastrar e atualizar taxas.

**Operações:**

#### **INSERT (Cadastro)**
```sql
INSERT INTO sind.valor_taxa_cartao (divisao, valor, descricao) 
VALUES (:divisao, :valor, :descricao)
```

**Validações:**
- ✓ Verifica se já existe taxa para a divisão
- ✓ Campos obrigatórios: divisao, valor, descricao
- ✓ Converte valor de formato brasileiro para decimal

#### **UPDATE (Atualização)**
```sql
UPDATE sind.valor_taxa_cartao 
SET valor = :valor, descricao = :descricao
WHERE id = :id AND divisao = :divisao
```

**Validações:**
- ✓ ID obrigatório
- ✓ Verifica se o registro pertence à divisão do usuário

---

## 🎯 Como Usar

### **1. Acessar a Interface**
```
/Adm/pages/txcartao/valor_taxa_gerenciar.html
```

### **2. Cadastrar Nova Taxa**
1. Preencha o **Valor** (ex: 5,00)
2. Preencha a **Descrição** (ex: Taxa de Cartão Mensal)
3. Clique em **Salvar**
4. Confirme a operação

### **3. Editar Taxa Existente**
1. Na tabela, clique em **Editar** na linha desejada
2. Modifique o **Valor** ou **Descrição**
3. Clique em **Atualizar**
4. Confirme a operação

### **4. Cancelar Edição**
- Clique em **Cancelar** para voltar ao modo de cadastro

---

## 🔒 Regras de Negócio

### **Restrições:**
1. **Uma taxa por divisão**: Cada divisão pode ter apenas uma configuração de taxa
2. **Divisão fixa**: Não é possível alterar a divisão após o cadastro
3. **Campos obrigatórios**: Valor e descrição são obrigatórios
4. **Formato de valor**: Aceita formato brasileiro (0,00)

### **Integração com Trigger:**
Após configurar a taxa na tabela `sind.valor_taxa_cartao`, o trigger `trg_insere_taxa_cartao_automatica` na tabela `sind.conta` utilizará esses valores para inserir automaticamente a taxa de cartão quando um lançamento for feito.

---

## 📊 Estrutura da Tabela

### **sind.valor_taxa_cartao**
```sql
CREATE TABLE sind.valor_taxa_cartao (
    id SERIAL PRIMARY KEY,
    divisao INTEGER NOT NULL,
    valor DOUBLE PRECISION NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    UNIQUE(divisao)
);
```

**Campos:**
- `id`: Identificador único (auto-incremento)
- `divisao`: ID da divisão (chave única)
- `valor`: Valor da taxa em formato decimal
- `descricao`: Descrição da taxa

---

## 🔗 Relação com Outros Módulos

### **Trigger Automático**
O sistema possui um trigger (`trg_insere_taxa_cartao_automatica`) que:
1. Dispara quando há INSERT na tabela `sind.conta`
2. Verifica se o associado já tem taxa de cartão no mês
3. Se não tiver, busca o valor na `sind.valor_taxa_cartao`
4. Insere automaticamente a taxa de cartão

**Arquivo:** `trigger_taxa_cartao_automatica.sql`

### **Lançamento Manual**
O arquivo `txcartao_cadastro.html` permite lançar taxa de cartão manualmente para todos os associados de um mês específico.

**Diferença:**
- **Gerenciar**: Configura o valor da taxa (tabela `valor_taxa_cartao`)
- **Lançamento Manual**: Insere taxa para todos os associados de uma vez (tabela `conta`)
- **Trigger Automático**: Insere taxa automaticamente ao criar lançamento (tabela `conta`)

---

## 🛡️ Segurança

### **Validações Frontend:**
- ✓ Campos obrigatórios
- ✓ Formato de valor monetário
- ✓ Confirmação antes de salvar

### **Validações Backend:**
- ✓ Verificação de campos obrigatórios
- ✓ Conversão segura de valores
- ✓ Proteção contra duplicação
- ✓ Filtro por divisão do usuário

### **Proteção XSS:**
- ✓ Função `escapeHtml()` no JavaScript
- ✓ Conversão de encoding no PHP

---

## 📝 Exemplos de Uso

### **Exemplo 1: Cadastrar Taxa**
```javascript
// Dados enviados via AJAX
{
  "operation": "insert",
  "divisao": 1,
  "valor": "5,00",
  "descricao": "Taxa de Cartão Mensal"
}

// Resposta
{
  "success": true,
  "message": "Taxa cadastrada com sucesso!",
  "operation": "insert"
}
```

### **Exemplo 2: Atualizar Taxa**
```javascript
// Dados enviados via AJAX
{
  "operation": "update",
  "id": 1,
  "divisao": 1,
  "valor": "7,50",
  "descricao": "Taxa de Cartão Mensal Atualizada"
}

// Resposta
{
  "success": true,
  "message": "Taxa atualizada com sucesso!",
  "operation": "update"
}
```

---

## 🐛 Troubleshooting

### **Problema: Taxa não aparece na lista**
**Solução:**
1. Verifique se a divisão do usuário está correta
2. Verifique se há dados na tabela `sind.valor_taxa_cartao`
3. Verifique o console do navegador para erros JavaScript

### **Problema: Erro ao salvar**
**Solução:**
1. Verifique se todos os campos estão preenchidos
2. Verifique se o valor está no formato correto (0,00)
3. Verifique se já existe taxa para esta divisão (ao cadastrar)

### **Problema: Trigger não insere taxa automaticamente**
**Solução:**
1. Verifique se a taxa está cadastrada na `sind.valor_taxa_cartao`
2. Verifique se o trigger está instalado: `trg_insere_taxa_cartao_automatica`
3. Verifique os logs do PostgreSQL para mensagens do trigger

---

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique este README
2. Verifique o arquivo `README_TAXA_CARTAO_AUTOMATICA.md` para informações sobre o trigger
3. Consulte os logs do navegador (F12 > Console)
4. Consulte os logs do servidor PHP
