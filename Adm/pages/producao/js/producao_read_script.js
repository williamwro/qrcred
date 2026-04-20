var table;
var divisao;
var divisao_nome;
var usuario_global;
var usuario_cod;
var tipo;
var convenio;
var mes;
var parcela;

$C_parcela = $('#C_parcela');
$C_convenio = $('#cod_convenio');


$(document).ready(function() {
    divisao = sessionStorage.getItem("divisao");
    divisao_nome = sessionStorage.getItem("divisao_nome");
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");

     $("#fechar_colapse").on('click',function(){
        $('.collapse').collapse('hide');
    });
    var mescorrente = "";
    $.getJSON( "../Adm/pages/producao/meses_conta.php",{ "origem": "convenio", "divisao": divisao, "divisao_nome": divisao_nome },function( data ) {
        $.each(data, function (index, value) {
            if (value.mes_corrente !== undefined) {
                mescorrente = value.mes_corrente;
            }
            if (value.abreviacao !== undefined) {
                var textoOpcao = value.abreviacao;
                if (value.periodo !== undefined && value.periodo !== null && value.periodo !== '') {
                    textoOpcao = value.abreviacao + ' - ' + value.periodo;
                }
                if (mescorrente === value.abreviacao) {
                    $('#C_mes').append('<option selected value="' + value.abreviacao + '">' + textoOpcao + '</option>');
                } else {
                    $('#C_mes').append('<option value="' + value.abreviacao + '">' + textoOpcao + '</option>');
                }
            }
        });
        $C_convenio.html("<option></option>");
        $C_convenio.attr({"title":"Escolha o convenio"});
    });
    $C_parcela.empty();
    $C_parcela.append("<option selected value=''>  </option>");
    $C_parcela.append("<option value='01'> 01 </option>");
    $C_parcela.append("<option value='02'> 02 </option>");
    $C_parcela.append("<option value='03'> 03 </option>");
    $C_parcela.append("<option value='04'> 04 </option>");
    $C_parcela.append("<option value='05'> 05 </option>");
    $C_parcela.append("<option value='06'> 06 </option>");
    $C_parcela.append("<option value='07'> 07 </option>");
    $C_parcela.append("<option value='08'> 08 </option>");
    $C_parcela.append("<option value='09'> 09 </option>");
    $C_parcela.append("<option value='10'> 10 </option>");
    $C_parcela.append("<option value='11'> 11 </option>");
    $C_parcela.append("<option value='12'> 12 </option>");
    $C_parcela.append("<option value='13'> 13 </option>");
    $C_parcela.append("<option value='14'> 14 </option>");
    $C_parcela.append("<option value='15'> 15 </option>");
    $C_parcela.append("<option value='16'> 16 </option>");
    $C_parcela.append("<option value='17'> 17 </option>");
    $C_parcela.append("<option value='18'> 18 </option>");
    $C_parcela.append("<option value='19'> 19 </option>");
    $C_parcela.append("<option value='20'> 20 </option>");
    $("#chkTodos").checked = false;
    waitingDialog.hide();
});
$('#C_mes').change(function () {
    carrega_dados('');
});
$C_parcela.change(function () {
    carrega_dados('');
});
$('#btnExibir').click(function () { 
    waitingDialog.show('Carregando, aguarde ...',);
    $("#tabela_producao").show();
    // constroi uma datatabe no primeiro carregamento da tela
    carrega_dados('');
    tipo           = "E";
    mes            = $('#C_mes').val();
    convenio       = $('#C_nome_convenio').val();
    parcela        = $('#C_parcela').val();
    grava_log(convenio,mes,'',parcela,tipo,usuario_cod,usuario_global);
    // Array to track the ids of the details displayed rows
    var detailRows = [];
    $('#tabela_producao tbody').on( 'click', 'tr td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row( tr );
        var idx = $.inArray( tr.attr('id'), detailRows );

        if ( row.child.isShown() ) {
            tr.removeClass( 'details' );
            row.child.hide();

            // Remove from the 'open' array
            detailRows.splice( idx, 1 );
        }
        else {
            tr.addClass( 'details' );
            row.child( format( row.data() ) ).show();

            // Add to the 'open' array
            if ( idx === -1 ) {
                detailRows.push( tr.attr('id') );
            }
        }
    } );
    // On each draw, loop over the `detailRows` array and show any child rows
   /* table.on( 'draw', function () {
        $.each( detailRows, function ( i, id ) {
            $('#'+id+' td.details-control').trigger( 'click' );
        } );
    } );*/
    waitingDialog.hide();
});
$('#gerarpdf').click(function () {
    var cod_convenio = $C_convenio.val();
    var mes_atual = $('#C_mes').val();
    var ano = $('#ano').val();
    var ordem = "";
    var selected = $("input[type='radio'][name='exampleRadios']:checked");
    tipo           = "R";
    mes            = $('#C_mes').val();
    convenio       = $('#C_nome_convenio').val();
    parcela        = $('#C_parcela').val();

    grava_log(convenio,mes,'',parcela,tipo,usuario_cod,usuario_global);
    if (selected.length > 0) {
        ordem = selected.val();
    }
    $.redirect('../Adm/pages/producao/producao_gerador_pdf.php',{ cod_convenio: cod_convenio, mes_atual: mes_atual, ano: ano,  ordem: ordem, parcela: parcela, divisao : divisao,divisao_nome: divisao_nome }, "POST", "_blank");
});

