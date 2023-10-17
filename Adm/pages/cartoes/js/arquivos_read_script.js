var table;
var table;
var tableconsulta;
var codigo_isa;
var nome_titular;
var dependente;
var parentesco;
var nascimento;
var empregador;
var divisao;
var nome_divisao;
var idade;
var card1;
var card2;
var card3;
var card4;
var card5;
var card6;
$(document).ready(function() {
    waitingDialog.show('Carregando, aguarde ...');

    divisao = sessionStorage.getItem("divisao");
    nome_divisao = sessionStorage.getItem("divisao_nome");
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");
    card1 = sessionStorage.getItem("card1");
    card2 = sessionStorage.getItem("card2");
    card3 = sessionStorage.getItem("card3");
    card4 = sessionStorage.getItem("card4");
    card5 = sessionStorage.getItem("card5");
    card6 = sessionStorage.getItem("card6");
    var mescorrente = "";
    var lote_aux = "";

    $('#C_tipoarquivo').append("<option value='1'> Planilha excel </option>");
    $('#C_tipoarquivo').append("<option value='2'> Arquivos texto </option>");

    listar_cartoes();
    $('#C_lotes').empty();
    $.getJSON("../Adm/pages/cartoes/lotes.php",{ "divisao": divisao },function (data) {
        $('#C_lotes').append('<option value="aberto">Aberto</option>');
        $.each(data, function (index, value) {
            lote_aux = value.datalote;
            lote_aux = lote_aux.substr(0, 10);
            $('#C_lotes').append('<option value="' + value.lote + '">(' + lote_aux + ") - " + value.lote + '</option>');
        });
    });
    waitingDialog.hide();
    table.ajax.reload();
});
function listar_cartoes() {
    // constroi uma datatabe no primeiro carregamento da tela

    if ($.fn.dataTable.isDataTable('#tabela_dados')) {
        table.destroy();
        table = $('#tabela_dados').DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            serverSide: false,
            responsive: true,
            autoWidth: true,
            JQueryUI: true,
            searching: true,
            deferRender: true,
            paging:   false,
            ajax: {
                url: '../Adm/pages/cartoes/selecionar_dados.php',
                method: 'POST',
                data: function (data) {
                    data.lote = "aberto";
                    data.divisao = divisao;
                    data.card1 = card1;
                    data.card2 = card2;
                    data.card3 = card3;
                    data.card4 = card4;
                    data.card5 = card5;
                    data.card6 = card6;
                },
                dataType: 'json'
            },
            order: [[2, "asc"]],
            columns: [
                {data: "cartao"},
                {data:
                        "codigo",
                    "class": "noExl"
                },
                {data:
                        "abreviacao",
                    "class": "noExl"
                },
                {data: "nome"},
                {data: "botaoexcluir",
                    orderable: false,
                    "class": "noExl"
                }
            ],
            dom: '<"top"ifl><"clear">rt<"bottom"p><"clear">',
            stateSave: true,
            pagingType: "full_numbers",
            language: {
                //url: "pages/conta/Portuguese-Brasil.json"
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
    } else {

        table = $('#tabela_dados').DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            serverSide: false,
            responsive: true,
            autoWidth: true,
            JQueryUI: true,
            searching: true,
            deferRender: true,
            paging:   false,
            ajax: {
                url: '../Adm/pages/cartoes/selecionar_dados.php',
                method: 'POST',
                data: function (data) {
                    data.lote = "aberto";
                    data.divisao = divisao;
                    data.card1 = card1;
                    data.card2 = card2;
                    data.card3 = card3;
                    data.card4 = card4;
                    data.card5 = card5;
                    data.card6 = card6;
                },
                dataType: 'json'
            },
            order: [[2, "asc"]],
            columns: [
                {data: "cartao"},
                {data:
                       "codigo",
                       "class": "noExl"
                },
                {data:
                       "abreviacao",
                       "class": "noExl"
                },
                {data: "nome"},
                {data: "botaoexcluir",
                    orderable: false,
                    "class": "noExl"
                }
            ],
            dom: '<"top"ifl><"clear">rt<"bottom"lp><"clear">',
            stateSave: true,
            pagingType: "full_numbers",
            language: {
                //url: "pages/conta/Portuguese-Brasil.json"
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
$("#gerararquivo").click(function () {
    var data = table.rows().data();
    var texto = '';
    var obj = {};
    obj.dados = [];
    var d = new Date();
    var dataHora = (d.toLocaleString());
    dataHora.substring(0,10);

    var linha = '';
    if ($("#C_tipoarquivo").val() === '2' ) {
        if (table.rows().count() > 0) {
            data.each(function (value, index) {
                linha += value.cartao + ' ' + value.nome + "\r\n";
            });
            let blob = new Blob([linha], {type: "text/plain;charset=utf-8"});
            saveAs(blob, nome_divisao + "_CARTOES_" + dataHora.substring(0, 10));
        }
    }else{

        BootstrapDialog.confirm({
            message: '<table style="width: 100%;">' +
                     '<tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">Confirma a criação do arquivo?</th></tr>' +
                     '</table>',
            title: 'Geração de arquivo para fabrica',
            type: BootstrapDialog.TYPE_PRIMARY,
            closable: true,
            draggable: true,
            btnCancelLabel: 'Não',
            btnOKLabel: 'Sim',
            btnOKClass: 'btn btn-success',
            btnCancelClass: 'btn btn-warning',
            callback: function (result) {

                if (result) {

                    $.ajax({
                       url: "pages/cartoes/lote_cadastro.php",
                       method: "POST",
                       dataType: "json",
                       async:false,
                       data: {"divisao": divisao},
                       success: function (data) {
                           $("#tabela_dados").table2excel({
                               exclude: ".noExl",
                               name:"Cartoes",
                               filename:"Cartoes-"+nome_divisao+"-"+Date()+".xls",//do not include extension
                               fileext:".xls",
                               exclude_img:true,
                               exclude_links:true,
                               exclude_inputs:true
                           });

                           listar_cartoes();
                           $('#C_lotes').empty();
                           $.getJSON( "../Adm/pages/cartoes/lotes.php",{ "divisao": divisao }, function( data ) {
                               $('#C_lotes').append('<option value="aberto">Aberto</option>');
                               $.each(data, function (index, value) {
                                   $('#C_lotes').append('<option value="' + value.lote+ '">' + value.datalote + " - " + value.lote+ '</option>');
                               });
                           });
                           table.ajax.reload();
                       }
                   });
                } else {
                    //alert('No');
                }
            }
        });
    }
});
$("#btnConsultar").click(function () {
    $("#ModalBuscaAssociado").modal("show");

    if ( $.fn.dataTable.isDataTable( '#tabela_busca_associado' ) ) {
        tableconsulta = $('#tabela_busca_associado').DataTable();
    }
    else {
        tableconsulta = $('#tabela_busca_associado').DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            ServerSide: false,
            responsive: true,
            autoWidth: true,
            ajax: {
                url: 'pages/cartoes/exibe_todos_associados.php',
                method: 'POST',
                data: {"divisao": divisao, 'card1': card1, 'card2': card2, 'card3': card3, 'card4': card4, 'card5': card5, 'card6': card6},
                dataType: 'json'
            },
            deferRender: true,
            order: [[1, "asc"]],
            columns: [
                {data: "codigo"},
                {data: "nome"},
                {data: "empregador"},
                {data: "codempregador"}
            ],
            "columnDefs": [
                {
                    "targets": [ 3 ],
                    "visible": false,
                    "searchable": false
                }
            ],
            language: {
                url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Portuguese-Brasil.json",
                decimal: ",",
                thousands: "."
            },
            pagingType: "full_numbers"
        });
        $('#ModalBuscaAssociado tbody').on('click', 'tr', function () {
            if ($(this).hasClass('selected')) {
                $(this).removeClass('selected');
            } else {
                tableconsulta.$('tr.selected').removeClass('selected');
                $(this).addClass('selected');
            }
        });
    }
});
$('#tabela_dados').on('click', 'tbody .btnexcluirCartao', function () {
    var data_row = table.row($(this).closest('tr')).data();
    var $button = $(this);
    BootstrapDialog.confirm({
        message: '<table style="width: 100%;"><tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">CARTÃO:</th><th style="background-color: #dddddd;"><b>' + data_row.cartao + '</b></th>' +
            '<tr><th style="text-align: right;padding: 8px;">NOME:</th><th><b>' + data_row.nome + '</th>',
        title: 'Confirma a exclusão do cartão?',
        type: BootstrapDialog.TYPE_PRIMARY,
        closable: true,
        draggable: true,
        btnCancelLabel: 'Não',
        btnOKLabel: 'Sim',
        btnOKClass: 'btn btn-success',
        btnCancelClass: 'btn btn-warning',
        callback: function (result) {
            if (result) {
                waitingDialog.show('Excluindo, aguarde ...');
                $.ajax({
                    url: "pages/cartoes/cartao_exclui.php",
                    method: "POST",
                    dataType: "json",
                    data: {"cartao": data_row.cartao},
                    success: function (data) {

                        if (data.resultado === "excluido") {
                            table.row( $button.parents('tr') ).remove().draw();
                            waitingDialog.hide();
                            BootstrapDialog.show({
                                closable: false,
                                title: 'Atenção',
                                message: 'Excluído com Sucesso!!!',
                                buttons: [{
                                    cssClass: 'btn-warning',
                                    label: 'Ok',
                                    action: function (dialogItself) {
                                        dialogItself.close();
                                    }
                                }]
                            });
                            table.ajax.reload();
                        }else{
                            alert("Não Excluiu");
                            waitingDialog.hide();
                        }
                    }
                });
            } else {
                //alert('No');
            }
        }
    });
    $('#tab_matricula_origem tbody').on( 'click', 'tr', function () {
        $(this).toggleClass('selected');
    } );
});
$("#C_lotes").change(function () {
    waitingDialog.show('Carregando, aguarde ...',);
    //waitingDialog.show('Carregando, aguarde ...',);
    var lote = $("#C_lotes").val();
    if (lote === "aberto"){
        $("#gerararquivo").prop("disabled",false);
        $("#btnConsultar").prop("disabled",false);
        $("#C_tipoarquivo").prop("disabled",false);

        if ( $.fn.dataTable.isDataTable( '#tabela_dados' ) ) {
            table.destroy();
            table = $('#tabela_dados').DataTable({
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                processing: false,
                serverSide: false,
                responsive: true,
                autoWidth: true,
                JQueryUI: true,
                searching: true,
                paging:   false,
                info: true,
                dom: '<"top"ifl><"clear">rt<"bottom"p><"clear">',
                ajax: {
                    url: 'pages/cartoes/selecionar_dados.php',
                    method: 'POST',
                    async:true,
                    data: function (data) {
                        data.lote = lote;
                        data.divisao = divisao;
                        data.card1 = card1;
                        data.card2 = card2;
                        data.card3 = card3;
                        data.card4 = card4;
                        data.card5 = card5;
                        data.card6 = card6;
                    },
                    dataType: 'json'
                },
                order: [[2, "asc"]],
                columns: [
                    {data: "cartao"},
                    {data:
                            "codigo",
                        "class": "noExl"
                    },
                    {data:
                            "abreviacao",
                        "class": "noExl"
                    },
                    {data: "nome"},
                    {data: "botaoexcluir",
                        orderable: false,
                        "class": "noExl"
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
                }
            });
        }else{
            table = $('#tabela_dados').DataTable({
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                processing: false,
                serverSide: false,
                responsive: true,
                autoWidth: true,
                JQueryUI: true,
                searching: true,
                paging:   false,
                info: true,
                dom: '<"top"ifl><"clear">rt<"bottom"p><"clear">',
                ajax: {
                    url: 'pages/cartoes/selecionar_dados.php',
                    method: 'POST',
                    async:true,
                    data: function (data) {
                        data.lote = lote;
                        data.divisao = divisao;
                        data.card1 = card1;
                        data.card2 = card2;
                        data.card3 = card3;
                        data.card4 = card4;
                        data.card5 = card5;
                        data.card6 = card6;
                    },
                    dataType: 'json'
                },
                order: [[2, "asc"]],
                columns: [
                    {data: "cartao"},
                    {data:
                           "codigo",
                           "class": "noExl"
                    },
                    {data:
                           "abreviacao",
                           "class": "noExl"
                    },
                    {data: "nome"},
                    {data: "botaoexcluir",
                        orderable: false,
                        "class": "noExl"
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
                }
            });
        }
        table.ajax.reload();
    }else{
        $("#gerararquivo").prop("disabled",true);
        $("#btnConsultar").prop("disabled",true);
        //$("#C_tipoarquivo").prop("disabled",true);
        if ( $.fn.dataTable.isDataTable( '#tabela_dados' ) ) {
            table.destroy();
            table = $('#tabela_dados').DataTable({
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                processing: false,
                serverSide: false,
                responsive: true,
                autoWidth: true,
                JQueryUI: true,
                searching: true,
                paging:false,
                info: true,
                dom: '<"top"ifl><"clear">rt<"bottom"p><"clear">',
                ajax: {
                    url: 'pages/cartoes/selecionar_dados.php',
                    method: 'POST',
                    async:true,
                    data: function (data) {
                        data.lote = lote;
                        data.divisao = divisao;
                        data.card1 = card1;
                        data.card2 = card2;
                        data.card3 = card3;
                        data.card4 = card4;
                        data.card5 = card5;
                        data.card6 = card6;
                    },
                    dataType: 'json'
                },
                order: [[2, "asc"]],
                columns: [
                    {data: "cartao"},
                    {data:
                           "codigo",
                           "class": "noExl"
                    },
                    {data:
                           "abreviacao",
                           "class": "noExl"
                    },
                    {data: "nome"},
                    {data: "botaoexcluir",
                        orderable: false,
                        "class": "noExl"
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
                }
            });
        }else{
            table = $('#tabela_dados').DataTable({
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                processing: false,
                serverSide: false,
                responsive: true,
                autoWidth: true,
                JQueryUI: true,
                searching: true,
                paging:   false,
                info: true,
                dom: '<"top"ifl><"clear">rt<"bottom"p><"clear">',
                ajax: {
                    url: 'pages/cartoes/selecionar_dados.php',
                    method: 'POST',
                    async:true,
                    data: function (data) {
                        data.lote = lote;
                        data.divisao = divisao;
                        data.card1 = card1;
                        data.card2 = card2;
                        data.card3 = card3;
                        data.card4 = card4;
                        data.card5 = card5;
                        data.card6 = card6;
                    },
                    dataType: 'json'
                },
                order: [[2, "asc"]],
                columns: [
                    {data: "cartao"},
                    {data:
                            "codigo",
                        "class": "noExl"
                    },
                    {data:
                            "abreviacao",
                        "class": "noExl"
                    },
                    {data: "nome"},
                    {data: "botaoexcluir",
                        orderable: false,
                        "class": "noExl"
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
                }
            });
        }
    }
    waitingDialog.hide();
});
$('#tabela_busca_associado').on( 'dblclick', 'tr', function () {
    // CAPTURA O VALOR DA LINHA SELECIONADA EM DUPLOCLICK

    waitingDialog.show('Criando cartão, aguarde ...',);
    var data = tableconsulta.row( this ).data();
    matricula  = data["codigo"];
    Codempregador_origem = data["codempregador"];
    $.ajax({
        url: "pages/cartoes/cadastra_cartao.php",
        method: "POST",
        dataType: "json",
        data: {
            "matricula": matricula,
            "empregador": Codempregador_origem,
            "id_divisao": divisao
        },
        success: function (data) {
            if (data.resultado === "cadastrado") {
                listar_cartoes();
                waitingDialog.hide();
                table.ajax.reload();
                BootstrapDialog.show({
                    closable: false,
                    title: 'Atenção',
                    message: 'Cadastrato com Sucesso!!!',
                    buttons: [{
                        cssClass: 'btn-warning',
                        label: 'Ok',
                        action: function (dialogItself) {
                            dialogItself.close();
                            $("#ModalBuscaAssociado").modal("hide");
                        }
                    }]
                });
            }
        }
    });
    waitingDialog.hide();
    table.ajax.reload();
    $("#ModalBuscaAssociado").modal("hide");
});
$("#btnRelatorio").click(function () {
    
    var selectedText = $("#C_lotes option:selected").html();
    var x = selectedText.split("-");
    var lote = $.trim(x[1]);
    var data = $.trim(x[0]);
    $.redirect('pages/cartoes/relatorio_cartoes.php', {
        lote: lote,
        data: data
    }, "POST", "_blank");
});