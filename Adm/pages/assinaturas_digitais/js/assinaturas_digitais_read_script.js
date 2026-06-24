var usuario_global;
var usuario_cod;
var divisao;
var divisao_nome;
var tabela_assinaturas_digitais;
var detailRows = [];
var d = new Date();
var curr_date = d.getDate();
var curr_month = d.getMonth()+1;
var curr_year = d.getFullYear();

// Variáveis para atualização automática
var lastUpdateCheck = null;
var autoUpdateInterval = null;
var isAutoUpdateEnabled = true;
var autoUpdateFrequency = 5000; // 5 segundos

// SISTEMA DE PERSISTÊNCIA: Manter registros aprovados
var registrosAprovados = new Set(); // IDs dos registros aprovados
var codigosVinculados = new Map(); // ID -> código vinculado

/**
 * 💾 FUNÇÕES DE PERSISTÊNCIA EM LOCALSTORAGE
 */
function carregarRegistrosAprovados() {
    try {
        var aprovados = localStorage.getItem('qrcred_registros_aprovados');
        var codigos = localStorage.getItem('qrcred_codigos_vinculados');
        
        if (aprovados) {
            var idsAprovados = JSON.parse(aprovados);
            idsAprovados.forEach(id => registrosAprovados.add(id));
            console.log('📥 Carregados', registrosAprovados.size, 'registros aprovados do localStorage');
        }
        
        if (codigos) {
            var codigosData = JSON.parse(codigos);
            Object.keys(codigosData).forEach(id => {
                codigosVinculados.set(id, codigosData[id]);
            });
            console.log('📥 Carregados códigos vinculados do localStorage');
        }
    } catch (e) {
        console.warn('⚠️ Erro ao carregar registros aprovados:', e);
    }
}

function salvarRegistrosAprovados() {
    try {
        localStorage.setItem('qrcred_registros_aprovados', JSON.stringify(Array.from(registrosAprovados)));
        
        var codigosObj = {};
        codigosVinculados.forEach((codigo, id) => {
            codigosObj[id] = codigo;
        });
        localStorage.setItem('qrcred_codigos_vinculados', JSON.stringify(codigosObj));
        
        console.log('📤 Registros aprovados salvos no localStorage');
    } catch (e) {
        console.warn('⚠️ Erro ao salvar registros aprovados:', e);
    }
}

function limparRegistrosAprovados() {
    registrosAprovados.clear();
    codigosVinculados.clear();
    localStorage.removeItem('qrcred_registros_aprovados');
    localStorage.removeItem('qrcred_codigos_vinculados');
    console.log('🗑️ Registros aprovados limpos!');
}

// Disponibilizar função globalmente para debug
window.limparRegistrosAprovados = limparRegistrosAprovados;

/**
 * 🔧 FUNÇÃO DE DEBUG PARA TESTAR SERIALIZAÇÃO DO CAMPO DATA_PGTO
 * Pode ser chamada manualmente pelo console: debugCampoDataPgto()
 */
function debugCampoDataPgto() {
    console.log('🔧 DEBUG MANUAL DO CAMPO DATA_PGTO:');
    
    var $campo = $("#C_data_pgto_assinatura");
    
    console.log('📋 Informações do campo:');
    console.log('  - Campo existe:', $campo.length > 0);
    console.log('  - Valor atual:', '"' + $campo.val() + '"');
    console.log('  - Atributo name:', '"' + $campo.attr('name') + '"');
    console.log('  - Atributo id:', '"' + $campo.attr('id') + '"');
    console.log('  - É readonly:', $campo.prop('readonly'));
    console.log('  - É disabled:', $campo.prop('disabled'));
    console.log('  - É visível:', $campo.is(':visible'));
    console.log('  - Classes:', $campo.attr('class'));
    
    // Testar serialização do formulário
    var formData = $('#frmassinatura').serialize();
    console.log('📊 Teste de serialização:');
    console.log('  - FormData completo:', formData);
    console.log('  - Contém C_data_pgto_assinatura?', formData.indexOf('C_data_pgto_assinatura') !== -1);
    console.log('  - Contém data_pgto?', formData.indexOf('data_pgto') !== -1);
    
    // Buscar padrão específico
    var matchDataPgto = formData.match(/C_data_pgto_assinatura=([^&]*)/i);
    if (matchDataPgto) {
        console.log('✅ Campo encontrado na serialização:', decodeURIComponent(matchDataPgto[1]));
    } else {
        console.log('❌ Campo NÃO encontrado na serialização!');
        
        // Tentar adicionar atributo name se não existir
        if (!$campo.attr('name')) {
            console.log('🔧 Tentando adicionar atributo name...');
            $campo.attr('name', 'C_data_pgto_assinatura');
            
            var novaSerializacao = $('#frmassinatura').serialize();
            console.log('🔄 Nova serialização após adicionar name:', novaSerializacao);
            console.log('  - Agora contém C_data_pgto_assinatura?', novaSerializacao.indexOf('C_data_pgto_assinatura') !== -1);
        }
    }
    
    // Listar todos os campos do formulário
    console.log('📝 Todos os campos do formulário:');
    $('#frmassinatura input, #frmassinatura select, #frmassinatura textarea').each(function(index) {
        var $input = $(this);
        console.log('  ' + (index + 1) + '. ID: "' + $input.attr('id') + '", Name: "' + $input.attr('name') + '", Valor: "' + $input.val() + '"');
    });
}

// Disponibilizar função globalmente
window.debugCampoDataPgto = debugCampoDataPgto;

/**
 * 🧪 FUNÇÃO DE TESTE FORÇADO DO CAMPO DATA_PGTO
 * Força um valor no campo e testa a serialização
 */
function testarCampoDataPgtoForcado() {
    console.log('🧪 TESTE FORÇADO DO CAMPO DATA_PGTO:');
    
    var $campo = $("#C_data_pgto_assinatura");
    var dataTesteAtual = new Date();
    var dataFormatada = dataTesteAtual.getFullYear() + '-' + 
                       String(dataTesteAtual.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(dataTesteAtual.getDate()).padStart(2, '0') + ' ' +
                       String(dataTesteAtual.getHours()).padStart(2, '0') + ':' + 
                       String(dataTesteAtual.getMinutes()).padStart(2, '0') + ':' + 
                       String(dataTesteAtual.getSeconds()).padStart(2, '0');
    
    console.log('🔧 Forçando valor no campo:', dataFormatada);
    
    // Garantir atributos corretos
    $campo.attr('name', 'C_data_pgto_assinatura');
    $campo.prop('disabled', false);
    $campo.prop('readonly', false);
    $campo.val(dataFormatada);
    
    console.log('📋 Estado após forçar valor:');
    console.log('  - Valor no campo:', '"' + $campo.val() + '"');
    console.log('  - Name:', '"' + $campo.attr('name') + '"');
    console.log('  - Disabled:', $campo.prop('disabled'));
    console.log('  - Readonly:', $campo.prop('readonly'));
    
    // Testar serialização
    var formData = $('#frmassinatura').serialize();
    console.log('📊 Serialização com valor forçado:');
    console.log('  - FormData:', formData);
    
    // Buscar especificamente o campo na string
    var regex = /C_data_pgto_assinatura=([^&]*)/i;
    var match = formData.match(regex);
    
    if (match) {
        var valorDecodificado = decodeURIComponent(match[1]);
        console.log('✅ Campo encontrado na serialização:', valorDecodificado);
        
        // Testar envio simulado
        console.log('🚀 Simulando envio para servidor...');
        
        // Adicionar outros campos necessários
        var dadosCompletos = formData + '&divisao=' + (divisao || '1') + '&usuario_cod=' + (usuario_cod || 'teste');
        
        // Debug do que seria enviado
        console.log('📤 Dados que seriam enviados:', dadosCompletos);
        
        // Verificar se todos os campos obrigatórios estão presentes
        var camposObrigatorios = ['C_id_assinatura', 'C_nome_assinatura', 'C_codigo_assinatura'];
        var camposFaltando = [];
        
        camposObrigatorios.forEach(function(campo) {
            if (dadosCompletos.indexOf(campo + '=') === -1) {
                camposFaltando.push(campo);
            }
        });
        
        if (camposFaltando.length > 0) {
            console.log('⚠️ CAMPOS OBRIGATÓRIOS FALTANDO:', camposFaltando);
            console.log('ℹ️ Para teste completo, abra o modal de um registro primeiro');
        } else {
            console.log('✅ Todos os campos obrigatórios presentes');
            console.log('✅ Campo data_pgto seria enviado com valor:', valorDecodificado);
        }
        
    } else {
        console.log('❌ CAMPO NÃO ENCONTRADO NA SERIALIZAÇÃO!');
        console.log('❌ Possível problema com o atributo name ou estrutura do campo');
    }
}

// Disponibilizar função globalmente
window.testarCampoDataPgtoForcado = testarCampoDataPgtoForcado;

/**
 * 🔬 FUNÇÃO DE TESTE COMPLETO DO CAMPO DATA_PGTO
 * Testa o campo com valor atual + hora e simula salvamento
 */
function testeCompletoDataPgto() {
    console.log('🔬 TESTE COMPLETO DO CAMPO DATA_PGTO:');
    
    // Verificar se modal está aberto
    if (!$("#ModalEditaAssinaturaDigital").is(':visible')) {
        console.log('❌ Modal não está aberto! Abra um modal de Aprovação primeiro.');
        return;
    }
    
    var $campo = $("#C_data_pgto_assinatura");
    var agora = new Date();
    var dataHoraCompleta = agora.getFullYear() + '-' + 
                          String(agora.getMonth() + 1).padStart(2, '0') + '-' + 
                          String(agora.getDate()).padStart(2, '0') + ' ' +
                          String(agora.getHours()).padStart(2, '0') + ':' + 
                          String(agora.getMinutes()).padStart(2, '0') + ':' + 
                          String(agora.getSeconds()).padStart(2, '0');
    
    console.log('📅 Data/hora de teste:', dataHoraCompleta);
    
    // Garantir configuração correta
    $campo.attr('name', 'C_data_pgto_assinatura');
    $campo.prop('disabled', false);
    $campo.prop('readonly', false);
    $campo.val(dataHoraCompleta);
    
    console.log('✅ Campo configurado e preenchido');
    console.log('📋 Estado atual do campo:');
    console.log('  - Valor:', $campo.val());
    console.log('  - Name:', $campo.attr('name'));
    console.log('  - Disabled:', $campo.prop('disabled'));
    console.log('  - Readonly:', $campo.prop('readonly'));
    
    // Simular clique no botão salvar para ver os logs
    console.log('🚀 Para ver os logs completos do PHP, clique no botão "Salvar" agora.');
    console.log('🔍 Verifique o error_log do PHP para ver se o valor chegou ao servidor.');
    console.log('📊 Logs esperados no PHP:');
    console.log('  - "POST C_data_pgto_assinatura isset: SIM"');
    console.log('  - "POST C_data_pgto_assinatura valor bruto: \'' + dataHoraCompleta + '\'"');
    console.log('  - "Variável _data_pgto final: \'' + dataHoraCompleta + '\'"');
}

// Disponibilizar função globalmente
window.testeCompletoDataPgto = testeCompletoDataPgto;

/**
 * 🕐 FUNÇÃO DE TESTE ESPECÍFICA PARA TIMESTAMP
 * Testa formatos de timestamp válidos para PostgreSQL
 */
function testeTimestampDataPgto() {
    console.log('🕐 TESTE ESPECÍFICO PARA TIMESTAMP DATA_PGTO:');
    
    if (!$("#ModalEditaAssinaturaDigital").is(':visible')) {
        console.log('❌ Modal não está aberto! Abra um modal de Aprovação primeiro.');
        return;
    }
    
    var $campo = $("#C_data_pgto_assinatura");
    
    // Testes com diferentes formatos de timestamp
    var formatosTestar = [
        '2024-01-15 14:30:25',    // Formato completo
        '2024-01-15 14:30:00',    // Sem segundos
        '2024-01-15',             // Apenas data
        new Date().toISOString().slice(0, 19).replace('T', ' '), // ISO format
        new Date().toLocaleString('sv-SE').replace('T', ' ')     // Swedish locale (YYYY-MM-DD HH:mm:ss)
    ];
    
    console.log('📋 Testando formatos de timestamp:');
    formatosTestar.forEach(function(formato, index) {
        console.log('  ' + (index + 1) + '. "' + formato + '"');
    });
    
    // Usar formato atual como teste
    var agora = new Date();
    var timestampAtual = agora.getFullYear() + '-' + 
                        String(agora.getMonth() + 1).padStart(2, '0') + '-' + 
                        String(agora.getDate()).padStart(2, '0') + ' ' +
                        String(agora.getHours()).padStart(2, '0') + ':' + 
                        String(agora.getMinutes()).padStart(2, '0') + ':' + 
                        String(agora.getSeconds()).padStart(2, '0');
    
    console.log('⏰ Usando timestamp atual para teste:', timestampAtual);
    
    // Configurar campo
    $campo.attr('name', 'C_data_pgto_assinatura');
    $campo.prop('disabled', false);
    $campo.prop('readonly', false);
    $campo.val(timestampAtual);
    
    console.log('✅ Campo configurado para timestamp');
    console.log('📊 Logs esperados no PHP:');
    console.log('  - "DateTime criado com sucesso: \'' + timestampAtual + '\'"');
    console.log('  - "Variável _data_pgto final: \'' + timestampAtual + '\'"');
    console.log('  - "DEBUG UPDATE - data_pgto antes do execute: \'' + timestampAtual + '\'"');
    
    console.log('🚀 Agora clique "Salvar" e verifique o error_log do PHP');
}

// Disponibilizar função globalmente
window.testeTimestampDataPgto = testeTimestampDataPgto;

/**
 * 🔍 FUNÇÃO DE DIAGNÓSTICO COMPLETO DO PROBLEMA DATA_PGTO
 * Verifica todos os aspectos do campo e simula salvamento
 */
function diagnosticoCompletoDataPgto() {
    console.log('🔍 DIAGNÓSTICO COMPLETO DO PROBLEMA DATA_PGTO:');
    
    if (!$("#ModalEditaAssinaturaDigital").is(':visible')) {
        console.log('❌ Modal não está aberto! Abra um modal de Aprovação primeiro.');
        return;
    }
    
    var $campo = $("#C_data_pgto_assinatura");
    var agora = new Date();
    var timestampTeste = agora.getFullYear() + '-' + 
                        String(agora.getMonth() + 1).padStart(2, '0') + '-' + 
                        String(agora.getDate()).padStart(2, '0') + ' ' +
                        String(agora.getHours()).padStart(2, '0') + ':' + 
                        String(agora.getMinutes()).padStart(2, '0') + ':' + 
                        String(agora.getSeconds()).padStart(2, '0');
    
    console.log('📋 ETAPA 1: Verificação inicial do campo');
    console.log('  - Campo existe:', $campo.length > 0);
    console.log('  - Está visível:', $campo.is(':visible'));
    console.log('  - Está no DOM:', document.getElementById('C_data_pgto_assinatura') !== null);
    
    // Configurar e preencher campo
    $campo.attr('name', 'C_data_pgto_assinatura');
    $campo.prop('disabled', false);
    $campo.prop('readonly', false);
    $campo.val(timestampTeste);
    
    console.log('📋 ETAPA 2: Estado após configuração');
    console.log('  - Name:', $campo.attr('name'));
    console.log('  - Valor:', $campo.val());
    console.log('  - Disabled:', $campo.prop('disabled'));
    console.log('  - Readonly:', $campo.prop('readonly'));
    
    // Verificar serialização
    var formData = $('#frmassinatura').serialize();
    var regex = /C_data_pgto_assinatura=([^&]*)/i;
    var match = formData.match(regex);
    
    console.log('📋 ETAPA 3: Verificação da serialização');
    console.log('  - FormData contém campo:', formData.indexOf('C_data_pgto_assinatura') !== -1);
    if (match) {
        var valorSerializado = decodeURIComponent(match[1]);
        console.log('  - Valor serializado:', valorSerializado);
        console.log('  - Tamanho:', valorSerializado.length);
        console.log('  - É igual ao valor do campo:', valorSerializado === $campo.val());
    } else {
        console.log('  - ❌ Campo NÃO encontrado na serialização!');
    }
    
    // Verificar outros campos obrigatórios
    var camposObrigatorios = ['C_id_assinatura', 'C_codigo_assinatura', 'C_nome_assinatura'];
    console.log('📋 ETAPA 4: Verificação de campos obrigatórios');
    camposObrigatorios.forEach(function(campo) {
        var valor = $('#' + campo).val();
        console.log('  - ' + campo + ':', valor ? '"' + valor + '"' : 'VAZIO');
    });
    
    // Verificar ID para UPDATE
    var idAssinatura = $("#C_id_assinatura").val();
    console.log('📋 ETAPA 5: Verificação do ID para UPDATE');
    console.log('  - ID presente:', idAssinatura ? 'SIM (' + idAssinatura + ')' : 'NÃO');
    
    console.log('📋 ETAPA 6: Instruções para teste completo');
    console.log('  1. Verifique se todos os valores acima estão corretos');
    console.log('  2. Clique no botão "Salvar"');
    console.log('  3. Verifique o error_log do PHP para os seguintes logs:');
    console.log('     - "POST C_data_pgto_assinatura valor bruto: \'' + timestampTeste + '\'"');
    console.log('     - "DateTime criado com sucesso: \'' + timestampTeste + '\'"');
    console.log('     - "DEBUG SQL COMPLETO: UPDATE sind.associados_sasmais SET..."');
    console.log('     - "VERIFICAÇÃO PÓS-EXECUÇÃO - data_pgto no banco: \'' + timestampTeste + '\'"');
    
    console.log('⚠️ Se aparecer "PROBLEMA: data_pgto foi enviado mas está NULL no banco!"');
    console.log('   isso indica um problema específico com o campo timestamp na tabela.');
}

// Disponibilizar função globalmente
window.diagnosticoCompletoDataPgto = diagnosticoCompletoDataPgto;

/**
 * 📱 VERIFICAR MODO RESPONSIVO
 * Controla indicador visual de responsividade
 */
function verificarModoResponsivo() {
    if (typeof tabela_assinaturas_digitais === 'undefined' || !tabela_assinaturas_digitais) {
        return;
    }
    
    // Verificar se DataTables Responsive está disponível
    if (typeof $.fn.dataTable.Responsive === 'undefined') {
        console.log('ℹ️ DataTables Responsive não disponível - usando modo básico');
        $('#responsive-info').removeClass('show');
        return;
    }
    
    try {
        var responsive = tabela_assinaturas_digitais.responsive;
        var $indicador = $('#responsive-info');
        
        if (responsive && responsive.hasHidden && responsive.hasHidden()) {
            // Há colunas ocultas - mostrar indicador
            var colunasOcultas = $('.dtr-hidden').length;
            var mensagem = `Modo responsivo ativo. ${colunasOcultas} coluna(s) oculta(s). Clique no ícone [+] para ver detalhes.`;
            
            $indicador.find('.message').text(mensagem);
            $indicador.addClass('show');
            
            console.log('📱 Modo responsivo ativo -', colunasOcultas, 'colunas ocultas');
        } else {
            // Todas as colunas visíveis - ocultar indicador
            $indicador.removeClass('show');
            console.log('🖥️ Modo desktop - todas as colunas visíveis');
        }
    } catch (e) {
        console.warn('⚠️ Erro ao verificar modo responsivo:', e);
        $('#responsive-info').removeClass('show');
    }
}

/**
 * 📏 DETECTAR MUDANÇA DE TAMANHO DA TELA
 */
$(window).on('resize', function() {
    if (typeof tabela_assinaturas_digitais !== 'undefined' && tabela_assinaturas_digitais) {
        // Aguardar um pouco para reajuste da tabela
        setTimeout(function() {
            try {
                tabela_assinaturas_digitais.columns.adjust();
                verificarModoResponsivo();
            } catch (e) {
                console.warn('⚠️ Erro no resize da tabela:', e);
            }
        }, 250);
    }
});

/**
 * 🎯 FUNÇÃO PARA TESTAR RESPONSIVIDADE (debug)
 */
function testarResponsividade() {
    var largura = $(window).width();
    console.log('📏 Largura atual da janela:', largura + 'px');
    
    if (largura <= 480) {
        console.log('📱 Dispositivo: Mobile muito pequeno');
    } else if (largura <= 767) {
        console.log('📱 Dispositivo: Mobile');
    } else if (largura <= 1024) {
        console.log('💻 Dispositivo: Tablet');
    } else {
        console.log('🖥️ Dispositivo: Desktop');
    }
    
    verificarModoResponsivo();
}

// Disponibilizar globalmente para debug
window.testarResponsividade = testarResponsividade;

$(document).ready(function(){

    d = new Date();
    curr_date = d.getDate();
    curr_month = d.getMonth()+1;
    curr_year = d.getFullYear();
    curr_date = pad(curr_date,2)
    curr_month = pad(curr_month,2)

    divisao = sessionStorage.getItem("divisao");
    divisao_nome = sessionStorage.getItem("divisao_nome");
    
    // 📥 CARREGAR registros aprovados salvos
    carregarRegistrosAprovados();

    // Campo C_has_signed agora é input text readonly

    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");
   
    console.log('🎯 QRCRED detectado - iniciando sistema...');
    filtra_assinaturas_digitais("signed",divisao);// filtra padrão assinados (has_signed = true)

    $('#tabela_assinaturas_digitais tbody').on('click', 'tr td.details-control', function() {
        var tr = $(this).closest('tr');
        var row = tabela_assinaturas_digitais.row(tr);
        var idx = $.inArray(tr.attr('id'), detailRows);

        if (row.child.isShown()) {
            tr.removeClass('details');
            row.child.hide();
            detailRows.splice(idx, 1);
        } else {
            tr.addClass('details');
            row.child(formatAssinaturaDigital(row.data())).show();
            if (idx === -1) {
                detailRows.push(tr.attr('id'));
            }
        }
    });
    
    // Configurar e iniciar atualização automática
    console.log('🚀 Iniciando sistema de auto-atualização...');
    initAutoUpdate();
    console.log('✅ Sistema de auto-atualização configurado!');
    
    // Iniciar monitoramento do sistema
    iniciarMonitoramentoSistema();
   
    
    // Inicializar indicador mesmo se não for QRCRED (para outras divisões se necessário)
    var indicator = $('#auto-update-indicator');
    if (indicator.length > 0) {
        // Garantir que o indicador esteja oculto inicialmente se não for QRCRED
        if (divisao !== "1") {
            indicator.hide();
        }
    }
    
    // Event listener para quando o modal for totalmente exibido
    $('#ModalEditaAssinaturaDigital').on('shown.bs.modal', function () {
        console.log('📋 Modal totalmente carregado');
        
        // Forçar visibilidade do campo chave_pix após modal ser exibido
        setTimeout(function() {
            var chavePix = $("#C_chave_pix_assinatura");
            var formGroup = chavePix.closest('.form-group');
            var colDiv = chavePix.closest('.col-xs-3');
            var rowDiv = chavePix.closest('.row');
            
            console.log('🔧 Forçando exibição do chave_pix no modal carregado:');
            console.log('- Campo:', chavePix.length);
            console.log('- Form group:', formGroup.length);
            console.log('- Col div:', colDiv.length);
            console.log('- Row div:', rowDiv.length);
            
            // Forçar exibição de todos os elementos relacionados
            chavePix.show().css({'display': 'block !important', 'visibility': 'visible !important'});
            formGroup.show().css({'display': 'block !important', 'visibility': 'visible !important'});
            colDiv.show().css({'display': 'block !important', 'visibility': 'visible !important'});
            rowDiv.show().css({'display': 'block !important', 'visibility': 'visible !important'});
            
            console.log('✅ Campo chave_pix visível após forçar:', chavePix.is(':visible'));
            console.log('✅ Valor atual do campo:', chavePix.val());
        }, 50);
    });
   
    $('#tabela_assinaturas_digitais tbody').on('click', 'tr', function () {
        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
        } else {
            tabela_assinaturas_digitais.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
        }
    });
});

// Validação para nome (apenas letras e espaços)
$("#C_nome_assinatura").keypress(function(event) {
    var character = String.fromCharCode(event.keyCode);
    return isValid(character);
});