// Exportar para Excel
$('#exportarExcel').click(function() {
    console.log('Botão Excel clicado');
    
    // Verifica se a tabela está vazia
    if (!$.fn.DataTable.isDataTable('#tabela_producao') || table.rows().count() === 0) {
        console.log('Tabela vazia');
        Swal.fire({
            title: 'Atenção!',
            text: 'Não há dados para exportar.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return false;
    }
    
    console.log('Iniciando exportação para Excel');
    
    var nomeConvenio = ($('#C_nome_convenio').val() || '').trim();
    var codConvenio = ($('#cod_convenio').val() || '').trim();
    var mesAtual = ($('#C_mes').val() || '').trim();
    var parcelaSel = ($('#C_parcela').val() || '').trim();
    var cabecalho = '';

    if (nomeConvenio || codConvenio) {
        cabecalho += 'Convênio: ' + (nomeConvenio || '');
        if (codConvenio) {
            cabecalho += (nomeConvenio ? ' ' : '') + '(' + codConvenio + ')';
        }
    }
    if (mesAtual) {
        cabecalho += (cabecalho ? ' | ' : '') + 'Mês: ' + mesAtual;
    }
    if (parcelaSel) {
        cabecalho += (cabecalho ? ' | ' : '') + 'Parcela: ' + parcelaSel;
    }
    if (divisao_nome) {
        cabecalho += (cabecalho ? ' | ' : '') + 'Divisão: ' + divisao_nome;
    }

    var totalValor = 0;
    var parseValor = function(v) {
        if (v === null || v === undefined) return 0;
        if (typeof v === 'number') return v;
        var s = v.toString();
        s = s.replace(/\s+/g, '');
        s = s.replace(/[^0-9,\.\-]/g, '');
        var lastComma = s.lastIndexOf(',');
        var lastDot = s.lastIndexOf('.');
        if (lastComma > -1 && lastDot > -1) {
            if (lastComma > lastDot) {
                s = s.replace(/\./g, '');
                s = s.replace(/,/g, '.');
            } else {
                s = s.replace(/,/g, '');
            }
        } else if (lastComma > -1) {
            s = s.replace(/\./g, '');
            s = s.replace(/,/g, '.');
        } else {
            s = s.replace(/,/g, '');
        }
        var n = parseFloat(s);
        return isNaN(n) ? 0 : n;
    };

    try {
        table.column(3, { search: 'applied', order: 'applied' }).data().each(function(v) {
            totalValor += parseValor(v);
        });
    } catch (e) {
        totalValor = 0;
    }

    var totalValorExcel = totalValor;

    function exportarExcelComCabecalho(cabecalhoFinal) {
        var button = new $.fn.dataTable.Buttons(table, {
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: 'Excel',
                    className: 'btn btn-success',
                    title: 'Relatorio_Producao',
                    filename: 'Relatorio_Producao_' + (mesAtual ? (mesAtual + '_') : '') + new Date().toLocaleDateString('pt-BR').replace(/\//g, '-'),
                    messageTop: cabecalhoFinal,
                    customize: function (xlsx) {
                        try {
                            var sheet = xlsx.xl.worksheets['sheet1.xml'];
                            var $sheet = $(sheet);
                            var $sheetData = $sheet.find('sheetData');
                            var $rows = $sheetData.find('row');
                            var lastRowNum = 0;
                            if ($rows.length) {
                                lastRowNum = parseInt($rows.last().attr('r'), 10) || 0;
                            }

                            var blankRowNum = lastRowNum + 1;
                            var totalRowNum = lastRowNum + 2;
                            var totalValue = (totalValorExcel || 0);
                            var totalValueStr = (Math.round(totalValue * 100) / 100).toFixed(2);

                            $sheetData.append('<row r="' + blankRowNum + '"></row>');
                            $sheetData.append('<row r="' + totalRowNum + '"><c r="D' + totalRowNum + '" t="n"><v>' + totalValueStr + '</v></c></row>');

                            var $dimension = $sheet.find('dimension');
                            if ($dimension.length) {
                                var ref = $dimension.attr('ref');
                                if (ref) {
                                    var m = ref.match(/^(\D+)(\d+):(\D+)(\d+)$/);
                                    if (m) {
                                        $dimension.attr('ref', m[1] + m[2] + ':' + m[3] + totalRowNum);
                                    }
                                }
                            }
                        } catch (e) {
                        }
                    },
                    exportOptions: {
                        columns: ':visible',
                        modifier: {
                            order: 'current',
                            page: 'all'
                        }
                    }
                }
            ]
        });

        var $container = $(button.container());
        $container.css({ position: 'absolute', left: '-9999px', top: '-9999px' }).appendTo('body');

        var $btn = $container.find('button, a').first();
        if ($btn.length) {
            $btn.trigger('click');
        } else {
            console.error('Não foi possível localizar o botão de exportação dentro do container do DataTables Buttons.');
        }

        setTimeout(function() {
            console.log('Limpando botão');
            button.destroy();
        }, 1000);
    }

    if (!codConvenio) {
        exportarExcelComCabecalho(cabecalho);
        return false;
    }

    $.when(
        $.ajax({
            url: '../Adm/pages/convenio/convenio_bancos.php',
            method: 'POST',
            dataType: 'json',
            data: { codigo_convenio: codConvenio }
        }),
        $.getJSON('../Adm/pages/convenio/bancos.php'),
        $.getJSON('../Adm/pages/convenio/conta_tipo.php'),
        $.getJSON('../Adm/pages/convenio/tipo_pix.php')
    ).done(function(respBancoConvenio, respBancos, respContaTipo, respTipoPix) {
        var bancoConvenioData = respBancoConvenio && respBancoConvenio[0] ? respBancoConvenio[0] : null;
        var bancosData = respBancos && respBancos[0] ? respBancos[0] : null;
        var contaTipoData = respContaTipo && respContaTipo[0] ? respContaTipo[0] : null;
        var tipoPixData = respTipoPix && respTipoPix[0] ? respTipoPix[0] : null;

        var bancoNome = '';
        var tipoContaNome = '';
        var tipoPixNome = '';
        var agencia = '';
        var conta = '';
        var chavePix = '';

        if (bancoConvenioData && bancoConvenioData[0]) {
            var bc = bancoConvenioData[0];
            agencia = (bc.agencia || '').toString().trim();
            conta = (bc.conta || '').toString().trim();
            chavePix = (bc.chave_pix || '').toString().trim();

            var codBanco = (bc.cod_banco || '').toString().trim();
            var codTipoConta = (bc.cod_tipo || '').toString().trim();
            var codTipoPix = (bc.id_chave_pix || '').toString().trim();

            if (bancosData) {
                $.each(bancosData, function(i, v) {
                    if (!v) return;
                    var id1 = (v.id || '').toString().trim();
                    var id2 = (v.cod_banco || '').toString().trim();
                    if ((codBanco && id1 === codBanco) || (codBanco && id2 === codBanco)) {
                        bancoNome = (v.banco || '').toString().trim();
                    }
                });
            }

            if (contaTipoData) {
                $.each(contaTipoData, function(i, v) {
                    if (!v) return;
                    var id = (v.id || '').toString().trim();
                    if (codTipoConta && id === codTipoConta) {
                        tipoContaNome = (v.tipo || '').toString().trim();
                    }
                });
            }

            if (tipoPixData) {
                $.each(tipoPixData, function(i, v) {
                    if (!v) return;
                    var id = (v.id_chave_pix || '').toString().trim();
                    if (codTipoPix && id === codTipoPix) {
                        tipoPixNome = (v.nome_chave || '').toString().trim();
                    }
                });
            }
        }

        var dadosBancarios = '';
        if (bancoNome || agencia || conta || tipoContaNome || tipoPixNome || chavePix) {
            dadosBancarios = 'DADOS BANCÁRIOS:';
            if (bancoNome) dadosBancarios += ' Banco: ' + bancoNome;
            if (agencia) dadosBancarios += (bancoNome ? ' |' : '') + ' Agência: ' + agencia;
            if (conta) dadosBancarios += (bancoNome || agencia ? ' |' : '') + ' Conta: ' + conta;
            if (tipoContaNome) dadosBancarios += (bancoNome || agencia || conta ? ' |' : '') + ' Tipo Conta: ' + tipoContaNome;
            if (tipoPixNome) dadosBancarios += (bancoNome || agencia || conta || tipoContaNome ? ' |' : '') + ' Tipo PIX: ' + tipoPixNome;
            if (chavePix) dadosBancarios += (bancoNome || agencia || conta || tipoContaNome || tipoPixNome ? ' |' : '') + ' Chave PIX: ' + chavePix;
        }

        var cabecalhoFinal = cabecalho;
        if (dadosBancarios) {
            cabecalhoFinal = cabecalhoFinal ? (cabecalhoFinal + "\n" + dadosBancarios) : dadosBancarios;
        }

        exportarExcelComCabecalho(cabecalhoFinal);
    }).fail(function() {
        exportarExcelComCabecalho(cabecalho);
    });

    return false;
});

$("#btnInserir").click(function(){
    $("#frmconvenio")[0].reset();
    $("#ModalEdita").modal("show");
    $.getJSON( "pages/associado/associado_ultimo_codigo.php" ).done( function( data ) {
        $( "#C_codigo" ).val(data.codigo);
        $('#operation').val("Add");
    });
    var d = new Date();
    var curr_date = d.getDate();
    var curr_month = d.getMonth();
    var curr_year = d.getFullYear();
    $('#C_datacadastro').val(curr_date + "/" + curr_month + "/" + curr_year);
});

$("#btnBuscaConvenio").click(function () {
    $("#ModalBuscaConvenio").modal("show");
    tipo           = "C";
    mes            = $('#C_mes').val();
    convenio       = $('#C_nome_convenio').val();
    parcela        = $('#C_parcela').val();
    carrega_convenios(mes);
    grava_log(convenio,mes,'',parcela,tipo,usuario_cod,usuario_global);
});

// Botão para limpar a seleção do convênio
$('#btnLimparConvenio').click(function () {
    $("#cod_convenio").val('');
    $('#C_nome_convenio').val('');
    $('#tabela_producao').hide();
    
    Swal.fire({
        icon: 'success',
        title: 'Convênio Limpo!',
        text: 'A seleção do convênio foi removida.',
        timer: 1500,
        showConfirmButton: false
    });
});

$('#tabela_busca_convenio').on( 'dblclick', 'tr', function () {
    // CAPTURA O VALOR DA LINHA SELECIONADA EM DUPLOCLICK
    var data = tableconsultaconv.row( this ).data();
    cod_convenio = data["codigo"];
    nome_convenio = data["razaosocial"];
    $('#cod_convenio').val(cod_convenio);
    $('#C_nome_convenio').val(nome_convenio);
    $("#ModalBuscaConvenio").modal("hide");
    $('#btnExibir').click();
});
function moedaParaNumero(valor){
    return isNaN(valor) === false ? parseFloat(valor) :   parseFloat(valor.replace("R$","").replace(".","").replace(",","."));
}
function numeroParaMoeda(n, c, d, t){
    c = isNaN(c = Math.abs(c)) ? 2 : c, d = d === undefined ? "," : d, t = t === undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}
function format ( d ) {
    return '<b>Hora       : </b><i>'+d.hora+'</i><br>'+
        '<b>Mês        : </b><i>'+d.mes+'</i><br>'+
        '<b>Convenio   : </b><i>'+d.convenio+'</i><br>'+
        '<b>Operador   : </b><i>'+d.funcionario+'</i><br>'+
        '<b>Parcela    : </b><i>'+d.parcela+'</i><br>'+
        '<b>Descricao  : </b><i>'+d.descricao+'</i><br>';
}
function carrega_convenios(mes){
    $('#mes_rotulo').text(mes);
    if ( $.fn.dataTable.isDataTable( '#tabela_busca_convenio' ) ) {
        tableconsultaconv.destroy();
    }
    tableconsultaconv = $('#tabela_busca_convenio').DataTable({
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        processing: false,
        ServerSide: false,
        responsive: true,
        autoWidth: true,
        JQueryUI: true,
        searching: true,
        ajax: {
            url: 'pages/producao/convenios.php',
            method: 'POST',
            data: {"mes" : mes,"divisao" : divisao},
            dataType: 'json'
        },
        deferRender: true,
        order: [[1, "asc"]],
        columns: [
            { data: "codigo" },
            { data: "razaosocial" },
            { data: "nomefantasia" },
            { data: "endereco" },
            { data: "telefone" },
            {
                data: "total",
                render: $.fn.dataTable.render.number( '.', ',', 2 ),
                className: "text-right"
            }
        ],
        language: {
            //url: "pages/conta/Portuguese-Brasil.json",
            decimal: ",",
            thousands: ".",
            zeroRecords: "Não ha dados",
            emptyTable: "Não ha dados.",
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
}
function carrega_dados(todos){
    if($('#C_nome_convenio') !== '') {
        if ($.fn.dataTable.isDataTable('#tabela_producao')) {
            table.destroy();
        }
        table = $('#tabela_producao').DataTable({
            dom: 'Bfrtip',
            buttons: [],
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            serverSide: false,
            responsive: true,
            autoWidth: true,
            JQueryUI: true,
            searching: true,
            info:     false,
            ajax: {
                url: '../Adm/pages/producao/producao_read2.php',
                method: 'POST',
                data: function (data) {
                    data.cod_convenio = $("#cod_convenio").val();
                    data.mes = $("#C_mes").val();
                    data.parcela = $("#C_parcela").val();
                    data.divisao = divisao;
                    data.todos = todos;
                },
                dataType: 'json'
            },
            order: [[6, "asc"]],
            columns: [
                {
                    class: "details-control",
                    orderable: false,
                    data: null,
                    defaultContent: ""
                },
                {data: "lancamento"},
                {data: "matricula"},
                {
                    data: "valor",
                    render: $.fn.dataTable.render.number(',', '.', 2)
                },
                {data: "data"},
                {data: "hora"},
                {data: "associado"},
                {data: "convenio"},
                {data: "empregador"},
                {data: "parcela"}
            ],
            pagingType: "full_numbers",
            language: {
                //url: "pages/conta/Portuguese-Brasil.json",
                decimal: ",",
                thousands: ".",
                zeroRecords: "Não ha dados",
                emptyTable: "Não ha dados.",
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
}

// ==================== RELATÓRIOS DE PRODUÇÃO ====================

// Opção 1: Somente Relatório (relatório original de produção)
$('#relatorio_producao_somente').click(function (e) {
    e.preventDefault();
    var mes_atual  = $('#C_mes').val();
    var cod_convenio = $('#cod_convenio').val();
    var parcela = $('#C_parcela').val();
    
    if (!mes_atual) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um mês.'
        });
        return;
    }
    
    if (!cod_convenio) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um convênio.'
        });
        return;
    }
    
    var params = { 
        mes_atual: mes_atual, 
        cod_convenio: cod_convenio,
        divisao: divisao, 
        divisao_nome: divisao_nome 
    };
    if (parcela && parcela !== '') {
        params.parcela = parcela;
    }
    $.redirect('../Adm/pages/producao/producao_gerador_pdf.php', params, "POST", "_blank");
});

// Opção 2: Ofício (relatório de produção com formato de ofício)
$('#relatorio_producao_oficio').click(function (e) {
    e.preventDefault();
    var mes_atual  = $('#C_mes').val();
    var cod_convenio = $('#cod_convenio').val();
    var parcela = $('#C_parcela').val();
    
    if (!mes_atual) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um mês.'
        });
        return;
    }
    
    if (!cod_convenio) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um convênio.'
        });
        return;
    }
    
    var params = { 
        mes_atual: mes_atual, 
        cod_convenio: cod_convenio,
        divisao: divisao, 
        divisao_nome: divisao_nome 
    };
    if (parcela && parcela !== '') {
        params.parcela = parcela;
    }
    $.redirect('../Adm/pages/producao/relatorio_producao_oficio.php', params, "POST", "_blank");
});

