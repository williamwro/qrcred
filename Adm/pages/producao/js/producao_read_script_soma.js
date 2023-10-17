var table;
var divisao;
$C_parcela = $('#C_parcela');
$C_empregador = $('#C_empregador');
$C_convenio = $('#cod_convenio');
$todos = "todos";
$(document).ready(function() {
    divisao = sessionStorage.getItem("divisao");
    $("#fechar_colapse").on('click',function(){
        $('.collapse').collapse('hide');
    });

    var mescorrente = "";
    $('#C_mes').append('<option selected value="todos">' + $todos + '</option>');
    $.getJSON( "../Adm/pages/producao/meses_conta.php",{ "origem": "convenio" },function( data ) {
        $.each(data, function (index, value) {

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
        $C_convenio.html("<option></option>");
        $C_convenio.attr({"title":"Escolha o convenio"});
    });
    waitingDialog.hide();

});
/*$('#C_mes').change(function () {
    waitingDialog.show('Carregando, aguarde ...',);
    carrega_dados('');
    waitingDialog.hide();
});*/
$('#btnExibir').click(function () {
    waitingDialog.show('Carregando, aguarde ...',);
    $("#tabela_producao").show();
    // constroi uma datatabe no primeiro carregamento da tela

    carrega_dados('');
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
    var empregador = $C_empregador.val();
    var parcela = $('#C_parcela').val();
    var selected = $("input[type='radio'][name='exampleRadios']:checked");
    if (selected.length > 0) {
        ordem = selected.val();
    }

    $.redirect('../Adm/pages/producao/producao_gerador_pdf.php',{ cod_convenio: cod_convenio, mes_atual: mes_atual, ano: ano,  ordem: ordem, empregador: empregador, parcela: parcela, divisao : divisao}, "POST", "_blank");
});// .update é o botão alterar
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

    var mes = $('#C_mes').val();
    carrega_convenios(mes);
});
$('#chkTodos').click(function () {

    var convenio = $('#C_nome_convenio').val();
    var codigo = $('#cod_convenio').val();
    if ($("#chkTodos").prop("checked")) {
        if(convenio !== "todos" ){
            $("#cod_convenio").val('');
            carrega_dados('todos');
            $('#C_nome_convenio').val('todos');
        }
    }else{
        $("#cod_convenio").val('');
        $('#C_nome_convenio').val('');
        $('#tabela_producao').hide();
    }
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
            url: 'pages/convenio/convenio_read2.php',
            method: 'POST',
            data: {"mes" : mes,"divisao" : divisao},
            dataType: 'json'
        },
        deferRender: true,
        order: [[1, "asc"]],
        columns: [
            {
                class: "details-control",
                orderable: false,
                data: null,
                defaultContent: ""
            },
            { data: "codigo" },
            { data: "razaosocial" },
            { data: "endereco" },
            { data: "telefone" },
            { data: "data_cadastro" },
        ],
        language: {
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
    if($('#C_nome_convenio').val() !== '') {
        table = $('#tabela_producao').DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            serverSide: false,
            responsive: true,
            autoWidth: true,
            JQueryUI: true,
            searching: true,
            destroy: true,
            ajax: {
                url: '../Adm/pages/producao/producao_soma_mes.php',
                method: 'POST',
                data: function (data) {
                    data.cod_convenio = $("#cod_convenio").val();
                    data.mes = $("#C_mes").val();
                },
                dataType: 'json'
            },
            order: [[0, "asc"]],
            columns: [
                {data: "mesx"},
                {data: "mesy"},
                {
                    data: "total",
                    render: $.fn.dataTable.render.number(',', '.', 2)
                }
            ],
            columnDefs: [
                {
                    "targets": [ 0 ],
                    "visible": false,
                    "searchable": false
                },
            ],
            pagingType: "full_numbers",
            language: {
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
