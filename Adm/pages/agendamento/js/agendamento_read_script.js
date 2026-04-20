var usuario_global;
var usuario_cod;
var divisao;
var divisao_nome;
var tabela_agendamento;
var tabela_processando = false;
var filtro_ativo_atual = '1'; // Filtro inicial (1 = Pendente)
var d = new Date();
var curr_date = d.getDate();
var curr_month = d.getMonth()+1;
var curr_year = d.getFullYear();

// Variáveis para atualização automática
var lastUpdateCheck = null;
var autoUpdateInterval = null;
var isAutoUpdateEnabled = true;
var autoUpdateFrequency = 5000; // 5 segundos



$(document).ready(function() {
    // Verificar se existe alguma sessão ativa no sessionStorage (formato correto do sistema)
    try {
        // Tentar obter dados do sessionStorage (formato real do sistema)
        usuario_global = sessionStorage.getItem('usuario_global');
        usuario_cod = sessionStorage.getItem('usuario_cod');
        divisao = sessionStorage.getItem('divisao');
        divisao_nome = sessionStorage.getItem('divisao_nome');
        
        if (usuario_global && usuario_cod && divisao) {
            console.log('👤 Usuário logado:', usuario_global);
            console.log('🏢 Divisão:', divisao_nome, '(' + divisao + ')');
            console.log('🆔 Código usuário:', usuario_cod);
            
            // Inicializar sistema após dados do usuário
            inicializarSistema();
        } else {
            throw new Error('Dados de sessão incompletos');
        }
        
    } catch(e) {
        console.warn('⚠️ Dados do usuário não encontrados no sessionStorage:', e.message);
        
        // Para desenvolvimento/teste, usar valores padrão
        usuario_global = 'admin';
        usuario_cod = '1';
        divisao = '1';
        divisao_nome = 'QRCRED';
        
        console.log('🔧 Usando dados padrão para desenvolvimento/teste');
        console.log('👤 Usuário:', usuario_global);
        console.log('🏢 Divisão:', divisao_nome, '(' + divisao + ')');
        console.log('🆔 Código usuário:', usuario_cod);
        
        // Salvar dados padrão no sessionStorage para manter consistência
        try {
            sessionStorage.setItem('usuario_global', usuario_global);
            sessionStorage.setItem('usuario_cod', usuario_cod);
            sessionStorage.setItem('divisao', divisao);
            sessionStorage.setItem('divisao_nome', divisao_nome);
            console.log('💾 Dados de teste salvos no sessionStorage');
        } catch(storageError) {
            console.warn('⚠️ Não foi possível salvar no sessionStorage:', storageError);
        }
        
        inicializarSistema();
    }
});

/**
 * 🚀 INICIALIZAR SISTEMA PRINCIPAL
 */
function inicializarSistema() {
    console.log('🚀 Inicializando sistema de agendamentos...');
    
    // Detectar qual filtro está selecionado inicialmente
    if ($('#RadioPendentes').is(':checked')) {
        filtro_ativo_atual = '1';
        console.log('🔄 Filtro inicializado: RadioPendentes (status = 1)');
    } else if ($('#RadioConfirmados').is(':checked')) {
        filtro_ativo_atual = '2';
        console.log('🔄 Filtro inicializado: RadioConfirmados (status = 2)');
    } else if ($('#RadioTodos').is(':checked')) {
        filtro_ativo_atual = 'todos';
        console.log('🔄 Filtro inicializado: RadioTodos (todos)');
    }
    
    // Configurar eventos dos radio buttons
    configurarEventosRadio();
    
    // Configurar outros eventos
    configurarEventos();
    
    // Carregar tabela inicial
    carregarTabelaInicial();
}

/**
 * 📻 CONFIGURAR EVENTOS DOS RADIO BUTTONS
 */
