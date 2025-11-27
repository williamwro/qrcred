/**
 * SISTEMA DE NOTIFICAÇÃO EM TEMPO REAL PARA APP SASCRED
 * JavaScript cliente para escutar assinaturas digitais via Server-Sent Events
 * 
 * Este script monitora quando um usuário específico assina digitalmente
 * e automaticamente habilita o menu completo do Sascred sem precisar relogar
 */

class SascredNotificationApp {
    constructor(options = {}) {
        this.options = {
            baseUrl: window.location.origin,
            sseEndpoint: 'sse_notificacao_app.php',
            reconnectInterval: 5000,
            maxReconnectAttempts: 10,
            debug: true,
            ...options
        };
        
        this.eventSource = null;
        this.reconnectAttempts = 0;
        this.isConnected = false;
        this.codigoUsuario = null;
        this.isListening = false;
        this.menuCompleto = false;
        
        // Callbacks para eventos
        this.onMenuHabilitado = null;
        this.onStatusUpdate = null;
        this.onError = null;
        
        this.log('Sistema de notificação Sascred inicializado');
    }
    
    /**
     * Iniciar monitoramento para um usuário específico
     * @param {string} codigoUsuario - Código do usuário para monitorar
     * @param {Object} callbacks - Callbacks para eventos
     */
    iniciarMonitoramento(codigoUsuario, callbacks = {}) {
        if (!codigoUsuario || codigoUsuario.trim() === '') {
            this.log('Erro: Código do usuário é obrigatório', 'error');
            return false;
        }
        
        this.codigoUsuario = codigoUsuario.trim();
        
        // Configurar callbacks
        this.onMenuHabilitado = callbacks.onMenuHabilitado || this.defaultOnMenuHabilitado;
        this.onStatusUpdate = callbacks.onStatusUpdate || this.defaultOnStatusUpdate;
        this.onError = callbacks.onError || this.defaultOnError;
        
        this.log(`Iniciando monitoramento para usuário: ${this.codigoUsuario}`);
        
        // Verificar status inicial antes de conectar SSE
        this.verificarStatusInicial()
            .then(resultado => {
                if (resultado.jaAderiu) {
                    this.log('Usuário já aderiu - habilitando menu completo');
                    this.habilitarMenuCompleto(resultado.dados);
                } else {
                    this.log('Usuário ainda não aderiu - iniciando monitoramento SSE');
                    this.conectarSSE();
                }
            })
            .catch(error => {
                this.log('Erro ao verificar status inicial: ' + error.message, 'error');
                // Continuar com SSE mesmo com erro na verificação inicial
                this.conectarSSE();
            });
        
        return true;
    }
    