function isValid(str) {
    return !/[~`!@#$%\^&*()+=\-\[\]\\'´.;,/{}|\\":<>\?]/g.test(str);
}

// Validação para código (apenas números e letras)
$('#C_codigo_assinatura').on('keypress', function (event) {
    var regex = new RegExp("^[a-zA-Z0-9]+$");
    var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
    if (!regex.test(key)) {
        event.preventDefault();
        return false;
    }
});

// Máscara para CPF
$('#C_cpf_assinatura').on('input', function () {
    var value = $(this).val().replace(/\D/g, '');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    $(this).val(value);
});

// Campos C_valor_aprovado_assinatura e C_data_pgto_assinatura removidos do modal

// Função para formatar valor como moeda
function formatarMoeda(valor) {
    if (!valor || valor === '' || valor === '0,00') {
        return '';
    }
    
    // Se já está formatado, converte para número primeiro
    var numeroLimpo = valor.toString().replace(/\./g, '').replace(',', '.');
    var numero = parseFloat(numeroLimpo);
    
    if (isNaN(numero)) {
        return '';
    }
    
    return numero.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Função para converter moeda formatada para número (para envio ao servidor)
function moedaParaNumero(valorFormatado) {
    if (!valorFormatado || valorFormatado === '' || valorFormatado === '0,00') {
        return '';
    }
    
    // Remove formatação e converte para número
    var numero = valorFormatado.toString().replace(/\./g, '').replace(',', '.');
    var resultado = parseFloat(numero);
    
    // Se não é um número válido, retorna string vazia
    if (isNaN(resultado)) {
        return '';
    }
    
    return resultado;
}

// Eventos removidos para campos C_valor_aprovado_assinatura e C_data_pgto_assinatura

// Evento para editar assinatura digital (removido - coluna não existe mais)

// Evento para inserir nova assinatura digital
$("#btnInserir").click(function(){
    $("#frmassinatura")[0].reset();
    $("#rotulo_assinatura").html("Cadastrando");
    resetModalFields(); // Resetar campos para modo normal
    // $.fn.modal.Constructor.prototype.enforceFocus = function() {}; // Comentado para evitar conflitos
    $("#ModalEditaAssinaturaDigital").modal("show");
    $('#operation').val("Add");
    $("#C_id_assinatura").val("");
});

// Função para resetar campos do modal para modo normal
function resetModalFields() {
    // Remover readonly de todos os campos (modo edição completa)
    $("#C_nome_assinatura").prop('readonly', false);
    $("#C_codigo_assinatura").prop('readonly', false);
    $("#C_celular_assinatura").prop('readonly', false);
    $("#C_email_assinatura").prop('readonly', false);
    $("#C_cpf_assinatura").prop('readonly', false);
    $("#C_limite_assinatura").prop('readonly', false);
    $("#C_chave_pix_assinatura").prop('readonly', false);
    // Campo "Assinado digitalmente" sempre permanece readonly
    $("#C_has_signed_readonly_assinatura").prop('readonly', true);
    
    // Mostrar todas as linhas que podem ter sido ocultadas
    $("#C_cel_informado_assinatura").closest('.row').show();
    $("#C_event_assinatura").closest('.row').show();
    $("#C_doc_token_assinatura").closest('.row').show();
    $("#C_doc_name_assinatura").closest('.row').show();
    $("#C_signed_at_assinatura").closest('.row').show();
    $("#C_limite_assinatura").closest('.row').show();
    $("#C_chave_pix_assinatura").closest('.row').show();
    $("#C_has_signed_readonly_assinatura").closest('.row').show();
    
    // Mostrar campo reprovar
    $("#C_reprovar").closest('.row').show();
}

// Evento para resetar modal quando for fechado - SOLUÇÃO ROBUSTA
$('#ModalEditaAssinaturaDigital').off('hidden.bs.modal').on('hidden.bs.modal', function (e) {
    console.log('📝 FECHAMENTO MODAL - Iniciando limpeza...');
    
    // SOLUÇÃO AGRESSIVA: Forçar fechamento completo
    try {
        // 1. Resetar campos
        resetModalFields();
        
        // 2. Forçar fechamento visual
        $('#ModalEditaAssinaturaDigital').hide();
        
        // 3. Remover TODOS os backdrops
        $('.modal-backdrop').remove();
        
        // 4. Resetar estado do body
        $('body').removeClass('modal-open');
        $('body').css('padding-right', '');
        
        // 5. Forçar fechamento de qualquer outro modal
        $('.modal:visible').each(function() {
            if (this.id !== 'ModalEditaAssinaturaDigital') {
                $(this).hide();
                console.log('📝 Fechando modal residual:', this.id);
            }
        });
        
        // 6. Limpar eventos problemáticos
        $(document).off('focusin.bs.modal');
        
        console.log('✅ Modal fechado com sucesso');
        
    } catch (error) {
        console.error('❌ Erro ao fechar modal:', error);
        
        // FALLBACK EXTREMO: Recarregar página se tudo falhar
        setTimeout(function() {
            if ($('#ModalEditaAssinaturaDigital').is(':visible')) {
                console.log('🔄 Modal ainda visível - forçando fechamento extremo');
                location.reload();
            }
        }, 2000);
    }
});

// Evento para atualização manual imediata
$("#btnAtualizarManual").click(function(){
    if (tabela_assinaturas_digitais) {
        $(this).prop('disabled', true).html('<span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Atualizando...');
        
        tabela_assinaturas_digitais.ajax.reload(function() {
            $("#btnAtualizarManual").prop('disabled', false).html('<span class="glyphicon glyphicon-refresh"></span> Atualizar');
            
            // Resetar timestamp para forçar nova verificação
            lastUpdateCheck = new Date().toISOString().slice(0, 19).replace('T', ' ');
            
            // Mostrar toast de sucesso
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Dados atualizados!',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        });
    }
});

// Evento para salvar
$("#btnSalvar").click(function(event){
   waitingDialog.show('Gravando, aguarde ...');
   
   $("#btnSalvar").attr("disabled", true);
   
   // Campos C_valor_aprovado_assinatura e C_data_pgto_assinatura removidos
   // O sistema agora grava automaticamente data_hora atual no campo data_hora da tabela
   
   // Comentário: Mensagem de "Campo Data Pgto Vazio" removida conforme solicitado
   
   // Preparar dados do formulário
   var formData = $('#frmassinatura').serialize();
   var rotulo = $("#rotulo_assinatura").html();
   
   // DEBUG: Verificar se data_pgto está incluído na serialização
   console.log('🔍 DEBUG SERIALIZAÇÃO:');
   console.log('  - FormData completo:', formData);
   console.log('  - Contém C_data_pgto_assinatura?', formData.indexOf('C_data_pgto_assinatura') !== -1);
   console.log('  - Contém data_pgto?', formData.indexOf('data_pgto') !== -1);
   
   // Se for aprovação, garantir que has_signed seja sempre enviado como true
   if (rotulo === "Aprovando") {
       console.log('🔒 Modo aprovação detectado - forçando has_signed = true');
       
       // Verificar se é uma reprovação
       var reprovar = $("#C_reprovar").val();
       console.log('📝 Campo reprovar valor:', reprovar);
       
       // Debug específico para botão btn-aprovado-filtro
       var idAssinatura = $("#C_id_assinatura").val();
       var $botaoAprovadoFiltro = $('button.btn-aprovado-filtro[data-id="' + idAssinatura + '"]');
       if ($botaoAprovadoFiltro.length > 0) {
           console.log('🔍 SALVAMENTO VIA BOTÃO BTN-APROVADO-FILTRO DETECTADO:');
           console.log('  - ID:', idAssinatura);
           console.log('  - Campo reprovar:', reprovar);
           console.log('  - Será atualizado no banco de dados sind.associados_sasmais campo reprovado');
       }
       
       // Campo C_has_signed foi removido - definir valor padrão para aprovação
       // Criar campo hidden temporário para envio ao servidor
       if ($("#C_has_signed_temp").length === 0) {
           $("#frmassinatura").append('<input type="hidden" id="C_has_signed_temp" name="C_has_signed" value="1">');
       } else {
           $("#C_has_signed_temp").val("1");
       }
       
       // Reserializar o formulário com o valor correto incluindo o campo reprovar
       formData = $('#frmassinatura').serialize();
       
       // Campo has_signed é enviado automaticamente pelo campo hidden criado acima
       
       console.log('✅ Campo has_signed forçado para true (valor 1)');
       console.log('📊 Dados do formulário:', formData);
       
       // DEBUG: Verificar novamente se data_pgto está na reserialização
       console.log('🔍 DEBUG RESERIALIZAÇÃO:');
       console.log('  - Contém C_data_pgto_assinatura?', formData.indexOf('C_data_pgto_assinatura') !== -1);
       console.log('  - Contém data_pgto?', formData.indexOf('data_pgto') !== -1);
       console.log('  - Valor atual do campo:', '"' + $("#C_data_pgto_assinatura").val() + '"');
   }
   
   formData += '&divisao='+divisao+'&usuario_cod='+usuario_cod;
   
   // DEBUG FINAL: Mostrar dados completos que serão enviados
   console.log('🚀 DEBUG ENVIO FINAL:');
   console.log('  - FormData final:', formData);
   console.log('  - Contém C_data_pgto_assinatura na string final?', formData.indexOf('C_data_pgto_assinatura') !== -1);
   console.log('  - Contém data_pgto na string final?', formData.indexOf('data_pgto') !== -1);
   
   // Extrair especificamente o valor de data_pgto da string
   var matchDataPgto = formData.match(/C_data_pgto_assinatura=([^&]*)/i);
   if (matchDataPgto) {
       var valorDataPgto = decodeURIComponent(matchDataPgto[1]);
       console.log('✅ Campo C_data_pgto_assinatura encontrado nos dados:', valorDataPgto);
       console.log('🔍 DEBUG ESPECÍFICO DATA_PGTO:');
       console.log('  - Valor bruto:', matchDataPgto[1]);
       console.log('  - Valor decodificado:', valorDataPgto);
       console.log('  - Tamanho do valor:', valorDataPgto.length);
       console.log('  - É string vazia?', valorDataPgto === '');
       console.log('  - É apenas espaços?', valorDataPgto.trim() === '');
   } else {
       console.log('❌ Campo C_data_pgto_assinatura NÃO encontrado nos dados!');
   }
   
    $.ajax({
        url: "pages/assinaturas_digitais/assinaturas_digitais_salvar.php",
        method: "POST",
        data: formData,
        success: function (data) {
            // Capturar ID, código E valor do reprovar ANTES de resetar o form
            var idAssinatura = $("#C_id_assinatura").val();
            var codigoAssinatura = $("#C_codigo_assinatura").val();
            var rotulo = $("#rotulo_assinatura").html();
            var reprovar = $("#C_reprovar").val(); // Capturar ANTES do reset
            
            // Debug específico para btn-aprovado-filtro e btn-reprovado-filtro
            var $botaoOriginal = $('button[data-id="' + idAssinatura + '"]');
            var ehBotaoAprovadoFiltro = $botaoOriginal.hasClass('btn-aprovado-filtro');
            var ehBotaoReprovadoFiltro = $botaoOriginal.hasClass('btn-reprovado-filtro') || $botaoOriginal.hasClass('btn-reprovado') || $botaoOriginal.text().includes('Reprovado');
            
            console.log('🔍 DEBUG SALVAMENTO:');
            console.log('- ID Assinatura:', idAssinatura);
            console.log('- Código:', codigoAssinatura); 
            console.log('- Rotulo:', rotulo);
            console.log('- Campo Reprovar:', reprovar);
            console.log('- É botão btn-aprovado-filtro:', ehBotaoAprovadoFiltro);
            console.log('- É botão btn-reprovado-filtro:', ehBotaoReprovadoFiltro);
            console.log('- Response do servidor:', data);
            
            $("#frmassinatura")[0].reset();
            
            if (data === "atualizado") {
                // Verificar se foi uma aprovação (rotulo = "Aprovando")
                if (rotulo === "Aprovando") {
                    
                    if (reprovar == "1") {
                        // Foi uma reprovação
                        console.log('❌ REPROVAÇÃO DETECTADA! ID:', idAssinatura);
                        console.log('❌ Tipo de botão original:', ehBotaoAprovadoFiltro ? 'btn-aprovado-filtro' : 'outro');
                        
                        // Remover do localStorage se estivesse aprovado
                        registrosAprovados.delete(idAssinatura);
                        codigosVinculados.delete(idAssinatura);
                        salvarRegistrosAprovados();
                        
                        // Aplicar mudanças visuais de reprovação com sistema robusto
                        console.log('🔄 Iniciando sistema robusto de mudanças visuais de reprovação...');
                        garantirMudancasVisuaisReprovacao(idAssinatura);
                        
                        Swal.fire({
                            title: "Reprovação Salva!",
                            text: "Assinatura digital reprovada com sucesso!",
                            icon: "warning",
                            showConfirmButton: false,
                            timer: 2000
                        });
                    } else if (reprovar == "0" && ehBotaoReprovadoFiltro) {
                        // Modal foi aberto por botão reprovado e usuário marcou "Reprovar = Não"
                        console.log('🔄 DETECÇÃO: Modal de botão reprovado com "Reprovar = Não"');
                        
                        // Verificar se valor aprovado é 0 ou null
                        var valorAprovadoFormatado = $("#C_valor_aprovado_assinatura").val();
                        var valorAprovadoNumerico = moedaParaNumero(valorAprovadoFormatado);
                        
                        console.log('💰 Valor Aprovado:', valorAprovadoFormatado, '→', valorAprovadoNumerico);
                        
                        if (valorAprovadoNumerico === 0 || valorAprovadoNumerico === null || isNaN(valorAprovadoNumerico)) {
                            // Valor aprovado é 0 ou null - remover cor da linha
                            console.log('🔄 CONDIÇÃO ATENDIDA: Removendo cor da linha (valor aprovado = 0 ou null)');
                            
                            // Remover do localStorage se estivesse aprovado
                            registrosAprovados.delete(idAssinatura);
                            codigosVinculados.delete(idAssinatura);
                            salvarRegistrosAprovados();
                            
                            // Aplicar mudanças visuais para remover cor
                            removerCorLinhaReprovada(idAssinatura);
                            
                            Swal.fire({
                                title: "Reprovação Removida!",
                                text: "Linha voltou ao estado normal (sem cor).",
                                icon: "info",
                                showConfirmButton: false,
                                timer: 2000
                            });
                        } else {
                            // Valor aprovado > 0 - tratar como aprovação normal
                            console.log('✅ Valor aprovado > 0 - tratando como aprovação normal');
                            
                            // Salvar ID como aprovado
                            registrosAprovados.add(idAssinatura);
                            codigosVinculados.set(idAssinatura, codigoAssinatura);
                            salvarRegistrosAprovados();
                            
                            // Aplicar mudanças visuais imediatamente
                            aplicarMudancasVisuaisAprovacao(idAssinatura, codigoAssinatura);
                            
                            Swal.fire({
                                title: "Aprovação Salva!",
                                text: `Registro aprovado com código: ${codigoAssinatura}`,
                                icon: "success",
                                showConfirmButton: false,
                                timer: 2000
                            });
                        }
                    } else {
                        // Foi uma aprovação normal
                        console.log('✅ Aprovação salva com sucesso! ID:', idAssinatura, 'Código:', codigoAssinatura);
                        
                        // Salvar ID como aprovado
                        registrosAprovados.add(idAssinatura);
                        codigosVinculados.set(idAssinatura, codigoAssinatura);
                        salvarRegistrosAprovados();
                        
                        // Aplicar mudanças visuais imediatamente
                        aplicarMudancasVisuaisAprovacao(idAssinatura, codigoAssinatura);
                        
                        // Aplicar novamente após reload da tabela
                        setTimeout(function() {
                            aplicarMudancasVisuaisAprovacao(idAssinatura, codigoAssinatura);
                        }, 1500);
                        
                        Swal.fire({
                            title: "Aprovação Salva!",
                            text: `Registro aprovado com código: ${codigoAssinatura}`,
                            icon: "success",
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                } else {
                    Swal.fire({
                        title: "Parabens!",
                        text: "Assinatura digital atualizada com sucesso !",
                        icon: "success",
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            } else if (data === "cadastrado") {
                Swal.fire({
                    title: "Parabens!",
                    text: "Assinatura digital cadastrada com sucesso !",
                    icon: "success",
                });
            } else if (data === "Código já existe!") {
                Swal.fire({
                    title: "Atenção!",
                    text: "Este código já existe no sistema.",
                    icon: "warning",
                });
            } else if (data === "Seu usuario não tem permissão!") {
                Swal.fire({
                    title: "Atenção!",
                    text: "Seu usuário não tem permissão.",
                    icon: "error",
                });
            }
            $("#frmassinatura")[0].reset();
            $("#btnSalvar").attr("disabled", false);
            waitingDialog.hide();
            
            // Usar fechamento customizado ao invés do Bootstrap
            fecharModalCompleto();
            
            // Recarregar tabela e reaplicar mudanças visuais especificamente para reprovação
            tabela_assinaturas_digitais.ajax.reload(function() {
                console.log('🔄 Tabela recarregada após salvamento');
                
                // Se foi uma reprovação, aplicar mudanças visuais novamente com verificação robusta
                if (rotulo === "Aprovando" && reprovar == "1") {
                    console.log('🔴 Reaplicando mudanças de reprovação após reload da tabela...');
                    garantirMudancasVisuaisReprovacao(idAssinatura);
                } else if (rotulo === "Aprovando" && reprovar == "0" && ehBotaoReprovadoFiltro) {
                    // Se foi remoção de reprovação (modal de botão reprovado com "Reprovar = Não")
                    var valorAprovadoFormatado = $("#C_valor_aprovado_assinatura").val();
                    var valorAprovadoNumerico = moedaParaNumero(valorAprovadoFormatado);
                    
                    if (valorAprovadoNumerico === 0 || valorAprovadoNumerico === null || isNaN(valorAprovadoNumerico)) {
                        console.log('🔄 Reaplicando remoção de cor após reload da tabela...');
                        removerCorLinhaReprovada(idAssinatura);
                    }
                }
            });
        },
        error: function() {
            // Restaurar valor formatado em caso de erro
            $("#C_valor_aprovado_assinatura").val(formatarMoeda(valorAprovado));
            $("#btnSalvar").attr("disabled", false);
            waitingDialog.hide();
        },
        complete: function() {
            // Garantir que o valor seja restaurado
            if ($("#ModalEditaAssinaturaDigital").is(':visible')) {
                setTimeout(function() {
                    var valorAtual = $("#C_valor_aprovado_assinatura").val();
                    if (valorAtual && !isNaN(valorAtual) && valorAtual.indexOf(',') === -1) {
                        $("#C_valor_aprovado_assinatura").val(formatarMoeda(valorAtual));
                    }
                    
                    // Campo C_has_signed foi removido do modal
                }, 100);
            }
        }
    });
    tabela_assinaturas_digitais.columns.adjust().draw();
});

// Variável para controlar se a tabela está sendo processada
var tabela_processando = false;

// Variável global para controlar qual filtro está ativo
var filtro_ativo_atual = null;

// Função para inicializar o filtro ativo baseado no radio selecionado
function inicializarFiltroAtivo() {
    // Resetar flags de processamento ao inicializar
    resetarFlagsProcessamento();
    
    if ($('#RadioAssinados').is(':checked')) {
        filtro_ativo_atual = 'signed';
        console.log('🔄 Filtro inicializado: RadioAssinados (signed)');
    } else if ($('#RadioAprovados').is(':checked')) {
        filtro_ativo_atual = 'approved';
        console.log('🔄 Filtro inicializado: RadioAprovados (approved)');
    } else if ($('#RadioReprovados').is(':checked')) {
        filtro_ativo_atual = 'reprovados';
        console.log('🔄 Filtro inicializado: RadioReprovados (reprovados)');
    } else if ($('#RadioTodos').is(':checked')) {
        filtro_ativo_atual = 'todos';
        console.log('🔄 Filtro inicializado: RadioTodos (todos)');
    } else {
        // Tentar detectar pelo valor do código situação
        if (typeof cod_situacao !== 'undefined') {
            switch (cod_situacao) {
                case 'signed':
                    filtro_ativo_atual = 'signed';
                    break;
                case 'approved':
                    filtro_ativo_atual = 'approved';
                    break;
                case 'reprovados':
                    filtro_ativo_atual = 'reprovados';
                    break;
                case '0':
                    filtro_ativo_atual = 'todos';
                    break;
                default:
                    filtro_ativo_atual = 'signed'; // Padrão
            }
            console.log('🔄 Filtro inicializado via cod_situacao:', cod_situacao, '→', filtro_ativo_atual);
        } else {
            filtro_ativo_atual = 'signed'; // Padrão
            console.log('🔄 Filtro inicializado como padrão: signed');
        }
    }
}

// Eventos para os filtros de radio
$('#RadioAssinados').change(function(){
    if (tabela_processando) {
        console.log('⏸️ Tabela ainda sendo processada, aguardando...');
        return;
    }
    
    // Resetar flags de processamento ao trocar filtro
    resetarFlagsProcessamento();
    
    cod_situacao = $('#RadioAssinados').val();
    filtro_ativo_atual = 'signed'; // Armazenar filtro ativo
    console.log('📻 RadioAssinados selecionado - Valor:', cod_situacao, '- Filtro ativo:', filtro_ativo_atual);
    tabela_processando = true;
    filtra_assinaturas_digitais(cod_situacao,divisao);

});

$('#RadioAprovados').change(function(){
    if (tabela_processando) {
        console.log('⏸️ Tabela ainda sendo processada, aguardando...');
        return;
    }
    
    // Resetar flags de processamento ao trocar filtro
    resetarFlagsProcessamento();
    
    cod_situacao = $('#RadioAprovados').val();
    filtro_ativo_atual = 'approved'; // Armazenar filtro ativo
    console.log('📻 RadioAprovados selecionado - Valor:', cod_situacao, '- Filtro ativo:', filtro_ativo_atual);
    tabela_processando = true;
    filtra_assinaturas_digitais(cod_situacao,divisao);
   
});

$('#RadioReprovados').change(function(){
    if (tabela_processando) {
        console.log('⏸️ Tabela ainda sendo processada, aguardando...');
        return;
    }
    
    // Resetar flags de processamento ao trocar filtro
    resetarFlagsProcessamento();
    
    cod_situacao = $('#RadioReprovados').val();
    filtro_ativo_atual = 'reprovados'; // Armazenar filtro ativo
    console.log('📻 RadioReprovados selecionado - Valor:', cod_situacao, '- Filtro ativo:', filtro_ativo_atual);
    tabela_processando = true;
    filtra_assinaturas_digitais(cod_situacao,divisao);
   
});

$('#RadioTodos').change(function(){
    if (tabela_processando) {
        console.log('⏸️ Tabela ainda sendo processada, aguardando...');
        return;
    }
    
    // Resetar flags de processamento ao trocar filtro
    resetarFlagsProcessamento();
    
    cod_situacao = $('#RadioTodos').val();
    filtro_ativo_atual = 'todos'; // Armazenar filtro ativo
    console.log('📻 RadioTodos selecionado - Valor:', cod_situacao, '- Filtro ativo:', filtro_ativo_atual);
    tabela_processando = true;
    filtra_assinaturas_digitais(cod_situacao,divisao);

});

// Função para filtrar e configurar o DataTable
function formatAssinaturaDigital(d) {
    var tipoLabel = d.tipo == 1 ? 'adesao' : (d.tipo == 2 ? 'antecipação' : 'indefinido');
    return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +
        '<tr><td><strong>ID:</strong></td><td>' + (d.id || '') + '</td></tr>' +
        '<tr><td><strong>Tipo:</strong></td><td>' + tipoLabel + '</td></tr>' +
        '<tr><td><strong>Autorizado:</strong></td><td>' + (d.autorizado || '') + '</td></tr>' +
        '<tr><td><strong>Aceitou Termo:</strong></td><td>' + (d.aceitou_termo || '') + '</td></tr>' +
        '<tr><td><strong>Event:</strong></td><td>' + (d.event || '') + '</td></tr>' +
        '<tr><td><strong>Doc Token:</strong></td><td>' + (d.doc_token || '') + '</td></tr>' +
        '<tr><td><strong>Doc Name:</strong></td><td>' + (d.doc_name || '') + '</td></tr>' +
        '<tr><td><strong>Signed At:</strong></td><td>' + (d.signed_at || '') + '</td></tr>' +
        '<tr><td><strong>Name:</strong></td><td>' + (d.name || '') + '</td></tr>' +
        '<tr><td><strong>Email:</strong></td><td>' + (d.email || '') + '</td></tr>' +
        '<tr><td><strong>CPF:</strong></td><td>' + (d.cpf || '') + '</td></tr>' +
        '<tr><td><strong>Has Signed:</strong></td><td>' + (d.has_signed || '') + '</td></tr>' +
        '<tr><td><strong>Cel Informado:</strong></td><td>' + (d.cel_informado || '') + '</td></tr>' +
        '</table>';
}

function filtra_assinaturas_digitais(codigo,divisao){
    console.log('🔧 Filtrando assinaturas digitais - Código:', codigo, 'Divisão:', divisao);
    debugger;
    // Verificar se as variáveis necessárias estão definidas
    if (typeof usuario_global === 'undefined' || !usuario_global) {
        console.error('❌ Erro: usuario_global não definido');
        tabela_processando = false;
        return;
    }
    
    if (codigo === undefined || codigo === null || divisao === undefined || divisao === null) {
        console.error('❌ Erro: código ou divisão não definidos', { codigo: codigo, divisao: divisao });
        tabela_processando = false;
        return;
    }
    
    // Verificar se a tabela já existe e destruí-la adequadamente
    if ($.fn.DataTable.isDataTable('#tabela_assinaturas_digitais')) {
        console.log('🗑️ Destruindo tabela existente...');
        $('#tabela_assinaturas_digitais').DataTable().clear().destroy();
        
        // Preservar o cabeçalho ao limpar a tabela
        var cabecalho = $('#tabela_assinaturas_digitais thead').html();
        $('#tabela_assinaturas_digitais').empty();
        $('#tabela_assinaturas_digitais').append('<thead>' + cabecalho + '</thead><tbody></tbody>');
    }
    
    // Aguardar um breve momento antes de recriar
    setTimeout(function() {
        console.log('🏗️ Criando nova tabela DataTables...');
        try {
            tabela_assinaturas_digitais = $('#tabela_assinaturas_digitais').DataTable({
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                "destroy": true,
                "processing": true,
                "serverSide": false,
                "paging": true,
                "deferRender": true,
                "autoWidth": false,
                "responsive": false,
                    "ajax": {
                    "url": 'pages/assinaturas_digitais/assinaturas_digitais_read2.php',
                    "method": 'POST',
                    "data":  function() {
                        var dados = { 'usuario_global': usuario_global, 'divisao': divisao, 'id_situacao': codigo };
                        console.log('📤 Dados sendo enviados:', dados);
                        return dados;
                    },
                    "dataType": 'json',
                    "dataSrc": function(json) {
                        console.log('📡 Resposta AJAX recebida:', json);
                        
                        // Verificar se a resposta tem erro
                        if (json.error) {
                            console.error('❌ Erro retornado pelo servidor:', json.message);
                            throw new Error(json.message);
                        }
                        
                        // Verificar se tem dados válidos
                        if (!json.data || !Array.isArray(json.data)) {
                            console.error('❌ Resposta inválida - propriedade data não encontrada ou não é array');
                            console.error('Estrutura recebida:', json);
                            return [];
                        }
                        
                        console.log('✅ Dados válidos recebidos:', json.data.length, 'registros');
                        return json.data;
                    },
                    "error": function(xhr, error, code) {
                        console.error('❌ Erro na requisição AJAX:', {
                            status: xhr.status,
                            error: error,
                            code: code,
                            responseText: xhr.responseText,
                            filtro: codigo
                        });
                        
                        // Resetar controle de processamento em caso de erro AJAX
                        tabela_processando = false;
                        
                        // Mostrar notificação específica
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Erro ao Carregar Dados',
                                text: `Erro no filtro "${codigo}": ${error}`,
                                icon: 'error',
                                toast: true,
                                position: 'top-end',
                                timer: 4000
                            });
                        }
                    }
                },
            "order": [[ 2, "desc" ]],
            "columns": [
                {
                    "class": "details-control",
                    "orderable": false,
                    "data": null,
                    "defaultContent": ""
                },
                { 
                    "data": "tipo",
                    "render": function(data, type, row) {
                        if (type === 'display') {
                            if (data == 1) {
                                return '<span class="label label-success">adesao</span>';
                            } else if (data == 2) {
                                return '<span class="label label-info">antecipação</span>';
                            } else {
                                return '<span class="label label-default">indefinido</span>';
                            }
                        }
                        return data;
                    }
                },
                { "data": "id" },
                { "data": "codigo" },
                { "data": "nome" },
                { "data": "celular" },
                { "data": "data_hora" },
                { "data": "autorizado" },
                { "data": "aceitou_termo" },
                { "data": "event" },
                { "data": "doc_token" },
                { "data": "doc_name" },
                { "data": "signed_at" },
                { "data": "name" },
                { "data": "email" },
                { "data": "cpf" },
                { "data": "has_signed" },
                { "data": "cel_informado" },
                { "data": "botao_vincular" },
                { "data": "botaoexcluir" }
            ],
            "columnDefs": [
                {
                    "targets": [ 2, 7, 8, 9, 10, 11, 12, 13, 14 ],
                    "visible": false,
                    "searchable": true,
                },
            ],
            language: {
                decimal: ",",
                thousands: ".",
                zeroRecords: "Não há dados",
                emptyTable: "Não há dados.",
                infoEmpty: 'Zero registros',
                processing: "🔄 Carregando dados das assinaturas digitais...",
                paginate: {
                    next: "Próximo",
                    previous: "Anterior",
                    first: "Primeiro",
                    last: "Último"
                },
                search: "Pesquisar",
                info: "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                infoFiltered: "(Filtrados de _MAX_ registros)",
                infoPostFix: "",
                lengthMenu: "_MENU_ resultados por página"
            },
            "pagingType": "full_numbers",
                    "drawCallback": function(settings) {
                        // Garantir que o cabeçalho esteja visível
                        $('#tabela_assinaturas_digitais thead').show();
                        
                        setTimeout(function() {
                            // Inicializar filtro ativo se ainda não foi definido
                            if (filtro_ativo_atual === null) {
                                inicializarFiltroAtivo();
                            }
                            
                            reaplicarMudancasVisuais();
                            
                            // Aplicar mudanças visuais aos registros aprovados em qualquer filtro
                            aplicarVisualizacaoRegistrosAprovados();
                            
                            // Aplicar mudanças visuais aos registros reprovados (com regra especial para RadioAssinados)
                            aplicarVisualizacaoRegistrosReprovados();
                            
                            // CORREÇÃO ESPECÍFICA: Forçar aplicação de cores para RadioAprovados
                            if (filtro_ativo_atual === 'approved') {
                                setTimeout(function() {
                                    console.log('🟢 FORÇANDO aplicação de cores verdes para RadioAprovados...');
                                    forcarCoresRadioAprovados();
                                }, 500);
                            }
                            
                            // Aplicar regras especiais dos filtros
                            setTimeout(function() {
                                aplicarRegraEspecialRadioAssinados();
                                aplicarRegraEspecialRadioAprovados();
                                
                                // RadioTodos executa DEPOIS para sobrescrever cores incorretas
                                setTimeout(function() {
                                    aplicarRegraEspecialRadioTodos();
                                }, 50);
                            }, 100);
                            
                            // Log do estado atual para debug
                            console.log('🔄 DrawCallback executado - Filtro ativo:', filtro_ativo_atual);
                        }, 100);
                    }
                });
                console.log('✅ Tabela DataTables criada com sucesso!');
                
                // Verificações finais após criação da tabela
                setTimeout(function() {
                    // Garantir que o cabeçalho esteja visível
                    $('#tabela_assinaturas_digitais thead').show();
                    $('#tabela_assinaturas_digitais thead tr').show();
                    $('#tabela_assinaturas_digitais thead th').show();
                    
                    console.log('👁️ Verificação do cabeçalho:', {
                        thead_visivel: $('#tabela_assinaturas_digitais thead').is(':visible'),
                        thead_existe: $('#tabela_assinaturas_digitais thead').length > 0,
                        tr_count: $('#tabela_assinaturas_digitais thead tr').length,
                        th_count: $('#tabela_assinaturas_digitais thead th').length
                    });
                    
                    // Inicializar filtro ativo se ainda não foi definido
                    if (filtro_ativo_atual === null) {
                        inicializarFiltroAtivo();
                    }
                    
                    // Aplicar mudanças visuais aos registros aprovados em qualquer filtro
                    aplicarVisualizacaoRegistrosAprovados();
                    
                    // Aplicar mudanças visuais aos registros reprovados (com regra especial para RadioAssinados)
                    aplicarVisualizacaoRegistrosReprovados();
                    
                    // CORREÇÃO ESPECÍFICA: Forçar aplicação de cores para RadioAprovados
                    if (filtro_ativo_atual === 'approved') {
                        setTimeout(function() {
                            console.log('🟢 FORÇANDO aplicação de cores verdes para RadioAprovados (2º local)...');
                            forcarCoresRadioAprovados();
                        }, 500);
                    }
                    
                    // Aplicar regras especiais dos filtros
                    setTimeout(function() {
                        aplicarRegraEspecialRadioAssinados();
                        aplicarRegraEspecialRadioAprovados();
                        
                        // RadioTodos executa DEPOIS para sobrescrever cores incorretas
                        setTimeout(function() {
                            aplicarRegraEspecialRadioTodos();
                        }, 50);
                    }, 150);
                    
                    tabela_processando = false;
                    console.log('🔓 Tabela liberada para novo processamento');
                }, 300);
            
        } catch (error) {
            console.error('❌ Erro ao criar tabela DataTables:', error);
            
            // Resetar controle de processamento em caso de erro
            tabela_processando = false;
            
            // Mostrar notificação de erro
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Erro na Tabela',
                    text: 'Erro ao carregar dados da tabela: ' + error.message,
                    icon: 'error',
                    toast: true,
                    position: 'top-end',
                    timer: 3000
                });
            }
        }
    }, 100);
}

/**
 * 🎨 FUNÇÃO PARA APLICAR MUDANÇAS VISUAIS APÓS APROVAÇÃO
 * Aplica mudanças visuais imediatamente após salvar aprovação
 */
function aplicarMudancasVisuaisAprovacao(idAssinatura, codigoAssinatura) {
    console.log('🎨 Aplicando mudanças visuais para ID:', idAssinatura, 'Código:', codigoAssinatura);
    
    // Garantir que o CSS existe
    if (!document.getElementById('linha-aprovada-style')) {
        var style = document.createElement('style');
        style.id = 'linha-aprovada-style';
        style.textContent = `
            .linha-aprovada {
                background-color: #d4edda !important;
                border-left: 4px solid #28a745 !important;
            }
            .linha-aprovada:hover {
                background-color: #c3e6cb !important;
            }
            .linha-aprovada td {
                background-color: inherit !important;
            }
            .btn-aprovado-custom {
                background-color: #28a745 !important;
                border-color: #28a745 !important;
                color: white !important;
            }
        `;
        document.head.appendChild(style);
    }
    
         // Buscar botão específico por ID
     var $botaoAprovado = $('button.aprovar_codigo[data-id="' + idAssinatura + '"]');
     console.log('🔍 Buscando botão com seletor: button.aprovar_codigo[data-id="' + idAssinatura + '"]');
     console.log('🔍 Botões encontrados:', $botaoAprovado.length);
     
     // Se não encontrou, tentar buscar só por data-id
     if ($botaoAprovado.length === 0) {
         $botaoAprovado = $('button[data-id="' + idAssinatura + '"]');
         console.log('🔍 Tentativa alternativa - Botões com data-id encontrados:', $botaoAprovado.length);
     }
     
          if ($botaoAprovado.length > 0) {
         console.log('✅ Botão encontrado - verificando estado atual');
         
         // Verificar se já foi aprovado
         if ($botaoAprovado.hasClass('btn-success')) {
             console.log('ℹ️ Botão já está aprovado - pulando mudanças');
             return;
         }
         
         console.log('🔄 Aplicando mudanças visuais ao botão');
         
         // Modificar o botão (habilitado)
         $botaoAprovado.removeClass('btn-primary')
                      .addClass('btn-success btn-aprovado-custom aprovar_codigo')
                      .prop('disabled', false)
                      .attr('name', 'aprovar_codigo')
                      .attr('title', 'Código aprovado: ' + codigoAssinatura + ' - Clique para editar')
                      .css({
                          'pointer-events': 'auto !important',
                          'cursor': 'pointer !important'
                      })
                      .html('<span class="glyphicon glyphicon-ok"></span> Aprovado');
        
        // CSS inline no botão para forçar estilo
        $botaoAprovado[0].setAttribute('style', 
            'background-color: #28a745 !important; ' +
            'border-color: #28a745 !important; ' +
            'color: white !important; ' +
            'background-image: none !important; ' +
            'box-shadow: none !important;'
        );
        
        // Modificar a linha
        var $linhaAprovada = $botaoAprovado.closest('tr');
        $linhaAprovada.addClass('linha-aprovada')
                      .css({
                          'background-color': '#d4edda',
                          'border-left': '4px solid #28a745'
                      });
        
                 console.log('✅ Mudanças visuais aplicadas com sucesso para ID:', idAssinatura);
         
         // Animação visual de sucesso com borda pulsante
         $linhaAprovada.css('border-left-width', '8px').animate({
             'border-left-width': '4px'
         }, 500);
        
    } else {
        console.log('⚠️ Botão não encontrado para ID:', idAssinatura);
        
        // Tentar encontrar após recarregar tabela
        setTimeout(function() {
            console.log('🔄 Tentando novamente após delay...');
            var $botaoTentativa = $('button.aprovar_codigo[data-id="' + idAssinatura + '"]');
            if ($botaoTentativa.length > 0) {
                aplicarMudancasVisuaisAprovacao(idAssinatura, codigoAssinatura);
            }
        }, 1000);
    }
}



/**
 * 🛡️ FUNÇÃO PARA GARANTIR APLICAÇÃO DAS MUDANÇAS VISUAIS DE REPROVAÇÃO
 * Aplica mudanças múltiplas vezes até conseguir (sistema robusto)
 */
function garantirMudancasVisuaisReprovacao(idAssinatura) {
    console.log('🛡️ GARANTINDO MUDANÇAS VISUAIS DE REPROVAÇÃO PARA ID:', idAssinatura);
    
    var tentativas = 0;
    var maxTentativas = 10;
    var intervalo = 500; // ms
    
    function tentarAplicar() {
        tentativas++;
        console.log('🔄 Tentativa', tentativas, 'de', maxTentativas, 'para ID:', idAssinatura);
        
        // Verificar se o botão já está como "Reprovado"
        var $botao = $('button[data-id="' + idAssinatura + '"]');
        
        if ($botao.length > 0) {
            var textoAtual = $botao.text().trim();
            var ehReprovado = textoAtual.includes('Reprovado');
            var temClasseReprovada = $botao.hasClass('btn-danger') || $botao.hasClass('btn-reprovado');
            
            console.log('🔍 Verificação botão ID ' + idAssinatura + ':');
            console.log('  - Texto atual:', textoAtual);
            console.log('  - É reprovado:', ehReprovado);
            console.log('  - Tem classe reprovada:', temClasseReprovada);
            
            if (ehReprovado && temClasseReprovada) {
                console.log('✅ REPROVAÇÃO JÁ APLICADA CORRETAMENTE!');
                return; // Sucesso - parar tentativas
            }
        }
        
        // Aplicar mudanças visuais
        aplicarMudancasVisuaisReprovacao(idAssinatura);
        
        // Se ainda não conseguiu e há tentativas restantes, tentar novamente
        if (tentativas < maxTentativas) {
            setTimeout(tentarAplicar, intervalo);
        } else {
            console.log('⚠️ LIMITE DE TENTATIVAS ATINGIDO PARA ID:', idAssinatura);
        }
    }
    
    // Iniciar primeira tentativa imediatamente
    tentarAplicar();
    
    // Tentativas adicionais em intervalos específicos
    setTimeout(tentarAplicar, 1000);
    setTimeout(tentarAplicar, 2000);
    setTimeout(tentarAplicar, 3000);
}

/**
 * 🔴 FUNÇÃO PARA APLICAR MUDANÇAS VISUAIS APÓS REPROVAÇÃO
 * Aplica fundo vermelho claro na linha e muda botão para "Reprovado" (habilitado)
 */
function aplicarMudancasVisuaisReprovacao(idAssinatura) {
    console.log('🔴 APLICANDO MUDANÇAS VISUAIS DE REPROVAÇÃO PARA ID:', idAssinatura);
    
    // Garantir que o CSS existe
    if (!document.getElementById('linha-reprovada-style')) {
        var style = document.createElement('style');
        style.id = 'linha-reprovada-style';
        style.textContent = `
            .linha-reprovada {
                background-color: #f8d7da !important;
                border-left: 4px solid #dc3545 !important;
            }
            .linha-reprovada:hover {
                background-color: #f1b0b7 !important;
            }
            .linha-reprovada td {
                background-color: inherit !important;
            }
            .btn-reprovado-custom {
                background-color: #dc3545 !important;
                border-color: #dc3545 !important;
                color: white !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Buscar botão específico por ID com múltiplas estratégias
    var $botaoReprovado = null;
    
    // Estratégia 1: Buscar por classe aprovar_codigo e data-id
    $botaoReprovado = $('button.aprovar_codigo[data-id="' + idAssinatura + '"]');
    console.log('🔍 Estratégia 1 - button.aprovar_codigo[data-id="' + idAssinatura + '"] - Encontrados:', $botaoReprovado.length);
    
    // Estratégia 2: Buscar qualquer botão com data-id (caso tenha perdido a classe)
    if ($botaoReprovado.length === 0) {
        $botaoReprovado = $('button[data-id="' + idAssinatura + '"]');
        console.log('🔍 Estratégia 2 - button[data-id="' + idAssinatura + '"] - Encontrados:', $botaoReprovado.length);
    }
    
    // Estratégia 3: Buscar por name="aprovar_codigo" e data-id
    if ($botaoReprovado.length === 0) {
        $botaoReprovado = $('button[name="aprovar_codigo"][data-id="' + idAssinatura + '"]');
        console.log('🔍 Estratégia 3 - button[name="aprovar_codigo"][data-id="' + idAssinatura + '"] - Encontrados:', $botaoReprovado.length);
    }
    
    if ($botaoReprovado.length > 0) {
        // Debug do botão encontrado
        console.log('✅ BOTÃO ENCONTRADO:');
        console.log('- Classes atuais:', $botaoReprovado.attr('class'));
        console.log('- Texto atual:', $botaoReprovado.text());
        console.log('- HTML atual:', $botaoReprovado.html());
        
        // Modificar o botão para "Reprovado" mas manter habilitado
        $botaoReprovado.removeClass('btn-primary btn-success btn-aprovado-filtro')
                      .addClass('btn-danger btn-reprovado btn-reprovado-custom aprovar_codigo')
                      .prop('disabled', false) // SEMPRE HABILITADO
                      .attr('name', 'aprovar_codigo')
                      .attr('title', 'Registro reprovado - Clique para editar')
                      .html('<span class="glyphicon glyphicon-remove"></span> Reprovado');
        
        // CSS inline no botão para forçar estilo
        $botaoReprovado[0].setAttribute('style', 
            'background-color: #dc3545 !important; ' +
            'border-color: #dc3545 !important; ' +
            'color: white !important; ' +
            'background-image: none !important; ' +
            'box-shadow: none !important;'
        );
        
        // Modificar a linha - fundo vermelho claro
        var $linhaReprovada = $botaoReprovado.closest('tr');
        $linhaReprovada.addClass('linha-reprovada')
                      .css({
                          'background-color': '#f8d7da',
                          'border-left': '4px solid #dc3545'
                      });
        
        console.log('✅ MUDANÇAS VISUAIS DE REPROVAÇÃO APLICADAS COM SUCESSO!');
        console.log('- Novo HTML do botão:', $botaoReprovado.html());
        console.log('- Novas classes do botão:', $botaoReprovado.attr('class'));
        
        // Animação visual de reprovação com borda pulsante
        $linhaReprovada.css('border-left-width', '8px').animate({
            'border-left-width': '4px'
        }, 500);
    } else {
        console.log('❌ BOTÃO NÃO ENCONTRADO PARA ID:', idAssinatura);
        console.log('🔍 LISTANDO TODOS OS BOTÕES NA TABELA:');
        $('button[data-id]').each(function(index, element) {
            var $btn = $(element);
            console.log('  - Botão ' + index + ': ID=' + $btn.data('id') + ', classes=' + $btn.attr('class') + ', texto=' + $btn.text());
        });
    }
}

/**
 * 🟢 FUNÇÃO PARA APLICAR VISUALIZAÇÃO DE REGISTROS APROVADOS
 * Aplica linha verde claro para todos os registros com limite ou valor aprovado preenchidos
 */
function aplicarVisualizacaoRegistrosAprovados() {
    // NÃO aplicar cores quando filtro "Todos" estiver ativo
    if (filtro_ativo_atual === 'todos') {
        console.log('🟢 Pulando aplicação de cores (filtro RadioTodos ativo)');
        return;
    }
    
    console.log('🟢 Aplicando visualização para registros aprovados consultando banco (valor_aprovado > 0.00)');
    
    // Garantir que o CSS existe
    if (!document.getElementById('linha-aprovada-style')) {
        var style = document.createElement('style');
        style.id = 'linha-aprovada-style';
        style.textContent = `
            .linha-aprovada {
                background-color: #d4edda !important;
                border-left: 4px solid #28a745 !important;
            }
            .linha-aprovada:hover {
                background-color: #c3e6cb !important;
            }
            .linha-aprovada td {
                background-color: inherit !important;
            }
            .btn-aprovado-filtro,
            .btn-aprovado-manual {
                background-color: #28a745 !important;
                border-color: #28a745 !important;
                color: white !important;
                background-image: none !important;
                box-shadow: none !important;
                pointer-events: auto !important;
                cursor: pointer !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Verificar registros aprovados consultando o banco (valor_aprovado > 0.00)
    var $todasLinhas = $('#tabela_assinaturas_digitais tbody tr');
    console.log('🔍 Verificando', $todasLinhas.length, 'registros no banco para aprovados...');
    
    $todasLinhas.each(function() {
        var $linha = $(this);
        var $botao = $linha.find('button[data-id]');
        
        if ($botao.length === 0) return;
        
        var idRegistro = $botao.data('id');
        
        // Consultar banco individualmente para verificar se valor_aprovado > 0.00
        $.ajax({
            url: "pages/assinaturas_digitais/assinaturas_digitais_exibe.php",
            method: "POST",
            data: {id_assinatura: idRegistro},
            dataType: "json",
            success: function(data) {
                // Debug detalhado do valor recebido
                console.log('🔍 RADIOAPROVADOS - Verificando registro:', idRegistro, '- valor_aprovado recebido:', data.valor_aprovado, '- tipo:', typeof data.valor_aprovado);
                
                // Verificação RIGOROSA do valor aprovado
                var valorAprovado = 0;
                if (data && data.valor_aprovado) {
                    // Converter valor para número, tratando vírgulas e pontos
                    var valorString = data.valor_aprovado.toString().replace(/,/g, '.'); // Trocar vírgulas por pontos
                    valorAprovado = parseFloat(valorString);
                    
                    // Se não conseguiu converter ou é NaN, considerar como 0
                    if (isNaN(valorAprovado)) {
                        valorAprovado = 0;
                    }
                }
                
                console.log('🔍 RADIOAPROVADOS - Valor processado:', valorAprovado, '- É MAIOR que 0?', valorAprovado > 0);
                
                // CONDIÇÃO RIGOROSA: APENAS valores maiores que 0 devem ser aprovados
                if (valorAprovado > 0) {
                    console.log('✅ RADIOAPROVADOS - Registro aprovado encontrado (banco):', idRegistro, '- valor_aprovado:', data.valor_aprovado, '- processado:', valorAprovado);
                    
                    // Aplicar cor verde
                    $linha.addClass('linha-aprovada')
                          .css({
                              'background-color': '#d4edda',
                              'border-left': '4px solid #28a745'
                          });
                    
                    // Botão verde "Aprovado"
                    $botao.removeClass('btn-primary btn-danger')
                          .addClass('btn-success btn-aprovado-filtro aprovar_codigo')
                          .prop('disabled', false)
                          .attr('name', 'aprovar_codigo')
                          .attr('title', 'Código aprovado: ' + (data.codigo || 'N/A') + ' - Clique para editar')
                          .css({
                              'background-color': '#28a745 !important',
                              'border-color': '#28a745 !important',
                              'color': 'white !important',
                              'pointer-events': 'auto !important',
                              'cursor': 'pointer !important'
                          })
                          .html('<span class="glyphicon glyphicon-ok"></span> Aprovado');
                    
                    console.log('✅ Cor verde aplicada para registro aprovado (filtro):', idRegistro);
                } else {
                    console.log('🔵 RADIOAPROVADOS - Registro NÃO aprovado (valor <= 0):', idRegistro, '- valor_aprovado:', data.valor_aprovado);
                }
            },
            error: function(xhr, status, error) {
                console.log('⚠️ Erro ao verificar registro aprovado (filtro):', idRegistro, '- Erro:', error);
            }
        });
    });
    
    console.log('✅ Visualização de registros aprovados via banco iniciada');
}

/**
 * 🔴 FUNÇÃO PARA APLICAR VISUALIZAÇÃO DE REGISTROS REPROVADOS
 * Aplica linha vermelha claro para todos os registros reprovados (habilitados)
 * REGRA ESPECIAL: No filtro RadioAssinados, linhas com valor_aprovado=0/null e reprovado=false ficam SEM COR
 */
function aplicarVisualizacaoRegistrosReprovados() {
    console.log('🔴 Aplicando visualização para registros reprovados');
    console.log('📊 Filtro ativo atual:', filtro_ativo_atual);
    
    // NÃO aplicar cores quando filtro "Todos" estiver ativo - EXCETO para reprovados reais
    if (filtro_ativo_atual === 'todos') {
        console.log('🔴 Filtro RadioTodos ativo - aplicando apenas reprovados REAIS');
        
        // Aplicar cor vermelha APENAS para registros com texto "Reprovado"
        var botoes_reprovados_reais = $('button:contains("Reprovado")');
        console.log('🔍 Encontrados', botoes_reprovados_reais.length, 'registros REALMENTE reprovados');
        
        botoes_reprovados_reais.each(function() {
            var $botao = $(this);
            var $linha = $botao.closest('tr');
            
            // Aplicar estilo vermelho à linha
            $linha.addClass('linha-reprovada')
                  .css({
                      'background-color': '#f8d7da',
                      'border-left': '4px solid #dc3545'
                  });
            
            // Garantir que o botão esteja com estilo correto
            $botao.addClass('btn-reprovado-filtro')
                  .css({
                      'background-color': '#dc3545 !important',
                      'border-color': '#dc3545 !important',
                      'color': 'white !important'
                  });
        });
        
        console.log('✅ Visualização de registros reprovados REAIS aplicada no RadioTodos');
        return;
    }
    
    // Garantir que o CSS existe
    if (!document.getElementById('linha-reprovada-style')) {
        var style = document.createElement('style');
        style.id = 'linha-reprovada-style';
        style.textContent = `
            .linha-reprovada {
                background-color: #f8d7da !important;
                border-left: 4px solid #dc3545 !important;
            }
            .linha-reprovada:hover {
                background-color: #f1b0b7 !important;
            }
            .linha-reprovada td {
                background-color: inherit !important;
            }
            .btn-reprovado-filtro,
            .btn-reprovado,
            .btn-reprovado-custom {
                background-color: #dc3545 !important;
                border-color: #dc3545 !important;
                color: white !important;
                background-image: none !important;
                box-shadow: none !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Aplicar mudanças visuais para todos os botões que contêm "Reprovado" (habilitados)
    var botoes_reprovados = $('button:contains("Reprovado")');
    console.log('🔍 Encontrados', botoes_reprovados.length, 'registros reprovados');
    
    botoes_reprovados.each(function(index) {
        var $botao = $(this);
        var $linha = $botao.closest('tr');
        
        console.log('🔴 Processando registro reprovado ' + (index + 1));
        
        // Garantir que o botão esteja habilitado
        $botao.prop('disabled', false);
        
        // Aplicar estilo vermelho à linha
        $linha.addClass('linha-reprovada')
              .css({
                  'background-color': '#f8d7da',
                  'border-left': '4px solid #dc3545'
              });
        
        // Garantir que o botão esteja com estilo correto
        $botao.addClass('btn-reprovado-filtro')
              .css({
                  'background-color': '#dc3545 !important',
                  'border-color': '#dc3545 !important',
                  'color': 'white !important'
              });
    });
    
    // Aplicar regras especiais dos filtros
    setTimeout(function() {
        aplicarRegraEspecialRadioAssinados();
        aplicarRegraEspecialRadioAprovados();
    }, 50);
    
    console.log('✅ Visualização de registros reprovados aplicada (com regra especial para RadioAssinados)');
}

/**
 * 🔄 FUNÇÃO ESPECÍFICA PARA APLICAR REGRA ESPECIAL DO RADIOASSINADOS
 * Remove cores de TODAS as linhas e força TODOS os botões como azuis "Aprovar" 
 * (Mostra apenas registros com valor_aprovado = 0 ou null)
 * 
 * OTIMIZAÇÃO: Inclui sistema de flags para evitar execuções múltiplas desnecessárias
 */
function aplicarRegraEspecialRadioAssinados() {
    // Só aplicar se o filtro RadioAssinados estiver ativo
    if (filtro_ativo_atual !== 'signed') {
        return;
    }
    
    // Evitar execuções múltiplas desnecessárias
    if (window.radioAssinadosProcessando) {
        console.log('🔍 RadioAssinados já está processando, pulando execução...');
        return;
    }
    
    window.radioAssinadosProcessando = true;
    console.log('🔍 APLICANDO REGRA ESPECIAL RADIOASSINADOS: Background neutro + Botões azuis "Aprovar"');
    
    // Buscar todas as linhas da tabela
    var $linhasTabela = $('#tabela_assinaturas_digitais tbody tr');
    console.log('🔍 Total de linhas encontradas:', $linhasTabela.length);
    
    $linhasTabela.each(function(index) {
        var $linha = $(this);
        var $botao = $linha.find('button[data-id]');
        
        if ($botao.length === 0) {
            return; // Pular se não tem botão
        }
        
        var idRegistro = $botao.data('id');
        var textoBotao = $botao.text().trim();
        
        console.log('🔄 APLICANDO REGRA RADIOASSINADOS: Linha ID:', idRegistro, '- Botão atual:', textoBotao);
        
        // REMOVER TODAS as cores das linhas (background neutro)
        $linha.removeClass('linha-reprovada linha-aprovada')
              .css({
                  'background-color': '',
                  'border-left': ''
              })
              .removeAttr('style');
        
        // FORÇAR TODOS os botões como azuis "Aprovar" (só aparecem registros com valor_aprovado = 0)
        $botao.removeClass('btn-danger btn-success btn-reprovado-filtro btn-aprovado-filtro btn-reprovado btn-aprovado-custom')
              .addClass('btn-primary aprovar_codigo')
              .prop('disabled', false)
              .attr('name', 'aprovar_codigo')
              .attr('title', 'Aprovar assinatura digital')
              .css({
                  'background-color': '',
                  'border-color': '',
                  'color': ''
              })
              .removeAttr('style')
              .html('<span class="glyphicon glyphicon-ok"></span> Aprovar');
        
        console.log('✅ Linha ID', idRegistro, 'configurada: BACKGROUND NEUTRO + BOTÃO AZUL "Aprovar"');
    });
    
    console.log('✅ Regra especial RadioAssinados aplicada: Background neutro + Botões azuis "Aprovar"!');
    
    // Liberar flag após processamento
    setTimeout(function() {
        window.radioAssinadosProcessando = false;
    }, 100);
    
    // Backup: Liberar flag após tempo maior caso algo dê errado
    setTimeout(function() {
        if (window.radioAssinadosProcessando) {
            console.log('⚠️ Flag radioAssinadosProcessando travada - forçando reset');
            window.radioAssinadosProcessando = false;
        }
    }, 2000);
}

/**
 * 🟢 FUNÇÃO ESPECÍFICA PARA APLICAR REGRA ESPECIAL DO RADIOAPROVADOS
 * Aplica background verde clarinho para TODAS as linhas e força TODOS os botões como azuis "Aprovar"
 * 
 * OTIMIZAÇÃO: Inclui sistema de flags para evitar execuções múltiplas desnecessárias
 */
function aplicarRegraEspecialRadioAprovados() {
    // Só aplicar se o filtro RadioAprovados estiver ativo
    if (filtro_ativo_atual !== 'approved') {
        return;
    }
    
    // Evitar execuções múltiplas desnecessárias
    if (window.radioAprovadosProcessando) {
        console.log('🟢 RadioAprovados já está processando, pulando execução...');
        return;
    }
    
    window.radioAprovadosProcessando = true;
    console.log('🟢 APLICANDO REGRA ESPECIAL RADIOAPROVADOS: Background verde (botões coloridos por outras funções)');
    
    // Buscar todas as linhas da tabela
    var $linhasTabela = $('#tabela_assinaturas_digitais tbody tr');
    console.log('🟢 Total de linhas encontradas:', $linhasTabela.length);
    
    $linhasTabela.each(function(index) {
        var $linha = $(this);
        var $botao = $linha.find('button[data-id]');
        
        if ($botao.length === 0) {
            return; // Pular se não tem botão
        }
        
        var idRegistro = $botao.data('id');
        var textoBotao = $botao.text().trim();
        
        console.log('🟢 APLICANDO BACKGROUND VERDE + BOTÃO AZUL: Linha ID:', idRegistro, '- Botão atual:', textoBotao);
        
        // Aplicar background verde clarinho para TODAS as linhas
        $linha.removeClass('linha-reprovada')
              .addClass('linha-aprovada')
              .css({
                  'background-color': '#d4edda',  // Verde clarinho
                  'border-left': '4px solid #28a745'
              });
        
        // NO RadioAprovados, NÃO forçar botões como azuis - deixar as outras funções aplicarem as cores corretas
        // Os botões serão coloridos pela função aplicarVisualizacaoRegistrosAprovados() e forcarCoresRadioAprovados()
        
        console.log('✅ Linha ID', idRegistro, 'configurada: BACKGROUND VERDE (botão será colorido por outras funções)');
    });
    
    console.log('✅ Regra especial RadioAprovados aplicada: Linhas verdes (botões coloridos por outras funções)!');
    
    // Liberar flag após processamento
    setTimeout(function() {
        window.radioAprovadosProcessando = false;
    }, 100);
    
    // Backup: Liberar flag após tempo maior caso algo dê errado
    setTimeout(function() {
        if (window.radioAprovadosProcessando) {
            console.log('⚠️ Flag radioAprovadosProcessando travada - forçando reset');
            window.radioAprovadosProcessando = false;
        }
    }, 2000);
}

/**
 * ⚪ FUNÇÃO ESPECÍFICA PARA APLICAR REGRA ESPECIAL DO RADIOTODOS
 * No filtro "Todos", mantém as cores originais de cada registro (aprovados verdes, reprovados vermelhos)
 * 
 * OTIMIZAÇÃO: Inclui sistema de flags para evitar execuções múltiplas desnecessárias
 */
function aplicarRegraEspecialRadioTodos() {
    // Só aplicar se o filtro RadioTodos estiver ativo
    if (filtro_ativo_atual !== 'todos') {
        return;
    }
    
    // Evitar execuções múltiplas desnecessárias
    if (window.radioTodosProcessando) {
        console.log('⚪ RadioTodos já está processando, pulando execução...');
        return;
    }
    
    window.radioTodosProcessando = true;
    console.log('⚪ APLICANDO REGRA ESPECIAL RADIOTODOS: Cores baseadas no status dos registros');
    
    // Aguardar um pouco para garantir que a tabela foi carregada
    setTimeout(function() {
        // Buscar todas as linhas visíveis da tabela
        var $linhasTabela = $('#tabela_assinaturas_digitais tbody tr:visible');
        console.log('⚪ Total de linhas encontradas:', $linhasTabela.length);
        
        if ($linhasTabela.length === 0) {
            console.log('⚠️ Nenhuma linha encontrada, tentando todas as linhas...');
            $linhasTabela = $('#tabela_assinaturas_digitais tbody tr');
        }
        
        var contadorProcessados = 0;
        var totalLinhas = $linhasTabela.length;
        
        // Processar cada linha individualmente
        $linhasTabela.each(function() {
            var $linha = $(this);
            var $botao = $linha.find('button[data-id]');
            
            if ($botao.length === 0) {
                contadorProcessados++;
                return;
            }
            
            var idRegistro = $botao.data('id');
            
            // Consultar status do registro no banco
            $.ajax({
                url: "pages/assinaturas_digitais/assinaturas_digitais_exibe.php",
                method: "POST",
                data: {id_assinatura: idRegistro},
                dataType: "json",
                success: function(data) {
                    // Debug COMPLETO dos dados recebidos
                    console.log('🔍 RADIOTODOS DEBUG - ID:', idRegistro, '- Dados completos:', data);
                    console.log('🔍 RADIOTODOS DEBUG - valor_aprovado:', data.valor_aprovado, '- tipo:', typeof data.valor_aprovado);
                    console.log('🔍 RADIOTODOS DEBUG - limite:', data.limite, '- tipo:', typeof data.limite);
                    console.log('🔍 RADIOTODOS DEBUG - reprovado:', data.reprovado, '- tipo:', typeof data.reprovado);
                    
                    // Primeiro, limpar estilo atual
                    $linha.removeClass('linha-reprovada linha-aprovada')
                          .removeAttr('style')
                          .css({'background-color': '', 'border-left': ''});
                    
                    // Verificar se é reprovado
                    var isReprovado = data && (data.reprovado === true || data.reprovado === 't' || data.reprovado == 1 || data.reprovado === 'true');
                    
                    // VERIFICAÇÃO SIMPLES: Registro aprovado quando qualquer valor monetário > 0
                    var valorAprovado = 0;
                    var valorLimite = 0;
                    var isAprovado = false;
                    
                    // Verificar valor_aprovado > 0
                    if (data && data.valor_aprovado !== null && data.valor_aprovado !== undefined && data.valor_aprovado !== '') {
                        var valorString = data.valor_aprovado.toString().trim().replace(/,/g, '.');
                        valorAprovado = parseFloat(valorString);
                        if (isNaN(valorAprovado)) {
                            valorAprovado = 0;
                        }
                        console.log('🔍 RADIOTODOS DEBUG - valor_aprovado processado:', valorAprovado);
                    }
                    
                    // Verificar limite > 0 (caso valor_aprovado seja 0)
                    if (data && data.limite !== null && data.limite !== undefined && data.limite !== '') {
                        var limiteString = data.limite.toString().trim().replace(/,/g, '.');
                        valorLimite = parseFloat(limiteString);
                        if (isNaN(valorLimite)) {
                            valorLimite = 0;
                        }
                        console.log('🔍 RADIOTODOS DEBUG - limite processado:', valorLimite);
                    }
                    
                    // Considerar aprovado se qualquer um dos valores for > 0
                    if (valorAprovado > 0 || valorLimite > 0) {
                        isAprovado = true;
                        console.log('🔍 RADIOTODOS DEBUG - APROVADO! valor_aprovado:', valorAprovado, '- limite:', valorLimite);
                    }
                    
                    console.log('🔍 RADIOTODOS DEBUG FINAL - isAprovado:', isAprovado, '- isReprovado:', isReprovado);
                    
                    if (isReprovado) {
                        // CASO 3: Registro reprovado - background vermelho claro, botão vermelho "Reprovado"
                        console.log('🔴 RADIOTODOS - Aplicando estilo REPROVADO:', idRegistro);
                        
                        $linha.addClass('linha-reprovada')
                              .css({
                                  'background-color': '#f8d7da',
                                  'border-left': '4px solid #dc3545'
                              });
                        
                        $botao.removeClass('btn-primary btn-success')
                              .addClass('btn-danger btn-reprovado-custom')
                              .prop('disabled', false)
                              .attr('name', 'aprovar_codigo')
                              .attr('title', 'Registro reprovado - Clique para editar')
                              .css({
                                  'background-color': '#dc3545',
                                  'border-color': '#dc3545',
                                  'color': 'white'
                              })
                              .html('<span class="glyphicon glyphicon-remove"></span> Reprovado');
                              
                    } else if (isAprovado) {
                        // CASO 2: Registro aprovado - background verde claro, botão verde "Aprovado"
                        console.log('🟢 RADIOTODOS - Aplicando estilo APROVADO:', idRegistro, '- valor:', valorAprovado);
                        
                        // Aplicar background verde na linha com !important
                        $linha.addClass('linha-aprovada')
                              .css({
                                  'background-color': '#d4edda !important',
                                  'border-left': '4px solid #28a745 !important'
                              });
                        
                        // FORÇA BRUTA: garantir que o background verde seja aplicado via atributo style direto
                        var linhaDOM = $linha[0];
                        if (linhaDOM) {
                            linhaDOM.setAttribute('style', 
                                'background-color: #d4edda !important; ' +
                                'border-left: 4px solid #28a745 !important;'
                            );
                        }
                        
                        // Aplicar estilo no botão
                        $botao.removeClass('btn-primary btn-danger btn-reprovado-custom')
                              .addClass('btn-success btn-aprovado-custom aprovar_codigo')
                              .prop('disabled', false)
                              .attr('name', 'aprovar_codigo')
                              .attr('title', 'Código aprovado - Clique para editar')
                              .css({
                                  'background-color': '#28a745 !important',
                                  'border-color': '#28a745 !important',
                                  'color': 'white !important'
                              })
                              .html('<span class="glyphicon glyphicon-ok"></span> Aprovado');
                        
                        console.log('✅ RADIOTODOS - Background e botão APROVADO aplicados para:', idRegistro);
                              
                    } else {
                        // CASO 1: Registro assinado (não aprovado/reprovado) - background sem cor, botão azul "Aprovar"
                        console.log('🔵 RADIOTODOS - Aplicando estilo ASSINADO:', idRegistro, '- valor:', valorAprovado);
                        
                        // Sem background especial (cor padrão da tabela) - forçar remoção
                        $linha.removeClass('linha-aprovada linha-reprovada')
                              .removeAttr('style')
                              .css({
                                  'background-color': '',
                                  'border-left': ''
                              });
                        
                        $botao.removeClass('btn-success btn-danger btn-aprovado-custom btn-reprovado-custom')
                              .addClass('btn-primary aprovar_codigo')
                              .prop('disabled', false)
                              .attr('name', 'aprovar_codigo')
                              .attr('title', 'Aprovar assinatura digital')
                              .removeAttr('style')
                              .html('<span class="glyphicon glyphicon-ok"></span> Aprovar');
                        
                        console.log('✅ RADIOTODOS - Estilo ASSINADO aplicado para:', idRegistro);
                    }
                    
                    contadorProcessados++;
                    
                    // Se processou todas as linhas
                    if (contadorProcessados >= totalLinhas) {
                        console.log('✅ Regra especial RadioTodos aplicada para todas as linhas:', contadorProcessados);
                        window.radioTodosProcessando = false;
                    }
                },
                error: function(xhr, status, error) {
                    console.log('⚠️ Erro ao verificar registro:', idRegistro, '- Erro:', error);
                    contadorProcessados++;
                    
                    // Se processou todas as linhas (mesmo com erro)
                    if (contadorProcessados >= totalLinhas) {
                        console.log('✅ Regra especial RadioTodos finalizada (com alguns erros):', contadorProcessados);
                        window.radioTodosProcessando = false;
                    }
                }
            });
        });
        
        // Backup: liberar flag após tempo limite
        setTimeout(function() {
            if (window.radioTodosProcessando) {
                console.log('⚠️ Flag radioTodosProcessando travada - forçando reset');
                window.radioTodosProcessando = false;
            }
        }, 5000);
        
    }, 100); // Aguardar 100ms para garantir que a tabela foi carregada
}

/**
 * 🔄 FUNÇÃO PARA RESETAR FLAGS DE PROCESSAMENTO
 * Reseta todas as flags de processamento para evitar travamentos
 */
function resetarFlagsProcessamento() {
    window.radioAssinadosProcessando = false;
    window.radioAprovadosProcessando = false;
    window.radioTodosProcessando = false;
    console.log('🔄 Flags de processamento resetadas');
}

/**
 * 🔧 FUNÇÃO DE DEBUG PARA VERIFICAR STATUS DAS FLAGS
 * Pode ser chamada manualmente pelo console: debugFlagsProcessamento()
 */
function debugFlagsProcessamento() {
    console.log('🔧 DEBUG - Status das flags de processamento:');
    console.log('  - radioAssinadosProcessando:', window.radioAssinadosProcessando);
    console.log('  - radioAprovadosProcessando:', window.radioAprovadosProcessando);
    console.log('  - radioTodosProcessando:', window.radioTodosProcessando);
    console.log('  - filtro_ativo_atual:', filtro_ativo_atual);
    console.log('  - tabela_processando:', tabela_processando);
    
    // Resetar flags se necessário
    if (window.radioAssinadosProcessando || window.radioAprovadosProcessando || window.radioTodosProcessando) {
        console.log('⚠️ Flags travadas detectadas - resetando...');
        resetarFlagsProcessamento();
    }
}

/**
 * 🚨 FUNÇÃO DE EMERGÊNCIA PARA RESETAR TODO O SISTEMA
 * Pode ser chamada manualmente pelo console em caso de travamento: resetarSistemaCompleto()
 */
function resetarSistemaCompleto() {
    console.log('🚨 RESETANDO SISTEMA COMPLETO...');
    
    // Resetar flags de processamento
    window.radioAssinadosProcessando = false;
    window.radioAprovadosProcessando = false;
    window.radioTodosProcessando = false;
    tabela_processando = false;
    
    // Resetar filtro ativo
    filtro_ativo_atual = 'signed';
    
    // Limpar timeouts pendentes
    var maxTimeoutId = setTimeout(function(){}, 0);
    for (var i = 0; i < maxTimeoutId; i++) {
        clearTimeout(i);
    }
    
    console.log('✅ Sistema resetado completamente');
    console.log('📋 Status atual:');
    console.log('  - radioAssinadosProcessando:', window.radioAssinadosProcessando);
    console.log('  - radioAprovadosProcessando:', window.radioAprovadosProcessando);
    console.log('  - radioTodosProcessando:', window.radioTodosProcessando);
    console.log('  - filtro_ativo_atual:', filtro_ativo_atual);
    console.log('  - tabela_processando:', tabela_processando);
}

/**
 * 🛡️ FUNÇÃO DE MONITORAMENTO DE SISTEMA
 * Monitora o sistema em segundo plano para detectar e resolver problemas
 */
function iniciarMonitoramentoSistema() {
    if (window.monitoramentoAtivo) {
        return; // Já está ativo
    }
    
    window.monitoramentoAtivo = true;
    console.log('🛡️ Sistema de monitoramento iniciado');
    
    // Monitorar a cada 10 segundos
    setInterval(function() {
        var problemaDetectado = false;
        
        // Verificar se flags estão travadas há muito tempo
        if (window.radioAssinadosProcessando || window.radioAprovadosProcessando || window.radioTodosProcessando) {
            if (!window.ultimoResetFlags) {
                window.ultimoResetFlags = Date.now();
            } else {
                var tempoTravado = Date.now() - window.ultimoResetFlags;
                if (tempoTravado > 30000) { // 30 segundos
                    console.log('⚠️ Flags travadas há mais de 30 segundos - resetando automaticamente');
                    resetarFlagsProcessamento();
                    window.ultimoResetFlags = Date.now();
                    problemaDetectado = true;
                }
            }
        } else {
            window.ultimoResetFlags = null;
        }
        
        // Log periódico apenas se houver problemas
        if (problemaDetectado) {
            console.log('🛡️ Monitoramento detectou e corrigiu problemas');
        }
    }, 10000); // 10 segundos
}

/**
 * 🟢 FUNÇÃO PARA FORÇAR CORES VERDES NO FILTRO RADIOAPROVADOS
 * Garantir que registros com valor_aprovado > 0.00 tenham cor verde + botão "Aprovado"
 */
function forcarCoresRadioAprovados() {
    if (filtro_ativo_atual !== 'approved') {
        console.log('⚠️ Filtro não é RadioAprovados, cancelando...');
        return;
    }
    
    console.log('🟢 FORÇANDO cores verdes para registros aprovados no RadioAprovados...');
    
    // Buscar todas as linhas da tabela
    var $linhasTabela = $('#tabela_assinaturas_digitais tbody tr');
    console.log('🔍 Verificando', $linhasTabela.length, 'linhas para aplicar cores verdes...');
    
    $linhasTabela.each(function() {
        var $linha = $(this);
        var $botao = $linha.find('button[data-id]');
        
        if ($botao.length === 0) return;
        
        var idRegistro = $botao.data('id');
        
        // Consultar banco para verificar se valor_aprovado > 0.00
        $.ajax({
            url: "pages/assinaturas_digitais/assinaturas_digitais_exibe.php",
            method: "POST",
            data: {id_assinatura: idRegistro},
            dataType: "json",
            success: function(data) {
                // Debug detalhado do valor recebido
                console.log('🔍 FORÇAR VERDE - Verificando registro:', idRegistro, '- valor_aprovado recebido:', data.valor_aprovado, '- tipo:', typeof data.valor_aprovado);
                
                // Verificação RIGOROSA do valor aprovado
                var valorAprovado = 0;
                if (data && data.valor_aprovado) {
                    // Converter valor para número, tratando vírgulas e pontos
                    var valorString = data.valor_aprovado.toString().replace(/,/g, '.'); // Trocar vírgulas por pontos
                    valorAprovado = parseFloat(valorString);
                    
                    // Se não conseguiu converter ou é NaN, considerar como 0
                    if (isNaN(valorAprovado)) {
                        valorAprovado = 0;
                    }
                }
                
                console.log('🔍 FORÇAR VERDE - Valor processado:', valorAprovado, '- É MAIOR que 0?', valorAprovado > 0);
                
                // CONDIÇÃO RIGOROSA: APENAS valores maiores que 0 devem ser aprovados
                if (valorAprovado > 0) {
                    console.log('🟢 FORÇANDO verde para registro aprovado:', idRegistro, '- valor_aprovado:', data.valor_aprovado, '- processado:', valorAprovado);
                    
                    // FORÇAR cor verde na linha
                    $linha.removeClass('linha-reprovada')
                          .addClass('linha-aprovada')
                          .css({
                              'background-color': '#d4edda !important',
                              'border-left': '4px solid #28a745 !important'
                          });
                    
                    // FORÇAR botão verde "Aprovado"
                    $botao.removeClass('btn-primary btn-danger btn-reprovado-filtro')
                          .addClass('btn-success btn-aprovado-filtro aprovar_codigo')
                          .prop('disabled', false)
                          .attr('name', 'aprovar_codigo')
                          .attr('title', 'Código aprovado: ' + (data.codigo || 'N/A') + ' - Clique para editar')
                          .css({
                              'background-color': '#28a745 !important',
                              'border-color': '#28a745 !important',
                              'color': 'white !important',
                              'background-image': 'none !important',
                              'box-shadow': 'none !important'
                          })
                          .html('<span class="glyphicon glyphicon-ok"></span> Aprovado');
                    
                    // CSS inline forçado no DOM
                    var botaoDOM = $botao[0];
                    if (botaoDOM) {
                        botaoDOM.setAttribute('style', 
                            'background-color: #28a745 !important; ' +
                            'border-color: #28a745 !important; ' +
                            'color: white !important; ' +
                            'background-image: none !important; ' +
                            'box-shadow: none !important;'
                        );
                    }
                    
                    console.log('✅ FORÇADO: Registro aprovado ID', idRegistro, 'com cores verdes');
                } else {
                    console.log('🔵 FORÇAR VERDE - Registro NÃO aprovado (valor <= 0):', idRegistro, '- valor_aprovado:', data.valor_aprovado, '- processado:', valorAprovado);
                }
            },
            error: function(xhr, status, error) {
                console.log('⚠️ Erro ao verificar registro (forçar verde):', idRegistro, '- Erro:', error);
            }
        });
    });
    
    console.log('✅ Processo de forçar cores verdes iniciado para RadioAprovados');
}

/**
 * 🧪 FUNÇÃO DE TESTE PARA VERIFICAR REGRA ESPECIAL RADIOASSINADOS
 * Pode ser chamada manualmente pelo console: testarRegraRadioAssinados()
 */
function testarRegraRadioAssinados() {
    console.log('🧪 TESTE MANUAL: Verificando regra especial RadioAssinados');
    console.log('🔍 Filtro ativo atual:', filtro_ativo_atual);
    
    // Forçar filtro como signed para teste
    var filtroOriginal = filtro_ativo_atual;
    filtro_ativo_atual = 'signed';
    
    // Aplicar regra especial
    aplicarRegraEspecialRadioAssinados();
    
    // Restaurar filtro original
    filtro_ativo_atual = filtroOriginal;
    
    console.log('🧪 Teste concluído. Verifique TODAS as linhas na tabela:');
    console.log('🧪 - Apenas registros com valor_aprovado = 0 ou null devem estar visíveis');
    console.log('🧪 - TODAS as linhas devem ter background NEUTRO (sem cor verde/vermelha)');
    console.log('🧪 - TODOS os botões devem ser AZUIS com texto "Aprovar"');
}

/**
 * 🧪 FUNÇÃO DE TESTE PARA VERIFICAR REGRA ESPECIAL RADIOAPROVADOS
 * Pode ser chamada manualmente pelo console: testarRegraRadioAprovados()
 */
function testarRegraRadioAprovados() {
    console.log('🧪 TESTE MANUAL: Verificando regra especial RadioAprovados');
    console.log('🔍 Filtro ativo atual:', filtro_ativo_atual);
    
    // Forçar filtro como approved para teste
    var filtroOriginal = filtro_ativo_atual;
    filtro_ativo_atual = 'approved';
    
    // Aplicar regra especial
    aplicarRegraEspecialRadioAprovados();
    
    // Restaurar filtro original
    filtro_ativo_atual = filtroOriginal;
    
    console.log('🧪 Teste concluído. Verifique TODAS as linhas na tabela:');
    console.log('🧪 - Apenas registros com valor aprovado > 0 devem estar visíveis');
    console.log('🧪 - Linhas devem ter background VERDE CLARINHO');
    console.log('🧪 - Botões com valor_aprovado > 0 devem ser VERDES com texto "Aprovado"');
    console.log('🧪 - Outros botões devem ser AZUIS com texto "Aprovar"');
}

/**
 * 🧪 FUNÇÃO DE TESTE PARA VERIFICAR REGRA ESPECIAL RADIOTODOS
 * Pode ser chamada manualmente pelo console: testarRegraRadioTodos()
 */
function testarRegraRadioTodos() {
    console.log('🧪 TESTE MANUAL: Verificando regra especial RadioTodos');
    console.log('🔍 Filtro ativo atual:', filtro_ativo_atual);
    
    // Forçar filtro como todos para teste
    var filtroOriginal = filtro_ativo_atual;
    filtro_ativo_atual = 'todos';
    
    // Aplicar regra especial
    aplicarRegraEspecialRadioTodos();
    
    // Restaurar filtro original
    filtro_ativo_atual = filtroOriginal;
    
    console.log('🧪 Teste concluído. Verifique TODAS as linhas na tabela:');
    console.log('🧪 - Todos os registros devem estar visíveis');
    console.log('🧪 - Registros APROVADOS devem ter background VERDE + botão verde "Aprovado"');
    console.log('🧪 - Registros REPROVADOS devem ter background VERMELHO + botão vermelho "Reprovado"');
    console.log('🧪 - Registros NORMAIS devem ter background NEUTRO + botão azul "Aprovar"');
}

/**
 * 🧪 FUNÇÃO DE TESTE PARA VERIFICAR CORES FORÇADAS NO RADIOAPROVADOS
 * Pode ser chamada manualmente pelo console: testarForcaCoresRadioAprovados()
 */
function testarForcaCoresRadioAprovados() {
    console.log('🧪 TESTE MANUAL: Verificando cores forçadas RadioAprovados');
    console.log('🔍 Filtro ativo atual:', filtro_ativo_atual);
    
    // Forçar filtro como approved para teste
    var filtroOriginal = filtro_ativo_atual;
    filtro_ativo_atual = 'approved';
    
    // Aplicar função de força
    forcarCoresRadioAprovados();
    
    // Restaurar filtro original
    filtro_ativo_atual = filtroOriginal;
    
    console.log('🧪 Teste concluído. Verifique registros com valor_aprovado > 0.00:');
    console.log('🧪 - Devem ter background VERDE + botão verde "Aprovado"');
    console.log('🧪 - Registros sem valor aprovado devem ficar inalterados');
}

/**
 * 🔄 FUNÇÃO PARA REMOVER COR DA LINHA REPROVADA
 * Remove background vermelho e volta ao estado normal
 */
function removerCorLinhaReprovada(idAssinatura) {
    console.log('🔄 Removendo cor da linha reprovada para ID:', idAssinatura);
    
    // Buscar botão específico por ID
    var $botaoReprovado = $('button[data-id="' + idAssinatura + '"]');
    console.log('🔍 Buscando botão reprovado com ID:', idAssinatura);
    console.log('🔍 Botões encontrados:', $botaoReprovado.length);
    
    if ($botaoReprovado.length > 0) {
        console.log('✅ Botão encontrado - removendo cores');
        
        // Modificar o botão para estado normal (azul "Aprovar")
        $botaoReprovado.removeClass('btn-danger btn-reprovado-filtro btn-reprovado btn-reprovado-custom')
                      .addClass('btn-primary aprovar_codigo')
                      .prop('disabled', false)
                      .attr('name', 'aprovar_codigo')
                      .attr('title', 'Aprovar assinatura digital')
                      .css({
                          'background-color': '',
                          'border-color': '',
                          'color': '',
                          'background-image': '',
                          'box-shadow': ''
                      })
                      .removeAttr('style')
                      .html('<span class="glyphicon glyphicon-ok"></span> Aprovar');
        
        // Modificar a linha para estado normal
        var $linhaReprovada = $botaoReprovado.closest('tr');
        $linhaReprovada.removeClass('linha-reprovada')
                      .css({
                          'background-color': '',
                          'border-left': ''
                      })
                      .removeAttr('style');
        
        console.log('✅ Cor removida com sucesso - linha voltou ao estado normal');
        
        // Animação visual para destacar a mudança
        $linhaReprovada.css('background-color', '#e6f3ff').animate({
            'background-color': 'transparent'
        }, 1000);
        
    } else {
        console.log('⚠️ Botão não encontrado para ID:', idAssinatura);
        
        // Tentar encontrar após recarregar tabela
        setTimeout(function() {
            console.log('🔄 Tentando novamente após delay...');
            var $botaoTentativa = $('button[data-id="' + idAssinatura + '"]');
            if ($botaoTentativa.length > 0) {
                removerCorLinhaReprovada(idAssinatura);
            }
        }, 1000);
    }
}

/**
 * 🔄 FUNÇÃO PARA REAPLICAR MUDANÇAS VISUAIS
 * Reaplica linha verde e botão verde para registros aprovados
 */
function reaplicarMudancasVisuais() {
    if (registrosAprovados.size === 0) {
        console.log('ℹ️ Nenhum registro aprovado para reaplicar');
        return;
    }
    
    console.log('🔄 Reaplicando mudanças para', registrosAprovados.size, 'registros aprovados');
    
    // Garantir que o CSS existe
    if (!document.getElementById('linha-aprovada-style')) {
        var style = document.createElement('style');
        style.id = 'linha-aprovada-style';
        style.textContent = `
            .linha-aprovada {
                background-color: #d4edda !important;
                border-left: 4px solid #28a745 !important;
            }
            .linha-aprovada:hover {
                background-color: #c3e6cb !important;
            }
            .linha-aprovada td {
                background-color: inherit !important;
            }
            .btn-aprovado-custom {
                background-color: #28a745 !important;
                border-color: #28a745 !important;
                color: white !important;
            }
            /* CSS ULTRA-ESPECÍFICO para forçar mudança do botão */
            button.btn.btn-xs.btn-success.btn-aprovado-custom,
            button.btn.btn-xs.btn-success.btn-aprovado-custom:hover,
            button.btn.btn-xs.btn-success.btn-aprovado-custom:focus,
            button.btn.btn-xs.btn-success.btn-aprovado-custom:active,
            button.btn.btn-xs.btn-success.btn-aprovado-custom:disabled {
                background-color: #28a745 !important;
                background-image: none !important;
                border-color: #28a745 !important;
                color: white !important;
                box-shadow: none !important;
                outline: none !important;
            }
        `;
        document.head.appendChild(style);
        console.log('✅ CSS dinâmico recriado');
    }
    
    // Procurar cada registro aprovado na tabela atual
    registrosAprovados.forEach(function(idAprovado) {
        var codigoVinculado = codigosVinculados.get(idAprovado);
        console.log('🔍 Procurando registro aprovado ID:', idAprovado, 'Código:', codigoVinculado);
        
        // Buscar botão com esse ID
        var $botaoAprovado = $('button[data-id="' + idAprovado + '"]');
        
        if ($botaoAprovado.length > 0) {
            console.log('✅ Registro encontrado na tabela atual - reaplicando mudanças');
            
            // Reaplicar mudanças no botão (habilitado)
            $botaoAprovado.removeClass('btn-primary vincular_codigo')
                         .addClass('btn-success btn-aprovado-custom aprovar_codigo')
                         .prop('disabled', false)
                         .attr('name', 'aprovar_codigo')
                         .attr('title', 'Código aprovado: ' + codigoVinculado + ' - Clique para editar')
                         .css({
                             'pointer-events': 'auto !important',
                             'cursor': 'pointer !important'
                         })
                         .html('<span class="glyphicon glyphicon-ok"></span> Aprovado');
            
            // CSS inline no botão
            var botaoDOM = $botaoAprovado[0];
            if (botaoDOM) {
                botaoDOM.setAttribute('style', 
                    'background-color: #28a745 !important; ' +
                    'border-color: #28a745 !important; ' +
                    'color: white !important; ' +
                    'background-image: none !important; ' +
                    'box-shadow: none !important;'
                );
            }
            
            // Reaplicar mudanças na linha
            var $linhaAprovada = $botaoAprovado.closest('tr');
            $linhaAprovada.addClass('linha-aprovada')
                          .css({
                              'background-color': '#d4edda',
                              'border-left': '4px solid #28a745'
                          });
            
            console.log('✅ Mudanças visuais reaplicadas para registro', idAprovado);
        } else {
            console.log('⚠️ Registro aprovado não encontrado na tabela atual:', idAprovado);
        }
    });
}

// Função auxiliar para formatação
function pad (str, max) {
    str = str.toString();
    str = str.length < max ? pad("0" + str, max) : str; // zero à esquerda
    str = str.length > max ? str.substr(0,max) : str; // máximo de caracteres
    return str;
}





// Evento para aprovar assinatura digital  
$(document).on('click', '.aprovar_codigo', function(e) {
    // Prevenir propagação dupla
    e.preventDefault();
    e.stopPropagation();
    
    // PROTEÇÃO: Verificar se modais estão bloqueados
    if (window.modalBlockTimeout) {
        console.log('🚫 Ação bloqueada temporariamente - modal em transição');
        return;
    }
    
    var id_assinatura = $(this).data('id');
    
    if (!id_assinatura) {
        console.error('❌ ID da assinatura não encontrado!');
        Swal.fire({
            title: "Erro!",
            text: "ID da assinatura não encontrado. Verifique se o botão foi criado corretamente.",
            icon: "error"
        });
        return;
    }

    console.log('✅ Abrindo modal para ID:', id_assinatura);
    $("#rotulo_assinatura").html("Aprovando");
    
    $.ajax({
        url: "pages/assinaturas_digitais/assinaturas_digitais_exibe.php",
        method: "POST",
        data: {id_assinatura : id_assinatura},
        dataType: "json",
        success:function (data) {
            console.log('✅ Dados carregados para modal, ID:', data.id);
            // $.fn.modal.Constructor.prototype.enforceFocus = function() {}; // Comentado para evitar conflitos
            
            // Verificar se é um botão "Aprovado" (btn-aprovado-filtro) para configurar campos editáveis
            var $botaoClicado = $('button[data-id="' + id_assinatura + '"]');
            var ehBotaoAprovado = $botaoClicado.hasClass('btn-aprovado-filtro');
            
            console.log('🔍 Análise do botão clicado:');
            console.log('  - ID:', id_assinatura);
            console.log('  - Classes:', $botaoClicado.attr('class'));
            console.log('  - É btn-aprovado-filtro?', ehBotaoAprovado);
            
            // Se é botão aprovado, tentar obter data_pgto da tabela também
            var dataPgtoTabela = '';
            if (ehBotaoAprovado) {
                var $linhaTabela = $botaoClicado.closest('tr');
                if ($linhaTabela.length > 0 && typeof tabela_assinaturas_digitais !== 'undefined') {
                    try {
                        var dadosLinha = tabela_assinaturas_digitais.row($linhaTabela).data();
                        if (dadosLinha && dadosLinha.data_pgto) {
                            dataPgtoTabela = dadosLinha.data_pgto;
                            console.log('📅 Data PGTO encontrada na tabela:', dataPgtoTabela);
                        }
                    } catch (e) {
                        console.log('⚠️ Erro ao obter data_pgto da tabela:', e);
                    }
                }
            }
            
            // Configurar campos como somente leitura
            $("#C_nome_assinatura").prop('readonly', true);
            $("#C_codigo_assinatura").prop('readonly', true);
            $("#C_celular_assinatura").prop('readonly', true);
            $("#C_email_assinatura").prop('readonly', true);
            $("#C_cpf_assinatura").prop('readonly', false); // CPF habilitado para edição
            $("#C_limite_assinatura").prop('readonly', true);
            $("#C_valor_aprovado_assinatura").prop('readonly', false); // Este é editável
            $("#C_has_signed_readonly_assinatura").prop('readonly', true); // Campo "Assinado digitalmente" sempre somente leitura
            
            // Campo Data Pgto: sempre editável no modal "Aprovando"
            $("#C_data_pgto_assinatura").prop('readonly', false);
            console.log('🔍 Modal "Aprovando" - campo "Data Pgto" configurado como editável');
            
            // Configurar múltiplos event listeners para preenchimento automático de data+hora
            $("#C_data_pgto_assinatura").off('change.dataHoraAtual input.dataHoraAtual blur.dataHoraAtual');
            
            function preencherDataSeHoje() {
                // 🔧 VERIFICAR SE NORMALIZAÇÃO ESTÁ DESABILITADA TEMPORARIAMENTE
                if (window.desabilitarNormalizacaoTemporaria) {
                    console.log('⏸️ NORMALIZAÇÃO DESABILITADA TEMPORARIAMENTE - Pulando processamento');
                    return;
                }
                
                var $campo = $("#C_data_pgto_assinatura");
                var dataSelecionada = $campo.val();
                
                console.log('🔍 DEBUG AUTOMÁTICO DATA (CAMPO DATE):');
                console.log('  - Campo encontrado:', $campo.length > 0);
                console.log('  - Campo visível:', $campo.is(':visible'));
                console.log('  - Valor bruto:', '"' + dataSelecionada + '"');
                console.log('  - Valor após trim:', dataSelecionada ? ('"' + dataSelecionada.trim() + '"') : 'undefined/null');
                console.log('  - Comprimento do valor:', dataSelecionada ? dataSelecionada.length : 0);
                console.log('  - IMPORTANTE: Campo agora é do tipo DATE (apenas data, sem hora)');
                
                if (dataSelecionada && typeof dataSelecionada === 'string' && dataSelecionada.trim() !== '') {
                    // Extrair apenas a parte da data (compatível com formatos ' ' e 'T')
                    var apenasData = dataSelecionada.trim();
                    
                    // Suporte para formato ISO (YYYY-MM-DDTHH:mm:ss) 
                    if (apenasData.indexOf('T') !== -1) {
                        apenasData = apenasData.split('T')[0];
                    }
                    // Suporte para formato padrão (YYYY-MM-DD HH:mm:ss)
                    else if (apenasData.indexOf(' ') !== -1) {
                        apenasData = apenasData.split(' ')[0];
                    }
                    // Se não tem separadores, assume que é apenas data (YYYY-MM-DD)
                    
                    // Verificar se a data selecionada é hoje
                    var hoje = new Date();
                    var dataHoje = hoje.getFullYear() + '-' + 
                                  String(hoje.getMonth() + 1).padStart(2, '0') + '-' + 
                                  String(hoje.getDate()).padStart(2, '0');
                    
                    console.log('📅 COMPARAÇÃO AUTOMÁTICA (CAMPO DATE):');
                    console.log('  - Valor original:', '"' + dataSelecionada + '"');
                    console.log('  - Data extraída: "' + apenasData + '"');
                    console.log('  - Data hoje: "' + dataHoje + '"');
                    console.log('  - São iguais?', apenasData === dataHoje);
                    
                    if (apenasData === dataHoje) {
                        // Para campo DATE, garantir que seja APENAS a data (sem hora)
                        var eApenasData = (dataSelecionada && typeof dataSelecionada === 'string') ? /^\d{4}-\d{2}-\d{2}$/.test(dataSelecionada.trim()) : false;
                        var temHora = (dataSelecionada && typeof dataSelecionada === 'string') ? dataSelecionada.indexOf(':') !== -1 : false;
                        
                        console.log('📅 Verificação para campo DATE:');
                        console.log('  - É apenas data (YYYY-MM-DD)?', eApenasData);
                        console.log('  - Tem hora (:)?', temHora);
                        console.log('  - Deve normalizar para apenas data?', !eApenasData || temHora);
                        
                        if (!eApenasData || temHora) {
                            // Normalizar para apenas data (formato DATE)
                            console.log('🔧 NORMALIZANDO PARA CAMPO DATE:');
                            console.log('  - Data normalizada:', apenasData);
                            
                            $campo.val(apenasData);
                            
                            // Forçar trigger do evento para que outros listeners detectem a mudança
                            $campo.trigger('input');
                            
                            // Verificar se realmente foi definido
                            setTimeout(function() {
                                var valorAposDefinir = $campo.val();
                                console.log('✅ Valor após normalizar (verificação):', '"' + valorAposDefinir + '"');
                            }, 50);
                            
                            console.log('📅 ✅ SUCESSO! Data normalizada para campo DATE:', apenasData);
                            
                        } else {
                            console.log('ℹ️ Data atual já está no formato correto para campo DATE:', dataSelecionada);
                        }
                    } else {
                        console.log('ℹ️ Data selecionada não é hoje. Extraída: "' + apenasData + '", Hoje: "' + dataHoje + '"');
                        
                        // Mesmo não sendo hoje, garantir formato DATE
                        if (dataSelecionada !== apenasData) {
                            console.log('🔧 Normalizando data para formato DATE:', apenasData);
                            $campo.val(apenasData);
                        }
                    }
                } else {
                    console.log('⚠️ Campo vazio ou sem valor válido para preenchimento automático');
                }
            }
            
            // MÚLTIPLOS EVENT LISTENERS PARA CAPTURAR TODAS AS MUDANÇAS (CAMPO DATE)
            $("#C_data_pgto_assinatura")
                .off('.dataAtual') // Remover listeners antigos primeiro
                .on('change.dataAtual', function() {
                    console.log('🔔 Evento CHANGE detectado (campo DATE)');
                    setTimeout(preencherDataSeHoje, 100);
                })
                .on('input.dataAtual', function() {
                    console.log('🔔 Evento INPUT detectado (campo DATE)');
                    setTimeout(preencherDataSeHoje, 100);
                })
                .on('blur.dataAtual', function() {
                    console.log('🔔 Evento BLUR detectado (campo DATE)');
                    setTimeout(preencherDataSeHoje, 100);
                })
                .on('keyup.dataAtual', function() {
                    console.log('🔔 Evento KEYUP detectado (campo DATE)');
                    setTimeout(preencherDataSeHoje, 200);
                })
                .on('paste.dataAtual', function() {
                    console.log('🔔 Evento PASTE detectado (campo DATE)');
                    setTimeout(preencherDataSeHoje, 200);
                })
                .on('focus.dataAtual', function() {
                    console.log('🔔 Evento FOCUS detectado (campo DATE)');
                    // Não executar no focus, apenas no blur/change
                });
            
            // Event listeners para datepickers específicos
            if (typeof $.fn.datepicker !== 'undefined') {
                $("#C_data_pgto_assinatura").on('changeDate', function() {
                    console.log('🔔 Evento DATEPICKER changeDate detectado (campo DATE)');
                    setTimeout(preencherDataSeHoje, 150);
                });
                console.log('📅 Event listener para jQuery datepicker adicionado (campo DATE)');
            }
            
            if (typeof $.fn.bsDatepicker !== 'undefined') {
                $("#C_data_pgto_assinatura").on('changeDate.bs.datepicker', function() {
                    console.log('🔔 Evento BOOTSTRAP-DATEPICKER detectado (campo DATE)');
                    setTimeout(preencherDataSeHoje, 150);
                });
                console.log('📅 Event listener para bootstrap-datepicker adicionado (campo DATE)');
            }
            
            // Event listener para Bootstrap 3/4 datepicker
            if (typeof $.fn.datetimepicker !== 'undefined') {
                $("#C_data_pgto_assinatura").on('dp.change', function() {
                    console.log('🔔 Evento BOOTSTRAP-DATETIMEPICKER detectado (campo DATE)');
                    setTimeout(preencherDataSeHoje, 150);
                });
                console.log('📅 Event listener para bootstrap-datetimepicker adicionado (campo DATE)');
            }
            
            console.log('✅ TODOS OS EVENT LISTENERS configurados para campo DATE (apenas data)');
            
            // Função de teste manual (disponível no console)
            window.testarPreenchimentoData = function() {
                console.log('🧪 TESTE MANUAL: Preenchendo data atual para campo DATE...');
                var hoje = new Date();
                var dataHoje = hoje.getFullYear() + '-' + 
                              String(hoje.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(hoje.getDate()).padStart(2, '0');
                
                var $campo = $("#C_data_pgto_assinatura");
                console.log('🔍 Campo encontrado:', $campo.length > 0);
                console.log('🔍 Campo visível:', $campo.is(':visible'));
                console.log('📅 IMPORTANTE: Campo é do tipo DATE (apenas data, sem hora)');
                
                // Definir valor e aguardar antes de processar
                $campo.val(dataHoje);
                console.log('✅ Data atual definida para campo DATE:', dataHoje);
                console.log('🔍 Valor no campo após definir:', $campo.val());
                
                // Aguardar um pouco e então processar
                setTimeout(function() {
                    console.log('⏰ Processando normalização para campo DATE...');
                    preencherDataSeHoje();
                    console.log('🔍 Valor final normalizado:', $campo.val());
                }, 100);
            };
            
            // Função para forçar preenchimento de DATA HOJE (campo DATE)
            window.forcarDataHoje = function() {
                console.log('🚀 FORÇANDO preenchimento da data de hoje (campo DATE)...');
                
                var hoje = new Date();
                var dataHoje = hoje.getFullYear() + '-' + 
                              String(hoje.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(hoje.getDate()).padStart(2, '0');
                
                var $campo = $("#C_data_pgto_assinatura");
                
                console.log('🔧 Estado antes do preenchimento:');
                console.log('  - Campo existe:', $campo.length > 0);
                console.log('  - Campo visível:', $campo.is(':visible'));
                console.log('  - Valor atual:', '"' + $campo.val() + '"');
                console.log('  - IMPORTANTE: Campo é do tipo DATE (apenas data)');
                
                $campo.val(dataHoje);
                
                // Forçar trigger dos eventos
                $campo.trigger('input').trigger('change');
                
                console.log('✅ FORÇADO MANUALMENTE: Data de hoje definida:', dataHoje);
                console.log('🔍 Verificação após preenchimento:', '"' + $campo.val() + '"');
                
                // Mostrar notificação
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Data de Hoje Forçada!',
                        text: 'Data atual preenchida (campo DATE): ' + dataHoje,
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
                
                return dataHoje;
            };
            
            // Função LEGACY para compatibilidade (agora para campo DATE)
            window.forcarDataHoraHoje = function() {
                console.log('⚠️ FUNÇÃO LEGACY: Campo agora é DATE, redirecionando...');
                return forcarDataHoje();
            };
            
            // Função para preencher apenas data de hoje (sem hora)
            window.preencherApenasDataHoje = function() {
                console.log('📅 PREENCHENDO apenas data de hoje...');
                
                var hoje = new Date();
                var dataHoje = hoje.getFullYear() + '-' + 
                              String(hoje.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(hoje.getDate()).padStart(2, '0');
                
                var $campo = $("#C_data_pgto_assinatura");
                
                console.log('🔧 Estado antes:');
                console.log('  - Valor antes:', '"' + $campo.val() + '"');
                
                $campo.val(dataHoje);
                
                console.log('✅ Data de hoje preenchida:', dataHoje);
                console.log('🔍 Verificação imediata:', '"' + $campo.val() + '"');
                
                // Verificar múltiplas vezes se o valor está sendo mantido
                setTimeout(function() {
                    var valorApos100ms = $campo.val();
                    console.log('🔍 Valor após 100ms:', '"' + valorApos100ms + '"');
                    
                    if (valorApos100ms === dataHoje) {
                        console.log('✅ Valor mantido - disparando eventos...');
                        $campo.trigger('input');
                        $campo.trigger('change');
                    } else {
                        console.log('❌ Valor foi alterado/limpo! Repreenchendo...');
                        $campo.val(dataHoje);
                        setTimeout(function() {
                            $campo.trigger('input');
                            $campo.trigger('change');
                        }, 100);
                    }
                }, 100);
                
                return dataHoje;
            };
            

            
            // EXECUÇÃO INICIAL: Verificar se campo já tem data de hoje para preenchimento automático
            setTimeout(function() {
                console.log('🔧 SISTEMA DE DATA TOTALMENTE CONFIGURADO (CAMPO DATE)!');
                console.log('📋 FUNÇÕES DISPONÍVEIS NO CONSOLE:');
                console.log('  1️⃣ testarPreenchimentoData() - Testa sistema automático (campo DATE)');
                console.log('  2️⃣ forcarDataHoje() - Força data de hoje (campo DATE)');
                console.log('  3️⃣ preencherApenasDataHoje() - Preenche apenas data (teste automático)');
                console.log('  4️⃣ debugUltimoRegistro() - Verifica dados no banco');
                console.log('  📅 IMPORTANTE: Campo agora é do tipo DATE (apenas data, sem hora)');
                
                // Verificação inicial do campo carregado
                var $campo = $("#C_data_pgto_assinatura");
                var valorInicial = $campo.val();
                
                console.log('🔍 VERIFICAÇÃO INICIAL DO CAMPO:');
                console.log('  - Campo existe:', $campo.length > 0);
                console.log('  - Valor inicial:', '"' + valorInicial + '"');
                
                if (valorInicial && typeof valorInicial === 'string' && valorInicial.trim() !== '') {
                    console.log('📅 Campo já possui valor - verificando se precisa de normalização para DATE...');
                    preencherDataSeHoje();
                } else {
                    console.log('📝 Campo vazio - aguardando entrada do usuário');
                }
                
                console.log('🎯 DICA: Digite/selecione qualquer data no campo - será automaticamente normalizada para formato DATE!');
                
                // Monitoramento adicional por polling (fallback)
                var ultimoValor = '';
                var intervalMonitoramento = setInterval(function() {
                    var valorAtual = $("#C_data_pgto_assinatura").val();
                    
                    // Só verificar se modal ainda está aberto
                    if (!$("#ModalEditaAssinaturaDigital").is(':visible')) {
                        clearInterval(intervalMonitoramento);
                        console.log('🚪 Modal fechado - parando monitoramento');
                        return;
                    }
                    
                    // Validar se valorAtual não é undefined ou null antes de usar .trim()
                    if (valorAtual !== undefined && valorAtual !== null && valorAtual !== ultimoValor && valorAtual.trim() !== '') {
                        console.log('👀 MONITORAMENTO: Campo mudou de "' + ultimoValor + '" para "' + valorAtual + '"');
                        ultimoValor = valorAtual;
                        
                        // Aguardar um pouco antes de verificar (para não interferir com datepickers)
                        setTimeout(preencherDataSeHoje, 100);
                    }
                }, 1000); // Verificar a cada 1 segundo
                
                console.log('👀 Monitoramento automático iniciado como fallback');
            }, 500);
            
            // Ocultar campos não necessários
            $("#C_cel_informado_assinatura").closest('.row').hide();
            $("#C_event_assinatura").closest('.row').hide();
            $("#C_doc_token_assinatura").closest('.row').hide();
            $("#C_doc_name_assinatura").closest('.row').hide();
            $("#C_signed_at_assinatura").closest('.row').hide();
            
            // PROTEÇÃO: Desabilitar TODOS os outros modais enquanto este estiver aberto
            $('.modal:not(#ModalEditaAssinaturaDigital):not(#ModalBuscaAssociadoAssinatura)').off('show.bs.modal').on('show.bs.modal', function(e) {
                console.log('🚫 IMPEDINDO abertura de modal antigo:', e.target.id);
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            });
            
            // Mostrar campo reprovar no modal de aprovação
            $("#C_reprovar").closest('.row').show();
            
            // Verificar se é um registro reprovado pelo botão clicado
            var ehReprovado = $botaoClicado.hasClass('btn-reprovado') || $botaoClicado.text().includes('Reprovado');
            
            // Garantir que campo reprovar esteja sempre visível
            $("#C_reprovar").closest('.row').show();
            console.log('🔍 Campo "Reprovar" garantido como visível');
            
            // Carregar valor do campo reprovar do banco de dados
            if (data.reprovado === true || data.reprovado === 't' || data.reprovado == 1) {
                $("#C_reprovar").val("1");
                console.log('✅ Campo reprovar carregado como "Sim" do banco de dados');
            } else if (data.reprovado === false || data.reprovado === 'f' || data.reprovado == 0) {
                $("#C_reprovar").val("0");
                console.log('✅ Campo reprovar carregado como "Não" do banco de dados');
            } else if (ehReprovado) {
                // Fallback: Se já está reprovado pelo botão, marcar como "Sim"
                $("#C_reprovar").val("1");
                console.log('✅ Campo reprovar definido como "Sim" baseado no botão reprovado');
            } else {
                // Default: marcar como "Não"
                $("#C_reprovar").val("0");
                console.log('✅ Campo reprovar definido como "Não" (padrão)');
            }
            
            // Campo C_has_signed foi removido do modal
            
            // Garantir que a linha com Limite, Valor Aprovado, Data Pgto e "Assinado digitalmente" esteja visível
            var rowLimiteValor = $("#C_limite_assinatura").closest('.row');
            rowLimiteValor.show();
            
            // Garantir que a linha com Chave PIX esteja visível
            var rowChavePix = $("#C_chave_pix_assinatura").closest('.row');
            rowChavePix.show();
            
            // Garantir que o campo "Assinado digitalmente" esteja sempre visível e preenchido
            $("#C_has_signed_readonly_assinatura").show();
            $("#C_has_signed_readonly_assinatura").closest('.form-group').show();
            $("#C_has_signed_readonly_assinatura").val("Sim");
            console.log('✅ Campo "Assinado digitalmente" configurado como visível e preenchido com "Sim"');
            
            // Debug específico para chave_pix
            console.log('🔧 Debug Chave PIX:');
            console.log('- Campo existe:', $("#C_chave_pix_assinatura").length > 0);
            console.log('- Campo visível:', $("#C_chave_pix_assinatura").is(':visible'));
            console.log('- Pai (.form-group) visível:', $("#C_chave_pix_assinatura").closest('.form-group').is(':visible'));
            console.log('- Pai (.row) visível:', rowChavePix.is(':visible'));
            console.log('- CSS display:', $("#C_chave_pix_assinatura").css('display'));
            console.log('- Parent CSS display:', $("#C_chave_pix_assinatura").parent().css('display'));
            
            // Forçar exibição do campo e seus pais
            $("#C_chave_pix_assinatura").show();
            $("#C_chave_pix_assinatura").closest('.form-group').show();
            $("#C_chave_pix_assinatura").closest('.col-xs-3').show();
            rowChavePix.show();
            
            // Preencher campos com dados carregados
            $("#C_id_assinatura").val(data.id);
            $("#C_codigo_assinatura").val(data.codigo);
            $("#C_nome_assinatura").val(data.nome);
            $("#C_celular_assinatura").val(data.celular);
            $("#C_email_assinatura").val(data.email);
            $("#C_cpf_assinatura").val(data.cpf);
            $("#C_limite_assinatura").val(data.limite);
            
            // VALOR APROVADO: Verificar se é botão btn-aprovado-filtro para carregar valor da tabela
            if (ehBotaoAprovado) {
                console.log('🔍 Botão btn-aprovado-filtro detectado - buscando valor aprovado da tabela...');
                
                // Buscar a linha da tabela que contém este botão
                var $linhaTabela = $botaoClicado.closest('tr');
                var valorAprovadoTabela = '';
                
                if ($linhaTabela.length > 0) {
                    // Tentar obter dados da linha via DataTables
                    if (typeof tabela_assinaturas_digitais !== 'undefined' && tabela_assinaturas_digitais) {
                        try {
                            var dadosLinha = tabela_assinaturas_digitais.row($linhaTabela).data();
                            if (dadosLinha && dadosLinha.valor_aprovado) {
                                valorAprovadoTabela = dadosLinha.valor_aprovado;
                                console.log('✅ Valor aprovado encontrado via DataTables:', valorAprovadoTabela);
                            }
                        } catch (e) {
                            console.log('⚠️ Erro ao obter dados via DataTables:', e);
                        }
                    }
                    
                    // Fallback: buscar valor diretamente da coluna HTML (se DataTables falhar)
                    if (!valorAprovadoTabela) {
                        // A coluna "VALOR APROVADO" está na posição visível 7 (considerando colunas ocultas)
                        var $celulaValor = $linhaTabela.find('td').eq(7);
                        if ($celulaValor.length > 0) {
                            valorAprovadoTabela = $celulaValor.text().trim();
                            console.log('✅ Valor aprovado encontrado via HTML na posição 7:', valorAprovadoTabela);
                        } else {
                            console.log('⚠️ Célula de valor aprovado não encontrada na posição 7');
                            // Debug: mostrar conteúdo de todas as células visíveis
                            console.log('🔍 Debug - Conteúdo de todas as células da linha:');
                            $linhaTabela.find('td').each(function(index) {
                                var textoColuna = $(this).text().trim();
                                console.log('  Posição ' + index + ':', '"' + textoColuna + '"');
                            });
                            
                            // Tentar buscar em todas as células por padrão monetário
                            $linhaTabela.find('td').each(function(index) {
                                var textoColuna = $(this).text().trim();
                                // Verificar se parece com valor monetário (contém vírgula e números)
                                if (textoColuna.match(/^\d+[,\.]\d{2}$/) || textoColuna.match(/^\d{1,3}(\.\d{3})*[,\.]\d{2}$/)) {
                                    valorAprovadoTabela = textoColuna;
                                    console.log('✅ Valor aprovado encontrado na posição', index, ':', valorAprovadoTabela);
                                    return false; // Parar loop
                                }
                            });
                        }
                    }
                }
                
                // Verificar se o valor encontrado é maior que 0
                if (valorAprovadoTabela) {
                    // Converter para número para verificar se > 0
                    var valorNumerico = parseFloat(valorAprovadoTabela.replace(/\./g, '').replace(',', '.'));
                    
                    console.log('🔍 Análise do valor aprovado:');
                    console.log('  - Valor da tabela:', valorAprovadoTabela);
                    console.log('  - Valor numérico:', valorNumerico);
                    console.log('  - É maior que 0?', valorNumerico > 0);
                    
                    if (valorNumerico > 0) {
                        // Usar valor da tabela (já formatado)
                        $("#C_valor_aprovado_assinatura").val(valorAprovadoTabela);
                        console.log('✅ Campo preenchido com valor da tabela:', valorAprovadoTabela);
                        
                        // Mostrar notificação
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Valor Carregado!',
                                text: 'Valor aprovado carregado da tabela: ' + valorAprovadoTabela,
                                icon: 'info',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        }
                    } else {
                        // Usar valor do banco de dados
                        $("#C_valor_aprovado_assinatura").val(formatarMoeda(data.valor_aprovado));
                        console.log('ℹ️ Valor da tabela <= 0, usando valor do banco:', data.valor_aprovado);
                    }
                } else {
                    // Usar valor do banco de dados
                    $("#C_valor_aprovado_assinatura").val(formatarMoeda(data.valor_aprovado));
                    console.log('ℹ️ Valor não encontrado na tabela, usando valor do banco:', data.valor_aprovado);
                }
            } else {
                // Não é botão aprovado, usar valor do banco normalmente
                $("#C_valor_aprovado_assinatura").val(formatarMoeda(data.valor_aprovado));
                console.log('ℹ️ Botão normal, usando valor do banco:', data.valor_aprovado);
            }
            
            // PREPARAR DATA PGTO - verificar qual usar (banco ou tabela)
            var dataPgtoFinal = data.data_pgto;
            
            // Fallback: Se data do banco está vazia mas há data na tabela, usar da tabela
            if ((!dataPgtoFinal || (typeof dataPgtoFinal === 'string' && dataPgtoFinal.trim() === '')) && dataPgtoTabela && typeof dataPgtoTabela === 'string' && dataPgtoTabela.trim() !== '') {
                dataPgtoFinal = dataPgtoTabela;
                console.log('📅 Usando data PGTO da tabela como fallback:', dataPgtoFinal);
            }
            
            // CARREGAR DATA PGTO no campo
            if (dataPgtoFinal && typeof dataPgtoFinal === 'string' && dataPgtoFinal.trim() !== '') {
                console.log('📅 Data PGTO encontrada:', dataPgtoFinal);
                
                // 🔧 DESABILITAR TEMPORARIAMENTE O SISTEMA DE NORMALIZAÇÃO
                // Para evitar que interfira com o carregamento inicial
                window.desabilitarNormalizacaoTemporaria = true;
                console.log('🔧 Sistema de normalização desabilitado temporariamente');
                
                // Converter formato se necessário
                var dataFormatada = dataPgtoFinal;
                
                // Se data está no formato brasileiro (dd/mm/yyyy hh:mm:ss), converter para ISO
                if (dataFormatada.match(/^\d{2}\/\d{2}\/\d{4}/)) {
                    // Formato: 15/01/2025 14:30:00 -> 2025-01-15T14:30
                    var partes = dataFormatada.split(' ');
                    var partesData = partes[0].split('/');
                    var hora = partes[1] ? partes[1].substring(0, 5) : '00:00'; // Apenas HH:mm
                    
                    dataFormatada = partesData[2] + '-' + partesData[1] + '-' + partesData[0] + 'T' + hora;
                    console.log('📅 Data convertida de BR para ISO:', dataPgtoFinal, '→', dataFormatada);
                }
                
                // Para campo DATE, usar apenas a parte da data (sem hora)
                var dataParaCampoDate = dataFormatada.split('T')[0]; // Pegar apenas YYYY-MM-DD
                
                console.log('📅 Convertendo para campo DATE (apenas data):');
                console.log('  - Data com hora:', dataFormatada);
                console.log('  - Data sem hora:', dataParaCampoDate);
                
                $("#C_data_pgto_assinatura").val(dataParaCampoDate);
                
                console.log('✅ Campo data_pgto preenchido com (DATE):', dataParaCampoDate);
                
                // Reabilitar sistema de normalização após um pequeno delay
                setTimeout(function() {
                    window.desabilitarNormalizacaoTemporaria = false;
                    console.log('✅ Sistema de normalização reabilitado');
                }, 2000);
                
                // Se é botão aprovado, destacar que a data veio do banco
                if (ehBotaoAprovado) {
                    console.log('🎯 Botão btn-aprovado-filtro: Data de pagamento carregada do banco!');
                    
                    // Mostrar notificação discreta que a data foi carregada
                    if (typeof Swal !== 'undefined') {
                        setTimeout(function() {
                            var origem = (dataPgtoFinal === data.data_pgto) ? 'banco de dados' : 'tabela';
                            Swal.fire({
                                title: 'Data de Pagamento Carregada',
                                text: 'Campo preenchido automaticamente com dados do ' + origem + ': ' + dataPgtoFinal,
                                icon: 'info',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }, 500);
                    }
                }
            } else {
                console.log('📅 Nenhuma data PGTO encontrada no banco, campo ficará vazio');
                $("#C_data_pgto_assinatura").val('');
            }
            
            $("#C_chave_pix_assinatura").val(data.chave_pix || '');
            
            // GARANTIR QUE CAMPO DATA_PGTO TENHA ATRIBUTO NAME CORRETO
            var $campoDataPgto = $("#C_data_pgto_assinatura");
            if (!$campoDataPgto.attr('name')) {
                $campoDataPgto.attr('name', 'C_data_pgto_assinatura');
                console.log('✅ Atributo name adicionado ao campo C_data_pgto_assinatura');
            } else {
                console.log('✅ Campo C_data_pgto_assinatura já possui name:', $campoDataPgto.attr('name'));
            }
            
            console.log('🔍 Debug - Dados carregados:', {
                id: data.id,
                codigo: data.codigo,
                nome: data.nome,
                data_pgto_banco: data.data_pgto,
                data_pgto_tabela: dataPgtoTabela,
                data_pgto_final: dataPgtoFinal,
                chave_pix: data.chave_pix,
                reprovado: data.reprovado,
                eh_botao_aprovado: ehBotaoAprovado,
                campo_chave_pix_valor: $("#C_chave_pix_assinatura").val(),
                campo_chave_pix_existe: $("#C_chave_pix_assinatura").length > 0,
                campo_chave_pix_visivel: $("#C_chave_pix_assinatura").is(':visible'),
                campo_reprovar_valor: $("#C_reprovar").val(),
                campo_reprovar_visivel: $("#C_reprovar").closest('.row').is(':visible'),
                campo_data_pgto_editavel: !$("#C_data_pgto_assinatura").prop('readonly'),
                campo_data_pgto_valor: $("#C_data_pgto_assinatura").val(),
                campo_data_pgto_tem_valor: (dataPgtoFinal && dataPgtoFinal.trim() !== '')
            });
            
            // ⚠️ DEBUG ESPECÍFICO PARA PROBLEMA DA DATA NÃO APARECER
            console.log('🚨 DIAGNÓSTICO ESPECÍFICO - DATA NÃO APARECE:');
            console.log('1️⃣ Data bruta do banco:', '"' + (data.data_pgto || 'VAZIO/NULL') + '"');
            console.log('2️⃣ Data da tabela:', '"' + (dataPgtoTabela || 'VAZIO/NULL') + '"');
            console.log('3️⃣ Data final escolhida:', '"' + (dataPgtoFinal || 'VAZIO/NULL') + '"');
            console.log('4️⃣ Campo C_data_pgto_assinatura existe?', $("#C_data_pgto_assinatura").length > 0);
            console.log('5️⃣ Campo é visível?', $("#C_data_pgto_assinatura").is(':visible'));
            console.log('6️⃣ Valor atual do campo:', '"' + $("#C_data_pgto_assinatura").val() + '"');
            
            // Se a data está vazia, investigar mais a fundo
            if (!dataPgtoFinal || (typeof dataPgtoFinal === 'string' && dataPgtoFinal.trim() === '')) {
                console.log('❌ PROBLEMA IDENTIFICADO: Data está vazia!');
                console.log('🔍 Possíveis causas:');
                console.log('   - Campo data_pgto está NULL no banco de dados');
                console.log('   - Campo data_pgto está vazio na tabela');
                console.log('   - Erro na consulta do banco');
                console.log('💡 Solução: Verifique se o registro tem data_pgto preenchida no banco');
                
                // Função para testar manualmente
                window.testarDataManual = function() {
                    var dataHoje = new Date().toISOString().slice(0, 16);
                    $("#C_data_pgto_assinatura").val(dataHoje);
                    console.log('✅ Data teste adicionada:', dataHoje);
                    console.log('🔍 Valor do campo agora:', $("#C_data_pgto_assinatura").val());
                };
                console.log('🧪 Para testar, execute: testarDataManual()');
            } else {
                console.log('✅ Data encontrada, mas campo não foi preenchido. Investigando...');
                
                // Verificar se há conflito de timing
                setTimeout(function() {
                    var valorAposTimeout = $("#C_data_pgto_assinatura").val();
                    console.log('🔍 Valor do campo após 1 segundo:', '"' + valorAposTimeout + '"');
                    
                    if (!valorAposTimeout || valorAposTimeout.trim() === '') {
                        console.log('❌ PROBLEMA: Campo continua vazio após timeout!');
                        console.log('🔧 Tentando forçar preenchimento...');
                        $("#C_data_pgto_assinatura").val(dataPgtoFinal);
                        $("#C_data_pgto_assinatura").trigger('input').trigger('change');
                        console.log('🔄 Valor após forçar:', '"' + $("#C_data_pgto_assinatura").val() + '"');
                    }
                                 }, 1000);
            }
            
            // 🆘 VERIFICAÇÃO AUTOMÁTICA DE PROBLEMA DE DATA
            // Aumentar tempo para permitir que o sistema de desabilitação funcione
            setTimeout(function() {
                var valorFinalCampo = $("#C_data_pgto_assinatura").val();
                
                if (!valorFinalCampo || valorFinalCampo.trim() === '') {
                    console.log('🆘 PROBLEMA DETECTADO AUTOMATICAMENTE:');
                    console.log('   - Data não apareceu no campo C_data_pgto_assinatura');
                    console.log('   - Data do banco:', '"' + (data.data_pgto || 'VAZIO') + '"');
                    console.log('');
                    console.log('🔧 SOLUÇÕES RÁPIDAS:');
                    console.log('   - Execute: forcarCarregamentoDataModal()');
                    console.log('   - Ou execute: debugDataPgtoProblema(' + data.id + ')');
                    console.log('   - Ou execute: testarCarregamentoDataRapido()');
                    console.log('   - Ou execute: ajudaDebugDataPgto()');
                    
                    // Criar função específica para este ID
                    window.debugEsteRegistro = function() {
                        debugDataPgtoProblema(data.id);
                    };
                    window.forcarEsteRegistro = function() {
                        forcarCarregamentoDataModal();
                    };
                    window.testarEsteRegistro = function() {
                        testarCarregamentoDataRapido();
                    };
                    
                    console.log('');
                    console.log('🚀 FUNÇÕES ESPECÍFICAS PARA ESTE REGISTRO:');
                    console.log('   - debugEsteRegistro()');
                    console.log('   - forcarEsteRegistro()');
                    console.log('   - testarEsteRegistro()');
                    
                    // Comentário: Segunda mensagem de "Campo Data Pgto Vazio" removida conforme solicitado
                } else {
                    console.log('✅ Campo Data Pgto carregado com sucesso:', valorFinalCampo);
                }
            }, 3000); // Aumentado para 3 segundos para permitir que o sistema funcione
            
            // Fechar apenas modais específicos, não todos
            $('.modal:not(#ModalEditaAssinaturaDigital)').modal('hide');
            
            // Limpar backdrops de forma mais segura
            setTimeout(function() {
                if ($('.modal:visible').length <= 1) {
                    $('.modal-backdrop').remove();
                }
            }, 50);
            
            // Verificar se modais estão bloqueados temporariamente
            if (window.modalBlockTimeout) {
                console.log('🚫 Modal bloqueado temporariamente - aguardando...');
                setTimeout(function() {
                    $("#ModalEditaAssinaturaDigital").modal("show");
                }, 600);
            } else {
                $("#ModalEditaAssinaturaDigital").modal("show");
            }
            
            // Verificar se existe Bootstrap modal
            if (typeof $.fn.modal === 'undefined') {
                console.error('❌ Bootstrap modal não está carregado!');
                $('#ModalEditaAssinaturaDigital').show();
            }
            
            // Verificar se o modal abriu corretamente
            setTimeout(function() {
                var modalAberto = $('#ModalEditaAssinaturaDigital').hasClass('in') || 
                                 $('#ModalEditaAssinaturaDigital').is(':visible') || 
                                 $('#ModalEditaAssinaturaDigital').hasClass('show');
                
                if (!modalAberto) {
                    console.warn('⚠️ Modal não abriu automaticamente, forçando abertura...');
                    $('#ModalEditaAssinaturaDigital').show().addClass('in show').css({
                        'display': 'block',
                        'opacity': '1'
                    });
                }
            }, 300);
            
            // Verificação adicional após modal ser exibido
            setTimeout(function() {
                console.log('🕐 Verificação pós-modal:');
                console.log('- Campo chave_pix visível após modal:', $("#C_chave_pix_assinatura").is(':visible'));
                console.log('- Valor do campo chave_pix:', $("#C_chave_pix_assinatura").val());
                
                // Forçar visibilidade novamente se necessário
                if (!$("#C_chave_pix_assinatura").is(':visible')) {
                    console.log('⚠️ Campo não visível, forçando exibição...');
                    $("#C_chave_pix_assinatura").show();
                    $("#C_chave_pix_assinatura").closest('.form-group').show();
                    $("#C_chave_pix_assinatura").closest('.col-xs-3').show();
                    $("#C_chave_pix_assinatura").closest('.row').show();
                }
            }, 100);
            
            $('#operation').val("Update");
            
            // Buscar dados do associado na tabela "associado" por CPF
            if (data.cpf && data.cpf.trim() !== '') {
                console.log('🔍 Buscando associado por CPF:', data.cpf);
                $.ajax({
                    url: "pages/assinaturas_digitais/buscar_associado_por_cpf.php",
                    method: "POST",
                    data: {cpf: data.cpf},
                    dataType: "json",
                    success: function(response) {
                        console.log('📋 Resposta completa da consulta:', response);
                        if (response.success) {
                            // Atualizar código e limite com dados da tabela "associado"
                            console.log('✅ Atualizando campos:');
                            console.log('  - Código anterior:', $("#C_codigo_assinatura").val());
                            console.log('  - Código novo:', response.codigo);
                            console.log('  - Limite anterior:', $("#C_limite_assinatura").val());
                            console.log('  - Limite novo:', response.limite);
                            
                            $("#C_codigo_assinatura").val(response.codigo);
                            $("#C_limite_assinatura").val(response.limite);
                            
                            // Verificar se os valores foram aplicados
                            console.log('✅ Valores após atualização:');
                            console.log('  - Código campo:', $("#C_codigo_assinatura").val());
                            console.log('  - Limite campo:', $("#C_limite_assinatura").val());
                            
                            // Mostrar notificação de sucesso
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Dados Atualizados!',
                                    text: `Código: ${response.codigo}\nLimite: ${response.limite}`,
                                    icon: 'success',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                            }
                        } else {
                            console.warn('⚠️ Associado não encontrado:', response);
                            // Mostrar notificação de aviso
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Associado não encontrado',
                                    text: response.message,
                                    icon: 'warning',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Erro na requisição:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText
                        });
                        
                        // Mostrar notificação de erro
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Erro na consulta',
                                text: 'Não foi possível buscar dados do associado',
                                icon: 'error',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    }
                });
            } else {
                console.warn('⚠️ CPF não informado ou vazio:', data.cpf);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Erro ao carregar dados:', error);
            
            Swal.fire({
                title: "Erro de Comunicação!",
                text: "Erro ao carregar dados da assinatura: " + error,
                icon: "error"
            });
        }
    });
});

// Evento para vincular código do associado (mantido para compatibilidade)
$(document).on('click', '.vincular_codigo', function() {
    var $botao = $(this);
    var id_registro = $botao.data('id');
    var cpf = $botao.data('cpf');
    var codigo_atual = $botao.data('codigo-atual') || '';
    
    if (!id_registro || !cpf) {
        Swal.fire({
            title: "Erro!",
            text: "Dados insuficientes para vincular o código.",
            icon: "error"
        });
        return;
    }
    
    // Preparar mensagem de confirmação
    var codigo_info = '';
    if (codigo_atual && codigo_atual.trim() !== '') {
        codigo_info = `<p><strong>Código atual:</strong> ${codigo_atual} <small>(temporário do webhook)</small></p>`;
    }
    
    // Confirmar ação
    Swal.fire({
        title: 'Vincular Código do Associado',
        html: `
            <div style="text-align: left; padding: 10px;">
                <p><strong>ID do Registro:</strong> ${id_registro}</p>
                <p><strong>CPF:</strong> ${cpf}</p>
                ${codigo_info}
                <br>
                <p>Deseja buscar o código definitivo do associado na base de dados e atualizar este registro?</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<span class="glyphicon glyphicon-link"></span> Vincular',
        cancelButtonText: '<span class="glyphicon glyphicon-remove"></span> Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return new Promise((resolve) => {
                $.ajax({
                    url: "pages/assinaturas_digitais/vincular_codigo_associado.php",
                    method: "POST",
                    data: {
                        'cpf': cpf,
                        'id_registro': id_registro
                    },
                    dataType: "json",
                    success: function(response) {
                        resolve(response);
                    },
                    error: function(xhr, status, error) {
                        resolve({
                            status: 'erro',
                            mensagem: 'Erro de comunicação: ' + error
                        });
                    }
                });
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            var response = result.value;
            
            if (response.status === 'sucesso') {
                // TOCAR SOM DE BEEP DE SUCESSO
                //playNotificationBeep();
                
                // Preparar informações adicionais baseadas na operação
                var operacao_info = '';
                if (response.dados.operacao === 'atualização' && response.dados.codigo_anterior) {
                    operacao_info = `<p><strong>Código anterior:</strong> ${response.dados.codigo_anterior} <small>(temporário)</small></p>`;
                }
                
                // Informações sobre ajuste de código para evitar duplicação
                var duplicacao_info = '';
                if (response.dados.codigo_original && response.dados.codigo_original !== response.dados.codigo_vinculado) {
                    duplicacao_info = `
                        <hr style="margin: 10px 0;">
                        <p><strong>Código original:</strong> ${response.dados.codigo_original}</p>
                        <p><small>⚠️ Código ajustado para evitar duplicação (tentativas: ${response.dados.tentativas || 1})</small></p>
                    `;
                }
                
                Swal.fire({
                    title: "Sucesso!",
                    html: `
                        <div style="text-align: left; padding: 10px;">
                            <p><strong>Operação:</strong> ${response.dados.operacao || 'vinculação'}</p>
                            ${operacao_info}
                            <p><strong>Código vinculado:</strong> ${response.dados.codigo_vinculado}</p>
                            <p><strong>Nome do associado:</strong> ${response.dados.nome_associado}</p>
                            <p><strong>CPF:</strong> ${response.dados.cpf}</p>
                            ${duplicacao_info}
                        </div>
                    `,
                    icon: "success",
                    timer: duplicacao_info ? 6000 : 4000, // Mais tempo se houve ajuste
                    showConfirmButton: true
                });
                
                // Aplicar mudanças visuais IMEDIATAMENTE
                console.log('🎯 Iniciando atualização visual do botão e linha...');
                console.log('🔍 Botão jQuery objeto:', $botao);
                console.log('🔍 Botão encontrado:', $botao.length > 0);
                console.log('🔍 Classes atuais do botão:', $botao.attr('class'));
                console.log('🔍 HTML atual do botão:', $botao.html());
                
                // Adicionar CSS dinâmico PRIMEIRO se não existir
                if (!document.getElementById('linha-aprovada-style')) {
                    var style = document.createElement('style');
                    style.id = 'linha-aprovada-style';
                    style.textContent = `
                        .linha-aprovada {
                            background-color: #d4edda !important;
                            border-left: 4px solid #28a745 !important;
                        }
                        .linha-aprovada:hover {
                            background-color: #c3e6cb !important;
                        }
                        .linha-aprovada td {
                            background-color: inherit !important;
                        }
                        .btn-aprovado-custom {
                            background-color: #28a745 !important;
                            border-color: #28a745 !important;
                            color: white !important;
                        }
                        /* CSS ULTRA-ESPECÍFICO para forçar mudança do botão */
                        button.btn.btn-xs.btn-success.btn-aprovado-custom,
                        button.btn.btn-xs.btn-success.btn-aprovado-custom:hover,
                        button.btn.btn-xs.btn-success.btn-aprovado-custom:focus,
                        button.btn.btn-xs.btn-success.btn-aprovado-custom:active,
                        button.btn.btn-xs.btn-success.btn-aprovado-custom:disabled {
                            background-color: #28a745 !important;
                            background-image: none !important;
                            border-color: #28a745 !important;
                            color: white !important;
                            box-shadow: none !important;
                            outline: none !important;
                        }
                    `;
                    document.head.appendChild(style);
                    console.log('✅ CSS dinâmico adicionado');
                } else {
                    console.log('ℹ️ CSS dinâmico já existe');
                }
                
                // ABORDAGEM ROBUSTA: Múltiplas tentativas para atualizar o botão
                console.log('🔧 PASSO 1: Removendo classes antigas...');
                $botao.removeClass('btn-primary vincular_codigo');
                console.log('✅ Classes removidas. Classes restantes:', $botao.attr('class'));
                
                console.log('🔧 PASSO 2: Adicionando novas classes...');
                $botao.addClass('btn-success btn-aprovado-custom');
                console.log('✅ Classes adicionadas. Classes atuais:', $botao.attr('class'));
                
                console.log('🔧 PASSO 3: Desabilitando botão...');
                $botao.prop('disabled', true);
                console.log('✅ Botão desabilitado:', $botao.prop('disabled'));
                
                console.log('🔧 PASSO 4: Atualizando HTML...');
                var novoHtml = '<span class="glyphicon glyphicon-ok"></span> Aprovado';
                $botao.html(novoHtml);
                console.log('✅ HTML atualizado para:', $botao.html());
                
                console.log('🔧 PASSO 5: Atualizando atributos...');
                $botao.attr('title', 'Código aprovado: ' + response.dados.codigo_vinculado);
                $botao.off('click'); // Remover evento de clique
                console.log('✅ Atributos atualizados');
                
                // FALLBACK: CSS inline ULTRA-ROBUSTO
                console.log('🔧 PASSO 6: Aplicando CSS inline ULTRA-ROBUSTO...');
                
                // Método 1: jQuery CSS
                $botao.css({
                    'background-color': '#28a745',
                    'border-color': '#28a745', 
                    'color': 'white',
                    'background-image': 'none',
                    'box-shadow': 'none'
                });
                
                // Método 2: setAttribute direto no DOM
                var botaoDOM = $botao[0];
                if (botaoDOM) {
                    botaoDOM.setAttribute('style', 
                        'background-color: #28a745 !important; ' +
                        'border-color: #28a745 !important; ' +
                        'color: white !important; ' +
                        'background-image: none !important; ' +
                        'box-shadow: none !important;'
                    );
                    console.log('✅ CSS direto no DOM aplicado');
                }
                
                console.log('✅ CSS inline aplicado ao botão');
                
                // Verificação final IMEDIATA
                console.log('🔍 VERIFICAÇÃO FINAL IMEDIATA:');
                console.log('  - Classes finais:', $botao.attr('class'));
                console.log('  - HTML final:', $botao.html());
                console.log('  - Disabled:', $botao.prop('disabled'));
                console.log('  - Background color:', $botao.css('background-color'));
                console.log('  - Style attribute:', $botao.attr('style'));
                
                // VERIFICAÇÃO TARDIA para detectar sobrescrita
                setTimeout(function() {
                    console.log('🔍 VERIFICAÇÃO APÓS 1 SEGUNDO:');
                    console.log('  - Classes após 1s:', $botao.attr('class'));
                    console.log('  - HTML após 1s:', $botao.html()); 
                    console.log('  - Background após 1s:', $botao.css('background-color'));
                    console.log('  - Style após 1s:', $botao.attr('style'));
                    
                    // Se ainda não está verde, FORÇAR novamente
                    var bgColor = $botao.css('background-color');
                    if (bgColor !== 'rgb(40, 167, 69)' && bgColor !== '#28a745') {
                        console.log('🚨 BOTÃO FOI SOBRESCRITO! Aplicando FORÇA BRUTA...');
                        
                        // FORÇA BRUTA: Recriar o botão inteiramente  
                        var novoHTML = '<button type="button" class="btn btn-success btn-xs btn-aprovado-custom" disabled ' +
                                      'style="background-color: #28a745 !important; border-color: #28a745 !important; color: white !important; background-image: none !important;" ' +
                                      'title="Código aprovado: ' + response.dados.codigo_vinculado + '">' +
                                      '<span class="glyphicon glyphicon-ok"></span> Aprovado</button>';
                        
                        $botao.replaceWith(novoHTML);
                        console.log('✅ Botão recriado com FORÇA BRUTA');
                    } else {
                        console.log('✅ Botão manteve as alterações!');
                    }
                }, 1000);
                
                // Atualizar tooltip
                $botao.tooltip('destroy').tooltip();
                
                // Adicionar background verde leve na linha toda
                var $linha = $botao.closest('tr');
                console.log('🔍 Linha encontrada:', $linha.length > 0);
                $linha.addClass('linha-aprovada');
                
                // Forçar aplicação do CSS na linha
                $linha.css({
                    'background-color': '#d4edda',
                    'border-left': '4px solid #28a745'
                });
                
                console.log('✅ Classe "linha-aprovada" e CSS inline aplicados à linha');
                
                // 💾 SALVAR registro como aprovado para persistência
                registrosAprovados.add(id_registro);
                codigosVinculados.set(id_registro, response.dados.codigo_vinculado);
                salvarRegistrosAprovados(); // Persistir no localStorage
                console.log('💾 Registro salvo como aprovado:', id_registro, 'Código:', response.dados.codigo_vinculado);
                console.log('📋 Registros aprovados total:', registrosAprovados.size);
                
                // Também atualizar a coluna código na mesma linha
                var row = tabela_assinaturas_digitais.row($botao.closest('tr'));
                var rowData = row.data();
                if (rowData) {
                    rowData.codigo = response.dados.codigo_vinculado;
                    row.data(rowData).draw(false);
                }
                
                // Não recarregar a tabela automaticamente para preservar mudanças visuais
                // setTimeout(function() {
                //     console.log('Atualizando tabela para detectar códigos duplicados...');
                //     if (tabela_assinaturas_digitais && tabela_assinaturas_digitais.ajax) {
                //         tabela_assinaturas_digitais.ajax.reload(null, false);
                //     }
                // }, 2000);
                
            } else if (response.status === 'nao_encontrado') {
                Swal.fire({
                    title: "Associado não encontrado",
                    html: `
                        <div style="text-align: left; padding: 10px;">
                            <p><strong>CPF pesquisado:</strong> ${response.cpf}</p>
                            <p>Não foi encontrado nenhum associado na base de dados com este CPF.</p>
                            <p>Verifique se o CPF está correto ou se o associado está cadastrado no sistema.</p>
                        </div>
                    `,
                    icon: "warning"
                });
                
            } else {
                Swal.fire({
                    title: "Erro!",
                    text: response.mensagem || "Erro desconhecido ao vincular código.",
                    icon: "error"
                });
            }
        }
    });
});

// Evento para botão "Preencher com data/hora atual" no campo Data Pgto
$(document).on('click', '#btnDataHojeDataPgto', function(e) {
    // Prevenir propagação dupla e comportamento padrão
    e.preventDefault();
    e.stopPropagation();
    
    var agora = new Date();
    
    // Ajustar para o fuso horário local
    var offset = agora.getTimezoneOffset();
    agora = new Date(agora.getTime() - (offset * 60000));
    
    // Formatear para datetime-local (YYYY-MM-DDTHH:mm)
    var dataFormatada = agora.toISOString().slice(0, 16);
    
    // Garantir que o campo existe e forçar o valor
    var $campo = $('#C_data_pgto_assinatura');
    if ($campo.length > 0) {
        console.log('📅 BOTÃO DATA/HORA ATUAL: Desabilitando normalização temporariamente');
        
        // 🔧 DESABILITAR TEMPORARIAMENTE A NORMALIZAÇÃO AUTOMÁTICA
        window.desabilitarNormalizacaoTemporaria = true;
        
        // Preencher o campo
        $campo.val(dataFormatada);
        
        console.log('📅 Data/hora atual preenchida no campo data_pgto:', dataFormatada);
        console.log('📅 Valor confirmado no campo:', $campo.val());
        
        // 🔧 REABILITAR NORMALIZAÇÃO APÓS 2 SEGUNDOS
        setTimeout(function() {
            window.desabilitarNormalizacaoTemporaria = false;
            console.log('📅 BOTÃO DATA/HORA ATUAL: Normalização reabilitada');
        }, 2000);
        
        // Feedback visual
        $('#btnDataHojeDataPgto').addClass('btn-success').removeClass('btn-default');
        setTimeout(function() {
            $('#btnDataHojeDataPgto').addClass('btn-default').removeClass('btn-success');
        }, 1000);
    } else {
        console.error('❌ Campo C_data_pgto_assinatura não encontrado!');
    }
});

// Evento para excluir (caso necessário)
$(document).on('click','.btnexcluir',function () {
    var data_row = tabela_assinaturas_digitais.row($(this).closest('tr')).data();
    var id_assinatura = data_row.id;
    var nome_assinatura = data_row.nome;
    var codigo_assinatura = data_row.codigo;
    
    Swal.fire({
        title: 'Confirma a exclusão da assinatura digital?',
        html: '<table style="width: 100%; margin: 10px 0; table-layout: fixed;"><tr><th style="text-align: right;padding: 8px;background-color: #f8f9fa; width: 80px;">ID:</th><td style="background-color: #f8f9fa; padding: 8px; word-wrap: break-word;"><b>' + id_assinatura + '</b></td></tr>' +
            '<tr><th style="text-align: right;padding: 8px; width: 80px;">CÓDIGO:</th><td style="padding: 8px; word-wrap: break-word; overflow-wrap: break-word;"><b>' + codigo_assinatura + '</b></td></tr>' +
            '<tr><th style="text-align: right;padding: 8px;background-color: #f8f9fa; width: 80px;">NOME:</th><td style="background-color: #f8f9fa; padding: 8px; word-wrap: break-word;"><b>' + nome_assinatura + '</b></td></tr></table>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#ffc107',
        confirmButtonText: 'Sim',
        cancelButtonText: 'Não',
        reverseButtons: true,
        width: '500px'
    }).then((result) => {
        if (result.isConfirmed) {
            waitingDialog.show('Excluindo, aguarde ...');
            $.ajax({
                url: "pages/assinaturas_digitais/assinaturas_digitais_excluir.php",
                method: "POST",
                dataType: "json",
                data: {"id_assinatura": id_assinatura},
                success: function (data) {
                    if (data.Resultado === "excluido") {
                        waitingDialog.hide();
                        Swal.fire({
                            title: 'Sucesso!',
                            text: 'Excluído com sucesso!',
                            icon: 'success',
                            confirmButtonColor: '#28a745',
                            confirmButtonText: 'Ok'
                        }).then(() => {
                            tabela_assinaturas_digitais.ajax.reload();
                        });
                    } else {
                        waitingDialog.hide();
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Não foi possível excluir',
                            icon: 'error',
                            confirmButtonColor: '#dc3545',
                            confirmButtonText: 'Ok'
                        });
                    }
                }
            });
        }
    });
});

// ===== FUNCIONALIDADES DE ATUALIZAÇÃO AUTOMÁTICA =====

/**
 * Inicializar sistema completo de atualização automática
 */
function initAutoUpdate() {
    console.log('Inicializando sistema de auto-atualização...');
    
    // Configurar indicador visual
    addAutoUpdateIndicator();
    
    // Iniciar polling automático
    startAutoUpdate();
    
    // Atualizar indicadores de status
    updateAutoUpdateIndicator(true);
    
    console.log('Sistema de auto-atualização inicializado com sucesso');
}

/**
 * Iniciar sistema de atualização automática
 */
function startAutoUpdate() {
    console.log('Iniciando atualização automática...');
    lastUpdateCheck = new Date().toISOString().slice(0, 19).replace('T', ' ');
    
    // Limpar intervalo anterior se existir
    if (autoUpdateInterval) {
        clearInterval(autoUpdateInterval);
    }
    
    // Configurar intervalo de verificação
    autoUpdateInterval = setInterval(function() {
        if (isAutoUpdateEnabled) {
            console.log('⏰ Timer executando - verificando novos dados...');
            checkForNewData();
        } else {
            console.log('⏸️ Auto-update desabilitado, pulando verificação...');
        }
    }, autoUpdateFrequency);
    
    // Adicionar indicador visual
    addAutoUpdateIndicator();
}

/**
 * Parar sistema de atualização automática
 */
function stopAutoUpdate() {
    console.log('Parando atualização automática...');
    isAutoUpdateEnabled = false;
    if (autoUpdateInterval) {
        clearInterval(autoUpdateInterval);
    }
    updateAutoUpdateIndicator(false);
}

/**
 * Verificar se há novos dados
 */
function checkForNewData() {
    console.log('🔍 Verificando novos dados... (lastUpdateCheck:', lastUpdateCheck, ')');
    $.ajax({
        url: "pages/assinaturas_digitais/check_new_data.php",
        method: "POST",
        data: {
            last_check: lastUpdateCheck,
            has_signed: true
        },
        dataType: "json",
        timeout: 10000, // 10 segundos de timeout
        success: function(response) {
            console.log('✅ Resposta recebida - Verificação de novos dados:', response);
            
            if (response.error) {
                console.error('Erro ao verificar novos dados:', response.message);
                return;
            }
            
            // Atualizar timestamp da última verificação
            if (response.latest_update) {
                lastUpdateCheck = response.latest_update;
            }
            
            // Se há novos dados, atualizar a tabela
            if (response.has_new_data) {
                console.log('Novos dados encontrados! Atualizando tabela...');
                
                // Mostrar notificação
                showNewDataNotification(response.new_records_count || 1);
                
                // Atualizar DataTable apenas se existir e não estiver em processo de carregamento
                // Não mudar automaticamente para "RadioTodos" - manter o filtro atual selecionado
                if (tabela_assinaturas_digitais && tabela_assinaturas_digitais.ajax) {
                    try {
                        tabela_assinaturas_digitais.ajax.reload(null, false); // false = manter paginação atual
                    } catch (e) {
                        console.error('Erro ao recarregar DataTable:', e);
                        // Tentar recriar a tabela se houver erro
                        setTimeout(function() {
                            var situacao_atual = $('input[name="RadioSituacao"]:checked').val() || "signed";
                                filtra_assinaturas_digitais(situacao_atual, divisao);
                        }, 1000);
                    }
                }
                
                // Piscar indicador
                blinkAutoUpdateIndicator();
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na verificação automática:', {
                xhr: xhr,
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            // Se for erro de timeout ou similar, não fazer nada drástico
            if (status === 'timeout') {
                console.warn('Timeout na verificação automática - continuando...');
            }
        }
    });
}

/**
 * Mostrar notificação de novos dados
 */
function showNewDataNotification(count) {
    // TOCAR SOM DE BEEP PRIMEIRO - REMOVIDO POR SOLICITAÇÃO
    // playNotificationBeep();
    
    // Usar toast notification se disponível
    if (typeof toastr !== 'undefined') {
        toastr.info(`${count} novo(s) registro(s) de assinatura digital!`, 'Atualização Automática', {
            timeOut: 3000,
            positionClass: 'toast-top-right'
        });
    } 
    // Usar SweetAlert2 se disponível
    else if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Dados Atualizados!',
            text: `${count} novo(s) registro(s) de assinatura digital foram adicionados.`,
            icon: 'info',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }
    // Fallback simples
    else {
        console.log(`✓ ${count} novo(s) registro(s) de assinatura digital!`);
    }
}

/**
 * Configurar indicador visual de atualização automática
 */
function addAutoUpdateIndicator() {
    var indicator = $('#auto-update-indicator');
    
    // Verificar se o elemento existe na página
    if (indicator.length === 0) {
        console.log('Auto-update indicator: Elemento não encontrado na página');
        return;
    }
    
    // Mostrar o indicador e atualizar conteúdo
    indicator.css('display', 'block');
    indicator.text(`🔄 Ativo (${autoUpdateFrequency/1000}s)`);
    
    // Adicionar evento de clique para pausar/retomar
    indicator.off('click').on('click', function() {
        if (isAutoUpdateEnabled) {
            stopAutoUpdate();
        } else {
            isAutoUpdateEnabled = true;
            startAutoUpdate();
        }
    });
    
    console.log('Auto-update indicator: Configurado e exibido');
}



/**
 * Atualizar indicador visual
 */
function updateAutoUpdateIndicator(isActive) {
    var indicator = $('#auto-update-indicator');
    var headerStatus = $('#status-auto-update');
    
    if (indicator.length > 0) {
        if (isActive) {
            indicator.css({
                'background': '#28a745',
                'display': 'block'
            }).text(`🔄 Ativo (${autoUpdateFrequency/1000}s)`);
        } else {
            indicator.css({
                'background': '#6c757d',
                'display': 'block'
            }).text('⏸️ Pausado');
        }
    }
    
    // Atualizar também o status do cabeçalho
    if (headerStatus.length > 0) {
        if (isActive) {
            headerStatus.css('color', '#28a745')
                        .html('<span class="glyphicon glyphicon-refresh"></span> Auto-atualização ativa');
        } else {
            headerStatus.css('color', '#6c757d')
                        .html('<span class="glyphicon glyphicon-pause"></span> Auto-atualização pausada');
        }
    }
}

/**
 * Fazer o indicador piscar quando há novos dados
 */
function blinkAutoUpdateIndicator() {
    var indicator = $('#auto-update-indicator');
    if (indicator.length > 0 && indicator.is(':visible')) {
        indicator.animate({opacity: 0.3}, 200)
                 .animate({opacity: 1}, 200)
                 .animate({opacity: 0.3}, 200)
                 .animate({opacity: 1}, 200);
    }
}

/**
 * Alterar frequência de atualização
 */
function changeAutoUpdateFrequency(newFrequency) {
    autoUpdateFrequency = newFrequency;
    console.log(`Auto-update indicator: Frequência alterada para ${newFrequency/1000}s`);
    
    // Atualizar o texto do indicador imediatamente
    var indicator = $('#auto-update-indicator');
    if (indicator.length > 0 && isAutoUpdateEnabled) {
        indicator.text(`🔄 Ativo (${autoUpdateFrequency/1000}s)`);
    }
    
    if (isAutoUpdateEnabled) {
        startAutoUpdate(); // Reiniciar com nova frequência
    }
}

// Limpar intervalo quando sair da página
$(window).on('beforeunload', function() {
    if (autoUpdateInterval) {
        clearInterval(autoUpdateInterval);
    }
});

// Função de debug para testar estrutura de dados
function debugDataStructure() {
    console.log('=== DEBUG - Testando estrutura de dados ===');
    
    $.ajax({
        url: 'pages/assinaturas_digitais/assinaturas_digitais_read2.php',
        method: 'POST',
        data: { 
            'usuario_global': usuario_global, 
            'divisao': divisao, 
            'id_situacao': 'false' 
        },
        dataType: 'json',
        success: function(response) {
            console.log('Resposta do servidor:', response);
            console.log('Tipo da resposta:', typeof response);
            console.log('Tem propriedade data?', response.hasOwnProperty('data'));
            console.log('Data é array?', Array.isArray(response.data));
            if (response.data) {
                console.log('Quantidade de registros:', response.data.length);
                if (response.data.length > 0) {
                    console.log('Primeiro registro:', response.data[0]);
                    console.log('Propriedades do primeiro registro:', Object.keys(response.data[0]));
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na requisição debug:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
        }
    });
}

// Executar debug automaticamente quando houver erro
window.debugAssinaturas = debugDataStructure;

// ===== FUNÇÃO DE SOM DE BEEP =====

/**
 * Tocar som de beep para notificações
 */
function playNotificationBeep() {
    console.log('🔊 Tocando som de notificação...');
    
    try {
        // Tentar múltiplos métodos em paralelo
        tryWebAudioBeep();
        tryHTMLAudioBeep();
        tryAlternativeBeep();
        
    } catch (error) {
        console.error('Erro geral no som:', error);
        tryAlternativeBeep();
    }
}

/**
 * Método 1: Web Audio API (melhor qualidade)
 */
function tryWebAudioBeep() {
    try {
        if (window.AudioContext || window.webkitAudioContext) {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
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
                    console.log('✅ Som tocado via Web Audio API (após resume)');
                });
            } else {
                createBeep(800, 0, 0.15);
                setTimeout(() => createBeep(1000, 0, 0.15), 200);
                console.log('✅ Som tocado via Web Audio API');
            }
        }
    } catch (error) {
        console.log('❌ Erro Web Audio API:', error);
    }
}

/**
 * Método 2: HTML5 Audio com WAV gerado programaticamente
 */
function tryHTMLAudioBeep() {
    try {
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
            const envelope = Math.exp(-t * 3);
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
                console.log('✅ Som tocado via HTML5 Audio');
                setTimeout(() => URL.revokeObjectURL(url), 1000);
            }).catch((error) => {
                console.log('❌ Erro HTML5 Audio:', error);
                URL.revokeObjectURL(url);
            });
        }
        
    } catch (error) {
        console.log('❌ Erro HTML5 Audio:', error);
    }
}

/**
 * Método 3: Métodos alternativos (Speech + Vibração + Visual)
 */
function tryAlternativeBeep() {
    // Speech Synthesis
    try {
        if ('speechSynthesis' in window) {
            speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance('beep beep');
            utterance.rate = 3;
            utterance.pitch = 2;
            utterance.volume = 0.3;
            speechSynthesis.speak(utterance);
            console.log('✅ Som via SpeechSynthesis');
        }
    } catch (error) {
        console.log('❌ Erro SpeechSynthesis:', error);
    }
    
    // Vibração (mobile)
    try {
        if ('vibrate' in navigator) {
            navigator.vibrate([200, 100, 200]);
            console.log('✅ Vibração ativada');
        }
    } catch (error) {
        console.log('❌ Erro vibração:', error);
    }
    
    // Feedback visual
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
        
        console.log('✅ Feedback visual aplicado');
        
    } catch (error) {
        console.log('❌ Erro feedback visual:', error);
    }
}

/**
 * Função de teste para som (disponível globalmente)
 */
function testNotificationBeep() {
    console.log('🧪 TESTE DE SOM DE BEEP iniciado...');
    //playNotificationBeep();
}

// Exportar função de teste globalmente
window.testNotificationBeep = testNotificationBeep;

// ===== INICIALIZAÇÃO RESPONSIVA =====

$(document).ready(function() {
    // Verificar responsividade após carregamento completo
    setTimeout(function() {
        verificarModoResponsivo();
        console.log('🎯 Sistema responsivo inicializado');
    }, 1000);
});

// Verificar novamente após todas as imagens carregarem
$(window).on('load', function() {
    setTimeout(function() {
        verificarModoResponsivo();
        console.log('🖼️ Sistema responsivo verificado após carregamento completo');
    }, 500);
});

/**
 * 🔍 FUNÇÃO DE DEBUG PARA VERIFICAR BANCO DE DADOS DIRETAMENTE
 * Consulta o banco para verificar se valores foram realmente gravados
 */
function debugVerificarBancoDados(idRegistro) {
    if (!idRegistro) {
        console.log('❌ ID do registro não fornecido');
        return;
    }
    
    console.log('🔍 VERIFICANDO DIRETAMENTE NO BANCO DE DADOS - ID:', idRegistro);
    
    $.ajax({
        url: "pages/assinaturas_digitais/assinaturas_digitais_exibe.php",
        method: "POST",
        data: {id_assinatura: idRegistro},
        dataType: "json",
        success: function(data) {
            console.log('📊 DADOS COMPLETOS DO BANCO:');
            console.log('  - ID:', data.id);
            console.log('  - Código:', data.codigo);
            console.log('  - Nome:', data.nome);
            console.log('  - Valor Aprovado (bruto):', '"' + data.valor_aprovado + '"');
            console.log('  - Valor Aprovado (tipo):', typeof data.valor_aprovado);
            console.log('  - Data Pgto (bruto):', '"' + data.data_pgto + '"');
            console.log('  - Data Pgto (tipo):', typeof data.data_pgto);
            console.log('  - Chave PIX:', '"' + (data.chave_pix || '') + '"');
            console.log('  - Reprovado:', data.reprovado);
            
            // Análise específica do valor aprovado
            if (data.valor_aprovado) {
                var valorString = data.valor_aprovado.toString();
                var valorNumerico = parseFloat(valorString.replace(/,/g, '.'));
                
                console.log('🔍 ANÁLISE VALOR APROVADO:');
                console.log('  - String:', '"' + valorString + '"');
                console.log('  - Numérico:', valorNumerico);
                console.log('  - É NaN?', isNaN(valorNumerico));
                console.log('  - É maior que 0?', valorNumerico > 0);
                
                if (valorNumerico <= 0) {
                    console.log('🚨 PROBLEMA: Valor aprovado no banco é <= 0!');
                    console.log('💡 Isso indica que o valor não foi gravado corretamente');
                }
            } else {
                console.log('🚨 PROBLEMA: Campo valor_aprovado está vazio/null no banco!');
            }
            
            // Análise específica da data_pgto
            if (data.data_pgto) {
                console.log('✅ Campo data_pgto TEM valor no banco:', data.data_pgto);
            } else {
                console.log('🚨 PROBLEMA: Campo data_pgto está vazio/null no banco!');
                console.log('💡 Isso indica que a data não foi gravada corretamente');
            }
        },
        error: function(xhr, status, error) {
            console.log('❌ Erro ao consultar banco:', error);
            console.log('❌ Status:', status);
            console.log('❌ Response:', xhr.responseText);
        }
    });
}

// Disponibilizar função globalmente
window.debugVerificarBancoDados = debugVerificarBancoDados;

/**
 * 🧪 FUNÇÃO DE TESTE PARA O ÚLTIMO REGISTRO EDITADO
 * Verifica os dados do registro ID 111 que acabou de ser editado
 */
function debugUltimoRegistro() {
    console.log('🧪 VERIFICANDO ÚLTIMO REGISTRO EDITADO (ID: 111)...');
    debugVerificarBancoDados(111);
}

// Disponibilizar função globalmente
window.debugUltimoRegistro = debugUltimoRegistro;

/**
 * 🔍 FUNÇÃO DE DEBUG ESPECÍFICA PARA PROBLEMA DE DATA NÃO APARECER
 * Verifica o ID específico que está com problema
 */
function debugDataPgtoProblema(idAssinatura) {
    if (!idAssinatura) {
        console.log('❌ Forneça o ID da assinatura. Exemplo: debugDataPgtoProblema(111)');
        return;
    }
    
    console.log('🔍 DEBUGANDO DATA_PGTO PARA ID:', idAssinatura);
    
    // 1. Verificar dados do banco diretamente
    $.ajax({
        url: "pages/assinaturas_digitais/assinaturas_digitais_exibe.php",
        method: "POST",
        data: {id_assinatura: idAssinatura},
        dataType: "json",
        success: function(data) {
            console.log('📊 DADOS DO BANCO:');
            console.log('  - ID:', data.id);
            console.log('  - Nome:', data.nome);
            console.log('  - Data PGTO (raw):', '"' + (data.data_pgto || 'NULL/VAZIO') + '"');
            console.log('  - Data PGTO (tipo):', typeof data.data_pgto);
            
            if (data.data_pgto && data.data_pgto.trim() !== '') {
                console.log('✅ BANCO TEM DATA!');
                console.log('📅 Formato atual:', data.data_pgto);
                
                // Testar conversão
                var dataFormatada = data.data_pgto;
                if (dataFormatada.match(/^\d{2}\/\d{2}\/\d{4}/)) {
                    var partes = dataFormatada.split(' ');
                    var partesData = partes[0].split('/');
                    var hora = partes[1] ? partes[1].substring(0, 5) : '00:00';
                    dataFormatada = partesData[2] + '-' + partesData[1] + '-' + partesData[0] + 'T' + hora;
                    console.log('🔄 Conversão BR->ISO:', data.data_pgto, '→', dataFormatada);
                }
                
                console.log('✅ Data está OK no banco. Problema é no frontend!');
                console.log('💡 Teste: Abra o modal e veja os logs de carregamento');
                
            } else {
                console.log('❌ BANCO NÃO TEM DATA!');
                console.log('💡 Soluções:');
                console.log('   1. Preencha a data manualmente no modal');
                console.log('   2. Salve o registro para gravar a data');
                console.log('   3. Execute: UPDATE sind.associados_sasmais SET data_pgto = NOW() WHERE id = ' + idAssinatura);
            }
        },
        error: function(xhr, status, error) {
            console.log('❌ Erro ao consultar banco:', error);
        }
    });
    
    // 2. Verificar dados da tabela (se estiver visível)
    if (typeof tabela_assinaturas_digitais !== 'undefined') {
        console.log('📋 VERIFICANDO DADOS DA TABELA:');
        
        var $botao = $('button[data-id="' + idAssinatura + '"]');
        if ($botao.length > 0) {
            var $linha = $botao.closest('tr');
            try {
                var dadosLinha = tabela_assinaturas_digitais.row($linha).data();
                if (dadosLinha) {
                    console.log('  - Data PGTO na tabela:', '"' + (dadosLinha.data_pgto || 'VAZIO') + '"');
                } else {
                    console.log('  - Não foi possível obter dados da linha');
                }
            } catch (e) {
                console.log('  - Erro ao obter dados da tabela:', e);
            }
        } else {
            console.log('  - Botão não encontrado na tabela');
        }
    }
    
    // 3. Verificar estado atual do modal (se estiver aberto)
    if ($("#ModalEditaAssinaturaDigital").is(':visible')) {
        console.log('📝 ESTADO ATUAL DO MODAL:');
        console.log('  - Campo existe:', $("#C_data_pgto_assinatura").length > 0);
        console.log('  - Campo visível:', $("#C_data_pgto_assinatura").is(':visible'));
        console.log('  - Valor atual:', '"' + $("#C_data_pgto_assinatura").val() + '"');
        console.log('  - Readonly:', $("#C_data_pgto_assinatura").prop('readonly'));
        console.log('  - Disabled:', $("#C_data_pgto_assinatura").prop('disabled'));
        console.log('  - Atributo name:', '"' + $("#C_data_pgto_assinatura").attr('name') + '"');
        
        var idModalAtual = $("#C_id_assinatura").val();
        if (idModalAtual == idAssinatura) {
            console.log('✅ Modal está aberto para o ID correto');
            
            // Função de teste rápido
            window.testarPreenchimentoRapido = function() {
                var dataHoje = new Date().toISOString().slice(0, 16);
                $("#C_data_pgto_assinatura").val(dataHoje);
                console.log('✅ Data teste preenchida:', dataHoje);
                console.log('🔍 Verificar campo:', $("#C_data_pgto_assinatura").val());
            };
            console.log('🧪 Para testar preenchimento: testarPreenchimentoRapido()');
            
        } else {
            console.log('⚠️ Modal está aberto para ID diferente:', idModalAtual);
        }
    } else {
        console.log('📝 Modal não está aberto');
        console.log('💡 Abra o modal primeiro clicando no botão do registro ID:', idAssinatura);
    }
}

// Disponibilizar função globalmente
window.debugDataPgtoProblema = debugDataPgtoProblema;

// ===== BOTÃO DE FECHAMENTO CUSTOMIZADO =====

// Botão de fechamento normal customizado
$("#btnFecharModal").click(function() {
    console.log('📝 Fechamento normal customizado...');
    fecharModalCompleto();
});

// Função para fechamento completo do modal
function fecharModalCompleto() {
    console.log('📝 FECHAMENTO COMPLETO - Iniciando...');
    
    try {
        // 1. Resetar campos primeiro
        resetModalFields();
        
        // 2. Forçar fechamento visual imediato
        $('#ModalEditaAssinaturaDigital').removeClass('show in').addClass('fade');
        $('#ModalEditaAssinaturaDigital').css('display', 'none');
        $('#ModalEditaAssinaturaDigital').attr('aria-hidden', 'true');
        
        // 3. Remover backdrop e classes do body
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        $('body').css({
            'padding-right': '',
            'overflow': 'auto'
        });
        
        // 4. Forçar fechamento de outros modais visíveis
        $('.modal:visible').each(function() {
            if (this.id !== 'ModalEditaAssinaturaDigital') {
                $(this).hide();
            }
        });
        
        // 5. Limpar eventos problemáticos
        $(document).off('focusin.bs.modal');
        
        // 6. Reabilitar scroll da página
        $('html, body').css('overflow', 'auto');
        
        // 7. IMPORTANTE: Resetar estado do modal para permitir reabertura
        $('#ModalEditaAssinaturaDigital').removeData('bs.modal');
        $('#ModalEditaAssinaturaDigital').off('shown.bs.modal hidden.bs.modal');
        
        // 8. Recriar eventos necessários para reabertura
        setTimeout(function() {
            // Recriar evento de modal totalmente carregado
            $('#ModalEditaAssinaturaDigital').on('shown.bs.modal', function () {
                console.log('📛 Modal reaberto - eventos recriados');
            });
            
            // Recriar evento de fechamento
            $('#ModalEditaAssinaturaDigital').on('hidden.bs.modal', function (e) {
                if (e.target.id === 'ModalEditaAssinaturaDigital') {
                    fecharModalCompleto();
                }
            });
            
            console.log('🔄 Eventos de modal recriados para reabertura');
        }, 200);
        
        console.log('✅ Modal fechado e resetado para reabertura');
        
    } catch (error) {
        console.error('❌ Erro no fechamento completo:', error);
        
        // Fallback: recarregar página
        if (confirm('Erro ao fechar modal. Recarregar página?')) {
            location.reload();
        }
    }
}

// ===== FUNCIONALIDADE DE BUSCA DE ASSOCIADO =====

// Evento para abrir modal de busca de associado
$("#btnBuscarAssociado").click(function() {
    console.log('🔍 Abrindo modal de busca de associado...');
    $("#ModalBuscaAssociadoAssinatura").modal("show");
    
    // Inicializar DataTable se não existir
    if (!$.fn.dataTable.isDataTable('#tabela_busca_associado_assinatura')) {
        var tabelaBuscaAssociado = $('#tabela_busca_associado_assinatura').DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            serverSide: false,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: 'pages/assinaturas_digitais/buscar_associados_modal.php',
                method: 'POST',
                data: function() {
                    return {
                        divisao: divisao || 1
                    };
                },
                dataType: 'json'
            },
            columns: [
                { data: "codigo" },
                { data: "nome" },
                { data: "cpf" },
                { data: "endereco" },
                { data: "cel" },
                { data: "empregador" },
                { data: "botao_selecionar" }
            ],
            language: {
                decimal: ",",
                thousands: ".",
                zeroRecords: "Não há dados",
                emptyTable: "Não há dados.",
                info: "Mostrando página _PAGE_ de _PAGES_ (_MAX_ registros no total)",
                infoEmpty: "Mostrando 0 até 0 de 0 registros",
                infoFiltered: "(Filtrados de _MAX_ registros)",
                infoThousands: ".",
                lengthMenu: "Mostrar _MENU_ registros por página",
                loadingRecords: "Carregando...",
                processing: "Processando...",
                search: "Buscar:",
                paginate: {
                    first: "Primeiro",
                    last: "Último",
                    next: "Próximo",
                    previous: "Anterior"
                }
            }
        });
        
        console.log('✅ DataTable de busca de associado inicializado');
    } else {
        // Se já existe, apenas recarregar dados
        $('#tabela_busca_associado_assinatura').DataTable().ajax.reload();
        console.log('🔄 DataTable de busca recarregado');
    }
});

// Evento para selecionar associado
$(document).on('click', '.selecionar-associado', function() {
    var codigo = $(this).data('codigo');
    var cpf = $(this).data('cpf');
    var nome = $(this).data('nome');
    var idAssociado = $(this).data('id_associado');
    var idDivisao = $(this).data('id_divisao');
    var idEmpregador = $(this).data('id_empregador');
    
    console.log('✅ Associado selecionado:', {codigo: codigo, cpf: cpf, nome: nome});
    
    var cpfModal = ($("#C_cpf_assinatura").val() || "").toString().trim();
    var cpfTabela = (cpf || "").toString().trim();

    var prosseguirSelecao = function() {
        $("#C_codigo_assinatura").val(codigo);
        $("#C_cpf_assinatura").val(cpfTabela || cpfModal);
        console.log('📝 Campos preenchidos - Código:', codigo, 'CPF:', cpfTabela || cpfModal);
        $("#ModalBuscaAssociadoAssinatura").modal("hide");
        Swal.fire({
            icon: 'success',
            title: 'Associado selecionado',
            text: 'Código: ' + codigo + ' | CPF: ' + (cpfTabela || cpfModal),
            timer: 2000,
            showConfirmButton: false
        });
    };

    if (!cpfTabela) {
        // Confirmar atribuição do CPF do modal principal ao cadastro do associado
        Swal.fire({
            icon: 'warning',
            title: 'Confirmar Atribuição de CPF',
            html: 'Tem certeza que deseja atribuir o CPF <b>' + (cpfModal || '(vazio)') + '</b> no cadastro definitivo do associado <b>' + nome + '</b>?',
            showCancelButton: true,
            confirmButtonText: 'Sim, atribuir',
            cancelButtonText: 'Cancelar'
        }).then(function(result){
            if (result.isConfirmed) {
                if (!cpfModal) {
                    Swal.fire({ icon: 'error', title: 'CPF não informado', text: 'Preencha o CPF no modal ASSINATURA DIGITAL antes de atribuir.' });
                    return;
                }
                $.ajax({
                    url: 'pages/assinaturas_digitais/atualizar_cpf_associado.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        id_associado: idAssociado,
                        id_divisao: idDivisao,
                        id_empregador: idEmpregador,
                        cpf: cpfModal
                    },
                    success: function(resp){
                        if (resp && resp.success) {
                            cpfTabela = cpfModal;
                            prosseguirSelecao();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Erro ao atualizar CPF', text: (resp && resp.message) ? resp.message : 'Não foi possível atualizar o CPF.' });
                        }
                    },
                    error: function(xhr){
                        Swal.fire({ icon: 'error', title: 'Erro de comunicação', text: 'Falha ao atualizar CPF: ' + xhr.status });
                    }
                });
            } else {
                // Cancelado
            }
        });
    } else {
        prosseguirSelecao();
    }
});

/**
 * 🔧 FUNÇÃO PARA FORÇAR CARREGAMENTO DA DATA NO MODAL ABERTO
 * Use quando o modal já estiver aberto e a data não aparecer
 */
function forcarCarregamentoDataModal() {
    if (!$("#ModalEditaAssinaturaDigital").is(':visible')) {
        console.log('❌ Modal não está aberto! Abra um modal primeiro.');
        return;
    }
    
    var idAssinatura = $("#C_id_assinatura").val();
    if (!idAssinatura) {
        console.log('❌ ID da assinatura não encontrado no modal!');
        return;
    }
    
    console.log('🔧 FORÇANDO CARREGAMENTO DE DATA PARA ID:', idAssinatura);
    
    // Buscar dados diretamente do banco
    $.ajax({
        url: "pages/assinaturas_digitais/assinaturas_digitais_exibe.php",
        method: "POST",
        data: {id_assinatura: idAssinatura},
        dataType: "json",
        success: function(data) {
            console.log('📊 Dados recebidos do banco:');
            console.log('  - data_pgto:', '"' + (data.data_pgto || 'VAZIO') + '"');
            
            if (data.data_pgto && data.data_pgto.trim() !== '') {
                console.log('✅ Data encontrada no banco, forçando preenchimento...');
                
                var dataFormatada = data.data_pgto;
                
                // Conversão de formato brasileiro para ISO
                if (dataFormatada.match(/^\d{2}\/\d{2}\/\d{4}/)) {
                    var partes = dataFormatada.split(' ');
                    var partesData = partes[0].split('/');
                    var hora = partes[1] ? partes[1].substring(0, 5) : '00:00';
                    dataFormatada = partesData[2] + '-' + partesData[1] + '-' + partesData[0] + 'T' + hora;
                    console.log('🔄 Convertendo formato:', data.data_pgto, '→', dataFormatada);
                }
                
                // 🔧 DESABILITAR TEMPORARIAMENTE O SISTEMA DE NORMALIZAÇÃO
                window.desabilitarNormalizacaoTemporaria = true;
                console.log('🔧 Sistema de normalização desabilitado temporariamente');
                
                // Para campo DATE, usar apenas a parte da data (sem hora)
                var dataParaCampoDate = dataFormatada.split('T')[0]; // Pegar apenas YYYY-MM-DD
                
                console.log('📅 Convertendo para campo DATE (apenas data):');
                console.log('  - Data com hora:', dataFormatada);
                console.log('  - Data sem hora:', dataParaCampoDate);
                
                // Forçar preenchimento
                $("#C_data_pgto_assinatura").val(dataParaCampoDate);
                $("#C_data_pgto_assinatura").trigger('input').trigger('change');
                
                console.log('✅ Campo preenchido com (DATE):', dataParaCampoDate);
                console.log('🔍 Verificação final:', '"' + $("#C_data_pgto_assinatura").val() + '"');
                
                // Reabilitar sistema de normalização após um pequeno delay
                setTimeout(function() {
                    window.desabilitarNormalizacaoTemporaria = false;
                    console.log('✅ Sistema de normalização reabilitado (força manual)');
                }, 2000);
                
                // Notificação visual
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Data Carregada Manualmente!',
                        text: 'Campo preenchido com: ' + dataFormatada,
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        timer: 3000
                    });
                }
                
            } else {
                console.log('❌ Nenhuma data encontrada no banco!');
                console.log('💡 O registro não possui data_pgto. Você pode:');
                console.log('   1. Preencher manualmente');
                console.log('   2. Usar o botão ⏰ ao lado do campo');
                console.log('   3. Executar: testarDataManual()');
                
                // Oferecer preenchimento com data atual
                window.preencherDataAtual = function() {
                    var dataHoje = new Date().toISOString().slice(0, 16);
                    $("#C_data_pgto_assinatura").val(dataHoje);
                    console.log('✅ Data atual preenchida:', dataHoje);
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Data Atual Preenchida!',
                            text: 'Campo preenchido com data/hora atual: ' + dataHoje,
                            icon: 'info',
                            toast: true,
                            position: 'top-end',
                            timer: 3000
                        });
                    }
                };
                console.log('🧪 Para preencher com data atual: preencherDataAtual()');
            }
        },
        error: function(xhr, status, error) {
            console.log('❌ Erro ao buscar dados:', error);
        }
    });
}

// Disponibilizar função globalmente
window.forcarCarregamentoDataModal = forcarCarregamentoDataModal;

/**
 * 📋 GUIA DE DEBUG PARA PROBLEMA DE DATA NÃO APARECER
 * Mostra todas as funções disponíveis para debugging
 */
function ajudaDebugDataPgto() {
    console.log('📋 GUIA DE DEBUG - PROBLEMA DE DATA NÃO APARECER');
    console.log('');
    console.log('🔍 FUNÇÕES DISPONÍVEIS:');
    console.log('');
    console.log('1️⃣ debugDataPgtoProblema(ID)');
    console.log('   - Verifica dados no banco e tabela para um ID específico');
    console.log('   - Exemplo: debugDataPgtoProblema(111)');
    console.log('');
    console.log('2️⃣ forcarCarregamentoDataModal()');
    console.log('   - Use quando o modal já estiver aberto e a data não aparecer');
    console.log('   - Força o carregamento da data do banco');
    console.log('');
    console.log('3️⃣ testarDataManual()');
    console.log('   - Preenche o campo com data de teste (disponível quando data está vazia)');
    console.log('');
    console.log('4️⃣ preencherDataAtual()');
    console.log('   - Preenche com data/hora atual (disponível quando banco está vazio)');
    console.log('');
    console.log('5️⃣ testarPreenchimentoRapido()');
    console.log('   - Teste rápido de preenchimento (disponível quando modal está aberto)');
    console.log('');
    console.log('6️⃣ testarCarregamentoDataRapido()');
    console.log('   - Simula carregamento de data como se fosse do banco');
    console.log('');
    console.log('🚀 PASSO A PASSO PARA RESOLVER:');
    console.log('');
    console.log('1. Primeiro, identifique o ID do registro com problema');
    console.log('2. Execute: debugDataPgtoProblema(ID) - substitua ID pelo número');
    console.log('3. Se o banco TEM data mas não aparece: execute forcarCarregamentoDataModal()');
    console.log('4. Se o banco NÃO TEM data: execute preencherDataAtual() para adicionar');
    console.log('5. Salve o registro para gravar a data no banco');
    console.log('');
    console.log('📞 Para ajuda: ajudaDebugDataPgto()');
    console.log('');
    
    // Detectar automaticamente se há modal aberto
    if ($("#ModalEditaAssinaturaDigital").is(':visible')) {
        var idAtual = $("#C_id_assinatura").val();
        console.log('ℹ️ MODAL DETECTADO ABERTO - ID:', idAtual);
        console.log('💡 AÇÃO SUGERIDA: forcarCarregamentoDataModal()');
    } else {
        console.log('ℹ️ Nenhum modal aberto no momento');
        console.log('💡 AÇÃO SUGERIDA: Abra um modal e execute debugDataPgtoProblema(ID)');
    }
}

// Disponibilizar função globalmente  
window.ajudaDebugDataPgto = ajudaDebugDataPgto;

/**
 * 🧪 FUNÇÃO DE TESTE RÁPIDO PARA CARREGAMENTO DE DATA
 * Simula o carregamento de data como se fosse do banco
 */
function testarCarregamentoDataRapido() {
    console.log('🧪 TESTE RÁPIDO - Simulando carregamento de data do banco...');
    
    if (!$("#ModalEditaAssinaturaDigital").is(':visible')) {
        console.log('❌ Modal não está aberto! Abra um modal primeiro.');
        return;
    }
    
    // Simular dados do banco
    var dataDoTeste = '2025-01-15T14:30';
    
    console.log('📊 Simulando dados do banco:');
    console.log('  - data_pgto:', dataDoTeste);
    
    // Desabilitar normalização temporariamente
    window.desabilitarNormalizacaoTemporaria = true;
    console.log('🔧 Sistema de normalização desabilitado temporariamente');
    
    // Para campo DATE, usar apenas a parte da data
    var dataParaCampoDate = dataDoTeste.split('T')[0];
    
    console.log('📅 Convertendo para campo DATE:');
    console.log('  - Data com hora:', dataDoTeste);
    console.log('  - Data sem hora:', dataParaCampoDate);
    
    // Preencher campo
    $("#C_data_pgto_assinatura").val(dataParaCampoDate);
    
    console.log('✅ Campo preenchido com:', dataParaCampoDate);
    console.log('🔍 Verificação imediata:', $("#C_data_pgto_assinatura").val());
    
    // Reabilitar normalização após delay
    setTimeout(function() {
        window.desabilitarNormalizacaoTemporaria = false;
        console.log('✅ Sistema de normalização reabilitado');
        
        // Verificar se valor foi mantido
        var valorFinal = $("#C_data_pgto_assinatura").val();
        console.log('🔍 Valor final após reabilitar:', valorFinal);
        
        if (valorFinal === dataParaCampoDate) {
            console.log('✅ SUCESSO! Data foi carregada e mantida corretamente!');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Teste Bem-Sucedido!',
                    text: 'Data foi carregada corretamente: ' + dataParaCampoDate,
                    icon: 'success',
                    toast: true,
                    position: 'top-end',
                    timer: 3000
                });
            }
        } else {
            console.log('❌ FALHA! Data foi perdida após reabilitar normalização');
            console.log('Expected:', dataParaCampoDate);
            console.log('Received:', valorFinal);
        }
    }, 2000);
}

// Disponibilizar função globalmente
window.testarCarregamentoDataRapido = testarCarregamentoDataRapido;

// ===== PROTEÇÃO GLOBAL CONTRA ABERTURA DE MODAIS ANTIGOS =====

// Interceptar TODOS os eventos de abertura de modal
$(document).on('show.bs.modal', function(e) {
    var modalId = e.target.id;
    
    // Se não é o modal correto e estamos em bloqueio, cancelar
    if (window.modalBlockTimeout && modalId !== 'ModalEditaAssinaturaDigital' && modalId !== 'ModalBuscaAssociadoAssinatura') {
        console.log('🚫 Bloqueando abertura de modal antigo:', modalId);
        e.preventDefault();
        e.stopPropagation();
        return false;
    }
    
    console.log('📝 Modal sendo aberto:', modalId);
});

// Interceptar cliques em elementos que podem abrir modais antigos
$(document).on('click', '[data-toggle="modal"]:not([data-target="#ModalEditaAssinaturaDigital"]):not([data-target="#ModalBuscaAssociadoAssinatura"])', function(e) {
    if (window.modalBlockTimeout) {
        console.log('🚫 Bloqueando clique em elemento que abre modal antigo');
        e.preventDefault();
        e.stopPropagation();
        return false;
    }
});
 