function configurarEventosRadio() {
    
    $('#RadioPendentes').change(function(){
        if (tabela_processando) {
            console.log('⏸️ Tabela ainda sendo processada, aguardando...');
            return;
        }
        
        var cod_situacao = $('#RadioPendentes').val();
        filtro_ativo_atual = '1';
        console.log('📻 RadioPendentes selecionado - Valor:', cod_situacao, '- Filtro ativo:', filtro_ativo_atual);
        if(divisao === "1"){ //QRCRED
            tabela_processando = true;
            filtra_agendamentos(cod_situacao, divisao);
        } else {
            console.log('⚠️ Divisão não é QRCRED:', divisao);
        }
    });

    $('#RadioConfirmados').change(function(){
        if (tabela_processando) {
            console.log('⏸️ Tabela ainda sendo processada, aguardando...');
            return;
        }
        
        var cod_situacao = $('#RadioConfirmados').val();
        filtro_ativo_atual = '2';
        console.log('📻 RadioConfirmados selecionado - Valor:', cod_situacao, '- Filtro ativo:', filtro_ativo_atual);
        if(divisao === "1"){ //QRCRED
            tabela_processando = true;
            filtra_agendamentos(cod_situacao, divisao);
        } else {
            console.log('⚠️ Divisão não é QRCRED:', divisao);
        }
    });

    $('#RadioTodos').change(function(){
        if (tabela_processando) {
            console.log('⏸️ Tabela ainda sendo processada, aguardando...');
            return;
        }
        
        var cod_situacao = $('#RadioTodos').val();
        filtro_ativo_atual = 'todos';
        console.log('📻 RadioTodos selecionado - Valor:', cod_situacao, '- Filtro ativo:', filtro_ativo_atual);
        if(divisao === "1"){ //QRCRED
            tabela_processando = true;
            filtra_agendamentos(cod_situacao, divisao);
        } else {
            console.log('⚠️ Divisão não é QRCRED:', divisao);
        }
    });
}

/**
 * ⚙️ CONFIGURAR OUTROS EVENTOS
 */
function configurarEventos() {
    

    
    // Evento para botão Salvar
    $(document).on('click', '#btnSalvar', function(e) {
        e.preventDefault();
        salvarAgendamento();
    });
    
    // Evento para editar agendamento
    $(document).on('click', '.editar_agendamento', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var id_agendamento = $(this).data('id');
        
        if (!id_agendamento) {
            console.error('❌ ID do agendamento não encontrado!');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: "Erro!",
                    text: "ID do agendamento não encontrado. Verifique se o botão foi criado corretamente.",
                    icon: "error"
                });
            } else {
                alert('Erro: ID do agendamento não encontrado!');
            }
            return;
        }

        console.log('✅ Abrindo modal para ID:', id_agendamento);
        abrirModalEdicao(id_agendamento);
    });
    
    // Evento para botão de atualizar tabela
    $('#btnAtualizarTabela').click(function() {
        atualizarTabela();
    });
    
    // Evento para botão de limpar filtros
    $('#btnLimparFiltros').click(function() {
        limparFiltros();
    });
    
    // Evento para quando o modal for fechado - resetar campos readonly
    $('#ModalEditaAgendamento').on('hidden.bs.modal', function() {
        console.log('🔓 Modal fechado - removendo readonly dos campos');
        
        // Remover readonly e restaurar cor de fundo (apenas campos visíveis)
        $('#C_cod_associado_agendamento').prop('readonly', false).css('background-color', '');
        $('#C_data_solicitacao_agendamento').prop('readonly', false).css('background-color', '');
        $('#C_profissional_agendamento').prop('readonly', false).css('background-color', '');
        $('#C_especialidade_agendamento').prop('readonly', false).css('background-color', '');
        $('#C_convenio_nome_agendamento').prop('readonly', false).css('background-color', '');
        
        // Restaurar estilo original do campo nome do associado
        restaurarCampoNomeEstadoOriginal();
        
        // Reabilitar campo de status e restaurar cor de fundo
        $('#C_status_agendamento').prop('disabled', false).css('background-color', '');
        console.log('🔓 Campo de status reabilitado');
        
        // Remover readonly dos campos ocultos (sem styling)
        $('#C_id_empregador_agendamento').prop('readonly', false);
        $('#C_cod_convenio_agendamento').prop('readonly', false);
        
        // Limpar ícone de data agendada e restaurar layout original
        limparIconeDataAgendada();
        
        // Limpar event listener do modal shown
        $('#ModalEditaAgendamento').off('shown.bs.modal');
        
        console.log('✅ Campos liberados para edição');
    });
    
    // Evento para detectar mudanças na data agendada e alterar status automaticamente
    $(document).on('change', '#C_data_agendada_agendamento', function() {
        var dataAgendada = $(this).val();
        var statusField = $('#C_status_agendamento');
        
        if (dataAgendada && dataAgendada.trim() !== '') {
            // Se data foi preenchida, alterar status para "Confirmado" (valor 2)
            statusField.val('2');
            console.log('📅 Data agendada preenchida - Status alterado para Confirmado');
        } else {
            // Se data foi limpa, alterar status para "Pendente" (valor 1)
            statusField.val('1');
            console.log('🔄 Data agendada limpa - Status alterado para Pendente');
        }
    });
    
    // Evento para o botão de limpar data agendada
    $(document).on('click', '#btnLimparDataAgendada', function(e) {
        e.preventDefault();
        $('#C_data_agendada_agendamento').val('').trigger('change');
        console.log('🗑️ Data agendada limpa pelo usuário');
    });
}

