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

$(document).ready(function(){
    divisao = sessionStorage.getItem("divisao");
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");

    // Um modal por cima do outro
    $(document).on('show.bs.modal', '.modal', function (event) {
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function() {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });

    // Carregar meses no select
    $.getJSON("../Adm/pages/conta/meses_conta.php", { "origem": "convenio", "divisao": divisao }, function(data) {
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
    // Aguarda o DataTable estar disponível antes de carregar
    setTimeout(function() {
        loadDataTableScripts(function() {
            carrega_solicitacoes_bloqueio($('#filtro_situacao_bloqueio').val());
        });
    }, 300);

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

    // Função para carregar scripts do DataTable dinamicamente
    function loadDataTableScripts(callback) {
        if (typeof $.fn.DataTable !== 'undefined') {
            callback();
            return;
        }
        
        if (!$('link[href*="datatables"]').length) {
            $('<link>')
                .attr('rel', 'stylesheet')
                .attr('href', '//cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css')
                .appendTo('head');
        }
        
        $.getScript('//cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js', function() {
            console.log('DataTable carregado com sucesso');
            $.when(
                $.getScript('https://cdn.datatables.net/rowgroup/1.1.2/js/dataTables.rowGroup.min.js'),
                $.getScript('https://cdn.datatables.net/select/1.3.1/js/dataTables.select.min.js'),
                $.getScript('https://cdn.datatables.net/fixedheader/3.1.6/js/dataTables.fixedHeader.min.js')
            ).done(function() {
                console.log('Extensões do DataTable carregadas');
                callback();
            }).fail(function() {
                console.error('Erro ao carregar extensões do DataTable');
                callback();
            });
        }).fail(function() {
            console.error('Erro ao carregar DataTable');
            alert('Erro ao carregar a biblioteca DataTable. Verifique sua conexão com a internet.');
        });
    }

    // Botão Consultar
    $("#btnConsultar").click(function() {
        $("#ModalBuscaAssociado").modal("show");
        
        loadDataTableScripts(function() {
            setTimeout(function() {
                if (!$.fn.dataTable.isDataTable('#tabela_busca_associado')) {
                    tableconsulta = $('#tabela_busca_associado').DataTable({
                        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                        processing: false,
                        ServerSide: false,
                        responsive: ($.fn.dataTable && $.fn.dataTable.Responsive) ? {
                            details: {
                                display: $.fn.dataTable.Responsive.display.modal({
                                    header: function(row) {
                                        return 'Detalhes';
                                    }
                                }),
                                renderer: $.fn.dataTable.Responsive.renderer.tableAll({
                                    tableClass: 'table'
                                })
                            }
                        } : true,
                        autoWidth: true,
                        scrollX: true,
                        scrollCollapse: false,
                        columnDefs: [
                            { "visible": false, "targets": [3] },
                            { "visible": false, "targets": [4] },
                            { "visible": false, "targets": [5] },
                            { "visible": false, "targets": [6] }
                        ],
                        ajax: {
                            url: 'pages/conta/exibe_todos_associados.php',
                            method: 'POST',
                            data: { "divisao": divisao },
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
                            infoPostFix: "",
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

                $("#ModalBuscaAssociado .modal-body").css({
                    'overflow-x': 'auto',
                    'overflow-y': 'auto',
                    'max-width': '100%'
                });
                $('#tabela_busca_associado_wrapper').css({
                    'width': '100%',
                    'overflow-x': 'auto',
                    'overflow-y': 'visible'
                });
                $('#tabela_busca_associado_filter').css({
                    'position': 'sticky',
                    'top': '0',
                    'background': 'white',
                    'z-index': '10',
                    'padding': '10px 0'
                });

                setTimeout(function() {
                    if ($.fn.dataTable.isDataTable('#tabela_busca_associado')) {
                        var dt = $('#tabela_busca_associado').DataTable();
                        dt.columns.adjust().draw(false);
                        if (dt.responsive && dt.responsive.recalc) {
                            dt.responsive.recalc();
                        }
                    }
                }, 150);
            }, 500);
        });
    });

    // Clique na linha da tabela de busca de associado
    $('#tabela_busca_associado').on('click', 'tr', function() {
        var data = tableconsulta.row(this).data();
        if (!data) return;
        
        nome = data["nome"];
        abreviacao = data["abreviacao"];
        matricula = data["matricula"];
        Codempregador_origem = data["codempregador"];
        id_associado_origem = data["id_associado"];
        id_divisao_origem = data["id_divisao"];

        $("#C_matricula_origem").val(matricula);
        $("#C_nome_origem").val(nome);
        $("#C_empregador_origem").val(abreviacao);
        $("#C_id_empregador_origem").val(Codempregador_origem);
        $("#C_id_associado").val(id_associado_origem);
        mes_escolhido = $('#C_mes').val();
        
        // Habilitar botão de solicitar bloqueio e select de mês
        $("#btnSolicitarBloqueio").prop("disabled", false);
        $("#C_mes").prop("disabled", false);
        
        carrega_origem();
        carrega_cartao_associado();
        carrega_solicitacoes_bloqueio();
        $("#ModalBuscaAssociado").modal("hide");
    });

    // Função para carregar os dados do cartão do associado
    function carrega_cartao_associado() {
        $.ajax({
            url: "pages/consultar/buscar_cartao_associado.php",
            method: "POST",
            data: {
                'id_associado': id_associado_origem,
                'id_divisao': id_divisao_origem
            },
            dataType: "json",
            success: function(response) {
                if (response.status === "success") {
                    $("#C_numero_cartao").val(response.numero_cartao);
                    
                    // Aplicar cor de acordo com a situação
                    var situacao = response.situacao_descricao;
                    var codSituacao = response.cod_situacao;
                    $("#C_situacao_cartao").val(situacao);
                    
                    // Cores por situação
                    var corFundo = "#ffffff";
                    var corTexto = "#000000";
                    
                    switch(parseInt(codSituacao)) {
                        case 1: // LIBERADO
                            corFundo = "#28a745";
                            corTexto = "#ffffff";
                            break;
                        case 2: // BLOQUEADO
                            corFundo = "#dc3545";
                            corTexto = "#ffffff";
                            break;
                        case 3: // CANCELADO
                            corFundo = "#6c757d";
                            corTexto = "#ffffff";
                            break;
                        case 4: // PRODUCAO
                            corFundo = "#17a2b8";
                            corTexto = "#ffffff";
                            break;
                        case 5: // SEGUNDA VIA
                            corFundo = "#ffc107";
                            corTexto = "#000000";
                            break;
                        case 6: // DISPONIVEL
                            corFundo = "#007bff";
                            corTexto = "#ffffff";
                            break;
                        case 7: // ENTREGUE
                            corFundo = "#28a745";
                            corTexto = "#ffffff";
                            break;
                        case 8: // BLOQUEIO MSG
                            corFundo = "#fd7e14";
                            corTexto = "#ffffff";
                            break;
                    }
                    
                    $("#C_situacao_cartao").css({
                        "background-color": corFundo,
                        "color": corTexto,
                        "font-weight": "bold",
                        "text-align": "center"
                    });
                } else {
                    $("#C_numero_cartao").val("");
                    $("#C_situacao_cartao").val("Sem cartão");
                    $("#C_situacao_cartao").css({
                        "background-color": "#e9ecef",
                        "color": "#6c757d",
                        "font-weight": "normal",
                        "text-align": "center"
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error("Erro ao buscar cartão:", error);
                $("#C_numero_cartao").val("");
                $("#C_situacao_cartao").val("");
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
                waitingDialog.show('Processando solicitação...');
                
                $.ajax({
                    url: "pages/consultar/solicitar_bloqueio.php",
                    method: "POST",
                    dataType: "json",
                    data: {
                        id_empregador: id_empregador,
                        id_associado: id_associado,
                        usuario_cod: usuario_cod,
                        divisao: divisao
                    },
                    success: function(response) {
                        waitingDialog.hide();
                        
                        if (response.status === "success") {
                            Swal.fire({
                                icon: 'success',
                                title: 'Solicitação Efetuada!',
                                html: '<p>A solicitação de bloqueio do cartão foi registrada com sucesso.</p>' +
                                      '<p><strong>Aguarde a resposta.</strong></p>' +
                                      '<p>Código de verificação: <strong>' + response.cod_verificacao + '</strong></p>',
                                confirmButtonText: 'OK'
                            });
                            // Recarregar tabela de solicitações
                            carrega_solicitacoes_bloqueio();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro',
                                text: response.message || 'Erro ao processar a solicitação.'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        waitingDialog.hide();
                        console.error("Erro:", error);
                        console.error("Response:", xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: 'Erro ao processar a solicitação. Tente novamente.'
                        });
                    }
                });
            }
        });
    });

    // Função para carregar os dados da conta
    function carrega_origem() {
        $.ajax({
            url: "pages/consultar/consultar_conta_list.php",
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
                if (mes_escolhido !== 'todos') {
                    var cartao = parseFloat(datab["categorias"] ? datab["categorias"].cartao : 0) || 0;
                    var taxa_cartao = parseFloat(datab["categorias"] ? datab["categorias"].taxacartao : 0) || 0;
                    var emprestimo = parseFloat(datab["categorias"] ? datab["categorias"].adiantamento : 0) || 0;
                    var total = cartao + taxa_cartao + emprestimo;
                    var limite = datab["limite"] ? datab["limite"].limite * 1 : 0;
                    var saldo = datab["limite"] ? datab["limite"].limite - total : 0;

                    $("#empgasto").html(emprestimo ? emprestimo.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0,00');
                    $("#taxagasto").html(taxa_cartao ? taxa_cartao.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0,00');
                    $("#cartgasto").html(cartao ? cartao.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0,00');
                    $("#totalgasto").html(total ? total.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0,00');
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
                    saldo = saldo.toString();
                    saldo = parseFloat(saldo).toFixed(2).replace(".", ",");
                }

                $("#limite").val(limite);
                $("#C_limite").val(limite.toLocaleString("pt-BR", { style: "decimal", currency: "BRL" }));
                $("#C_saldo").val(saldo.toLocaleString("pt-BR", { style: "decimal", currency: "BRL" }));

                // Inicializar ou recarregar a tabela de lançamentos
                if ($.fn.dataTable.isDataTable('#tab_matricula_origem')) {
                    table_origem.destroy();
                }

                table_origem = $('#tab_matricula_origem').DataTable({
                    columnDefs: [
                        { type: 'time-uni', targets: 4 },
                        { "targets": [1], "visible": false, "searchable": false }
                    ],
                    order: [[0, 'asc']],
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                    processing: false,
                    ServerSide: false,
                    responsive: ($.fn.dataTable && $.fn.dataTable.Responsive) ? {
                        details: {
                            display: $.fn.dataTable.Responsive.display.modal({
                                header: function(row) {
                                    return 'Detalhes';
                                }
                            }),
                            renderer: $.fn.dataTable.Responsive.renderer.tableAll({
                                tableClass: 'table'
                            })
                        }
                    } : true,
                    autoWidth: true,
                    ajax: {
                        url: 'pages/consultar/consultar_conta_list.php',
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
                        loadingRecords: "",
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
                        infoPostFix: "",
                        lengthMenu: "_MENU_ resultados por página"
                    }
                });

                waitingDialog.hide();
            },
            error: function(xhr, status, error) {
                console.error("Erro ao carregar dados:", error);
                waitingDialog.hide();
            }
        });
    }

    // Função para carregar todas as solicitações de bloqueio (com filtro por situação)
    function carrega_solicitacoes_bloqueio(situacao) {
        if ($.fn.dataTable.isDataTable('#tab_solicitacoes_bloqueio')) {
            table_solicitacoes.destroy();
        }

        var filtroSituacao = situacao || 'todos';

        table_solicitacoes = $('#tab_solicitacoes_bloqueio').DataTable({
            order: [[3, 'desc']], // Ordenar por data/hora solicitação (coluna 3 agora)
            lengthMenu: [[5, 10, 25, -1], [5, 10, 25, "todos"]],
            pageLength: 10,
            processing: false,
            ServerSide: false,
            autoWidth: true,
            ajax: {
                url: 'pages/consultar/listar_solicitacoes_bloqueio.php',
                method: 'POST',
                data: {
                    'divisao': divisao,
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
                { data: "situacao" }
            ],
            language: {
                decimal: ",",
                thousands: ".",
                processing: "Processando...",
                loadingRecords: "",
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
                infoPostFix: "",
                lengthMenu: "_MENU_ resultados por página"
            }
        });
    }
});
