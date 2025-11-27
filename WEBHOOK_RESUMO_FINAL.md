# 📋 Resumo Final - Webhook ZapSign

## 🎯 Objetivo

Implementar um sistema automatizado que recebe notificações da ZapSign quando documentos são assinados digitalmente e atualiza automaticamente a tabela PostgreSQL `sind.associados_sasmais`.

## 📁 Arquivos Criados

### 1. `webhook_zapsign.php` 
**Função**: Script principal do webhook
- Recebe requisições POST da ZapSign
- Processa dados JSON do webhook
- Conecta ao PostgreSQL e atualiza/insere registros
- Inclui logs detalhados para debugging
- Suporte a verificação de status

### 2. `webhook_zapsign_config.php`
**Função**: Arquivo de configuração
- Configurações de log (ativar/desativar, tamanho máximo)
- Configurações de segurança (IPs permitidos, tokens)
- Mapeamento de campos da tabela
- **Nota**: Usa conexão existente `Adm/php/banco.php`

### 3. `README_webhook_zapsign.md`
**Função**: Documentação completa
- Instruções passo-a-passo de instalação
- Configuração na ZapSign
- Testes e troubleshooting
- Consultas SQL úteis para monitoramento
- Fluxograma do processo

### 4. `setup_table_webhook.sql`
**Função**: Script de configuração do banco
- Adiciona colunas necessárias na tabela existente
- Cria índices para melhor performance
- Verifica estrutura da tabela
- Fornece consultas de exemplo e monitoramento

### 5. `teste_conexao_banco.php`
**Função**: Script de teste de conexão
- Verifica se `Adm/php/banco.php` existe e funciona
- Testa conexão PostgreSQL
- Verifica estrutura da tabela
- **Deve ser deletado após uso por segurança**

### 6. `WEBHOOK_RESUMO_FINAL.md`
**Função**: Este arquivo - resumo geral do projeto

## 🔄 Fluxo do Sistema

```
1. Usuário aceita termos → Registro gravado em sind.associados_sasmais
2. Usuário clica em "Acessar Verificação" → Abre ZapSign
3. Usuário assina documento na ZapSign
4. ZapSign dispara webhook → webhook_zapsign.php
5. Webhook localiza registro por CPF/nome
6. Webhook atualiza campos: has_signed=1, signed_at, doc_token, etc.
7. Sistema pode verificar status de assinatura em tempo real
```

## 📋 Checklist de Implementação

### ✅ Preparação do Servidor
- [ ] Servidor web com PHP 7.4+ e extensão PostgreSQL
- [ ] Certificado SSL configurado (HTTPS obrigatório)
- [ ] Permissões de escrita para logs

### ✅ Configuração do Banco
- [ ] Executar `setup_table_webhook.sql`
- [ ] Verificar se colunas foram criadas
- [ ] Verificar se arquivo `Adm/php/banco.php` existe
- [ ] Verificar se classe `Banco::conectar_postgres()` funciona

### ✅ Upload dos Arquivos
- [ ] `webhook_zapsign.php` → `/var/www/html/`
- [ ] `webhook_zapsign_config.php` → `/var/www/html/`
- [ ] Configurar credenciais no arquivo de config
- [ ] Definir permissões: `chmod 644 *.php`

### ✅ Configuração na ZapSign
- [ ] Acessar dashboard ZapSign → API → Webhooks
- [ ] Criar webhook: `https://seudominio.com/webhook_zapsign.php`
- [ ] Selecionar evento: `doc_signed`
- [ ] Testar webhook na ZapSign

### ✅ Testes
- [ ] **PRIMEIRO**: Teste de conexão: `teste_conexao_banco.php`
- [ ] Deletar arquivo de teste após verificação
- [ ] Teste de status: `webhook_zapsign.php?status=1`
- [ ] Teste manual com curl (comando no README)
- [ ] Teste real: enviar documento e assinar
- [ ] Verificar logs: `webhook_zapsign.log`
- [ ] Verificar dados no banco

