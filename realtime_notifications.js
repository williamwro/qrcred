/**
 * SISTEMA DE NOTIFICAÇÕES EM TEMPO REAL
 * JavaScript Client para Server-Sent Events
 * 
 * Este script conecta via SSE ao PHP que escuta PostgreSQL LISTEN/NOTIFY
 * e atualiza a interface em tempo real quando novos dados chegam.
 */

// Verificar se já foi definido para evitar redeclaração
if (typeof window.RealtimeNotifications === 'undefined') {

class RealtimeNotifications {
    constructor(options = {}) {
        this.options = {
            url: 'realtime_notifications.php',
            reconnectInterval: 5000,
            maxReconnectAttempts: 10,
            enableSounds: true,
            enableDesktopNotifications: false,
            debug: true,
            ...options
        };
        
        this.eventSource = null;
        this.reconnectAttempts = 0;
        this.isConnected = false;
        this.notificationCount = 0;
        this.startTime = Date.now();
        
        this.init();
    }
    
    init() {
        this.log('Inicializando sistema de notificações em tempo real...');
        
        // Solicitar permissão para notificações desktop
        if (this.options.enableDesktopNotifications && 'Notification' in window) {
            Notification.requestPermission();
        }
        
        // Criar elementos de status na interface
        this.createStatusUI();
        
        // Iniciar conexão SSE
        this.connect();
        
        // Expor para debug global
        window.realtimeNotifications = this;
    }
    
    connect() {
        try {
            this.log('Conectando ao servidor de notificações...');
            
            // Fechar conexão anterior se existir
            if (this.eventSource) {
                this.eventSource.close();
            }
            
            // Criar nova conexão SSE
            this.eventSource = new EventSource(this.options.url);
            
            // Event listeners
            this.eventSource.onopen = (event) => {
                this.log('Conectado ao servidor de notificações');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                this.updateStatusUI('connected');
            };
            
            this.eventSource.onerror = (event) => {
                this.log('Erro na conexão SSE', event);
                this.isConnected = false;
                this.updateStatusUI('error');
                this.handleReconnect();
            };
            
            // Eventos específicos
            this.eventSource.addEventListener('connected', (event) => {
                const data = JSON.parse(event.data);
                this.log('SSE conectado:', data);
                this.updateStatusUI('listening');
            });
            
            this.eventSource.addEventListener('heartbeat', (event) => {
                const data = JSON.parse(event.data);
                this.log('Heartbeat recebido:', data);
                this.updateStatusUI('active', `${data.notifications_received} notificações`);
            });
            
            this.eventSource.addEventListener('new_signature', (event) => {
                const data = JSON.parse(event.data);
                this.log('Nova assinatura recebida:', data);
                this.handleNewSignature(data);
            });
            
            this.eventSource.addEventListener('signature_updated', (event) => {
                const data = JSON.parse(event.data);
                this.log('Assinatura atualizada:', data);
                this.handleSignatureUpdate(data);
            });
            
            this.eventSource.addEventListener('error', (event) => {
                const data = JSON.parse(event.data);
                this.log('Erro do servidor:', data);
                this.showError('Erro do servidor: ' + data.message);
            });
            
        } catch (error) {
            this.log('Erro ao conectar SSE:', error);
            this.handleReconnect();
        }
    }
    
    handleNewSignature(data) {
        this.notificationCount++;
        const signature = data.data;
        
        this.log(`Nova assinatura: ${signature.nome} (${signature.cpf})`);
        
        // Tocar som de notificação PRIMEIRO
        if (this.options.enableSounds) {
            this.playNotificationSound();
        }
        
        // Mostrar notificação visual
        this.showNotification({
            title: '🆕 Nova Assinatura Digital',
            message: `${signature.nome} assinou o documento`,
            type: 'success',
            data: signature
        });
        
        // Notificação desktop
        if (this.options.enableDesktopNotifications) {
            this.showDesktopNotification(
                '🆕 Nova Assinatura Digital',
                `${signature.nome} assinou o documento`,
                signature
            );
        }
        
        // Atualizar tabela automaticamente
        this.refreshDataTable();
        
        // Disparar evento customizado para outras partes do sistema
        this.dispatchCustomEvent('realtimeNewSignature', signature);
    }
    
    handleSignatureUpdate(data) {
        const signature = data.data;
        const changes = signature.changes;
        
        this.log(`Assinatura atualizada: ${signature.nome}`, changes);
        
        // Tocar som para atualizações também
        if (this.options.enableSounds) {
            this.playNotificationSound();
        }
        
        // Determinar tipo de mudança
        let changeMessage = 'Status atualizado';
        if (changes.has_signed && changes.has_signed.new) {
            changeMessage = 'Documento assinado';
        } else if (changes.autorizado && changes.autorizado.new) {
            changeMessage = 'Autorização concedida';
        } else if (changes.aceitou_termo && changes.aceitou_termo.new) {
            changeMessage = 'Termo aceito';
        }
        
        this.showNotification({
            title: '📝 Assinatura Atualizada',
            message: `${signature.nome}: ${changeMessage}`,
            type: 'info',
            data: signature
        });
        
        // Atualizar tabela
        this.refreshDataTable();
        
        // Evento customizado
        this.dispatchCustomEvent('realtimeSignatureUpdate', signature);
    }
    
    handleReconnect() {
        if (this.reconnectAttempts < this.options.maxReconnectAttempts) {
            this.reconnectAttempts++;
            this.log(`Tentativa de reconexão ${this.reconnectAttempts}/${this.options.maxReconnectAttempts} em ${this.options.reconnectInterval/1000}s`);
            
            this.updateStatusUI('reconnecting', `Tentativa ${this.reconnectAttempts}`);
            
            setTimeout(() => {
                this.connect();
            }, this.options.reconnectInterval);
        } else {
            this.log('Máximo de tentativas de reconexão atingido');
            this.updateStatusUI('disconnected', 'Conexão perdida');
            this.showError('Não foi possível reconectar ao servidor de notificações');
        }
    }
    
    createStatusUI() {
        // Criar indicador de status se não existir
        if (!document.getElementById('realtime-status-indicator')) {
            const indicator = document.createElement('div');
            indicator.id = 'realtime-status-indicator';
            indicator.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #6c757d;
                color: white;
                padding: 8px 15px;
                border-radius: 20px;
                font-size: 12px;
                z-index: 9999;
                box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                transition: all 0.3s ease;
                cursor: pointer;
                user-select: none;
            `;
            indicator.innerHTML = '🔌 Conectando...';
            indicator.title = 'Status das notificações em tempo real';
            
            // Click para mostrar/ocultar
            indicator.addEventListener('click', () => {
                this.toggleStatusDetails();
            });
            
            document.body.appendChild(indicator);
        }
    }
    
    updateStatusUI(status, details = '') {
        const indicator = document.getElementById('realtime-status-indicator');
        if (!indicator) return;
        
        const statusConfig = {
            connected: { bg: '#28a745', icon: '🔗', text: 'Conectado' },
            listening: { bg: '#17a2b8', icon: '👂', text: 'Escutando' },
            active: { bg: '#28a745', icon: '🔴', text: 'Ativo' },
            reconnecting: { bg: '#ffc107', icon: '🔄', text: 'Reconectando' },
            error: { bg: '#dc3545', icon: '⚠️', text: 'Erro' },
            disconnected: { bg: '#6c757d', icon: '❌', text: 'Desconectado' }
        };
        
        const config = statusConfig[status] || statusConfig.disconnected;
        
        indicator.style.background = config.bg;
        indicator.innerHTML = `${config.icon} ${config.text}${details ? ` (${details})` : ''}`;
        indicator.title = `Notificações em tempo real: ${config.text}${details ? ` - ${details}` : ''}`;
    }
    
    showNotification({ title, message, type = 'info', data = null, duration = 5000 }) {
        // Usar SweetAlert2 se disponível, senão usar notificação simples
        if (typeof Swal !== 'undefined') {
            const icon = type === 'success' ? 'success' : type === 'error' ? 'error' : 'info';
            
            Swal.fire({
                title: title,
                text: message,
                icon: icon,
                timer: duration,
                timerProgressBar: true,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        } else {
            // Fallback para alert nativo
            console.log(`${title}: ${message}`);
        }
    }
    
    showDesktopNotification(title, message, data) {
        if ('Notification' in window && Notification.permission === 'granted') {
            const notification = new Notification(title, {
                body: message,
                icon: '/css/img/logo.png', // Ajustar caminho conforme necessário
                badge: '/css/img/logo.png',
                tag: 'assinatura-digital',
                renotify: true
            });
            
            notification.onclick = () => {
                window.focus();
                notification.close();
            };
            
            // Fechar automaticamente após 5 segundos
            setTimeout(() => notification.close(), 5000);
        }
    }
    
    playNotificationSound() {
        this.log('🔊 Tentando tocar som de notificação...');
        
        try {
            // Tentar múltiplos métodos em paralelo para garantir que funcione
            this.tryWebAudioAPI();
            this.tryHTMLAudio();
            this.tryAlternativeMethods();
            
        } catch (error) {
            this.log('Erro geral no som:', error);
            this.tryAlternativeMethods();
        }
    }
    
    tryWebAudioAPI() {
        try {
            if (window.AudioContext || window.webkitAudioContext) {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                
                // Função para criar beep
                const createBeep = (frequency, startTime, duration) => {
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.setValueAtTime(frequency, audioContext.currentTime + startTime);
                    oscillator.type = 'sine';
                    
                    const now = audioContext.currentTime + startTime;
                    gainNode.gain.setValueAtTime(0, now);
                    gainNode.gain.linearRampToValueAtTime(0.3, now + 0.01);
                    gainNode.gain.exponentialRampToValueAtTime(0.001, now + duration);
                    
                    oscillator.start(now);
                    oscillator.stop(now + duration);
                };
                
                // Verificar se contexto está suspenso
                if (audioContext.state === 'suspended') {
                    audioContext.resume().then(() => {
                        createBeep(800, 0, 0.15);
                        setTimeout(() => createBeep(1000, 0, 0.15), 200);
                        this.log('✅ Som tocado via Web Audio API (após resume)');
                    });
                } else {
                    createBeep(800, 0, 0.15);
                    setTimeout(() => createBeep(1000, 0, 0.15), 200);
                    this.log('✅ Som tocado via Web Audio API');
                }
            }
        } catch (error) {
            this.log('❌ Erro Web Audio API:', error);
        }
    }
    
    tryHTMLAudio() {
        try {
            // Criar beep programaticamente
            const sampleRate = 22050;
            const frequency = 800;
            const duration = 0.3;
            const samples = Math.floor(sampleRate * duration);
            
            const buffer = new ArrayBuffer(44 + samples * 2);
            const view = new DataView(buffer);
            
            // Header WAV
            const writeString = (offset, string) => {
                for (let i = 0; i < string.length; i++) {
                    view.setUint8(offset + i, string.charCodeAt(i));
                }
            };
            
            writeString(0, 'RIFF');
            view.setUint32(4, 36 + samples * 2, true);
            writeString(8, 'WAVE');
            writeString(12, 'fmt ');
            view.setUint32(16, 16, true);
            view.setUint16(20, 1, true);
            view.setUint16(22, 1, true);
            view.setUint32(24, sampleRate, true);
            view.setUint32(28, sampleRate * 2, true);
            view.setUint16(32, 2, true);
            view.setUint16(34, 16, true);
            writeString(36, 'data');
            view.setUint32(40, samples * 2, true);
            
            // Gerar samples
            for (let i = 0; i < samples; i++) {
                const t = i / sampleRate;
                const envelope = Math.exp(-t * 3); // Envelope exponencial
                const sample = Math.sin(frequency * 2 * Math.PI * t) * envelope * 0.5;
                view.setInt16(44 + i * 2, sample * 0x7FFF, true);
            }
            
            const blob = new Blob([buffer], { type: 'audio/wav' });
            const url = URL.createObjectURL(blob);
            const audio = new Audio(url);
            
            audio.volume = 0.7;
            
            const playPromise = audio.play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    this.log('✅ Som tocado via HTML5 Audio');
                    setTimeout(() => URL.revokeObjectURL(url), 1000);
                }).catch((error) => {
                    this.log('❌ Erro HTML5 Audio:', error);
                    URL.revokeObjectURL(url);
                });
            }
            
        } catch (error) {
            this.log('❌ Erro HTML5 Audio:', error);
        }
    }
    
    tryAlternativeMethods() {
        // Método 1: SpeechSynthesis
        try {
            if ('speechSynthesis' in window) {
                speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance('notification');
                utterance.rate = 5;
                utterance.pitch = 2;
                utterance.volume = 0.3;
                speechSynthesis.speak(utterance);
                this.log('✅ Som via SpeechSynthesis');
            }
        } catch (error) {
            this.log('❌ Erro SpeechSynthesis:', error);
        }
        
        // Método 2: Vibração (mobile)
        try {
            if ('vibrate' in navigator) {
                navigator.vibrate([200, 100, 200]);
                this.log('✅ Vibração ativada');
            }
        } catch (error) {
            this.log('❌ Erro vibração:', error);
        }
        
        // Método 3: Feedback visual
        this.showVisualFeedback();
    }
    
    showVisualFeedback() {
        try {
            const body = document.body;
            const flash = document.createElement('div');
            
            flash.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 0, 0.3);
                z-index: 999999;
                pointer-events: none;
                animation: flashNotification 0.5s ease-out;
            `;
            
            // Adicionar CSS de animação se não existir
            if (!document.getElementById('flash-notification-style')) {
                const style = document.createElement('style');
                style.id = 'flash-notification-style';
                style.textContent = `
                    @keyframes flashNotification {
                        0% { opacity: 0; }
                        50% { opacity: 1; }
                        100% { opacity: 0; }
                    }
                `;
                document.head.appendChild(style);
            }
            
            body.appendChild(flash);
            setTimeout(() => body.removeChild(flash), 500);
            
            this.log('✅ Feedback visual aplicado');
            
        } catch (error) {
            this.log('❌ Erro feedback visual:', error);
        }
    }
    
    refreshDataTable() {
        // Atualizar tabela DataTables se existir
        if (typeof $.fn.dataTable !== 'undefined' && $('#tabela_assinaturas_digitais').length) {
            try {
                $('#tabela_assinaturas_digitais').DataTable().ajax.reload();
                this.log('Tabela atualizada automaticamente');
            } catch (error) {
                this.log('Erro ao atualizar tabela:', error);
            }
        }
    }
    
    dispatchCustomEvent(eventName, data) {
        // Disparar evento customizado para integração com outras partes do sistema
        const event = new CustomEvent(eventName, {
            detail: data,
            bubbles: true,
            cancelable: true
        });
        
        document.dispatchEvent(event);
        this.log(`Evento customizado disparado: ${eventName}`, data);
    }
    
    toggleStatusDetails() {
        // Mostrar/ocultar detalhes do status
        const details = `
            🔌 Status: ${this.isConnected ? 'Conectado' : 'Desconectado'}
            📊 Notificações: ${this.notificationCount}
            ⏱️ Uptime: ${Math.floor((Date.now() - this.startTime) / 1000)}s
            🔄 Tentativas reconexão: ${this.reconnectAttempts}
            ⚙️ Debug: ${this.options.debug ? 'Ativo' : 'Inativo'}
        `;
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Status das Notificações',
                html: details.replace(/\n/g, '<br>'),
                icon: 'info',
                confirmButtonText: 'OK'
            });
        } else {
            alert(details);
        }
    }
    
    showError(message) {
        this.log('ERRO:', message);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Erro',
                text: message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } else {
            console.error('Realtime Notifications Error:', message);
        }
    }
    
    log(message, data = null) {
        if (this.options.debug) {
            const timestamp = new Date().toLocaleTimeString();
            if (data) {
                console.log(`[${timestamp}] RealtimeNotifications:`, message, data);
            } else {
                console.log(`[${timestamp}] RealtimeNotifications:`, message);
            }
        }
    }
    
    // Métodos públicos para controle externo
    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        this.isConnected = false;
        this.updateStatusUI('disconnected');
        this.log('Desconectado manualmente');
    }
    
    reconnect() {
        this.log('Reconectando manualmente...');
        this.reconnectAttempts = 0;
        this.connect();
    }
    
    getStatus() {
        return {
            connected: this.isConnected,
            notifications: this.notificationCount,
            uptime: Date.now() - this.startTime,
            reconnectAttempts: this.reconnectAttempts,
            options: this.options
        };
    }
    
    updateOptions(newOptions) {
        this.options = { ...this.options, ...newOptions };
        this.log('Opções atualizadas:', this.options);
    }
    
    // Método de teste público
    testSound() {
        this.log('🧪 TESTE DE SOM iniciado...');
        this.playNotificationSound();
    }
}

    // Inicializar automaticamente apenas se ainda não foi inicializado
    document.addEventListener('DOMContentLoaded', function() {
        if (!window.realtimeNotifications) {
            window.realtimeNotifications = new RealtimeNotifications({
                debug: true,
                enableSounds: true,
                enableDesktopNotifications: true
            });
            
            // Função de teste global
            window.testNotificationSound = function() {
                if (window.realtimeNotifications) {
                    window.realtimeNotifications.testSound();
                } else {
                    console.log('Sistema de notificações não iniciado');
                }
            };
            
            console.log('🔊 Para testar o som, digite: testNotificationSound()');
        }
    });

    window.RealtimeNotifications = RealtimeNotifications;

} // Fim do bloco if para evitar redeclaração