/**
 * 🔄 FILTRAR AGENDAMENTOS
 */
function filtra_agendamentos(codigo, divisao) {
    console.log('🔧 Filtrando agendamentos - Código:', codigo, 'Divisão:', divisao);
    
    // Destruir tabela existente se existir
    if (tabela_agendamento) {
        console.log('🗑️ Destruindo tabela existente...');
        tabela_agendamento.destroy();
        $('#tabela_agendamento').empty();
    }
    
    // Aguardar um pouco antes de criar nova tabela
    setTimeout(function() {
        console.log('🏗️ Criando nova tabela DataTables...');
        
        tabela_agendamento = $('#tabela_agendamento').DataTable({
            "processing": true,
            "serverSide": false,
            "autoWidth": false,
            "ajax": {
                "url": "pages/agendamento/agendamento_read2.php",
                "type": "POST",
                "data": {
                    "id_situacao": codigo,
                    "divisao": divisao
                },
                "dataSrc": function(json) {
                    console.log('📡 Resposta AJAX recebida:', json);
                    
                    if (json.data && Array.isArray(json.data)) {
                        console.log('✅ Dados válidos recebidos:', json.data.length, 'registros');
                        return json.data;
                    } else {
                        console.error('❌ Formato de dados inválido:', json);
                        return [];
                    }
                }
            },
            "columns": [
                { "title": "ID", "width": "5%", "visible": false },
                { "title": "Cód. Associado", "width": "10%" },
                { "title": "Nome Associado", "width": "15%" },
                { "title": "ID Empregador", "width": "10%", "visible": false },
                { "title": "Empregador", "width": "15%" },
                { "title": "Data Solicitação", "width": "12%" },
                { "title": "Data Agendada", "width": "12%" },
                { "title": "Cód. Convênio", "width": "10%", "visible": false },
                { "title": "Status", "width": "8%" },
                { "title": "Profissional", "width": "12%" },
                { "title": "Especialidade", "width": "12%" },
                { "title": "Convênio", "width": "12%" },
                { "title": "Ações", "width": "8%", "orderable": false }
            ],
            "language": {
                "processing": "Processando...",
                "zeroRecords": "Nenhum registro encontrado",
                "emptyTable": "Nenhum dado disponível"
            },
            "order": [[ 0, "desc" ]],
            "pageLength": 25,
            "drawCallback": function(settings) {
                console.log('🔄 DrawCallback executado - Filtro ativo:', filtro_ativo_atual);
                aplicarEstilosBaseadosNoStatus();
            },
            "initComplete": function(settings, json) {
                console.log('✅ Tabela DataTables criada com sucesso!');
                tabela_processando = false;
            }
        });
        
    }, 100);
}

/**
 * 🎨 APLICAR ESTILOS BASEADOS NO STATUS
 */
function aplicarEstilosBaseadosNoStatus() {
    console.log('🎨 Aplicando estilos baseados no status...');
    
    // Buscar todas as linhas da tabela
    $('#tabela_agendamento tbody tr').each(function() {
        var $linha = $(this);
        var $colunaStatus = $linha.find('td:eq(8)'); // Coluna Status (índice 8)
        
        if ($colunaStatus.length > 0) {
            var textoStatus = $colunaStatus.text().toLowerCase().trim();
            
            // Remover estilos anteriores
            $linha.removeClass('agendamento-pendente agendamento-aprovado agendamento-cancelado agendamento-concluido');
            
            // Aplicar estilo baseado no status
            if (textoStatus.includes('pendente')) {
                $linha.addClass('agendamento-pendente');
                $linha.css('background-color', '#fff3cd'); // Amarelo claro
            } else if (textoStatus.includes('confirmado')) {
                $linha.addClass('agendamento-confirmado');
                $linha.css('background-color', '#d4edda'); // Verde claro
            }
        }
    });
    
    console.log('✅ Estilos aplicados baseados no status');
}

