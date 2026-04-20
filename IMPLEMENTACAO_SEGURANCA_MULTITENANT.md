# 🔒 Implementação de Segurança Multi-tenant - QRCred

## 📋 Visão Geral

Sistema de segurança incremental para proteger o QRCred contra acesso cross-tenant (vazamento de dados entre divisões/clientes).

**Status:** ✅ Arquivos criados - Pronto para implementação  
**Versão:** 1.0  
**Data:** 2026-02-12

---

## 🎯 Objetivos

1. ✅ Validar server-side que usuário só acessa sua própria divisão
2. ✅ Registrar tentativas de acesso indevido
3. ✅ Bloquear automaticamente após múltiplas tentativas
4. ✅ Fornecer dashboard de monitoramento
5. ✅ **NÃO quebrar** o sistema em produção

---

## 📁 Arquivos Criados

### 1. SQL - Tabelas de Auditoria
- **Arquivo:** `sql/create_tenant_security_tables.sql`
- **Função:** Criar tabelas de log e configuração
- **Execução:** Rodar UMA VEZ no banco PostgreSQL

### 2. PHP - Logger
- **Arquivo:** `Adm/php/tenant_logger.php`
- **Função:** Registrar logs de acesso e violações

### 3. PHP - Middleware
- **Arquivo:** `Adm/php/tenant_security.php`
- **Função:** Validar acessos e bloquear cross-tenant

---

## 🚀 Implementação em 3 Fases

### **FASE 1: Instalação (Não-Invasiva)**

#### Passo 1.1: Executar SQL no PostgreSQL

**Opção A - Via pgAdmin:**
1. Abrir pgAdmin
2. Conectar ao servidor: `216.245.210.4`
3. Selecionar database: `qrcred`
4. Abrir Query Tool
5. Copiar e colar conteúdo de `sql/create_tenant_security_tables.sql`
6. Executar (F5)

**Opção B - Via psql (linha de comando):**
```bash
psql -h 216.245.210.4 -U postgres -d qrcred -f sql/create_tenant_security_tables.sql
```

#### Passo 1.2: Verificar Tabelas Criadas
```sql
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'sind' 
AND table_name LIKE 'tenant%'
ORDER BY table_name;
```

**Resultado esperado:**
```
tenant_access_stats
tenant_security_config
tenant_security_log
usuario_divisao_permitida
```

#### Passo 1.3: Verificar Dados Populados
```sql
-- Ver configurações por divisão
SELECT * FROM sind.tenant_security_config;

-- Ver mapeamento usuário-divisão
SELECT 
    u.username,
    u.nome,
    d.nome as divisao_nome,
    udp.is_admin
FROM sind.usuario_divisao_permitida udp
INNER JOIN sind.usuarios u ON udp.codigo_usuario = u.codigo
INNER JOIN sind.divisao d ON udp.id_divisao = d.id_divisao
ORDER BY u.username;
```

---

### **FASE 2: Modificar Login (Armazenar divisão em $_SESSION)**

#### Passo 2.1: Backup do arquivo de login
```bash
# Criar backup antes de modificar
copy c:\xampp2\htdocs\qrcred\login_adm_localiza.php c:\xampp2\htdocs\qrcred\login_adm_localiza.php.backup
```

#### Passo 2.2: Modificar login_adm_localiza.php

**Localização:** Linha 50 (após `$std->divisao = $row["divisao"];`)

**ADICIONAR:**
```php
// SEGURANÇA MULTI-TENANT: Armazenar divisão em $_SESSION
$_SESSION['usuario_cod'] = $codigo;
$_SESSION['divisao'] = $row["divisao"];
$_SESSION['user_name'] = $username;
```

**Contexto completo:**
```php
$std->divisao = $row["divisao"];
$std->descricao = $row["descricao"];
$std->divisao_nome = $row["divisao_nome"];

// SEGURANÇA MULTI-TENANT: Armazenar divisão em $_SESSION
$_SESSION['usuario_cod'] = $codigo;
$_SESSION['divisao'] = $row["divisao"];
$_SESSION['user_name'] = $username;

if($row["situacao"] == 2){
```

---

### **FASE 3: Proteger Endpoints Críticos (Gradual)**

#### Passo 3.1: Criar arquivo de teste

**Arquivo:** `test_tenant_security.php`

