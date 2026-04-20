# 🟡 ETAPA 2: ESCALABILIDADE - GUIA DE IMPLEMENTAÇÃO

## 📋 Visão Geral

Esta etapa foca em preparar o banco de dados e a aplicação para escalabilidade em ambiente Multi-tenant, garantindo performance e integridade dos dados.

---

## 🎯 Objetivos

1. ✅ Padronizar nomenclatura (`id_divisao` em todas as tabelas)
2. ✅ Adicionar Foreign Keys com `ON DELETE CASCADE`
3. ✅ Criar índices compostos para otimização
4. ✅ Implementar connection pooling por divisão

---

## 📂 Arquivos Criados

```
database/multi_tenant/
├── 02_auditoria_nomenclatura.sql          # Auditoria inicial
├── 02_padronizar_nomenclatura.sql         # Renomear colunas
├── 02_criar_foreign_keys.sql              # Foreign Keys com CASCADE
├── 02_criar_indices_compostos.sql         # Índices de performance
├── 02_connection_pooling.php              # Pool de conexões
└── 02_GUIA_IMPLEMENTACAO_ESCALABILIDADE.md # Este arquivo
```

---

## 🚀 Ordem de Execução

### **PASSO 1: Backup Completo**

```bash
# Backup do banco de dados
pg_dump -U postgres -d qrcred -F c -b -v -f qrcred_backup_etapa2_$(date +%Y%m%d_%H%M%S).backup

# Verificar backup
pg_restore --list qrcred_backup_etapa2_*.backup | head -20
```

### **PASSO 2: Auditoria de Nomenclatura**

```bash
# Conectar ao PostgreSQL
psql -U postgres -d qrcred

# Executar auditoria
\i database/multi_tenant/02_auditoria_nomenclatura.sql

# Analisar resultados
# - Verificar todas as colunas relacionadas a divisão
# - Identificar inconsistências de nomenclatura
# - Confirmar estrutura das tabelas
```

**Resultado esperado:**
- Lista de todas as colunas com nomenclatura `divisao`, `div`, `id_divisao`
- Foreign Keys existentes
- Índices atuais
- Distribuição de dados por divisão

### **PASSO 3: Padronizar Nomenclatura**

```bash
# ATENÇÃO: Este script modifica a estrutura do banco!
# Certifique-se de ter backup antes de executar

psql -U postgres -d qrcred

# Executar padronização
\i database/multi_tenant/02_padronizar_nomenclatura.sql
```

**O que será feito:**
- ✅ Renomear `sind.conta.divisao` → `sind.conta.id_divisao`
- ✅ Renomear `sind.antecipacao.divisao` → `sind.antecipacao.id_divisao`
- ✅ Renomear `sind.convenio.divisao` → `sind.convenio.id_divisao`
- ✅ Renomear `sind.empregador.divisao` → `sind.empregador.id_divisao`
- ✅ Renomear `sind.valor_taxa_cartao.divisao` → `sind.valor_taxa_cartao.id_divisao`
- ✅ Adicionar comentários de documentação

**Verificação:**
```sql
-- Confirmar que todas as colunas foram renomeadas
SELECT table_name, column_name 
FROM information_schema.columns
WHERE table_schema = 'sind' AND column_name = 'id_divisao'
ORDER BY table_name;
```

### **PASSO 4: Criar Foreign Keys com CASCADE**

```bash
psql -U postgres -d qrcred

# Executar criação de FKs
\i database/multi_tenant/02_criar_foreign_keys.sql
```

**O que será feito:**
- ✅ Criar FK `sind.associado.id_divisao` → `sind.divisao.id` (CASCADE)
- ✅ Criar FK `sind.conta.id_divisao` → `sind.divisao.id` (CASCADE)
- ✅ Criar FK `sind.antecipacao.id_divisao` → `sind.divisao.id` (CASCADE)
- ✅ Criar FK `sind.convenio.id_divisao` → `sind.divisao.id` (CASCADE)
- ✅ Criar FK `sind.empregador.id_divisao` → `sind.divisao.id` (CASCADE)
- ✅ Criar FK `sind.mes_corrente.id_divisao` → `sind.divisao.id` (CASCADE)
- ✅ Criar FK `sind.valor_taxa_cartao.id_divisao` → `sind.divisao.id` (CASCADE)
- ✅ Criar FK `sind.solicitacao_bloqueio.id_divisao` → `sind.divisao.id` (CASCADE)
- ✅ Criar FKs entre tabelas relacionadas

**Verificação:**
```sql
-- Listar todas as Foreign Keys criadas
SELECT
    tc.table_name, 
    tc.constraint_name,
    kcu.column_name,
    ccu.table_name AS foreign_table,
    rc.delete_rule
FROM information_schema.table_constraints AS tc 
JOIN information_schema.key_column_usage AS kcu
  ON tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage AS ccu
  ON ccu.constraint_name = tc.constraint_name
JOIN information_schema.referential_constraints AS rc
  ON rc.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY' 
  AND tc.table_schema = 'sind'
  AND tc.constraint_name LIKE 'fk_%'
ORDER BY tc.table_name;
```