/**
 * 📋 CARREGAR TABELA INICIAL
 */
function carregarTabelaInicial() {
    console.log('📋 Carregando tabela inicial...');
    
    // Carregar com filtro inicial (pendentes - status = 1)
    if(divisao === "1"){ //QRCRED
        tabela_processando = true;
        filtra_agendamentos('1', divisao);
    } else {
        console.log('⚠️ Divisão não é QRCRED:', divisao);
    }
}

/**
 * 📝 ABRIR MODAL DE EDIÇÃO
 */
function abrirModalEdicao(id_agendamento) {
    console.log('📝 Abrindo modal de edição para ID:', id_agendamento);
    
    // Limpar formulário
    $('#frmagendamento')[0].reset();
    $('#C_id_agendamento').val(id_agendamento);
    
    // Buscar dados do agendamento
    console.log(' Iniciando AJAX para buscar dados do ID:', id_agendamento);
    $.ajax({
        url: "pages/agendamento/agendamento_exibe.php",
        method: "POST",
        data: {id_agendamento: id_agendamento},
        dataType: "json",
        beforeSend: function() {
            console.log(' Enviando requisição para agendamento_exibe.php...');
        },
        success: function(data) {
            console.log(' Resposta recebida do servidor');
            console.log(' Dados do agendamento recebidos:', data);
            console.log(' Tipo de dados:', typeof data);
            console.log(' Data agendada recebida:', data.data_agendada);
            
            if (data) {
                // Preencher campos do formulário
                $('#C_cod_associado_agendamento').val(data.cod_associado || '');
                $('#C_nome_associado_agendamento').val(data.nome_associado || '');
                $('#C_id_empregador_agendamento').val(data.id_empregador || '');
                $('#C_nome_empregador_agendamento').val(data.nome_empregador || data.abreviacao_empregador || '');
                $('#C_data_solicitacao_agendamento').val(data.data_solicitacao || '');
                $('#C_data_agendada_agendamento').val(data.data_agendada || '');
                $('#C_data_pretendida_agendamento').val(data.data_pretendida || '');
                $('#C_cod_convenio_agendamento').val(data.cod_convenio || '');
                $('#C_status_agendamento').val(data.status || '1');
                $('#C_profissional_agendamento').val(data.profissional || '');
                $('#C_especialidade_agendamento').val(data.especialidade || '');
                $('#C_convenio_nome_agendamento').val(data.convenio_nome || '');
                
                // Definir campos como somente leitura (apenas campos visíveis)
                $('#C_cod_associado_agendamento').prop('readonly', true).css('background-color', '#f5f5f5');
                $('#C_data_solicitacao_agendamento').prop('readonly', true).css('background-color', '#f5f5f5');
                $('#C_profissional_agendamento').prop('readonly', true).css('background-color', '#f5f5f5');
                $('#C_especialidade_agendamento').prop('readonly', true).css('background-color', '#f5f5f5');
                $('#C_convenio_nome_agendamento').prop('readonly', true).css('background-color', '#f5f5f5');
                
                // Configurar campo nome do associado para ocupar toda a linha (com delay)
                setTimeout(function() {
                    configurarCampoNomeParaLinhaToda();
                }, 200);
                
                // Desabilitar campo de status (controlado automaticamente pela data)
                $('#C_status_agendamento').prop('disabled', true).css('background-color', '#f5f5f5');
                console.log('🔒 Campo de status desabilitado - controlado automaticamente pela data agendada');
                
                // Campos ocultos também ficam readonly, mas não precisam de styling
                $('#C_id_empregador_agendamento').prop('readonly', true);
                $('#C_cod_convenio_agendamento').prop('readonly', true);
                
                console.log('🔒 Campos definidos como somente leitura');
                
                // Limpar ícone anterior e adicionar novo ícone de limpar data
                limparIconeDataAgendada();
                setTimeout(function() {
                    adicionarIconeLimparData();
                }, 100);
                
                // Verificar status inicial baseado na data agendada
                verificarStatusInicialDataAgendada();
                
                // Abrir modal
                $("#ModalEditaAgendamento").modal("show");
                
                // Verificação final após modal ser mostrado
                $("#ModalEditaAgendamento").on('shown.bs.modal', function() {
                    // Aplicar configuração novamente para garantir
                    setTimeout(function() {
                        configurarCampoNomeParaLinhaToda();
                        console.log('🔄 Configuração do campo nome reaplicada após modal mostrado');
                    }, 100);
                });
                
                console.log('✅ Modal aberto com dados preenchidos');
            } else {
                console.error('❌ Dados do agendamento não encontrados');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: "Erro!",
                        text: "Não foi possível carregar os dados do agendamento.",
                        icon: "error"
                    });
                } else {
                    alert('Erro: Não foi possível carregar os dados do agendamento.');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Erro ao buscar dados do agendamento:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: "Erro!",
                    text: "Erro ao carregar dados: " + error,
                    icon: "error"
                });
            } else {
                alert('Erro ao carregar dados: ' + error);
            }
        }
    });
}

