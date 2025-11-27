/**
 * Gerenciador de Usuário Logado em Tempo Real
 * Para ser incluído em qualquer página do sistema
 */

// Verificar se UserManager já foi definido
if (typeof UserManager === 'undefined') {

class UserManager {
    constructor() {
        this.checkInterval = 30000; // 30 segundos
        this.heartbeatInterval = 30000; // 30 segundos - reduzido para manter usuários online
        this.user = null;
        this.callbacks = [];
        this.init();
    }

    /**
     * Inicializar o gerenciador
     */
    init() {
        this.loadUserFromSession();
        
        // Aguardar 3 segundos antes do primeiro heartbeat para não interferir com o login
        if (this.isLoggedIn()) {
            console.log('UserManager inicializado, aguardando antes do primeiro heartbeat...');
            setTimeout(() => {
                console.log('Enviando primeiro heartbeat...');
                this.sendHeartbeatSimple();
            }, 3000);
        }
        
        this.startHeartbeat(); // Reativado apenas para atualizar ultimo_acesso
        this.bindEvents();
        
        // Verificação de login desabilitada para não expirar sessão
        // if (!this.isLoggedIn()) {
        //     this.redirectToLogin();
        // }
    }

    /**
     * Gerar session ID único
     */
    generateSessionId() {
        return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Carregar dados do usuário do sessionStorage
     */
    loadUserFromSession() {
        // Usar session_id do PHP se disponível, senão gerar um novo
        const existingSessionId = sessionStorage.getItem('session_id');
        const sessionId = existingSessionId || this.generateSessionId();
        
        this.user = {
            codigo: sessionStorage.getItem('usuario_cod'),
            username: sessionStorage.getItem('usuario_global'),
            divisao: sessionStorage.getItem('divisao'),
            divisao_nome: sessionStorage.getItem('divisao_nome'),
            cod_empregador: sessionStorage.getItem('cod_empregador'),
            login_time: sessionStorage.getItem('login_time') || Date.now(),
            last_activity: Date.now(),
            session_id: sessionId
        };
        
        // Salvar session_id no sessionStorage se não existia
        if (!existingSessionId) {
            sessionStorage.setItem('session_id', sessionId);
        }
    }

    /**
     * Verificar se usuário está logado
     */
    isLoggedIn() {
        return this.user && this.user.codigo && this.user.username;
    }

    /**
     * Obter dados do usuário atual
     */
    getCurrentUser() {
        return this.user;
    }

    /**
     * Atualizar atividade do usuário
     */
    updateActivity() {
        if (this.user) {
            this.user.last_activity = Date.now();
            sessionStorage.setItem('last_activity', this.user.last_activity);
        }
    }

    /**
     * Iniciar heartbeat para manter sessão ativa
     */
    startHeartbeat() {
        // Heartbeat apenas para atualizar ultimo_acesso, sem verificação de expiração
        setInterval(() => {
            if (this.isLoggedIn()) {
                this.sendHeartbeatSimple();
            }
        }, this.heartbeatInterval);

        // Detectar atividade do usuário para atualizar ultimo_acesso
        ['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
            document.addEventListener(event, () => {
                this.updateActivity();
            }, { passive: true });
        });
    }

    /**
     * Enviar heartbeat para o servidor
     */
    sendHeartbeat() {
        // Heartbeat original desabilitado para não expirar sessão automaticamente
    }

    /**
     * Heartbeat simples apenas para atualizar ultimo_acesso
     */
    sendHeartbeatSimple() {
        if (!this.isLoggedIn()) return;

        console.log('Enviando heartbeat para usuário:', this.user.username, '(código:', this.user.codigo, ')');

        $.ajax({
            url: 'pages/usuarios/user_heartbeat.php',
            method: 'POST',
            data: {
                action: 'update_activity',
                usuario_cod: this.user.codigo,
                session_id: this.user.session_id,
                last_activity: this.user.last_activity
            },
            dataType: 'json',
            timeout: 5000,
            success: (response) => {
                console.log('Heartbeat enviado com sucesso para:', this.user.username, 'Session:', this.user.session_id);
            },
            error: () => {
                console.warn('Erro ao atualizar atividade do usuário:', this.user.username);
            }
        });
    }

    /**
     * Verificar se sessão ainda é válida
     */
    verifySession() {
        // Verificação de sessão desabilitada para não expirar automaticamente
        // $.ajax({
        //     url: '../Adm/php/user_heartbeat.php',
        //     method: 'POST',
        //     data: {
        //         action: 'verify',
        //         usuario_cod: this.user.codigo
        //     },
        //     dataType: 'json',
        //     success: (response) => {
        //         if (!response.session_valid) {
        //             this.handleSessionExpired();
        //         }
        //     },
        //     error: () => {
        //         this.handleSessionExpired();
        //     }
        // });
    }

    /**
     * Lidar com sessão expirada
     */
    handleSessionExpired() {
        this.clearSession();
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Sessão Expirada',
                text: 'Sua sessão expirou. Você será redirecionado para o login.',
                icon: 'warning',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => {
                this.redirectToLogin();
            });
        } else {
            alert('Sessão expirada. Redirecionando para login...');
            this.redirectToLogin();
        }
    }

    /**
     * Limpar dados da sessão
     */
    clearSession() {
        ['usuario_cod', 'usuario_global', 'divisao', 'divisao_nome', 'cod_empregador', 'login_time', 'last_activity'].forEach(key => {
            sessionStorage.removeItem(key);
        });
        this.user = null;
    }

    /**
     * Redirecionar para login
     */
    redirectToLogin() {
        window.location.href = '../../../index.html';
    }

    /**
     * Registrar callback para mudanças no usuário
     */
    onUserChange(callback) {
        this.callbacks.push(callback);
    }

    /**
     * Notificar callbacks sobre mudanças
     */
    notifyCallbacks() {
        this.callbacks.forEach(callback => callback(this.user));
    }

    /**
     * Logout manual
     */
    logout() {
        if (!this.isLoggedIn()) {
            this.redirectToLogin();
            return;
        }

        // Finalizar TODAS as sessões do usuário
        $.ajax({
            url: 'pages/usuarios/user_heartbeat.php',
            method: 'POST',
            data: {
                action: 'close_all_user_sessions',
                usuario_cod: this.user.codigo,
                session_id: this.user.session_id
            },
            dataType: 'json',
            async: false, // Importante: síncrono para garantir execução
            complete: () => {
                this.clearSession();
                this.redirectToLogin();
            }
        });
    }

    /**
     * Obter tempo de sessão
     */
    getSessionTime() {
        if (!this.user || !this.user.login_time) return 0;
        return Date.now() - this.user.login_time;
    }

    /**
     * Obter tempo de inatividade
     */
    getInactiveTime() {
        if (!this.user || !this.user.last_activity) return 0;
        return Date.now() - this.user.last_activity;
    }

    /**
     * Bind events globais
     */
    bindEvents() {
        // Detectar quando usuário fecha a aba/janela - estratégias múltiplas para Chrome
        const closeSession = () => {
            if (this.isLoggedIn()) {
                console.log('Tentando finalizar sessão ao fechar navegador...');
                
                // Estratégia 1: sendBeacon (mais confiável para Chrome)
                try {
                    const success = navigator.sendBeacon('pages/usuarios/user_heartbeat.php', 
                        new URLSearchParams({
                            action: 'close_all_user_sessions',
                            usuario_cod: this.user.codigo,
                            session_id: this.user.session_id
                        })
                    );
                    console.log('SendBeacon result:', success);
                } catch (e) {
                    console.log('SendBeacon failed:', e);
                }
                
                // Estratégia 2: fetch keepalive (moderno, funciona bem no Chrome)
                try {
                    fetch('pages/usuarios/user_heartbeat.php', {
                        method: 'POST',
                        keepalive: true,
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=close_all_user_sessions&usuario_cod=${this.user.codigo}&session_id=${this.user.session_id}`
                    });
                } catch (e) {
                    console.log('Fetch keepalive failed:', e);
                }
                
                // Estratégia 3: XMLHttpRequest síncrono (backup)
                try {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'pages/usuarios/user_heartbeat.php', false);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.send(`action=close_all_user_sessions&usuario_cod=${this.user.codigo}&session_id=${this.user.session_id}`);
                } catch (e) {
                    console.log('XHR sync failed:', e);
                }
            }
        };

        // Múltiplos eventos para capturar fechamento
        window.addEventListener('beforeunload', closeSession);
        window.addEventListener('unload', closeSession);
        window.addEventListener('pagehide', closeSession);
        
        // Específico para Chrome - detectar perda de foco
        let isPageVisible = true;
        let pageHiddenTime = 0;
        
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                isPageVisible = false;
                pageHiddenTime = Date.now();
                
                // Tentativa imediata quando página fica oculta (pode ser fechamento)
                setTimeout(() => {
                    if (!isPageVisible) {
                        console.log('Página oculta por tempo suficiente, tentando finalizar sessão...');
                        closeSession();
                    }
                }, 1000); // 1 segundo
                
            } else if (document.visibilityState === 'visible') {
                isPageVisible = true;
                
                if (pageHiddenTime > 0) {
                    // Se a página ficou oculta por mais de 2 minutos, enviar heartbeat
                    if (Date.now() - pageHiddenTime > 120000) {
                        console.log('Página retornou após longo período, enviando heartbeat...');
                        this.sendHeartbeatSimple();
                    }
                    pageHiddenTime = 0;
                }
            }
        });
        
        // Detectar inatividade extrema (mais de 5 minutos sem heartbeat)
        let lastHeartbeatTime = Date.now();
        setInterval(() => {
            if (this.isLoggedIn() && (Date.now() - lastHeartbeatTime > 300000)) {
                console.log('Inatividade detectada, finalizando sessão...');
                closeSession();
            }
        }, 30000);
        
        // Atualizar tempo do último heartbeat
        const originalSendHeartbeat = this.sendHeartbeatSimple.bind(this);
        this.sendHeartbeatSimple = function() {
            lastHeartbeatTime = Date.now();
            return originalSendHeartbeat();
        };

        // Detecção de mudanças no sessionStorage desabilitada para evitar logout automático
        // window.addEventListener('storage', (e) => {
        //     if (['usuario_cod', 'usuario_global'].includes(e.key)) {
        //         if (!e.newValue) {
        //             // Sessão foi limpa em outra aba
        //             this.handleSessionExpired();
        //         } else {
        //             // Dados foram atualizados, recarregar
        //             this.loadUserFromSession();
        //             this.notifyCallbacks();
        //         }
        //     }
        // });
    }

    /**
     * Mostrar informações do usuário na tela
     */
    displayUserInfo(containerId = 'user-info') {
        const container = document.getElementById(containerId);
        if (!container || !this.isLoggedIn()) return;

        const sessionTime = Math.floor(this.getSessionTime() / 60000); // em minutos
        
        container.innerHTML = `
            <div class="user-info-panel">
                <div class="user-details">
                    <span class="user-name">${this.user.username}</span>
                    <span class="user-division">${this.user.divisao_nome || ''}</span>
                </div>
                <div class="session-info">
                    <span class="session-time">Logado há ${sessionTime}min</span>
                    <button onclick="userManager.logout()" class="btn-logout">Sair</button>
                </div>
            </div>
        `;
    }

    /**
     * Obter lista de usuários online
     */
    getOnlineUsers() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: 'pages/usuarios/user_heartbeat.php',
                method: 'POST',
                data: {
                    action: 'get_online_users'
                },
                dataType: 'json',
                timeout: 10000,
                success: (response) => {
                    if (response && response.success) {
                        resolve(response.users || []);
                    } else {
                        resolve([]);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Erro ao buscar usuários online:', status, error);
                    resolve([]);
                }
            });
        });
    }

    /**
     * Verificar se um usuário específico está online
     */
    isUserOnline(userCode) {
        return this.getOnlineUsers().then(onlineUsers => {
            return onlineUsers.some(user => user.codigo == userCode);
        });
    }

    /**
     * Atualizar indicadores de status online no DataTable
     */
    updateOnlineStatus() {
        if (typeof table_usuario === 'undefined' || !table_usuario) {
            return;
        }
        
        // Verificar se a tabela tem dados carregados
        try {
            var rowCount = table_usuario.rows().count();
            if (rowCount === 0) {
                return;
            }
        } catch (e) {
            console.log('Erro ao verificar dados da tabela:', e);
            return;
        }
        
        this.getOnlineUsers().then(onlineUsers => {
            console.log('=== DEBUG FRONTEND ===');
            console.log('Resposta do servidor - Usuários online:', onlineUsers);
            console.log('Total de usuários online:', onlineUsers.length);
            onlineUsers.forEach(user => {
                console.log('- Usuário online:', user.codigo, user.username, user.ultimo_acesso);
            });
            
            if (!onlineUsers || onlineUsers.length === 0) {
                console.log('Nenhum usuário online do servidor, usando sessão atual');
                // Se não há dados do servidor, mostrar apenas usuário atual como online
                const currentUserCode = parseInt(sessionStorage.getItem('usuario_cod'));
                
                $('#tabela_usuario tbody tr').each(function() {
                    try {
                        const rowData = table_usuario.row(this).data();
                        if (!rowData || !rowData.codigo) return;
                        
                        const userCode = parseInt(rowData.codigo);
                        const isCurrentUser = userCode === currentUserCode;
                        
                    const statusHTML = isCurrentUser ? 
                        '<span class="status-online" title="Online (você)"><i class="fa fa-circle text-success" style="animation: blink 2s infinite;"></i> Online</span>' : 
                        '<span class="status-offline" title="Offline"><i class="fa fa-circle text-muted"></i> Offline</span>';
                        
                        $(this).find('td:first').html(statusHTML);
                    } catch (e) {
                        console.log('Erro ao atualizar status fallback:', e);
                    }
                });
                return;
            }
            
            const onlineUserCodes = onlineUsers.map(user => user.codigo);
            console.log('Atualizando status:', onlineUsers.length, 'usuários online');
            console.log('Códigos dos usuários online:', onlineUserCodes);
            console.log('Lista de usuários online completa:', onlineUsers);
            
            // Atualizar diretamente no DOM das células visíveis
            var linhasAtualizadas = 0;
            
            // Usar seletor jQuery para atualizar células diretamente
            $('#tabela_usuario tbody tr').each(function(index) {
                try {
                    const rowData = table_usuario.row(this).data();
                    
                    if (!rowData || !rowData.codigo) {
                        return; // Pular linhas sem dados
                    }
                    
                    const userCode = parseInt(rowData.codigo);
                    const isOnline = onlineUserCodes.includes(userCode);
                    
                    console.log(`Atualizando usuário ${userCode} (${rowData.username || 'N/A'}): ${isOnline ? 'ONLINE' : 'OFFLINE'}`);
                    
                    const statusHTML = isOnline ? 
                        '<span class="status-online" title="Online"><i class="fa fa-circle text-success" style="animation: blink 2s infinite;"></i> Online</span>' : 
                        '<span class="status-offline" title="Offline"><i class="fa fa-circle text-muted"></i> Offline</span>';
                    
                    // Atualizar diretamente no DOM
                    $(this).find('td:first').html(statusHTML);
                    linhasAtualizadas++;
                    
                } catch (e) {
                    console.error('Erro ao atualizar linha DOM:', e);
                }
            });
            
            console.log('Total de linhas atualizadas no DOM:', linhasAtualizadas);
        }).catch(error => {
            console.error('Erro na atualização de status:', error);
        });
    }
}

// Instância global
window.userManager = new UserManager();

// Função de conveniência para verificar usuário logado
window.getCurrentUser = function() {
    return window.userManager.getCurrentUser();
};

// Função de conveniência para verificar se está logado
window.isUserLoggedIn = function() {
    return window.userManager.isLoggedIn();
};

} // Fechamento do if para verificação de UserManager