### **PASSO 5: Criar Índices Compostos**

```bash
psql -U postgres -d qrcred

# Executar criação de índices
# ATENÇÃO: Pode demorar em tabelas grandes
\i database/multi_tenant/02_criar_indices_compostos.sql
```

**O que será feito:**
- ✅ 15 índices compostos otimizados
- ✅ Índices com `INCLUDE` para covering indexes
- ✅ Índices parciais para filtros específicos
- ✅ Atualização de estatísticas

**Índices criados:**

| Tabela | Índice | Colunas | Objetivo |
|--------|--------|---------|----------|
| associado | idx_associado_divisao_empregador | id_divisao, empregador | Busca por empregador |
| associado | idx_associado_divisao_codigo | id_divisao, codigo | Busca por matrícula |
| associado | idx_associado_divisao_nome | id_divisao, nome | Busca por nome |
| conta | idx_conta_divisao_mes | id_divisao, mes | Consultas por mês |
| conta | idx_conta_divisao_associado | id_divisao, id_associado | Contas do associado |
| conta | idx_conta_divisao_data | id_divisao, data | Consultas por período |
| antecipacao | idx_antecipacao_divisao_mes | id_divisao, mes | Antecipações por mês |
| antecipacao | idx_antecipacao_divisao_associado | id_divisao, id_associado | Antecipações do associado |
| antecipacao | idx_antecipacao_divisao_aprovado | id_divisao, aprovado | Filtro por situação |
| convenio | idx_convenio_divisao_codigo | id_divisao, codigo | Busca por código |
| convenio | idx_convenio_divisao_status | id_divisao, status | Convênios ativos |
| empregador | idx_empregador_divisao_codigo | id_divisao, codigo | Busca por código |
| solicitacao_bloqueio | idx_solicitacao_divisao_situacao | id_divisao, id_situacao | Filtro por situação |
| solicitacao_bloqueio | idx_solicitacao_divisao_associado | id_divisao, id_associado | Solicitações do associado |
| mes_corrente | idx_mes_corrente_divisao | id_divisao | Único por divisão |

**Verificação:**
```sql
-- Verificar índices criados e seus tamanhos
SELECT
    schemaname,
    tablename,
    indexname,
    pg_size_pretty(pg_relation_size(schemaname||'.'||indexname)) as size
FROM pg_indexes
WHERE schemaname = 'sind' AND indexname LIKE 'idx_%'
ORDER BY pg_relation_size(schemaname||'.'||indexname) DESC;
```

### **PASSO 6: Implementar Connection Pooling**

**6.1. Integrar classe ConnectionPoolManager**

Copiar o arquivo `02_connection_pooling.php` para:
```
Adm/php/ConnectionPoolManager.php
```

**6.2. Modificar classe Banco existente**

Editar `Adm/php/banco.php`:

```php
<?php
require_once(__DIR__ . '/ConnectionPoolManager.php');

class Banco {
    
    // Método atualizado para usar pool
    public static function conectar_postgres($divisao_id = null) {
        if ($divisao_id !== null) {
            // Usar connection pooling por divisão
            $poolManager = ConnectionPoolManager::getInstance();
            return $poolManager->getConnection($divisao_id);
        } else {
            // Conexão tradicional (para compatibilidade)
            return self::conectar_postgres_tradicional();
        }
    }
    
    // Método tradicional (renomeado)
    private static function conectar_postgres_tradicional() {
        // Código existente da conexão...
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '5432';
        $dbname = getenv('DB_NAME') ?: 'qrcred';
        $user = getenv('DB_USER') ?: 'postgres';
        $password = getenv('DB_PASSWORD') ?: '';
        
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        
        try {
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("Erro de conexão: " . $e->getMessage());
        }
    }
}
?>
```

**6.3. Atualizar arquivos PHP para usar divisão**

Exemplo de uso nos arquivos existentes:

```php
<?php
// ANTES (sem pool)
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// DEPOIS (com pool)
$divisao = $_SESSION['divisao'] ?? 1;
$pdo = Banco::conectar_postgres($divisao);
// Não precisa setAttribute, já vem configurado
```

---

## 🔄 Atualização dos Arquivos PHP

### **Arquivos que precisam ser atualizados:**

1. **Arquivos de listagem/consulta:**
   - `conta_list_mes.php`
   - `antecipacao_read2.php`
   - `associado_list.php`
   - `convenio_list.php`
   - `empregador_list.php`

2. **Arquivos de operações:**
   - `conta_salvar.php`
   - `antecipacao_salvar.php`
   - `associado_salvar.php`
   - `convenio_salvar.php`