/**
 * 🔒 DESABILITAR BOTÃO SALVAR COM LOADING
 */
function desabilitarBotaoSalvar() {
    var $btnSalvar = $('#btnSalvar');
    var textoOriginal = $btnSalvar.html();
    
    // Salvar texto original no data attribute
    $btnSalvar.data('texto-original', textoOriginal);
    
    $btnSalvar.prop('disabled', true);
    $btnSalvar.html('<i class="fa fa-spinner fa-spin"></i> Salvando...');
    $btnSalvar.css('pointer-events', 'none');
    
    console.log('🔒 Botão salvar desabilitado com loading');
    return $btnSalvar;
}

/**
 * 🔓 REABILITAR BOTÃO SALVAR
 */
function reabilitarBotaoSalvar() {
    var $btnSalvar = $('#btnSalvar');
    var textoOriginal = $btnSalvar.data('texto-original') || 'Salvar';
    
    $btnSalvar.prop('disabled', false);
    $btnSalvar.html(textoOriginal);
    $btnSalvar.css('pointer-events', 'auto');
    $btnSalvar.removeData('texto-original');
    
    console.log('🔓 Botão salvar reabilitado');
}

/**
 * 💾 SALVAR AGENDAMENTO
 */
function salvarAgendamento() {
    console.log('💾 Salvando agendamento...');
    
    // Verificar se já está salvando para evitar cliques duplos
    var $btnSalvar = $('#btnSalvar');
    if ($btnSalvar.prop('disabled')) {
        console.log('⚠️ Salvamento já em andamento, ignorando clique duplo');
        return;
    }
    
    // Desabilitar botão salvar e adicionar loading
    desabilitarBotaoSalvar();
    
    // Habilitar temporariamente o campo de status para incluir no formulário
    var statusField = $('#C_status_agendamento');
    var wasDisabled = statusField.prop('disabled');
    statusField.prop('disabled', false);
    
    // Preparar dados do formulário
    var formData = $('#frmagendamento').serialize();
    formData += '&operation=salvar';
    formData += '&usuario_cod=' + encodeURIComponent(usuario_cod);
    formData += '&divisao=' + encodeURIComponent(divisao);
    
    // Restaurar estado disabled do campo de status
    if (wasDisabled) {
        statusField.prop('disabled', true);
    }
    
    console.log('📤 Dados a serem enviados:', formData);
    
    // Enviar dados
    $.ajax({
        url: "pages/agendamento/agendamento_salvar.php",
        method: "POST",
        data: formData,
        dataType: "json",
        success: function(response) {
            console.log('📨 Resposta do servidor:', response);
            
            if (response.success) {
                // Obter ID do agendamento para push notification
                var agendamento_id = $('#C_id_agendamento').val();
                
                // Enviar push notification após confirmar agendamento
                if (agendamento_id) {
                    console.log('📱 Enviando push notification para agendamento ID:', agendamento_id);
                    
                    fetch('https://sas.makecard.com.br/webhook_push_working.php?agendamento_id=' + agendamento_id, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.text())
                    .then(data => {
                        console.log('✅ Push notification enviado:', data);
                    })
                    .catch(error => {
                        console.error('❌ Erro ao enviar push notification:', error);
                    });
                }
                
                // Reabilitar botão salvar
                reabilitarBotaoSalvar();
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: "Sucesso!",
                        text: response.message,
                        icon: "success"
                    }).then(() => {
                        // Fechar modal
                        $("#ModalEditaAgendamento").modal("hide");
                        
                        // Atualizar tabela
                        atualizarTabela();
                    });
                } else {
                    alert('Sucesso: ' + response.message);
                    $("#ModalEditaAgendamento").modal("hide");
                    atualizarTabela();
                }
            } else {
                // Reabilitar botão salvar em caso de erro
                reabilitarBotaoSalvar();
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: "Erro!",
                        text: response.message || "Erro ao salvar agendamento.",
                        icon: "error"
                    });
                } else {
                    alert('Erro: ' + (response.message || "Erro ao salvar agendamento."));
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Erro no AJAX:', error);
            
            // Reabilitar botão salvar em caso de erro AJAX
            reabilitarBotaoSalvar();
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: "Erro!",
                    text: "Erro de comunicação: " + error,
                    icon: "error"
                });
            } else {
                alert('Erro de comunicação: ' + error);
            }
        }
    });
}

