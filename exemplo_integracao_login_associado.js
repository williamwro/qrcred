/**
 * EXEMPLO DE INTEGRAÇÃO - LOGIN DO ASSOCIADO
 * 
 * Este arquivo mostra como integrar o sistema de notificações Sascred
 * no código de login existente do associado.
 * 
 * INSTRUÇÕES:
 * 1. Incluir o arquivo sascred_notificacao_app.js na página
 * 2. Adicionar o código abaixo no seu JavaScript de login existente
 * 3. Adaptar as funções conforme necessário para seu app
 */

// ============================================================================
// VARIÁVEIS GLOBAIS DO SISTEMA SASCRED
// ============================================================================

let sascredNotificationSystem = null;
let isMonitoringSascred = false;

// ============================================================================
// INTEGRAÇÃO NO LOGIN EXISTENTE
// ============================================================================

// Substituir ou adicionar ao seu código de login existente
function loginAssociadoComSascred(cartao, senha) {
    // Seu código de login existente...
    $.ajax({
        url: "localiza_associado_app_2.php",
        method: "POST",
        data: { cartao: cartao, senha: senha },
        dataType: 'json',
        success: function(data) {
            if (data.situacao === 1) {
                console.log('✅ Login bem-sucedido:', data);
                
                // SUA LÓGICA DE LOGIN EXISTENTE AQUI
                // ...
                
                // ============================================
                // ADICIONAR: INICIAR MONITORAMENTO SASCRED
                // ============================================
                iniciarMonitoramentoSascredParaUsuario(data.matricula, {
                    nomeUsuario: data.nome,
                    emailUsuario: data.email,
                    celularUsuario: data.cel
                });
                
                // Continuar com sua lógica de login...
                redirecionarUsuarioLogado(data);
                
            } else if (data.situacao === 6) {
                // Senha errada - seu tratamento existente
                console.log('❌ Senha incorreta');
                mostrarErroSenha();
                
            } else {
                // Outros erros - seu tratamento existente
                console.log('❌ Erro no login:', data);
                mostrarErroLogin(data);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Erro na requisição de login:', error);
            mostrarErroConexao();
        }
    });
}

// ============================================================================
// FUNÇÃO PRINCIPAL: INICIAR MONITORAMENTO SASCRED
// ============================================================================

function iniciarMonitoramentoSascredParaUsuario(codigoUsuario, dadosUsuario = {}) {
    // Verificar se o sistema está disponível
    if (typeof SascredNotificationApp === 'undefined') {
        console.log('⚠️ Sistema de notificação Sascred não carregado');
        return false;
    }
    
    // Evitar múltiplas instâncias
    if (isMonitoringSascred) {
        console.log('⚠️ Monitoramento Sascred já está ativo');
        return false;
    }
    
    console.log(`🚀 Iniciando monitoramento Sascred para usuário: ${codigoUsuario}`);
    
    // Criar instância do sistema de notificação
    sascredNotificationSystem = new SascredNotificationApp({
        debug: true, // Mudar para false em produção
        baseUrl: window.location.origin
    });
    
    // Iniciar monitoramento
    const success = sascredNotificationSystem.iniciarMonitoramento(codigoUsuario, {
        // CALLBACK PRINCIPAL: Quando menu Sascred for habilitado
        onMenuHabilitado: function(dadosAssinatura) {
            console.log('🎉 MENU SASCRED HABILITADO!', dadosAssinatura);
            
            // Aqui você implementa a lógica do seu app:
            habilitarMenuCompletoSascred(dadosAssinatura, dadosUsuario);
        },
        
        // CALLBACK: Updates de status da conexão
        onStatusUpdate: function(status, mensagem) {
            console.log(`📊 Status Sascred: ${status} - ${mensagem}`);
            atualizarIndicadorConexaoSascred(status, mensagem);
        },
        
        // CALLBACK: Tratamento de erros
        onError: function(tipo, mensagem) {
            console.error(`❌ Erro Sascred ${tipo}: ${mensagem}`);
            tratarErroSascred(tipo, mensagem);
        }
    });
    
    if (success) {
        isMonitoringSascred = true;
        mostrarIndicadorMonitoramento(true);
        return true;
    } else {
        console.error('❌ Falha ao iniciar monitoramento Sascred');
        return false;
    }
}

// ============================================================================
// FUNÇÃO: HABILITAR MENU COMPLETO (IMPLEMENTAR CONFORME SEU APP)
// ============================================================================

function habilitarMenuCompletoSascred(dadosAssinatura, dadosUsuario) {
    console.log('🎯 Habilitando menu completo Sascred...');
    
    // OPÇÃO 1: Mostrar elementos que estavam ocultos
    const menuSascred = document.getElementById('menu-sascred');
    if (menuSascred) {
        menuSascred.style.display = 'block';
        menuSascred.classList.add('menu-ativo');
    }
    
    // OPÇÃO 2: Adicionar itens de menu dinamicamente
    adicionarItensMenuSascred();
    
    // OPÇÃO 3: Redirecionar para página principal com menu completo
    // window.location.href = 'pagina_principal_sascred.html';
    
    // OPÇÃO 4: Recarregar página (se necessário)
    // window.location.reload();
    
    // OPÇÃO 5: Atualizar estado do app (para SPA - Single Page Apps)
    // updateAppState({ sascredEnabled: true });
    
    // Mostrar notificação de sucesso
    mostrarNotificacaoSascredHabilitado(dadosAssinatura);
    
    // Parar monitoramento (objetivo alcançado)
    pararMonitoramentoSascred();
    
    // Log para analytics/debug
    console.log('✅ Menu Sascred habilitado com sucesso');
    
    // Se você usa analytics, trackear evento
    // analytics.track('sascred_menu_habilitado', {
    //     usuario_codigo: dadosUsuario.codigo,
    //     usuario_nome: dadosUsuario.nome,
    //     assinatura_id: dadosAssinatura.id
    // });
}

// ============================================================================
// FUNÇÕES AUXILIARES (IMPLEMENTAR CONFORME SEU APP)
// ============================================================================

function adicionarItensMenuSascred() {
    // Exemplo de como adicionar itens de menu dinamicamente
    const menuPrincipal = document.getElementById('menu-principal');
    
    if (menuPrincipal) {
        const itensMenuSascred = `
            <li class="menu-item sascred-item">
                <a href="#sascred-cartao">
                    <i class="fa fa-credit-card"></i>
                    <span>Cartão Sascred</span>
                </a>
            </li>
            <li class="menu-item sascred-item">
                <a href="#sascred-emprestimos">
                    <i class="fa fa-money"></i>
                    <span>Empréstimos</span>
                </a>
            </li>
            <li class="menu-item sascred-item">
                <a href="#sascred-relatorios">
                    <i class="fa fa-chart-bar"></i>
                    <span>Relatórios Sascred</span>
                </a>
            </li>
        `;
        
        menuPrincipal.insertAdjacentHTML('beforeend', itensMenuSascred);
    }
}

function mostrarNotificacaoSascredHabilitado(dadosAssinatura) {
    const nomeUsuario = dadosAssinatura?.nome || 'Usuário';
    
    // Usar SweetAlert2 se disponível
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '🎉 Sascred Habilitado!',
            html: `
                <p>Parabéns, <strong>${nomeUsuario}</strong>!</p>
                <p>Você agora tem acesso completo aos serviços do Sascred.</p>
                <ul style="text-align: left; display: inline-block;">
                    <li>💳 Cartão de Crédito</li>
                    <li>💰 Empréstimos</li>
                    <li>📊 Relatórios Financeiros</li>
                    <li>⚙️ Configurações</li>
                </ul>
            `,
            icon: 'success',
            timer: 5000,
            showConfirmButton: true,
            confirmButtonText: 'Começar a Usar!'
        });
    }
    // Fallback para notificação básica
    else {
        alert(`🎉 Parabéns, ${nomeUsuario}! Você agora tem acesso completo ao Sascred!`);
    }
}