## 🔧 Configurações Importantes

### Arquivo `webhook_zapsign_config.php`:
```php
// ESSENCIAL: Configurar apenas os logs
// As configurações de banco são obtidas de Adm/php/banco.php

// Para desenvolvimento
define('ENABLE_DEBUG_LOGS', true);

// Para produção
define('ENABLE_DEBUG_LOGS', false);
```

**Conexão com Banco**: O webhook usa automaticamente o arquivo `Adm/php/banco.php` e a classe `Banco::conectar_postgres()` do seu sistema existente.

### URL do Webhook na ZapSign:
```
https://seudominio.com/webhook_zapsign.php
```

## 📊 Monitoramento

### Consultas SQL para verificar funcionamento:
```sql
-- Ver últimas assinaturas
SELECT nome, cpf, signed_at, has_signed 
FROM sind.associados_sasmais 
WHERE event = 'doc_signed' 
ORDER BY signed_at DESC LIMIT 10;

-- Contar assinaturas hoje
SELECT COUNT(*) 
FROM sind.associados_sasmais 
WHERE DATE(signed_at) = CURRENT_DATE 
  AND has_signed = 1;
```

### Arquivos de log:
- `webhook_zapsign.log` - Logs do webhook
- `/var/log/nginx/error.log` - Logs do servidor web
- `/var/log/postgresql/` - Logs do PostgreSQL

## 🔒 Segurança

### Recomendações para produção:
1. **HTTPS obrigatório** - ZapSign só envia para URLs HTTPS
2. **Desabilitar logs detalhados** - `ENABLE_DEBUG_LOGS = false`
3. **Backup dos logs** - Configurar rotação automática
4. **Monitorar acessos** - Verificar logs do servidor regularmente
5. **Validar IPs** - Opcional: restringir IPs da ZapSign

## 🚨 Troubleshooting Rápido

### Webhook não recebe dados:
1. Verificar URL na ZapSign
2. Testar `webhook_zapsign.php?status=1`
3. Verificar logs do servidor web

### Erro de banco:
1. Verificar se `Adm/php/banco.php` existe
2. Verificar se classe `Banco` está correta
3. Testar conexão PostgreSQL
4. Verificar se tabela existe

### Dados não são gravados:
1. Verificar logs: `webhook_zapsign.log`
2. Verificar estrutura da tabela
3. Verificar permissões do usuário do banco

## 📞 Comandos Úteis

```bash
# Ver logs em tempo real
tail -f webhook_zapsign.log

# Testar webhook manualmente
curl -X POST https://seudominio.com/webhook_zapsign.php \
  -H "Content-Type: application/json" \
  -d '{"event":"doc_signed","doc_token":"teste","signers":[{"name":"Teste","email":"teste@email.com","cpf":"12345678901","has_signed":true}]}'

# Verificar status do webhook
curl https://seudominio.com/webhook_zapsign.php?status=1

# Verificar estrutura da tabela
psql -d qrcred -c "\d sind.associados_sasmais"
```

## ✅ Vantagens desta Implementação

1. **Automático**: Assinaturas são registradas automaticamente
2. **Tempo real**: Status atualizado imediatamente após assinatura
3. **Robusto**: Logs detalhados para debugging
4. **Flexível**: Configurações separadas para dev/produção
5. **Monitorável**: Queries SQL prontas para acompanhamento
6. **Seguro**: Validações e tratamento de erros
7. **Escalável**: Preparado para receber múltiplas assinaturas

## 🎉 Resultado Final

Com este sistema implementado:
- ✅ Usuários assinam documentos na ZapSign
- ✅ Dados são automaticamente gravados no PostgreSQL
- ✅ Status de assinatura é verificável em tempo real
- ✅ Relatórios e estatísticas disponíveis via SQL
- ✅ Logs completos para auditoria e debugging

---

**Este sistema substitui completamente a necessidade de verificação manual via API ZapSign, implementando um processo automatizado e confiável para rastrear assinaturas digitais.** 