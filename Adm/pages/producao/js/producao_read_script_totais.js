var divisao;
var card1;
var card2;
var card3;
var card4;
var card5;
var card6;
$(document).ready(function(){

    var mescorrente = "";
    divisao = sessionStorage.getItem("divisao");
    card1 = sessionStorage.getItem("card1");
    card2 = sessionStorage.getItem("card2");
    card3 = sessionStorage.getItem("card3");
    card4 = sessionStorage.getItem("card4");
    card5 = sessionStorage.getItem("card5");
    card6 = sessionStorage.getItem("card6");
    $('#C_empregador').html("<option> Carregando ... </option>");
    $.getJSON( "../Adm/pages/producao/meses_conta.php",{ "origem": "convenio" }, function( data ) {
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
    });
    $('#C_tipo').attr({"title":"Escollha o tipo"});
    $('#C_tipo').append('<option value=""></option>');
    $.getJSON( "../Adm/pages/producao/producao_tipo.php", function( data ) {
        $.each(data, function (index, value) {
            $('#C_tipo').append('<option value="' + value.codigo + '">' + value.nome + '</option>');
        });
    });
    $('#C_empregador').empty();
    $('#C_empregador').append('<option value=""></option>');
    $.getJSON( "../Adm/pages/producao/producao_empregador.php", { "divisao": divisao },function( data ) {
        $.each(data, function (index, value) {
            $('#C_empregador').append('<option data-subtext="' + value.abreviacao + '" value="' + value.id + '">' + value.nome + '</option>');
        });
    });
    $("#C_empregador").html("<option></option>");
    $('#C_empregador').attr({"title":"Escollha o empregador"});
    var total_por_convenio = "CONVENIO";
    var total_por_empregador = "EMPREGADOR";
    $('#C_subtipo').attr({"title":""});
    $('#C_subtipo').append('<option value="' + total_por_convenio + '">' + total_por_convenio + '</option>');
    $('#C_subtipo').append('<option value="' + total_por_empregador + '">' + total_por_empregador + '</option>');
});
$('#btnExibir').click(function () {
    $("#tabela_producao").show();
    var table;
    // constroi uma datatabe no primeiro carregamento da tela
    table = $('#tabela_producao').DataTable({
        "destroy": true,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": false,
        "serverSide": false,
        "responsive": true,
        "autoWidth": true,
        //"bFilter": true,
        "ajax": {
            "url": '../Adm/pages/producao/producao_read2_totais.php',
            "method": 'POST',
            "data":  function(data) {
                data.cod_tipo = $("#C_tipo").val();
                data.mes = $("#C_mes").val();
                data.empregador = $("#C_empregador").val();
                data.cod_subtipo = $("#C_subtipo").val();
                data.divisao = divisao;
            },
            "dataType": 'json'
        },
        "order": [[ 0, "asc" ]],
        "columns": [
            { "data": "descricao" },
            { "data": "total",
                render: $.fn.dataTable.render.number( '.', ',', 2 ),
                className: "text-right"
            }
        ],
        "pagingType": "full_numbers",
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

        "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api(), data;
            // Remove the formatting to get integer data for summation
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
            // Total de todas as paginas
            total = api
                .column( 1 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Total da pagina exibida
            pageTotal = api
                .column( 1, { page: 'current'} )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Update footer
            $(  api.column( 1 ).footer() ).html(
                'Total do relatório : R$ '+ total.toLocaleString()
            );
        }
    });
});
$('#gerarpdf').click(function () {
    var cod_tipo   = $('#C_tipo').val();
    var mes_atual  = $('#C_mes').val();
    var empregador = $('#C_empregador').val();
    var subtipo    = $('#C_subtipo').val();
    $.redirect('../Adm/pages/producao/producao_gerador_pdf_totais.php',{ cod_tipo: cod_tipo, mes_atual: mes_atual, empregador: empregador, subtipo: subtipo, divisao: divisao, 'card1': card1, 'card2': card2, 'card3': card3, 'card4': card4, 'card5': card5}, "POST", "_blank");
});// .update é o botão alterar
function moedaParaNumero(valor)
{
    return isNaN(valor) == false ? parseFloat(valor) :   parseFloat(valor.replace("R$","").replace(".","").replace(",","."));
}
function numeroParaMoeda(n, c, d, t)
{
    c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "," : d, t = t == undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}


