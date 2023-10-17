var table;
var divisao;
var divisao_nome;
var usuario_global;
var usuario_cod;
var tipo;
var cod_empregador;
var convenio;
var mes;
var parcela;

$C_parcela = $('#C_parcela');
$C_empregador = $('#C_empregador');
$C_convenio = $('#cod_convenio');
var card1;
var card2;
var card3;
var card4;
var card5;
var card6;

$(document).ready(function() {
    divisao = sessionStorage.getItem("divisao");
    divisao_nome = sessionStorage.getItem("divisao_nome");
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");

    card1 = sessionStorage.getItem("card1");
    card2 = sessionStorage.getItem("card2");
    card3 = sessionStorage.getItem("card3");
    card4 = sessionStorage.getItem("card4");
    card5 = sessionStorage.getItem("card5");
    card6 = sessionStorage.getItem("card6");
    $("#fechar_colapse").on('click',function(){
        $('.collapse').collapse('hide');
    });
    var mescorrente = "";
    $.getJSON( "../Adm/pages/producao/meses_conta.php",{ "origem": "convenio" },function( data ) {
        $.each(data, function (index, value) {
            debugger;
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
    debugger;
    $C_empregador.empty();
    $C_empregador.append('<option value=""></option>');
    $.getJSON( "../Adm/pages/producao/producao_empregador.php",{ "divisao": divisao }, function( data ) {
        $.each(data, function (index, value) {
            $C_empregador.append('<option data-subtext="' + value.abreviacao + '" value="' + value.id + '">' + value.nome + '</option>');
        });
    });
    $C_empregador.html("<option></option>");
    $C_empregador.attr({"title":"Escollha o empregador"});
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
    if ($C_empregador.val() !== ""){
        waitingDialog.show('Carregando, aguarde ...',);
        carrega_dados('');
        waitingDialog.hide();
    }

});
$C_parcela.change(function () {
    carrega_dados('');
});
$C_empregador.change(function () {
    carrega_dados('');
});
$('#btnExibir').click(function () { 
    waitingDialog.show('Carregando, aguarde ...',);
    $("#tabela_producao").show();
    // constroi uma datatabe no primeiro carregamento da tela
    debugger;
    carrega_dados('');
    tipo           = "E";
    mes            = $('#C_mes').val();
    convenio       = $('#C_nome_convenio').val();
    cod_empregador = $('#C_empregador').val();
    parcela        = $('#C_parcela').val();
    grava_log(convenio,mes,cod_empregador,parcela,tipo,usuario_cod,usuario_global);
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
    var selected = $("input[type='radio'][name='exampleRadios']:checked");
    tipo           = "R";
    mes            = $('#C_mes').val();
    convenio       = $('#C_nome_convenio').val();
    cod_empregador = $('#C_empregador').val();
    parcela        = $('#C_parcela').val();
    grava_log(convenio,mes,cod_empregador,parcela,tipo,usuario_cod,usuario_global);
    if (selected.length > 0) {
        ordem = selected.val();
    }
    debugger;
    $.redirect('../Adm/pages/producao/producao_gerador_pdf.php',{ cod_convenio: cod_convenio, mes_atual: mes_atual, ano: ano,  ordem: ordem, empregador: empregador, parcela: parcela, divisao : divisao, 'card1': card1, 'card2': card2, 'card3': card3, 'card4': card4, 'card5': card5, 'card6': card6}, "POST", "_blank");
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
    tipo           = "C";
    mes            = $('#C_mes').val();
    convenio       = $('#C_nome_convenio').val();
    cod_empregador = $('#C_empregador').val();
    parcela        = $('#C_parcela').val();
    carrega_convenios(mes);
    debugger;
    grava_log(convenio,mes,cod_empregador,parcela,tipo,usuario_cod,usuario_global);
});
$('#chkTodos').click(function () {
    debugger;
    convenio = $('#C_nome_convenio').val();
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
    debugger;
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
    debugger;
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
                    data.empregador = $C_empregador.val();
                    data.parcela = $("#C_parcela").val();
                    data.divisao = divisao;
                    data.todos = todos;
                    data.card1 = card1;
                    data.card2 = card2;
                    data.card3 = card3;
                    data.card4 = card4;
                    data.card5 = card5;
                    data.card6 = card6;
                },
                dataType: 'json'
            },
            order: [[5, "asc"]],
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