// Botão para gerar todos os relatórios de produção em um único PDF
$('#relatorio_producao_todos').click(function () {
    var mes_atual = $('#C_mes').val();
    var cod_convenio = $('#cod_convenio').val();
    var empregador = $('#C_empregador').val();
    var parcela = $('#C_parcela').val();
    
    // Validações
    if (!mes_atual) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um mês.'
        });
        return;
    }
    
    // Confirmação antes de gerar
    Swal.fire({
        title: 'Confirmar Geração',
        text: 'Deseja gerar o relatório consolidado com todos os convênios? Este processo pode levar alguns minutos.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Sim, gerar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Gerando Relatórios...',
                text: 'Aguarde enquanto todos os convênios são compilados.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            var params = { 
                mes_atual: mes_atual,
                divisao: divisao,
                divisao_nome: divisao_nome 
            };
            if (empregador && empregador !== '') {
                params.empregador = empregador;
            }
            if (parcela && parcela !== '') {
                params.parcela = parcela;
            }
            $.redirect('../Adm/pages/producao/relatorio_producao_todos.php', params, "POST", "_blank");
            
            // Fechar o loading após um tempo
            setTimeout(() => {
                Swal.close();
            }, 3000);
        }
    });
});

// Botão para gerar todos os convênios em 1 PDF - Formato Ofício
$('#relatorio_producao_todos_oficio').click(function () {
    var mes_atual = $('#C_mes').val();
    var empregador = $('#C_empregador').val();
    var parcela = $('#C_parcela').val();
    
    // Validações
    if (!mes_atual) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um mês.'
        });
        return;
    }
    
    // Confirmação antes de gerar
    Swal.fire({
        title: 'Confirmar Geração de Ofício',
        text: 'Deseja gerar o ofício consolidado com todos os convênios? Este processo pode levar alguns minutos.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f57c00',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Sim, gerar ofício!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Gerando Ofício...',
                text: 'Aguarde enquanto todos os convênios são compilados no formato de ofício.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            var params = { 
                mes_atual: mes_atual,
                divisao: divisao,
                divisao_nome: divisao_nome 
            };
            if (empregador && empregador !== '') {
                params.empregador = empregador;
            }
            if (parcela && parcela !== '') {
                params.parcela = parcela;
            }
            $.redirect('../Adm/pages/producao/relatorio_producao_todos_oficio.php', params, "POST", "_blank");
            
            // Fechar o loading após um tempo
            setTimeout(() => {
                Swal.close();
            }, 3000);
        }
    });
});