```php
<?php
session_start();
require_once 'Adm/php/tenant_security.php';

echo "<h1>Teste de Segurança Multi-tenant</h1>";

try {
    $tenantSec = new TenantSecurity();
    
    echo "<h2>✅ Middleware carregado com sucesso</h2>";
    
    $usuario = $tenantSec->getUsuarioAutenticado();
    echo "<h3>Usuário Autenticado:</h3>";
    echo "<pre>" . print_r($usuario, true) . "</pre>";
    
    echo "<h3>Divisão Autenticada:</h3>";
    echo "<p><strong>" . $tenantSec->getDivisaoAutenticada() . "</strong></p>";
    
    // Testar validação
    $divisaoTeste = $tenantSec->getDivisaoAutenticada();
    if ($tenantSec->validateAccess($divisaoTeste)) {
        echo "<p style='color: green;'>✅ Acesso à divisão $divisaoTeste: PERMITIDO</p>";
    } else {
        echo "<p style='color: red;'>❌ Acesso à divisão $divisaoTeste: NEGADO</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERRO: " . $e->getMessage() . "</p>";
}
?>
```

#### Passo 3.2: Proteger Primeiro Endpoint (Exemplo Piloto)

**Arquivo:** `Adm/pages/associado/associado_read2.php`

**ANTES (linha ~10):**
```php
$divisao = $_POST['divisao'];
```

**DEPOIS:**
```php
require_once '../../php/tenant_security.php';
$tenantSec = new TenantSecurity();
$divisao = $tenantSec->getSecureDivisao($_POST['divisao']);
```

#### Passo 3.3: Lista de Endpoints para Proteger (Ordem de Prioridade)

**🔴 CRÍTICOS (Proteger primeiro - Dados sensíveis):**
1. ✅ `Adm/pages/associado/associado_read2.php` - Dados de associados
2. ⏳ `Adm/pages/conta/conta_list_mes.php` - Movimentações financeiras
3. ⏳ `Adm/pages/antecipacao/antecipacao_read2.php` - Antecipações
4. ⏳ `Adm/pages/convenio/convenio_read2.php` - Convênios
5. ⏳ `Adm/pages/empregador/empregador_read2.php` - Empregadores

**🟡 IMPORTANTES (Proteger em seguida):**
6. ⏳ `Adm/pages/producao/producao_read2_totais.php`
7. ⏳ `Adm/pages/cheques/cheques_read2_totais.php`
8. ⏳ `Adm/pages/cobranca/cobranca_read2_totais.php`
9. ⏳ `Adm/pages/recibos/recibos_read2_totais.php`

**🟢 SECUNDÁRIOS (Proteger por último):**
10. ⏳ `Adm/pages/manutencao/atualizar.php`
11. ⏳ Outros endpoints conforme necessário

---

## 📊 Monitoramento e Consultas Úteis

### Consulta 1: Ver tentativas bloqueadas (últimas 24h)
```sql
SELECT 
    data_hora,
    username,
    divisao_usuario,
    divisao_tentada,
    endpoint,
    motivo,
    ip_address
FROM sind.tenant_security_log
WHERE bloqueado = true
AND data_hora > NOW() - INTERVAL '24 hours'
ORDER BY data_hora DESC;
```

### Consulta 2: Estatísticas por divisão (últimos 7 dias)
```sql
SELECT 
    d.nome as divisao_nome,
    s.data_referencia,
    s.total_acessos,
    s.total_tentativas_bloqueadas,
    ROUND((s.total_tentativas_bloqueadas::NUMERIC / NULLIF(s.total_acessos, 0)) * 100, 2) as percentual_bloqueado
FROM sind.tenant_access_stats s
INNER JOIN sind.divisao d ON s.id_divisao = d.id_divisao
WHERE s.data_referencia >= CURRENT_DATE - 7
ORDER BY s.data_referencia DESC, d.nome;
```

### Consulta 3: Usuários com mais tentativas bloqueadas
```sql
SELECT 
    username,
    divisao_usuario,
    COUNT(*) as total_tentativas,
    MAX(data_hora) as ultima_tentativa
FROM sind.tenant_security_log
WHERE bloqueado = true
AND data_hora > NOW() - INTERVAL '7 days'
GROUP BY username, divisao_usuario
ORDER BY total_tentativas DESC
LIMIT 10;
```