function atualizarIndicadorConexaoSascred(status, mensagem) {
    const indicador = document.getElementById('indicador-sascred');
    
    if (indicador) {
        indicador.className = `indicador-sascred ${status}`;
        indicador.title = mensagem;
        
        // Adicionar texto do status
        const textoStatus = indicador.querySelector('.status-text');
        if (textoStatus) {
            textoStatus.textContent = mensagem;
        }
    }
}

function mostrarIndicadorMonitoramento(ativo) {
    const indicador = document.getElementById('indicador-sascred');
    
    if (indicador) {
        if (ativo) {
            indicador.style.display = 'block';
            indicador.innerHTML = `
                <span class="status-icon">🔄</span>
                <span class="status-text">Monitorando Sascred...</span>
            `;
        } else {
            indicador.style.display = 'none';
        }
    }
}

function tratarErroSascred(tipo, mensagem) {
    console.error(`Erro Sascred (${tipo}):`, mensagem);
    
    // Não mostrar erros críticos para o usuário final
    // Apenas logar para debug/analytics
    
    if (tipo === 'max_reconnect') {
        // Se não conseguir reconectar, tentar novamente em 5 minutos
        setTimeout(() => {
            const codigoUsuario = getCurrentUserCode(); // Implementar conforme seu app
            if (codigoUsuario) {
                iniciarMonitoramentoSascredParaUsuario(codigoUsuario);
            }
        }, 5 * 60 * 1000); // 5 minutos
    }
}

function pararMonitoramentoSascred() {
    if (sascredNotificationSystem) {
        sascredNotificationSystem.pararMonitoramento();
        sascredNotificationSystem = null;
    }
    
    isMonitoringSascred = false;
    mostrarIndicadorMonitoramento(false);
    
    console.log('⏹️ Monitoramento Sascred encerrado');
}