// Botão para gerar PDFs individuais separados para cada convênio (Somente Relatório)
$('#relatorio_producao_individuais').click(function () {
    var mes_atual = $('#C_mes').val();
    var empregador = $('#C_empregador').val();
    var parcela = $('#C_parcela').val();
    
    // Validações
    if (!mes_atual) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um mês.'
        });
        return;
    }
    
    if (!divisao) {
        Swal.fire({
            icon: 'warning',
            title: 'Erro de Sessão!',
            text: 'Divisão não encontrada. Por favor, recarregue a página e tente novamente.'
        });
        return;
    }
    
    // Confirmação antes de gerar
    Swal.fire({
        title: 'Confirmar Geração de PDFs por Convênio',
        html: 'Deseja gerar um PDF separado para cada convênio?<br><br>' +
              '<strong>Cada arquivo terá o nome:</strong><br>' +
              '<em>Convenio_Mês.pdf</em><br><br>' +
              '<small>Este processo pode levar alguns minutos e irá baixar múltiplos arquivos.</small>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Sim, gerar PDFs!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Buscar lista de convênios com produção no mês
            var ajaxData = {
                mes: mes_atual,
                divisao: divisao
            };
            if (empregador && empregador !== '') {
                ajaxData.empregador = empregador;
            }
            if (parcela && parcela !== '') {
                ajaxData.parcela = parcela;
            }
            
            $.ajax({
                url: '../Adm/pages/producao/convenios.php',
                method: 'POST',
                data: ajaxData,
                dataType: 'json',
                success: function(response) {
                    var convenios = response.data || [];
                    if (!convenios || convenios.length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Nenhum Convênio',
                            text: 'Não foram encontrados convênios com dados para o mês selecionado.'
                        });
                        return;
                    }
                    
                    // Mostrar progresso
                    let currentIndex = 0;
                    const totalConvenios = convenios.length;
                    
                    Swal.fire({
                        title: 'Gerando PDFs por Convênio...',
                        html: `Progresso: <strong>0 de ${totalConvenios}</strong> convênios`,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Gerar PDFs sequencialmente, um por vez
                    function gerarProximoPDF() {
                        if (currentIndex >= totalConvenios) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Concluído!',
                                html: `<strong>${totalConvenios} PDFs foram gerados!</strong><br><br>` +
                                      `<small>✅ Arquivos baixados automaticamente na pasta Downloads<br>` +
                                      `📁 Nome: NomeEmpresa-Mês-Data.pdf</small>`,
                                timer: 5000
                            });
                            return;
                        }
                        
                        const convenio = convenios[currentIndex];
                        
                        Swal.update({
                            html: `Progresso: <strong>${currentIndex + 1} de ${totalConvenios}</strong> convênios<br>
                                   Gerando: <em>${convenio.razaosocial}</em>`
                        });
                        
                        // Criar nome único para a janela
                        const windowName = 'pdf_' + convenio.codigo + '_' + currentIndex;
                        
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': '../Adm/pages/producao/producao_gerador_pdf.php',
                            'target': windowName
                        });
                        
                        form.append($('<input>', {'type': 'hidden', 'name': 'mes_atual', 'value': mes_atual}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'cod_convenio', 'value': convenio.codigo}));
                        if (empregador && empregador !== '') {
                            form.append($('<input>', {'type': 'hidden', 'name': 'empregador', 'value': empregador}));
                        }
                        if (parcela && parcela !== '') {
                            form.append($('<input>', {'type': 'hidden', 'name': 'parcela', 'value': parcela}));
                        }
                        form.append($('<input>', {'type': 'hidden', 'name': 'divisao', 'value': divisao}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'divisao_nome', 'value': divisao_nome}));
                        
                        // Abrir janela antes de submeter
                        window.open('', windowName);
                        
                        $('body').append(form);
                        form.submit();
                        form.remove();
                        
                        currentIndex++;
                        setTimeout(gerarProximoPDF, 2500);
                    }
                    
                    gerarProximoPDF();
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Erro ao buscar lista de convênios: ' + error
                    });
                }
            });
        }
    });
});

