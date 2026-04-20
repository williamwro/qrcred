// consultar_colaborador.js - Versão para Empregador (standalone)
var table_origem;
var tableconsulta;
var table_solicitacoes;
var matricula;
var empregador;
var nome = "";
var abreviacao = "";
var Codempregador_origem;
var mes_escolhido;
var divisao;
var mescorrente = "";
var id_associado_origem;
var id_divisao_origem;
var usuario_global;
var usuario_cod;
var empregador_id;
var empregador_nome;

$(document).ready(function(){
    // Verificar se está logado como empregador
    empregador_id = sessionStorage.getItem('empregador_id');
    empregador_nome = sessionStorage.getItem('empregador_nome');
    divisao = sessionStorage.getItem('empregador_divisao');
    var empregador_usuario = sessionStorage.getItem('empregador_usuario');
    var tipo_login = sessionStorage.getItem('tipo_login');

    if (!empregador_id || tipo_login !== 'empregador') {
        Swal.fire({
            icon: 'warning',
            title: 'Acesso Negado',
            text: 'Você precisa fazer login para acessar esta página.',
            confirmButtonText: 'Ir para Login'
        }).then(() => {
            window.location.href = '../index.html';
        });
        return;
    }

    // Exibir informações do empregador
    $('#empregadorNome').text(empregador_nome);
    $('#nomeEmpregadorHeader').text(empregador_nome);
    $('#usuarioEmpregador').text(empregador_usuario);
    
    // Preencher o empregador automaticamente
    Codempregador_origem = empregador_id;
    $("#C_id_empregador_origem").val(empregador_id);
    $("#C_empregador_origem").text(empregador_nome);

    // Logout
    $('#btnLogout').click(function() {
        Swal.fire({
            title: 'Sair do Sistema?',
            text: 'Você será desconectado.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, sair',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                sessionStorage.clear();
                window.location.href = '../index.html';
            }
        });
    });

    // Um modal por cima do outro
    $(document).on('show.bs.modal', '.modal', function (event) {
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function() {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });

    // Carregar meses no select
    $.getJSON("meses_conta.php", { "origem": "convenio", "divisao": divisao }, function(data) {
        $('#C_mes').append('<option value="todos">Todos os meses</option>');
        $.each(data, function(index, value) {
            if (value.mes_corrente !== undefined) {
                mescorrente = value.mes_corrente;
            }
            if (value.abreviacao !== undefined) {
                if (mescorrente === value.abreviacao) {
                    $('#C_mes').append('<option selected value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                } else {
                    $('#C_mes').append('<option value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                }
            }
        });
    });

    // Carregar todas as solicitações de bloqueio ao abrir a tela (filtro padrão: Pendente)
    setTimeout(function() {
        carrega_solicitacoes_bloqueio($('#filtro_situacao_bloqueio').val());
    }, 500);

    // Evento de mudança do filtro de situação de bloqueio
    $("#filtro_situacao_bloqueio").change(function() {
        var situacao = $(this).val();
        carrega_solicitacoes_bloqueio(situacao);
    });

    // Evento de mudança do select de mês
    $("#C_mes").change(function() {
        matricula = $('#C_matricula_origem').val();
        mes_escolhido = $('#C_mes').val();
        if (matricula !== "") {
            carrega_origem();
        }
    });

    // Botão Consultar
    $("#btnConsultar").click(function() {
        $("#ModalBuscaAssociado").modal("show");
        
        setTimeout(function() {
            if (!$.fn.dataTable.isDataTable('#tabela_busca_associado')) {
                tableconsulta = $('#tabela_busca_associado').DataTable({
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                    processing: true,
                    ServerSide: false,
                    autoWidth: true,
                    scrollX: true,
                    columnDefs: [
                        { "visible": false, "targets": [3] },
                        { "visible": false, "targets": [4] },
                        { "visible": false, "targets": [5] },
                        { "visible": false, "targets": [6] }
                    ],
                    ajax: {
                        url: 'exibe_associados_empregador.php',
                        method: 'POST',
                        data: { 
                            "divisao": divisao,
                            "empregador_id": empregador_id
                        },
                        dataType: 'json'
                    },
                    deferRender: true,
                    order: [[1, "asc"]],
                    columns: [
                        { data: "matricula" },
                        { data: "nome" },
                        { data: "endereco" },
                        { data: "numero" },
                        { data: "bairro" },
                        { data: "nascimento" },
                        { data: "empregador" },
                        { data: "abreviacao" },
                        { data: "id_associado", visible: false },
                        { data: "id_divisao", visible: false }
                    ],
                    language: {
                        decimal: ",",
                        thousands: ".",
                        zeroRecords: "Não há dados",
                        emptyTable: "Não há dados.",
                        infoEmpty: 'Zero registros',
                        paginate: {
                            next: "Próximo",
                            previous: "Anterior",
                            first: "Primeiro",
                            last: "Último"
                        },
                        search: "Pesquisar",
                        info: "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                        infoFiltered: "(Filtrados de _MAX_ registros)",
                        lengthMenu: "_MENU_ resultados por página"
                    },
                    pagingType: "full_numbers"
                });

                $('#ModalBuscaAssociado tbody').on('click', 'tr', function() {
                    if ($(this).hasClass('selected')) {
                        $(this).removeClass('selected');
                    } else {
                        tableconsulta.$('tr.selected').removeClass('selected');
                        $(this).addClass('selected');
                    }
                });
            } else {
                tableconsulta = $('#tabela_busca_associado').DataTable();
                tableconsulta.ajax.reload();
            }
        }, 300);
    });

    // Clique na linha da tabela de busca de associado
    $('#tabela_busca_associado').on('click', 'tr', function() {
        var data = tableconsulta.row(this).data();
        if (!data) return;
        
        nome = data["nome"];
        abreviacao = data["abreviacao"];
        matricula = data["matricula"];
        Codempregador_origem = data["codempregador"] || empregador_id;
        id_associado_origem = data["id_associado"];
        id_divisao_origem = data["id_divisao"];

        $("#C_matricula_origem").val(matricula);
        $("#C_nome_origem").val(nome);
        $("#C_empregador_origem").text(abreviacao || empregador_nome);
        $("#C_id_empregador_origem").val(Codempregador_origem);
        $("#C_id_associado").val(id_associado_origem);
        $("#C_id_divisao").val(id_divisao_origem);
        mes_escolhido = $('#C_mes').val();
        
        // Habilitar botão de solicitar bloqueio e select de mês
        $("#btnSolicitarBloqueio").prop("disabled", false);
        $("#C_mes").prop("disabled", false);
        
        carrega_origem();
        carrega_cartao_associado();
        carrega_solicitacoes_bloqueio($('#filtro_situacao_bloqueio').val());
        $("#ModalBuscaAssociado").modal("hide");
    });

    // Função para carregar os dados do cartão do associado
    function carrega_cartao_associado() {
        $.ajax({
            url: "buscar_cartao_associado.php",
            method: "POST",
            data: {
                'id_associado': id_associado_origem,
                'id_divisao': id_divisao_origem
            },
            dataType: "json",
            success: function(response) {
                if (response.status === "success") {
                    $("#C_numero_cartao").text(response.numero_cartao);
                    
                    var situacao = response.situacao_descricao;
                    var codSituacao = response.cod_situacao;
                    $("#C_situacao_cartao").text(situacao);
                    
                } else {
                    $("#C_numero_cartao").text("-");
                    $("#C_situacao_cartao").text("Sem cartão");
                }
            },
            error: function(xhr, status, error) {
                console.error("Erro ao buscar cartão:", error);
                $("#C_numero_cartao").text("-");
                $("#C_situacao_cartao").text("-");
            }
        });
    }

    // Botão Solicitar Bloqueio do Cartão
    $("#btnSolicitarBloqueio").click(function() {
        var id_empregador = $("#C_id_empregador_origem").val();
        var id_associado = $("#C_id_associado").val();
        var matricula = $("#C_matricula_origem").val();
        var nome_associado = $("#C_nome_origem").val();

        if (!id_associado || id_associado === "") {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Selecione um associado primeiro!'
            });
            return;
        }

        // Verificar se já existe solicitação pendente para este associado
        Swal.fire({
            title: 'Verificando...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: "verificar_solicitacao_pendente.php",
            method: "POST",
            dataType: "json",
            data: {
                id_associado: id_associado,
                empregador_id: empregador_id
            },
            success: function(response) {
                Swal.close();
                
                if (response.existe_pendente) {
                    // Já existe solicitação pendente
                    Swal.fire({
                        icon: 'warning',
                        title: 'Solicitação Já Existe',
                        html: '<p>Já existe uma solicitação de bloqueio <strong>PENDENTE</strong> para este associado.</p>' +
                              '<p>Código de verificação: <strong>' + response.cod_verificacao + '</strong></p>' +
                              '<p>Data da solicitação: <strong>' + response.data_solicitacao + '</strong></p>' +
                              '<p>Aguarde a análise da solicitação anterior.</p>',
                        confirmButtonText: 'Entendi'
                    });
                } else {
                    // Não existe pendente, pode solicitar
                    Swal.fire({
                        title: 'Confirmar Solicitação de Bloqueio',
                        html: '<p>Deseja solicitar o bloqueio do cartão do associado:</p>' +
                              '<p><strong>' + nome_associado + '</strong></p>' +
                              '<p>Matrícula: <strong>' + matricula + '</strong></p>',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sim, Solicitar Bloqueio',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Processando...',
                                allowOutsideClick: false,
                                didOpen: () => { Swal.showLoading(); }
                            });
                            
                            $.ajax({
                                url: "solicitar_bloqueio.php",
                                method: "POST",
                                dataType: "json",
                                data: {
                                    id_empregador: id_empregador,
                                    id_associado: id_associado,
                                    divisao: divisao
                                },
                                success: function(response) {
                                    Swal.close();
                                    
                                    if (response.status === "success") {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Solicitação Efetuada!',
                                            html: '<p>A solicitação de bloqueio do cartão foi registrada com sucesso.</p>' +
                                                  '<p><strong>Aguarde a resposta.</strong></p>' +
                                                  '<p>Código de verificação: <strong>' + response.cod_verificacao + '</strong></p>',
                                            confirmButtonText: 'OK'
                                        });
                                        carrega_solicitacoes_bloqueio($('#filtro_situacao_bloqueio').val());
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Erro',
                                            text: response.message || 'Erro ao processar a solicitação.'
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    Swal.close();
                                    console.error("Erro:", error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Erro',
                                        text: 'Erro ao processar a solicitação. Tente novamente.'
                                    });
                                }
                            });
                        }
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error("Erro ao verificar:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Erro ao verificar solicitações pendentes. Tente novamente.'
                });
            }
        });
    });

    // Função para carregar os dados da conta
    function carrega_origem() {
        Swal.fire({
            title: 'Carregando...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: "consultar_conta_list.php",
            method: "POST",
            data: {
                'matricula': matricula,
                'mes': mes_escolhido,
                'codempregador': Codempregador_origem,
                'id_associado': id_associado_origem,
                'id_divisao': id_divisao_origem
            },
            dataType: "json",
            success: function(datab) {
                Swal.close();

                if (mes_escolhido !== 'todos') {
                    var cartao = parseFloat(datab["categorias"] ? datab["categorias"].cartao : 0) || 0;
                    var taxa_cartao = parseFloat(datab["categorias"] ? datab["categorias"].taxacartao : 0) || 0;
                    var emprestimo = parseFloat(datab["categorias"] ? datab["categorias"].adiantamento : 0) || 0;
                    var total = cartao + taxa_cartao + emprestimo;
                    var limite = datab["limite"] ? datab["limite"].limite * 1 : 0;
                    var saldo = datab["limite"] ? datab["limite"].limite - total : 0;

                    $("#empgasto").html("R$ " + emprestimo.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    $("#taxagasto").html("R$ " + taxa_cartao.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    $("#cartgasto").html("R$ " + cartao.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    $("#totalgasto").html("R$ " + total.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                } else {
                    var limite = datab["limite"] ? datab["limite"].limite * 1 : 0;
                    var saldo = datab["limite"] ? datab["limite"].limite : 0;
                    $("#empgasto").html('');
                    $("#taxagasto").html('');
                    $("#cartgasto").html('');
                    $("#totalgasto").html('');
                }

                if (isNaN(limite)) {
                    limite = '';
                } else {
                    limite = parseFloat(limite).toFixed(2).replace(".", ",");
                }
                if (isNaN(saldo)) {
                    saldo = '';
                } else {
                    saldo = parseFloat(saldo).toFixed(2).replace(".", ",");
                }

                $("#limite").val(limite);
                $("#C_limite").text("R$ " + limite);
                $("#C_saldo").text("R$ " + saldo);

                // Inicializar ou recarregar a tabela de lançamentos
                if ($.fn.dataTable.isDataTable('#tab_matricula_origem')) {
                    table_origem.destroy();
                }

                table_origem = $('#tab_matricula_origem').DataTable({
                    columnDefs: [
                        { "targets": [1], "visible": false, "searchable": false }
                    ],
                    order: [[0, 'asc']],
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                    processing: false,
                    ServerSide: false,
                    autoWidth: true,
                    ajax: {
                        url: 'consultar_conta_list.php',
                        method: 'POST',
                        data: {
                            'matricula': matricula,
                            'mes': mes_escolhido,
                            'codempregador': Codempregador_origem
                        },
                        dataType: 'json',
                        dataSrc: function(json) {
                            if (!json) { return []; }
                            if (Array.isArray(json)) { return json; }
                            if (json.data && Array.isArray(json.data)) { return json.data; }
                            return [];
                        }
                    },
                    deferRender: true,
                    columns: [
                        { data: "registro" },
                        { data: "matricula" },
                        {
                            data: "valor",
                            render: $.fn.dataTable.render.number('.', ',', 2),
                            className: "text-center"
                        },
                        { data: "data" },
                        { data: "hora" },
                        { data: "parcela" },
                        { data: "mes" },
                        { data: "razaosocial" },
                        { data: "nomefantasia" },
                        { data: "funcionario" },
                        { data: "mes_controle" }
                    ],
                    language: {
                        decimal: ",",
                        thousands: ".",
                        processing: "Processando...",
                        zeroRecords: "Não há dados",
                        emptyTable: "Não há dados.",
                        infoEmpty: 'Zero registros',
                        paginate: {
                            next: "Próximo",
                            previous: "Anterior",
                            first: "Primeiro",
                            last: "Último"
                        },
                        search: "Pesquisar",
                        info: "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                        infoFiltered: "(Filtrados de _MAX_ registros)",
                        lengthMenu: "_MENU_ resultados por página"
                    }
                });
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error("Erro ao carregar dados:", error);
            }
        });
    }

    // Função para carregar todas as solicitações de bloqueio
    function carrega_solicitacoes_bloqueio(situacao) {
        if ($.fn.dataTable.isDataTable('#tab_solicitacoes_bloqueio')) {
            table_solicitacoes.destroy();
        }

        var filtroSituacao = situacao || 'todos';

        table_solicitacoes = $('#tab_solicitacoes_bloqueio').DataTable({
            order: [[3, 'desc']],
            lengthMenu: [[5, 10, 25, -1], [5, 10, 25, "todos"]],
            pageLength: 10,
            processing: false,
            ServerSide: false,
            autoWidth: true,
            ajax: {
                url: 'listar_solicitacoes_bloqueio.php',
                method: 'POST',
                data: {
                    'divisao': divisao,
                    'empregador_id': empregador_id,
                    'situacao': filtroSituacao
                },
                dataType: 'json',
                dataSrc: function(json) {
                    if (!json) { return []; }
                    if (Array.isArray(json)) { return json; }
                    if (json.data && Array.isArray(json.data)) { return json.data; }
                    return [];
                }
            },
            columns: [
                { data: "cod_verificacao" },
                { data: "nome_associado" },
                { data: "matricula" },
                { data: "data_hora" },
                { data: "data_hora_resposta" },
                { data: "situacao" },
                { 
                    data: null,
                    orderable: false,
                    className: "text-center",
                    render: function(data, type, row) {
                        // Mostrar botão de cancelar apenas se a situação for "Pendente" (cod_situacao = 1)
                        if (row.cod_situacao == 1) {
                            return '<button class="btn btn-sm btn-danger-modern btn-cancelar-bloqueio" data-id="' + row.id + '" data-cod="' + row.cod_verificacao + '" data-nome="' + row.nome_associado + '" title="Cancelar Solicitação">' +
                                   '<i class="fas fa-times"></i> Cancelar' +
                                   '</button>';
                        } else {
                            return '<span class="text-muted" style="font-size: 0.75rem;">-</span>';
                        }
                    }
                }
            ],
            language: {
                decimal: ",",
                thousands: ".",
                processing: "Processando...",
                zeroRecords: "Nenhuma solicitação encontrada",
                emptyTable: "Nenhuma solicitação de bloqueio.",
                infoEmpty: 'Zero registros',
                paginate: {
                    next: "Próximo",
                    previous: "Anterior",
                    first: "Primeiro",
                    last: "Último"
                },
                search: "Pesquisar",
                info: "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                infoFiltered: "(Filtrados de _MAX_ registros)",
                lengthMenu: "_MENU_ resultados por página"
            }
        });
    }

    // Evento de clique no botão Cancelar Solicitação de Bloqueio
    $(document).on('click', '.btn-cancelar-bloqueio', function() {
        var id_solicitacao = $(this).data('id');
        var cod_verificacao = $(this).data('cod');
        var nome_associado = $(this).data('nome');

        Swal.fire({
            title: 'Cancelar Solicitação de Bloqueio?',
            html: '<p>Deseja realmente cancelar a solicitação de bloqueio do cartão?</p>' +
                  '<p><strong>Associado:</strong> ' + nome_associado + '</p>' +
                  '<p><strong>Código:</strong> ' + cod_verificacao + '</p>' +
                  '<p class="text-danger mt-3"><small><i class="fas fa-exclamation-triangle"></i> Esta ação não poderá ser desfeita.</small></p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check"></i> Sim, Cancelar Solicitação',
            cancelButtonText: '<i class="fas fa-times"></i> Não'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processando...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: "cancelar_solicitacao_bloqueio.php",
                    method: "POST",
                    dataType: "json",
                    data: {
                        id_solicitacao: id_solicitacao,
                        empregador_id: empregador_id
                    },
                    success: function(response) {
                        Swal.close();
                        
                        if (response.status === "success") {
                            Swal.fire({
                                icon: 'success',
                                title: 'Solicitação Cancelada!',
                                html: '<p>A solicitação de bloqueio foi cancelada com sucesso.</p>' +
                                      '<p><strong>Código:</strong> ' + cod_verificacao + '</p>',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Recarregar a tabela de solicitações
                                carrega_solicitacoes_bloqueio($('#filtro_situacao_bloqueio').val());
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro',
                                text: response.message || 'Erro ao cancelar a solicitação.'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        console.error("Erro:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: 'Erro ao processar o cancelamento. Tente novamente.'
                        });
                    }
                });
            }
        });
    });
});