/**
 * 🔄 ATUALIZAR TABELA
 */
function atualizarTabela() {
    console.log('🔄 Atualizando tabela...');
    
    if (tabela_agendamento) {
        tabela_agendamento.ajax.reload(null, false);
        console.log('✅ Tabela atualizada');
    } else {
        console.log('⚠️ Tabela não existe, carregando novamente...');
        carregarTabelaInicial();
    }
}

/**
 * 🧹 LIMPAR FILTROS
 */
function limparFiltros() {
    console.log('🧹 Limpando filtros...');
    
    // Limpar campos de filtro
    $('#filtro_profissional').val('');
    $('#filtro_especialidade').val('');
    $('#filtro_convenio').val('');
    
    // Selecionar filtro padrão (Pendentes)
    $('#RadioPendentes').prop('checked', true).trigger('change');
    
    console.log('✅ Filtros limpos');
}

/**
 * ✅ VERIFICAR STATUS INICIAL BASEADO NA DATA AGENDADA
 */
function verificarStatusInicialDataAgendada() {
    console.log('✅ Verificando status inicial baseado na data agendada...');
    
    var dataAgendada = $('#C_data_agendada_agendamento').val();
    var statusField = $('#C_status_agendamento');
    
    if (dataAgendada && dataAgendada.trim() !== '') {
        // Se existe data agendada, garantir que status seja "Confirmado"
        if (statusField.val() !== '2') {
            statusField.val('2');
            console.log('📅 Data agendada presente - Status ajustado para Confirmado');
        }
    } else {
        // Se não existe data agendada, garantir que status seja "Pendente"
        if (statusField.val() !== '1') {
            statusField.val('1');
            console.log('🔄 Data agendada ausente - Status ajustado para Pendente');
        }
    }
}

/**
 * 🧹 LIMPAR ÍCONE DE DATA AGENDADA
 */
function limparIconeDataAgendada() {
    console.log('🧹 Limpando ícone de data agendada...');
    
    var $campoData = $('#C_data_agendada_agendamento');
    
    if ($campoData.length > 0) {
        // Remover o botão se existir
        $('#btnLimparDataAgendada').remove();
        
        // Restaurar padding original do campo
        $campoData.css('padding-right', '');
        
        // Se o campo está em um container personalizado, remover o wrapper
        var $container = $campoData.closest('.data-field-container');
        if ($container.length > 0) {
            $campoData.unwrap();
        }
        
        console.log('✅ Ícone de data removido e layout restaurado');
    }
}

/**
 * 🗑️ ADICIONAR ÍCONE PARA LIMPAR DATA AGENDADA
 */
