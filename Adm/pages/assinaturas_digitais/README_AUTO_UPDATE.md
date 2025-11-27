# Sistema de Atualização Automática - Assinaturas Digitais

## 📝 Descrição

Este sistema implementa atualização automática em tempo real para a tela de Assinaturas Digitais, detectando automaticamente quando novos dados chegam via webhook do ZapSign e atualizando a interface sem necessidade de intervenção do usuário.

## ⚙️ Como Funciona

### 1. **Polling Automático**
- O sistema verifica a cada **15 segundos** (padrão) se há novos dados na tabela `sind.associados_sasmais`
- Utiliza timestamps para detectar registros criados/atualizados após a última verificação
- Não sobrecarrega o servidor com requisições desnecessárias

### 2. **Detecção de Novos Dados**
- Arquivo `check_new_data.php` compara timestamp da última verificação com registros recentes
- Retorna informações sobre novos dados encontrados
- Mantém controle de estado para evitar atualizações desnecessárias

### 3. **Atualização da Interface**
- **DataTable é recarregado automaticamente** quando novos dados são detectados
- **Notificação visual** informa ao usuário sobre a atualização
- **Indicador visual** mostra status da atualização automática
- **Paginação é mantida** para não perder posição atual do usuário

## 🎛️ Controles Disponíveis

### **Indicador Visual**
- **Verde com 🔄**: Auto-atualização ativa
- **Cinza com ⏸️**: Auto-atualização pausada
- **Pisca**: Quando novos dados são encontrados
- **Clicável**: Pausa/retoma a atualização

### **Botões de Controle**
- **Inserir**: Adicionar novo registro
- **Atualizar**: Forçar atualização imediata
- **Auto-atualização**: Menu dropdown com opções de configuração

### **Configurações de Frequência**
- **5 segundos**: Atualização muito rápida (alta carga)
- **10 segundos**: Atualização rápida
- **15 segundos**: Padrão recomendado
- **30 segundos**: Atualização mais espaçada

## 🔧 Arquivos do Sistema

```
📁 assinaturas_digitais/
├── 📄 check_new_data.php                    # Verifica novos dados
├── 📄 assinaturas_digitais_read.html        # Interface principal (com controles)
├── 📄 js/assinaturas_digitais_read_script.js # JavaScript com polling
└── 📄 README_AUTO_UPDATE.md                 # Esta documentação
```

## 🚀 Funcionalidades

### ✅ **Automáticas**
- ✅ Verificação periódica de novos dados
- ✅ Atualização automática do DataTable
- ✅ Notificações de novos registros
- ✅ Indicador visual de status
- ✅ Limpeza automática de recursos

### ✅ **Manuais**
- ✅ Pausa/retomada da atualização
- ✅ Configuração de frequência
- ✅ Atualização forçada imediata
- ✅ Controle visual de status

## 📊 Notificações

O sistema suporta múltiplos tipos de notificação:

1. **SweetAlert2** (preferencial): Toasts elegantes no canto superior direito
2. **Toastr** (fallback): Notificações toast simples
3. **Console** (fallback): Logs no console do browser

## 🔒 Segurança

- ✅ Verificação de permissões no PHP
- ✅ Sanitização de dados de entrada
- ✅ Tratamento de erros robusto
- ✅ Validação de timestamps
- ✅ Prevenção de SQL injection

## 🎯 Benefícios

1. **Experiência do Usuário**: Dados sempre atualizados sem ação manual
2. **Produtividade**: Não precisa ficar atualizando a página manualmente
3. **Tempo Real**: Vê as assinaturas digitais assim que chegam do webhook
4. **Flexibilidade**: Pode configurar frequência ou pausar quando necessário
5. **Performance**: Sistema otimizado para não sobrecarregar o servidor

## 🛠️ Configuração Técnica

### **Webhook ZapSign**
O sistema funciona em conjunto com `webhook_zapsign.php` que processa dados do ZapSign e insere na tabela `sind.associados_sasmais`.

### **Requisitos**
- PHP 7.0+
- PostgreSQL
- jQuery/JavaScript
- DataTables
- SweetAlert2 (opcional, mas recomendado)

### **Personalização**
Para alterar a frequência padrão, edite no JavaScript:
```javascript
var autoUpdateFrequency = 15000; // 15 segundos
```

## 🔍 Troubleshooting

### **Problema**: Auto-atualização não funciona
**Solução**: Verificar se divisão = "1" (QRCRED) e se há erros no console

### **Problema**: Notificações não aparecem
**Solução**: Verificar se SweetAlert2 está carregado, caso contrário usar console

### **Problema**: Muitas requisições ao servidor
**Solução**: Aumentar frequência de atualização para 30+ segundos

### **Problema**: Dados não atualizam
**Solução**: Verificar conexão com banco no arquivo `check_new_data.php`

---

**📞 Suporte**: Em caso de problemas, verificar logs do navegador (F12 → Console) para mensagens de erro detalhadas. 