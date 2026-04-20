# 🛡️ Plano de Proteção de Endpoints - QRCred

## ✅ Status Atual
- Middleware implementado e testado
- Sessão PHP funcionando
- Logs de segurança ativos
- Tabelas de auditoria criadas

---

## 🎯 Objetivo
Proteger endpoints críticos gradualmente, sem quebrar o sistema em produção.

---

## 📋 Endpoints Prioritários (Ordem de Implementação)

### 🔴 **FASE 1: Endpoints Críticos de Dados Financeiros** (Implementar AGORA)

#### 1. **Associados** - Dados pessoais e financeiros
- **Arquivo:** `Adm/pages/associado/associado_read2.php`
- **Risco:** Alto - Dados pessoais (CPF, salário, limite)
- **Prioridade:** 🔴 CRÍTICA

#### 2. **Conta (Movimentações)** - Transações financeiras
- **Arquivo:** `Adm/pages/conta/conta_list_mes.php`
- **Risco:** Alto - Movimentações financeiras
- **Prioridade:** 🔴 CRÍTICA

#### 3. **Antecipações** - Solicitações de crédito
- **Arquivo:** `Adm/pages/antecipacao/antecipacao_read2.php`
- **Risco:** Alto - Dados financeiros sensíveis
- **Prioridade:** 🔴 CRÍTICA

---

### 🟡 **FASE 2: Endpoints Importantes** (Implementar em seguida)

#### 4. **Convênios** - Rede credenciada
- **Arquivo:** `Adm/pages/convenio/convenio_read2.php`
- **Risco:** Médio - Dados comerciais
- **Prioridade:** 🟡 IMPORTANTE

#### 5. **Empregadores** - Dados corporativos
- **Arquivo:** `Adm/pages/empregador/empregador_read2.php`
- **Risco:** Médio - Dados empresariais
- **Prioridade:** 🟡 IMPORTANTE

---

### 🟢 **FASE 3: Endpoints Secundários** (Implementar por último)

#### 6. **Produção** - Relatórios
- **Arquivo:** `Adm/pages/producao/producao_read2_totais.php`
- **Risco:** Baixo - Apenas relatórios
- **Prioridade:** 🟢 SECUNDÁRIA

#### 7. **Cheques** - Controle de cheques
- **Arquivo:** `Adm/pages/cheques/cheques_read2_totais.php`
- **Risco:** Baixo - Dados operacionais
- **Prioridade:** 🟢 SECUNDÁRIA

---

## 🔧 Como Implementar (Passo a Passo)

### **Template de Implementação:**

**ANTES:**
```php
<?php
require_once '../../php/banco.php';
$divisao = $_POST['divisao'];
```

**DEPOIS:**
```php
<?php
require_once '../../php/banco.php';
require_once '../../php/tenant_security.php';

$tenantSec = new TenantSecurity();
$divisao = $tenantSec->getSecureDivisao($_POST['divisao']);
```

---

## 📊 Checklist de Implementação

### **Para cada endpoint:**

- [ ] 1. Fazer backup do arquivo original
- [ ] 2. Adicionar `require_once` do tenant_security.php
- [ ] 3. Substituir `$_POST['divisao']` por `$tenantSec->getSecureDivisao()`
- [ ] 4. Testar endpoint com usuário da divisão correta
- [ ] 5. Testar tentativa de acesso cross-tenant (deve bloquear)
- [ ] 6. Verificar logs em `sind.tenant_security_log`
- [ ] 7. Confirmar que funcionalidade não quebrou
- [ ] 8. Marcar como concluído

---

## 🧪 Testes Recomendados

### **Teste 1: Acesso Normal**
1. Login com usuário da divisão 1
2. Acessar endpoint protegido
3. **Resultado esperado:** Dados da divisão 1 exibidos

### **Teste 2: Tentativa Cross-Tenant**
1. Login com usuário da divisão 1
2. Modificar `divisao` no POST para 2 (via DevTools)
3. **Resultado esperado:** 
   - Dados da divisão 1 retornados (fallback seguro)
   - Log registrado em `tenant_security_log`

### **Teste 3: Verificar Logs**
```sql
SELECT 
    data_hora,
    username,
    divisao_usuario,
    divisao_tentada,
    bloqueado,
    motivo,
    endpoint
FROM sind.tenant_security_log
WHERE data_hora > NOW() - INTERVAL '1 hour'
ORDER BY data_hora DESC;
```

---

## 📈 Métricas de Sucesso

Após implementar FASE 1:
- ✅ 3 endpoints críticos protegidos
- ✅ Zero vazamentos de dados cross-tenant
- ✅ Logs de todas as tentativas
- ✅ Sistema funcionando normalmente

---

## ⚠️ Pontos de Atenção

### **NÃO fazer:**
- ❌ Não implementar todos de uma vez
- ❌ Não pular testes
- ❌ Não ignorar logs de erro
- ❌ Não modificar queries SQL (apenas adicionar validação)

### **FAZER:**
- ✅ Implementar um por vez
- ✅ Testar cada endpoint após modificação
- ✅ Monitorar logs após cada implementação
- ✅ Fazer backup antes de modificar
- ✅ Documentar problemas encontrados

---

## 🚀 Começar Agora

**Primeiro endpoint a proteger:**
`Adm/pages/associado/associado_read2.php`

**Motivo:** Contém dados pessoais sensíveis (CPF, salário, limite de crédito)

**Tempo estimado:** 5-10 minutos por endpoint

---

## 📞 Suporte

Se encontrar problemas:
1. Verificar logs em `sind.tenant_security_log`
2. Testar com `test_tenant_security.php`
3. Verificar se sessão está ativa com `debug_session.php`

---

**Última atualização:** 2026-02-13  
**Status:** Pronto para implementação FASE 1