### Consulta 4: Endpoints mais acessados
```sql
SELECT 
    endpoint,
    COUNT(*) as total_acessos,
    SUM(CASE WHEN bloqueado THEN 1 ELSE 0 END) as total_bloqueados
FROM sind.tenant_security_log
WHERE data_hora > NOW() - INTERVAL '7 days'
GROUP BY endpoint
ORDER BY total_acessos DESC
LIMIT 20;
```

---

## ⚠️ Pontos de Atenção

### 1. **Não Quebra o Sistema Atual**
- ✅ Middleware é **opcional** (não obrigatório)
- ✅ Pode ser adicionado **gradualmente** endpoint por endpoint
- ✅ Se falhar, retorna divisão autenticada (fallback seguro)
- ✅ Sistema continua funcionando mesmo sem o middleware

### 2. **Performance**
- ✅ Índices criados para queries rápidas
- ✅ Validação em memória (não consulta banco toda vez)
- ✅ Logs assíncronos (não bloqueiam requisição)
- ⚠️ Estimativa: < 50ms de overhead por requisição

### 3. **Compatibilidade**
- ✅ Funciona com sistema atual (sessionStorage + $_POST)
- ✅ Adiciona camada extra de segurança server-side
- ✅ Não altera comportamento do JavaScript
- ✅ Compatível com PHP 7.0+

### 4. **Rollback**
Se houver problemas, basta:
1. Remover as linhas `require_once` e `$tenantSec` dos arquivos modificados
2. Sistema volta ao estado anterior
3. Tabelas de log permanecem para análise

---

## 🔧 Configurações Avançadas

### Permitir usuário acessar múltiplas divisões
```sql
-- Exemplo: Permitir usuário 123 acessar divisão 2
INSERT INTO sind.usuario_divisao_permitida (codigo_usuario, id_divisao, is_admin)
VALUES (123, 2, false)
ON CONFLICT (codigo_usuario, id_divisao) DO NOTHING;
```

### Alterar configurações de bloqueio por divisão
```sql
-- Aumentar tentativas permitidas para divisão 1
UPDATE sind.tenant_security_config
SET max_failed_attempts = 10,
    lockout_duration_minutes = 60
WHERE id_divisao = 1;
```

### Desbloquear usuário manualmente
```sql
-- Remover logs de bloqueio do usuário 123
DELETE FROM sind.tenant_security_log
WHERE codigo_usuario = 123
AND bloqueado = true
AND data_hora > NOW() - INTERVAL '1 hour';
```

---

## 📈 Métricas de Sucesso

Após implementação completa, monitorar:

1. ✅ **Zero tentativas cross-tenant bem-sucedidas**
2. ✅ **Logs de todas as tentativas bloqueadas**
3. ✅ **Performance < 50ms por validação**
4. ✅ **Zero impacto em funcionalidades existentes**
5. ✅ **100% dos endpoints críticos protegidos**

---

## 🔄 Próximos Passos

- [x] **ETAPA 1.1:** Criar tabelas SQL ✅
- [x] **ETAPA 1.2:** Criar classe TenantLogger ✅
- [x] **ETAPA 1.3:** Criar classe TenantSecurity ✅
- [x] **ETAPA 1.4:** Criar documentação ✅
- [ ] **ETAPA 1.5:** Modificar login para armazenar divisão em $_SESSION
- [ ] **ETAPA 2:** Proteger endpoints críticos (gradual)
- [ ] **ETAPA 3:** Implementar RLS no PostgreSQL
- [ ] **ETAPA 4:** Padronizar nomenclatura (id_divisao)
- [ ] **ETAPA 5:** Adicionar Foreign Keys e índices

---

## 📞 Troubleshooting

### Problema: "Class 'TenantSecurity' not found"
**Solução:** Verificar caminho do require_once (usar caminho relativo correto)

### Problema: "Table sind.tenant_security_log does not exist"
**Solução:** Executar o SQL de criação das tabelas

### Problema: Usuário não consegue acessar nada
**Solução:** Verificar se divisão está em $_SESSION e na tabela usuario_divisao_permitida

### Problema: Performance lenta
**Solução:** Verificar se índices foram criados corretamente

---

**Última atualização:** 2026-02-12  
**Responsável:** Arquiteto de Software Sênior  
**Contato:** Verificar logs em `sind.tenant_security_log`