function adicionarIconeLimparData() {
    console.log('🗑️ Adicionando ícone de limpar data...');
    
    // Encontrar o campo de data agendada
    var $campoData = $('#C_data_agendada_agendamento');
    
    if ($campoData.length > 0) {
        // Criar wrapper para o campo de data com layout mais harmonioso
        var $fieldWrapper = $campoData.parent();
        
        // Envolver o campo em um container personalizado para posicionamento
        $campoData.wrap('<div class="data-field-container" style="position: relative; display: inline-block; width: 100%;"></div>');
        $fieldWrapper = $campoData.parent();
        
        // Criar o botão de limpar com design mais elegante
        var btnLimpar = $('<button>', {
            type: 'button',
            id: 'btnLimparDataAgendada',
            title: 'Limpar data agendada',
            'class': 'btn',
            style: 'position: absolute; right: 8px; top: 50%; transform: translateY(-50%); ' +
                   'border: none; background: transparent; padding: 2px 6px; ' +
                   'z-index: 10; border-radius: 3px; transition: all 0.2s ease;',
            html: '<i class="fa fa-times-circle" style="color: #dc3545; font-size: 14px;"></i>'
        });
        
        // Adicionar efeitos hover
        btnLimpar.hover(
            function() {
                $(this).css({
                    'background-color': '#f8f9fa',
                    'transform': 'translateY(-50%) scale(1.1)'
                });
                $(this).find('i').css('color', '#dc3545');
            },
            function() {
                $(this).css({
                    'background-color': 'transparent',
                    'transform': 'translateY(-50%) scale(1)'
                });
                $(this).find('i').css('color', '#dc3545');
            }
        );
        
        // Adicionar o botão ao container
        $fieldWrapper.append(btnLimpar);
        
        // Ajustar padding do campo para não sobrepor o botão
        $campoData.css('padding-right', '35px');
        
        console.log('✅ Ícone de limpar data adicionado com layout harmonioso');
    } else {
        console.warn('⚠️ Campo de data agendada não encontrado');
    }
}

/**
 * 📏 CONFIGURAR CAMPO NOME PARA OCUPAR LINHA TODA
 */
function configurarCampoNomeParaLinhaToda() {
    console.log('📏 Configurando campo nome para ocupar toda a linha...');
    
    var $nomeAssociado = $('#C_nome_associado_agendamento');
    
    if ($nomeAssociado.length === 0) {
        console.warn('⚠️ Campo nome do associado não encontrado');
        return;
    }
    
    // Encontrar todos os possíveis containers
    var $formGroup = $nomeAssociado.closest('.form-group');
    var $colContainer = $nomeAssociado.closest('[class*="col-"]');
    var $divContainer = $nomeAssociado.closest('div[class*="col-"]');
    var $row = $nomeAssociado.closest('.row');
    
    console.log('🔍 Analisando estrutura do campo nome:');
    console.log('- Form Group:', $formGroup.length > 0 ? $formGroup.attr('class') : 'não encontrado');
    console.log('- Col Container:', $colContainer.length > 0 ? $colContainer.attr('class') : 'não encontrado');
    console.log('- Div Container:', $divContainer.length > 0 ? $divContainer.attr('class') : 'não encontrado');
    console.log('- Row:', $row.length > 0 ? 'encontrada' : 'não encontrada');
    
    // Estratégia 1: Manipular container de coluna se existir
    var $targetContainer = $colContainer.length > 0 ? $colContainer : $divContainer;
    
    if ($targetContainer.length > 0) {
        // Salvar classes originais
        $targetContainer.data('original-classes', $targetContainer.attr('class'));
        $targetContainer.data('original-style', $targetContainer.attr('style') || '');
        
        // Remover todas as classes de coluna e adicionar col-12
        var currentClasses = $targetContainer.attr('class');
        var newClasses = currentClasses.replace(/col-\w*-\d+/g, '').replace(/\s+/g, ' ').trim();
        newClasses += ' col-12 col-md-12 col-sm-12 col-xs-12';
        
        $targetContainer.attr('class', newClasses);
        
        // Forçar largura 100% via CSS
        $targetContainer.css({
            'width': '100%',
            'max-width': '100%',
            'flex': '0 0 100%'
        });
        
        console.log('✅ Container modificado:', newClasses);
    }
    
    // Estratégia 2: Configurar o campo diretamente
    $nomeAssociado.css({
        'width': '100% !important',
        'max-width': '100%',
        'box-sizing': 'border-box',
        'display': 'block'
    });
    
    // Estratégia 3: Se estiver em um form-group, forçar largura total
    if ($formGroup.length > 0) {
        $formGroup.css({
            'width': '100%',
            'max-width': '100%'
        });
    }
    
    // Estratégia 4: Verificar se está dentro de um row e forçar quebra de linha
    if ($row.length > 0) {
        // Se o campo está em uma row, criar uma nova linha só para ele
        var $parentContainer = $targetContainer.length > 0 ? $targetContainer : $nomeAssociado.parent();
        
        if ($parentContainer.siblings().length > 0) {
            // Há outros elementos na mesma linha, mover para nova linha
            $parentContainer.addClass('col-12 w-100');
            $parentContainer.css('flex-basis', '100%');
            console.log('✅ Campo movido para nova linha');
        }
    }
    
    // Estratégia 5: Criar estilo CSS inline personalizado mais agressivo
    var customStyle = `
        #ModalEditaAgendamento #C_nome_associado_agendamento {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 100% !important;
        }
        #ModalEditaAgendamento #C_nome_associado_agendamento.form-control {
            width: 100% !important;
            max-width: 100% !important;
        }
    `;
    
    // Adicionar estilo personalizado se não existir
    if ($('#style-nome-associado-modal').length === 0) {
        $('<style id="style-nome-associado-modal">' + customStyle + '</style>').appendTo('head');
        console.log('✅ Estilo CSS personalizado adicionado');
    }
    
    // Estratégia 6: Forçar via jQuery de forma mais agressiva
    setTimeout(function() {
        $nomeAssociado.attr('style', $nomeAssociado.attr('style') + '; width: 100% !important;');
        console.log('✅ Estilo inline forçado');
    }, 100);
    
    // Debug: Verificar se o campo realmente está ocupando toda a linha
    setTimeout(function() {
        var larguraModal = $('#ModalEditaAgendamento .modal-body').width();
        var larguraCampo = $nomeAssociado.outerWidth();
        var porcentagem = (larguraCampo / larguraModal) * 100;
        
        console.log('🔍 Debug largura do campo nome:');
        console.log('- Largura do modal:', larguraModal + 'px');
        console.log('- Largura do campo:', larguraCampo + 'px');
        console.log('- Porcentagem ocupada:', porcentagem.toFixed(1) + '%');
        
        if (porcentagem < 80) {
            console.warn('⚠️ Campo não está ocupando largura suficiente, aplicando correção...');
            
            // Aplicação forçada mais agressiva
            $nomeAssociado.css({
                'width': larguraModal + 'px !important',
                'max-width': larguraModal + 'px !important'
            });
            
            // Se tiver container, forçar também
            if ($targetContainer.length > 0) {
                $targetContainer.css({
                    'width': larguraModal + 'px !important',
                    'max-width': larguraModal + 'px !important'
                });
            }
        }
    }, 300);
    
    console.log('📏 Campo nome configurado para ocupar toda a linha');
}

