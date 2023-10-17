var divisao;
var table;
var todos;
var card1;
var card2;
var card3;
var card4;
var card5;
var card6;
$(document).ready(function(){

    var mescorrente = "";
    var detailRows = [];
    todos = "Todos";
    divisao = sessionStorage.getItem("divisao");
    card1 = sessionStorage.getItem("card1");
    card2 = sessionStorage.getItem("card2");
    card3 = sessionStorage.getItem("card3");
    card4 = sessionStorage.getItem("card4");
    card5 = sessionStorage.getItem("card5");
    card6 = sessionStorage.getItem("card6");


    $('#C_mes').append('<option selected value="' + todos + '">' + todos + '</option>');
    $.getJSON( "../Adm/pages/producao/meses_conta.php",{ "origem": "convenio" }, function( data ) {
        $.each(data, function (index, value) {
            if(value.abreviacao !== undefined) {
                $('#C_mes').append('<option value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
            }
        });
    });

    table = $('#tabela_producao').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "destroy": true,
        "processing": false,
        "serverSide": false,
        "paging": true,
        "deferRender": true,
        "fixedColumns": true,
        //"bFilter": true,
        "ajax": {
            "url": '../Adm/pages/conta/estorno.php',
            "method": 'POST',
            "data":  function(data) {
                data.mes = $("#C_mes").val();
                data.divisao = divisao;
                data.card1 = card1;
                data.card2 = card2;
                data.card3 = card3;
                data.card4 = card4;
                data.card5 = card5;
                data.card6 = card6;
            },
            "dataType": 'json'
        },
        "order": [[ 1, "asc" ]],
        "columns": [
            {
                "class":"details-control",
                "orderable":false,
                "data":null,
                "defaultContent": ""
            },
            { "data": "lancamento" },
            { "data": "matricula" },
            { "data": "nome" },
            { "data": "razaosocial" },
            { "data": "valor",
                render: $.fn.dataTable.render.number( '.', ',', 2 ),
                className: "text-right"
            },
            { "data": "data" },
            { "data": "hora" },
            { "data": "mes" },
            { "data": "username_estornado" },
            { "data": "botaocancelar" }
        ],
        "columnDefs": [
            { "width": "3px", "targets": 0 },
            { "width": "8px", "targets": 1 },
            { "width": "7px", "targets": 2 },
            { "width": "250px", "targets": 3 },
            { "width": "150px", "targets": 4 },
            { "width": "5px", "targets": 5 },
            { "width": "5px", "targets": 6 },
            { "width": "5px", "targets": 7 },
            { "width": "5px", "targets": 8 },
            { "width": "5px", "targets": 9 },
            { "width": "3px", "targets": 10 }
        ],
        "pagingType": "full_numbers",
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
    // Add event listener for opening and closing details
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
    });
});
$('#btnExibir').click(function () {
    $("#tabela_producao").show();
    // constroi uma datatabe no primeiro carregamento da tela
    table = $('#tabela_producao').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "destroy": true,
        "processing": false,
        "serverSide": false,
        "paging": true,
        "deferRender": true,
        "fixedColumns": true,
        //"bFilter": true,
        "ajax": {
            "url": '../Adm/pages/conta/estorno.php',
            "method": 'POST',
            "data":  function(data) {
                data.mes = $("#C_mes").val();
                data.divisao = divisao;
                data.card1 = card1;
                data.card2 = card2;
                data.card3 = card3;
                data.card4 = card4;
                data.card5 = card5;
                data.card6 = card6;
            },
            "dataType": 'json'
        },
        "order": [[ 1, "asc" ]],
        "columns": [
            {
                "class":"details-control",
                "orderable":false,
                "data":null,
                "defaultContent": ""
            },
            { "data": "lancamento" },
            { "data": "matricula" },
            { "data": "nome" },
            { "data": "razaosocial" },
            { "data": "valor",
                render: $.fn.dataTable.render.number( '.', ',', 2 ),
                className: "text-right"
            },
            { "data": "data" },
            { "data": "hora" },
            { "data": "mes" },
            { "data": "username_estornado" },
            { "data": "botaocancelar" }
        ],
        "columnDefs": [
            { "width": "3px", "targets": 0 },
            { "width": "8px", "targets": 1 },
            { "width": "7px", "targets": 2 },
            { "width": "250px", "targets": 3 },
            { "width": "150px", "targets": 4 },
            { "width": "5px", "targets": 5 },
            { "width": "5px", "targets": 6 },
            { "width": "5px", "targets": 7 },
            { "width": "5px", "targets": 8 },
            { "width": "5px", "targets": 9 },
            { "width": "3px", "targets": 10 }
        ],
        "pagingType": "full_numbers",
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
});
$('#tabela_producao').on('click','tbody .btncancelarestorno',function () {
    var data_row = table.row($(this).closest('tr')).data();
    var $button = $(this);
    var valor;
    valor =  parseFloat(data_row.valor).toFixed(2).replace(".", ",");
    BootstrapDialog.confirm({
        message: '<table style="width: 100%;"><tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">REGISTRO:</th><th style="background-color: #dddddd;"><b>' + data_row.lancamento + '</b></th>' +
            '<tr><th style="text-align: right;padding: 8px;">MATRICULA:</th><th><b>' + data_row.matricula + '</th>' +
            '<tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">NOME:</th><th style="background-color: #dddddd;"><b>' + data_row.nome + '</th>' +
            '<tr><th style="text-align: right;padding: 8px;">VALOR:</th><th><b>' + valor + '</th>' +
            '<tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">DATA:</th><th style="background-color: #dddddd;"><b>' + data_row.data + '</th>' +
            '<tr><th style="text-align: right;padding: 8px;">HORA:</th><th><b>' + data_row.hora + '</th>' +
            '<tr><th style="text-align: right;padding: 8px;background-color: #dddddd;"></b>CONVENIO:<b></th><th style="background-color: #dddddd;">' + data_row.razaosocial + '</b><th>',
        title: 'Confirma o cancelamento do estorno ?',
        type: BootstrapDialog.TYPE_PRIMARY,
        closable: true,
        draggable: true,
        btnCancelLabel: 'Não',
        btnOKLabel: 'Sim',
        btnOKClass: 'btn btn-success',
        btnCancelClass: 'btn btn-warning',
        callback: function (result) {
            if (result) {
                waitingDialog.show('Cancelando, aguarde ...');
                $.ajax({
                    url: "pages/conta/cancelar_estorno.php",
                    method: "POST",
                    dataType: "json",
                    data: {"lancamento": data_row.lancamento,"mes":data_row.mes},
                    success: function (data) {
                        if (data.Resultado === "excluido") {
                            table.row( $button.parents('tr') ).remove().draw();
                            //alert("Excluido com sucesso");
                            waitingDialog.hide();
                            BootstrapDialog.show({
                                closable: false,
                                title: 'Atenção',
                                message: 'Cancelado com Sucesso!!!',
                                buttons: [{
                                    cssClass: 'btn-primary',
                                    label: 'Ok',
                                    action: function (dialogItself) {
                                        dialogItself.close();
                                        //$("#C_Senha").focus();
                                    }
                                }]
                            });
                        }else if (data.Resultado === "mes_bloqueado") {
                            waitingDialog.hide();
                            BootstrapDialog.show({
                                closable: false,
                                title: 'Atenção',
                                message: 'Mês bloqueado não é possivel cancelar!!!',
                                buttons: [{
                                    cssClass: 'btn-warning',
                                    label: 'Ok',
                                    action: function (dialogItself) {
                                        dialogItself.close();
                                        //$("#C_Senha").focus();
                                    }
                                }]
                            });
                        }
                    }
                });
            } else {
                //alert('No');
            }
        }
    });
});
function moedaParaNumero(valor){
    return isNaN(valor) == false ? parseFloat(valor) :   parseFloat(valor.replace("R$","").replace(".","").replace(",","."));
}
function numeroParaMoeda(n, c, d, t){
    c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "," : d, t = t == undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}
function format ( d ) {
    return'<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
        '<tr>'+
        '<td>Data estorno  :</td>'+
        '<td>'+d.data_estorno+'</td>'+
        '</tr>'+
        '<tr>'+
        '<td>Hora estorno  :</td>'+
        '<td>'+d.hora_estorno+'</td>'+
        '</tr>'+
        '<tr>'+
        '<td>Empregador  :</td>'+
        '<td>'+d.nome_empregador+'</td>'+
        '</tr>'+
        '<tr>'+
        '<td>Parcela  :</td>'+
        '<td>'+d.parcela+'</td>'+
        '</tr>'+
        '<tr>'+
        '<td>Usuario cad  :</td>'+
        '<td>'+d.username+'</td>'+
        '</tr>'+
        '</table>';
}