// ============================================================================
// INTEGRAÇÃO COM LOGOUT
// ============================================================================

function logoutUsuario() {
    // Parar monitoramento Sascred ao fazer logout
    pararMonitoramentoSascred();
    
    // Seu código de logout existente...
    sessionStorage.clear();
    window.location.href = 'login.html';
}

// ============================================================================
// INTEGRAÇÃO COM EVENTOS DA PÁGINA
// ============================================================================

// Parar monitoramento quando usuário sair da página
window.addEventListener('beforeunload', function() {
    pararMonitoramentoSascred();
});

// Pausar/retomar monitoramento conforme visibilidade da página
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Página não está visível - manter monitoramento ativo
        console.log('📱 App em background - mantendo monitoramento Sascred');
    } else {
        // Página voltou a ficar visível
        console.log('📱 App em foreground - monitoramento Sascred ativo');
    }
});

// ============================================================================
// FUNÇÕES UTILITÁRIAS (IMPLEMENTAR CONFORME SEU APP)
// ============================================================================

function getCurrentUserCode() {
    // Implementar conforme como você armazena dados do usuário
    // Exemplos:
    
    // Se usar sessionStorage:
    // return sessionStorage.getItem('codigo_usuario');
    
    // Se usar variável global:
    // return window.currentUser?.codigo;
    
    // Se usar DOM:
    // return document.getElementById('user-code')?.value;
    
    // Placeholder - implementar conforme seu sistema
    return null;
}

function redirecionarUsuarioLogado(dadosUsuario) {
    // Sua lógica de redirecionamento existente
    // Exemplo:
    // window.location.href = 'dashboard.html';
}

function mostrarErroSenha() {
    // Seu tratamento de erro de senha existente
}

function mostrarErroLogin(dados) {
    // Seu tratamento de erro de login existente
}

function mostrarErroConexao() {
    // Seu tratamento de erro de conexão existente
}

// ============================================================================
// EXEMPLO DE CSS PARA O INDICADOR (ADICIONAR AO SEU CSS)
// ============================================================================

/*
.indicador-sascred {
    position: fixed;
    top: 10px;
    right: 10px;
    background: #007bff;
    color: white;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 12px;
    z-index: 1000;
    display: none;
}

.indicador-sascred.conectado {
    background: #28a745;
}

.indicador-sascred.erro {
    background: #dc3545;
}

.indicador-sascred.parado {
    background: #6c757d;
}

.indicador-sascred .status-icon {
    margin-right: 5px;
}

.menu-item.sascred-item {
    background-color: #e8f5e8;
    border-left: 3px solid #28a745;
}

.menu-item.sascred-item:hover {
    background-color: #d4edda;
}
*/

// ============================================================================
// EXEMPLO DE HTML PARA O INDICADOR (ADICIONAR AO SEU HTML)
// ============================================================================

/*
<!-- Adicionar em algum lugar do seu HTML -->
<div id="indicador-sascred" class="indicador-sascred" style="display: none;">
    <span class="status-icon">🔄</span>
    <span class="status-text">Inicializando...</span>
</div>

<!-- Menu onde serão adicionados os itens do Sascred -->
<ul id="menu-principal" class="menu-principal">
    <!-- Seus itens de menu existentes -->
    <li class="menu-item">
        <a href="#home">
            <i class="fa fa-home"></i>
            <span>Início</span>
        </a>
    </li>
    <!-- Itens do Sascred serão adicionados aqui dinamicamente -->
</ul>

<!-- Seção do menu Sascred (inicialmente oculta) -->
<div id="menu-sascred" style="display: none;">
    <h3>Serviços Sascred</h3>
    <!-- Conteúdo do menu completo Sascred -->
</div>
*/

// ============================================================================
// INSTRUÇÕES DE USO
// ============================================================================

/*
PASSO A PASSO PARA INTEGRAR:

1. Incluir o JavaScript no seu HTML:
   <script src="js/sascred_notificacao_app.js"></script>
   <script src="exemplo_integracao_login_associado.js"></script>

2. Adicionar o indicador HTML na sua página (ver exemplo acima)

3. Adicionar o CSS para estilização (ver exemplo acima)

4. Substituir sua função de login por loginAssociadoComSascred()
   ou adicionar a chamada iniciarMonitoramentoSascredParaUsuario()

5. Implementar as funções utilitárias conforme seu app:
   - getCurrentUserCode()
   - redirecionarUsuarioLogado()
   - mostrarErroSenha()
   - etc.

6. Testar com um usuário que ainda não assinou digitalmente

7. Simular assinatura digital e verificar se menu aparece

8. Desabilitar debug em produção (debug: false)

PRONTO! O sistema agora monitora automaticamente e habilita o menu
quando o usuário assinar digitalmente, sem precisar relogar.
*/ 