/**
 * 🧹 RESTAURAR CAMPO NOME AO ESTADO ORIGINAL
 */
function restaurarCampoNomeEstadoOriginal() {
    console.log('🧹 Restaurando campo nome ao estado original...');
    
    var $nomeAssociado = $('#C_nome_associado_agendamento');
    
    if ($nomeAssociado.length === 0) {
        return;
    }
    
    // Restaurar CSS do campo
    $nomeAssociado.css({
        'width': '',
        'max-width': '',
        'box-sizing': '',
        'display': ''
    });
    
    // Encontrar container que foi modificado
    var $containers = $nomeAssociado.closest('[class*="col-"]');
    
    $containers.each(function() {
        var $container = $(this);
        
        // Restaurar classes originais se foram salvas
        if ($container.data('original-classes')) {
            $container.attr('class', $container.data('original-classes'));
            $container.removeData('original-classes');
        }
        
        // Restaurar estilo original
        if ($container.data('original-style')) {
            $container.attr('style', $container.data('original-style'));
            $container.removeData('original-style');
        } else {
            $container.css({
                'width': '',
                'max-width': '',
                'flex': ''
            });
        }
    });
    
    // Remover estilo CSS personalizado
    $('#style-nome-associado-modal').remove();
    
    console.log('✅ Campo nome restaurado ao estado original');
}

/**
 * 📊 FUNÇÃO AUXILIAR PARA CONVERTER MOEDA PARA NÚMERO
 */
function moedaParaNumero(valor) {
    if (!valor) return 0;
    // Remove pontos (separadores de milhares) e substitui vírgula por ponto
    return parseFloat(valor.toString().replace(/\./g, '').replace(',', '.')) || 0;
} 