// Botão para gerar PDFs individuais separados em formato Ofício para cada convênio
$('#relatorio_producao_individuais_oficio').click(function () {
    var mes_atual = $('#C_mes').val();
    var empregador = $('#C_empregador').val();
    var parcela = $('#C_parcela').val();
    
    // Validações
    if (!mes_atual) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um mês.'
        });
        return;
    }
    
    if (!divisao) {
        Swal.fire({
            icon: 'warning',
            title: 'Erro de Sessão!',
            text: 'Divisão não encontrada. Por favor, recarregue a página e tente novamente.'
        });
        return;
    }
    
    // Confirmação antes de gerar
    Swal.fire({
        title: 'Confirmar Geração de Ofícios por Convênio',
        html: 'Deseja gerar um ofício em PDF separado para cada convênio?<br><br>' +
              '<strong>Cada arquivo terá o nome:</strong><br>' +
              '<em>Oficio_Convenio_Mês.pdf</em><br><br>' +
              '<small>Este processo pode levar alguns minutos e irá baixar múltiplos arquivos.</small>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f57c00',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Sim, gerar ofícios!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Buscar lista de convênios com produção no mês
            var ajaxData = {
                mes: mes_atual,
                divisao: divisao
            };
            if (empregador && empregador !== '') {
                ajaxData.empregador = empregador;
            }
            if (parcela && parcela !== '') {
                ajaxData.parcela = parcela;
            }
            
            $.ajax({
                url: '../Adm/pages/producao/convenios.php',
                method: 'POST',
                data: ajaxData,
                dataType: 'json',
                success: function(response) {
                    var convenios = response.data || [];
                    if (!convenios || convenios.length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Nenhum Convênio',
                            text: 'Não foram encontrados convênios com dados para o mês selecionado.'
                        });
                        return;
                    }
                    
                    // Mostrar progresso
                    let currentIndex = 0;
                    const totalConvenios = convenios.length;
                    
                    Swal.fire({
                        title: 'Gerando Ofícios por Convênio...',
                        html: `Progresso: <strong>0 de ${totalConvenios}</strong> convênios`,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Função para gerar ofício de cada convênio sequencialmente
                    function gerarProximoOficio() {
                        if (currentIndex >= totalConvenios) {
                            // Todos os ofícios foram gerados
                            Swal.fire({
                                icon: 'success',
                                title: 'Concluído!',
                                html: `<strong>${totalConvenios} ofícios foram gerados!</strong><br><br>` +
                                      `<small>✅ Arquivos baixados automaticamente na pasta Downloads<br>` +
                                      `📁 Nome: Oficio-NomeEmpresa-Mês-Data.pdf</small>`,
                                timer: 5000
                            });
                            return;
                        }
                        
                        const convenio = convenios[currentIndex];
                        
                        // Atualizar progresso
                        Swal.update({
                            html: `Progresso: <strong>${currentIndex + 1} de ${totalConvenios}</strong> convênios<br>
                                   Gerando ofício: <em>${convenio.razaosocial}</em>`
                        });
                        
                        // Criar um nome único para a janela
                        const windowName = 'oficio_' + convenio.codigo + '_' + Date.now();
                        
                        // Gerar ofício para este convênio
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': '../Adm/pages/producao/relatorio_producao_oficio.php',
                            'target': windowName
                        });
                        
                        form.append($('<input>', {'type': 'hidden', 'name': 'mes_atual', 'value': mes_atual}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'cod_convenio', 'value': convenio.codigo}));
                        if (empregador && empregador !== '') {
                            form.append($('<input>', {'type': 'hidden', 'name': 'empregador', 'value': empregador}));
                        }
                        if (parcela && parcela !== '') {
                            form.append($('<input>', {'type': 'hidden', 'name': 'parcela', 'value': parcela}));
                        }
                        form.append($('<input>', {'type': 'hidden', 'name': 'divisao', 'value': divisao}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'divisao_nome', 'value': divisao_nome}));
                        
                        // Abrir nova janela/aba
                        window.open('', windowName);
                        
                        // Submeter formulário
                        $('body').append(form);
                        form.submit();
                        form.remove();
                        
                        // Próximo convênio após 2.5 segundos
                        currentIndex++;
                        setTimeout(gerarProximoOficio, 2500);
                    }
                    
                    // Iniciar geração
                    gerarProximoOficio();
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Erro ao buscar lista de convênios: ' + error
                    });
                }
            });
        }
    });
});

// ==================== FIM RELATÓRIOS DE PRODUÇÃO ====================

function grava_log(convenio,mes,empregador,parcela,tipo,cod_usuario,usuario){

    $.ajax({
        url: "pages/producao/grava_log_convenios.php",
        method: "POST",
        data: {convenio:convenio,mes:mes,empregador:empregador,parcela:parcela,tipo:tipo,cod_usuario:cod_usuario,usuario:usuario},
        dataType: "json",
        success:function (data) {
           var result = data
        }
    })
}