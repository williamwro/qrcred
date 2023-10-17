var cod_convenio;
var data_inicial;
var data_final;
var mes_atual;
var mescorrente = "";
var table;
var parcelas_conv;
$(document).ready(function(){
    debugger
    $('#C_datainicial').mask('99/99/9999');
    $('#C_datafinal').mask('99/99/9999');
    cod_convenio = $("#cod_convenio").val();
    //**********OCULTAR OS PARAMETROS DA URL NO NAVEGADOR*************
    var uri = window.location.toString();
    if (uri.indexOf("?") > 0) {
        var clean_uri = uri.substring(0, uri.indexOf("?"));
        window.history.replaceState({}, document.title, clean_uri);
    }
    //****************************************************************
});
$('#btnAtualizar').click(function () {
    debugger;
    data_inicial = $('#C_datainicial').val();
    data_final = $('#C_datafinal').val();


    table = $('#tabela_producao').DataTable({
        "destroy": true,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "serverSide": false,
        "responsive": true,
        "autoWidth": true,
        "paging": false,
        "ajax": {
            "url": 'list_vendas_conv3.php',
            "method": 'POST',
            "data": {
                cod_convenio: cod_convenio,
                data_inicial: data_inicial,
                data_final: data_final
            },
            "dataType": 'json'
        },
        "order": [[ 1, "asc" ]],
        "columns": [
            { "data": "lancamento" },
            { "data": "associado",
                "width": "40%"
            },
            { "data": "data",
              "width": "20%"
            },
            { "data": "hora" },
            { "data": "valor_total",
                "render": $.fn.dataTable.render.number( '.', ',', 2, '' ),
                "className": "text-right"
            },
            { "data": "valor_parcela",
                "render": $.fn.dataTable.render.number( '.', ',', 2, '' ),
                "className": "text-right"
            },
            { "data": "parcela" }
        ],
        "pagingType": "full_numbers",
        "language": {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Portuguese-Brasil.json",
            "decimal": ",",
            "thousands": "."
        },
        "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api(), data;
            // Remove the formatting to get integer data for summation
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
            // Total over all pages
            total = api
                .column( 4 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Total over this page
            pageTotal = api
                .column( 4, { page: 'current'} )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Total over this page
            pageTotalparcela = api
                .column( 5, { page: 'current'} )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Update footer
            $( api.column( 1 ).footer() ).html(
                'Total do relatório : R$ '+ total.toLocaleString()
            );
            $( api.column( 4 ).footer() ).html(
                pageTotal.toLocaleString()
            );
            $( api.column( 5 ).footer() ).html(
                pageTotalparcela.toLocaleString()
            );
        }
    });
});
$('#gerarplanilha').click(function () {
    $("#tabela_producao").table2excel({
        exclude: ".noExl",
        name:"Cartoes",
        filename:"CONVENIO-VARGINHA-"+Date()+".xls",//do not include extension
        fileext:".xlsx",
        exclude_img:true,
        exclude_links:true,
        exclude_inputs:true
    });
});