    /**
     * Verificar status atual do usuário via API
     */
    async verificarStatusInicial() {
        try {
            const response = await fetch('api_verificar_adesao_sasmais.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    codigo: this.codigoUsuario
                })
            });
            
            const result = await response.json();
            
            if (result.status === 'sucesso') {
                return {
                    jaAderiu: result.jaAderiu,
                    dados: result.dados
                };
            } else {
                throw new Error(result.mensagem || 'Erro ao verificar status');
            }
        } catch (error) {
            this.log('Erro na verificação inicial: ' + error.message, 'error');
            throw error;
        }
    }
    
    /**
     * Conectar ao endpoint SSE
     */
    conectarSSE() {
        try {
            this.log('Conectando ao servidor de notificações...');
            
            // Fechar conexão anterior se existir
            if (this.eventSource) {
                this.eventSource.close();
            }
            
            // Construir URL do SSE
            const sseUrl = `${this.options.baseUrl}/${this.options.sseEndpoint}?codigo=${encodeURIComponent(this.codigoUsuario)}`;
            
            // Criar nova conexão SSE
            this.eventSource = new EventSource(sseUrl);
            
            // Event listeners principais
            this.eventSource.onopen = (event) => {
                this.log('Conectado ao servidor de notificações');
                this.isConnected = true;
                this.isListening = true;
                this.reconnectAttempts = 0;
                
                if (this.onStatusUpdate) {
                    this.onStatusUpdate('conectado', 'Monitoramento ativo');
                }
            };
            
            this.eventSource.onerror = (event) => {
                this.log('Erro na conexão SSE', 'error');
                this.isConnected = false;
                this.isListening = false;
                
                if (this.onStatusUpdate) {
                    this.onStatusUpdate('erro', 'Erro de conexão');
                }
                
                this.handleReconnect();
            };
            
            // Eventos específicos do sistema
            this.eventSource.addEventListener('connected', (event) => {
                const data = JSON.parse(event.data);
                this.log('SSE conectado para usuário:', data);
            });
            
            this.eventSource.addEventListener('status_inicial', (event) => {
                const data = JSON.parse(event.data);
                this.log('Status inicial recebido:', data);
                
                if (data.jaAderiu) {
                    this.habilitarMenuCompleto(data.dados);
                }
            });
            
            this.eventSource.addEventListener('nova_assinatura', (event) => {
                const data = JSON.parse(event.data);
                this.log('🎉 NOVA ASSINATURA DETECTADA!', data);
                this.habilitarMenuCompleto(data.dados);
            });
            
            this.eventSource.addEventListener('assinatura_confirmada', (event) => {
                const data = JSON.parse(event.data);
                this.log('✅ ASSINATURA CONFIRMADA!', data);
                this.habilitarMenuCompleto(data.dados);
            });
            
            this.eventSource.addEventListener('usuario_autorizado', (event) => {
                const data = JSON.parse(event.data);
                this.log('🔓 USUÁRIO AUTORIZADO!', data);
                this.habilitarMenuCompleto(data.dados);
            });
            
            this.eventSource.addEventListener('heartbeat', (event) => {
                const data = JSON.parse(event.data);
                // Log apenas a cada 5 heartbeats para não poluir
                if (data.notifications_received % 5 === 0) {
                    this.log(`Heartbeat: ${data.notifications_received} notificações recebidas`);
                }
            });
            
            this.eventSource.addEventListener('error', (event) => {
                const data = JSON.parse(event.data);
                this.log('Erro do servidor:', data, 'error');
                
                if (this.onError) {
                    this.onError('servidor', data.message);
                }
            });
            
        } catch (error) {
            this.log('Erro ao conectar SSE: ' + error.message, 'error');
            this.handleReconnect();
        }
    }
    
    /**
     * Habilitar menu completo do Sascred
     */
    habilitarMenuCompleto(dadosUsuario = null) {
        if (this.menuCompleto) {
            this.log('Menu completo já estava habilitado');
            return;
        }
        
        this.menuCompleto = true;
        this.log('🎯 HABILITANDO MENU COMPLETO DO SASCRED!');
        
        // Parar monitoramento pois objetivo foi alcançado
        this.pararMonitoramento();
        
        // Chamar callback personalizado
        if (this.onMenuHabilitado) {
            this.onMenuHabilitado(dadosUsuario);
        }
        
        // Mostrar notificação visual
        this.mostrarNotificacaoSucesso(dadosUsuario);
    }
    
    /**
     * Mostrar notificação de sucesso
     */
    mostrarNotificacaoSucesso(dadosUsuario) {
        const mensagem = dadosUsuario ? 
            `Bem-vindo ao Sascred, ${dadosUsuario.nome || 'usuário'}!` : 
            'Menu completo do Sascred habilitado!';
        
        // Tentar usar SweetAlert2 se disponível
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '🎉 Sucesso!',
                text: mensagem,
                icon: 'success',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } 
        // Fallback para alert básico
        else {
            alert('✅ ' + mensagem);
        }
        
        this.log('Notificação de sucesso exibida');
    }
    
    /**
     * Parar monitoramento
     */
    pararMonitoramento() {
        this.log('Parando monitoramento...');
        
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        
        this.isConnected = false;
        this.isListening = false;
        
        if (this.onStatusUpdate) {
            this.onStatusUpdate('parado', 'Monitoramento encerrado');
        }
    }
    
    /**
     * Tentar reconectar em caso de erro
     */
    handleReconnect() {
        if (this.reconnectAttempts >= this.options.maxReconnectAttempts) {
            this.log('Máximo de tentativas de reconexão atingido', 'error');
            
            if (this.onError) {
                this.onError('max_reconnect', 'Não foi possível restabelecer conexão');
            }
            return;
        }
        
        this.reconnectAttempts++;
        this.log(`Tentativa de reconexão ${this.reconnectAttempts}/${this.options.maxReconnectAttempts}`);
        
        setTimeout(() => {
            if (!this.isConnected && this.codigoUsuario) {
                this.conectarSSE();
            }
        }, this.options.reconnectInterval);
    }
    
    /**
     * Verificar se está monitorando
     */
    estaMonitorando() {
        return this.isListening && this.isConnected;
    }
    
    /**
     * Verificar se menu já foi habilitado
     */
    menuJaHabilitado() {
        return this.menuCompleto;
    }
    
    // Callbacks padrão
    defaultOnMenuHabilitado(dadosUsuario) {
        this.log('🎯 CALLBACK PADRÃO: Menu completo habilitado!', dadosUsuario);
        
        // Aqui você pode implementar a lógica específica do seu app
        // Por exemplo: mostrar elementos de menu, redirecionar, etc.
        
        // Exemplo de como você pode implementar no seu app:
        // window.location.reload(); // Recarregar página para mostrar novo menu
        // ou
        // this.atualizarInterfaceApp();
    }
    
    defaultOnStatusUpdate(status, mensagem) {
        this.log(`Status: ${status} - ${mensagem}`);
    }
    
    defaultOnError(tipo, mensagem) {
        this.log(`Erro ${tipo}: ${mensagem}`, 'error');
    }
    
    /**
     * Log com timestamp
     */
    log(message, level = 'info') {
        if (!this.options.debug) return;
        
        const timestamp = new Date().toLocaleTimeString();
        const prefix = `[${timestamp}] SASCRED:`;
        
        switch (level) {
            case 'error':
                console.error(prefix, message);
                break;
            case 'warn':
                console.warn(prefix, message);
                break;
            default:
                console.log(prefix, message);
        }
    }
}

// Expor globalmente para uso
window.SascredNotificationApp = SascredNotificationApp;

// Exemplo de uso:
/*
// Criar instância
const sascredNotification = new SascredNotificationApp({
    debug: true
});

// Iniciar monitoramento para um usuário
sascredNotification.iniciarMonitoramento('12345', {
    onMenuHabilitado: function(dadosUsuario) {
        console.log('🎉 Menu habilitado para:', dadosUsuario);
        
        // Implementar sua lógica aqui:
        // - Mostrar elementos de menu
        // - Redirecionar para página principal
        // - Atualizar interface do app
        // - etc.
        
        // Exemplo simples:
        window.location.reload();
    },
    
    onStatusUpdate: function(status, mensagem) {
        console.log('Status:', status, mensagem);
        // Atualizar UI com status da conexão
    },
    
    onError: function(tipo, mensagem) {
        console.error('Erro:', tipo, mensagem);
        // Tratar erros conforme necessário
    }
});
*/ 