3. **Arquivos do módulo empregador:**
   - `empregador/listar_solicitacoes_bloqueio.php`
   - `empregador/cancelar_solicitacao_bloqueio.php`
   - `empregador/consultar_conta_list.php`

### **Padrão de atualização:**

```php
<?php
// Incluir banco
include "../Adm/php/banco.php";

// Obter divisão da sessão
session_start();
$divisao = isset($_SESSION['divisao']) ? (int)$_SESSION['divisao'] : 1;

// Conectar usando pool
$pdo = Banco::conectar_postgres($divisao);

// Usar normalmente
$stmt = $pdo->prepare("SELECT * FROM sind.associado WHERE id_divisao = :divisao");
$stmt->execute([':divisao' => $divisao]);
$results = $stmt->fetchAll();

// Liberar conexão (opcional, mas recomendado)
$poolManager = ConnectionPoolManager::getInstance();
$poolManager->releaseConnection($divisao, $pdo);
?>
```

---

## ✅ Checklist de Validação

### **Banco de Dados:**
- [ ] Backup realizado com sucesso
- [ ] Auditoria executada e analisada
- [ ] Nomenclatura padronizada (`id_divisao` em todas as tabelas)
- [ ] Foreign Keys criadas com `ON DELETE CASCADE`
- [ ] Índices compostos criados
- [ ] Estatísticas atualizadas (`ANALYZE`)

### **Aplicação:**
- [ ] `ConnectionPoolManager.php` copiado para `Adm/php/`
- [ ] Classe `Banco` atualizada
- [ ] Arquivos PHP principais atualizados para usar pool
- [ ] Testes realizados em ambiente de desenvolvimento

### **Performance:**
- [ ] Queries principais testadas com `EXPLAIN ANALYZE`
- [ ] Índices sendo utilizados corretamente
- [ ] Tempo de resposta melhorado
- [ ] Pool de conexões funcionando

---

## 📊 Testes de Performance

### **Teste 1: Verificar uso de índices**

```sql
-- Query de exemplo
EXPLAIN ANALYZE
SELECT a.* 
FROM sind.associado a
WHERE a.id_divisao = 1 
  AND a.empregador = 123;

-- Resultado esperado: Index Scan usando idx_associado_divisao_empregador
```

### **Teste 2: Verificar CASCADE**

```sql
BEGIN;

-- Criar divisão de teste
INSERT INTO sind.divisao (id, nome, status) VALUES (999, 'TESTE_CASCADE', 1);

-- Criar associado de teste
INSERT INTO sind.associado (id_divisao, nome, codigo) 
VALUES (999, 'Teste Associado', 'TEST999');

-- Deletar divisão (deve deletar associado automaticamente)
DELETE FROM sind.divisao WHERE id = 999;

-- Verificar se associado foi deletado
SELECT COUNT(*) FROM sind.associado WHERE codigo = 'TEST999'; 
-- Deve retornar 0

ROLLBACK;
```

### **Teste 3: Verificar Connection Pool**

```php
<?php
$poolManager = ConnectionPoolManager::getInstance();

// Criar 5 conexões para divisão 1
for ($i = 0; $i < 5; $i++) {
    $pdo = $poolManager->getConnection(1);
    echo "Conexão {$i} criada\n";
}

// Ver estatísticas
$stats = $poolManager->getPoolStats(1);
print_r($stats);
// Deve mostrar: total_connections: 5, active_connections: 5
?>
```

---

## 🎯 Próximos Passos (Etapa 3)

Após concluir esta etapa, você estará pronto para:

1. **🔵 Etapa 3: Isolamento de Dados**
   - Implementar Row Level Security (RLS)
   - Criar políticas de acesso por divisão
   - Implementar auditoria de acessos

2. **🟢 Etapa 4: Monitoramento**
   - Dashboard de métricas por divisão
   - Alertas de performance
   - Logs centralizados

---

## 📞 Suporte

Em caso de dúvidas ou problemas:

1. Verificar logs do PostgreSQL: `/var/log/postgresql/`
2. Verificar logs da aplicação: `error_log`
3. Executar `ROLLBACK` se algo der errado
4. Restaurar backup se necessário

---

## 📝 Notas Importantes

⚠️ **ATENÇÃO:**
- Execute em ambiente de desenvolvimento primeiro
- Faça backup antes de cada etapa
- Teste todas as funcionalidades após cada mudança
- Monitore performance após implementação
- Documente qualquer customização adicional

✅ **BENEFÍCIOS ALCANÇADOS:**
- Integridade referencial garantida
- Performance otimizada com índices
- Escalabilidade preparada para crescimento
- Código mais limpo e padronizado
- Facilita manutenção futura

---

**Data de criação:** 2026-02-21  
**Versão:** 1.0  
**Status:** Pronto para implementação
