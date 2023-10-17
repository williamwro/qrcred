var divisao;
var table;
var usuarioglobal;
var usuario_cod;
var ordem;
$(document).ready(function(){

    var mescorrente = "";
    divisao = sessionStorage.getItem("divisao");
    $('#data').mask('99/99/9999');
    var d = new Date();
    //soma um mes, e define o quinto dia do mes.
    var dn = new Date(d.getFullYear(), d.getMonth() + 1, 5);
    datapgto = (dn.toLocaleString());
    $('#data').val(datapgto.substring(0,10));
    $('#C_empregador').html("<option> Carregando ... </option>");
    $.getJSON( "../Adm/pages/cheques/meses_conta.php", function( data ) {
        $.each(data, function (index, value) {
            $('#C_mes').append('<option value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
        });
    });
    $('#C_categoria').attr({"title":"Escollha a categoria"});
    $('#C_categoria').append('<option value="">Todas a categoria</option>');
    $.getJSON( "../Adm/pages/recibos/categoria_recibo.php", function( data ) {
        $.each(data, function (index, value) {
            $('#C_categoria').append('<option value="' + value.id_categoria_recibo + '">' + remAcentos(value.nome) + '</option>');
        });
    });
    ordem = "categoria";
});
$('#btnExibir').click(function () {
    $("#tabela_producao").show();
    // construir uma datatable no primeiro carregamento da tela
    if (usuario_cod == 1) {
        table = $('#tabela_producao').DataTable({
            "destroy": true,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            "processing": false,
            "serverSide": false,
            "responsive": true,
            "autoWidth": false,
            paging: false,
            //"bFilter": true,
            "ajax": {
                "url": '../Adm/pages/recibos/recibos.php',
                "method": 'POST',
                "data": function (data) {
                    data.categoria = $("#C_categoria").val();
                    data.mes = $("#C_mes").val();
                    data.dia = $("#data").val();
                    data.divisao = divisao;
                },
                "dataType": 'json'
            },
            "order": [[2, "asc"]],
            "columnDefs": [
                {"width": "60px", "targets": 2,},
                {"width": "60px", "targets": 3}
            ],
            "columns": [
                {"data": "cod_convenio"},
                {"data": "descricao"},
                {"data": "categoria_recibo", className: "noExl"},
                {"data": "extenso", className: "noExl"},
                {"data": "dia", className: "noExl"},
                {"data": "mes", className: "noExl"},
                {
                    "data": "total",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right, noExl"
                },
                {
                    "data": "prolabore",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right, noExl"
                },
                {
                    "data": "valor_prolabore",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right, noExl"
                },
                {
                    "data": "total_liquido",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right"
                }
            ],
            "language": {
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
            "footerCallback": function (row, data, start, end, display) {
                var api = this.api(), data;
                // Remove the formatting to get integer data for summation
                var intVal = function (i) {
                    return typeof i === 'string' ?
                        i.replace(/[\$,]/g, '') * 1 :
                        typeof i === 'number' ?
                            i : 0;
                };
                // Total de todas as paginas
                total = api
                    .column(6)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Total_prolabore de todas as paginas
                total_prolabore = api
                    .column(8)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Total_prolabore de todas as paginas
                total_liquido = api
                    .column(9)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Total da pagina exibida
                pageTotal = api
                    .column(1, {page: 'current'})
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Update footer
                $(api.column(6).footer()).html(
                    total.toLocaleString()
                );
                $(api.column(8).footer()).html(
                    total_prolabore.toLocaleString()
                );
                $(api.column(9).footer()).html(
                    total_liquido.toLocaleString()
                );
            }
        });
    }else{
        table = $('#tabela_producao').DataTable({
            "destroy": true,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            "processing": false,
            "serverSide": false,
            "responsive": true,
            "autoWidth": false,
            paging: false,
            //"bFilter": true,
            "ajax": {
                "url": '../Adm/pages/recibos/recibos.php',
                "method": 'POST',
                "data": function (data) {
                    data.categoria = $("#C_categoria").val();
                    data.mes = $("#C_mes").val();
                    data.dia = $("#data").val();
                    data.divisao = divisao;
                },
                "dataType": 'json'
            },
            "order": [[2, "asc"]],
            "columnDefs": [
                {"width": "60px", "targets": 2,},
                {"width": "60px", "targets": 3},
                {"targets": [3, 6, 7, 8], "visible": false, "searchable": false}
            ],
            "columns": [
                {"data": "cod_convenio"},
                {"data": "descricao"},
                {"data": "categoria_recibo", className: "noExl"},
                {"data": "extenso", className: "noExl"},
                {"data": "dia", className: "noExl"},
                {"data": "mes", className: "noExl"},
                {
                    "data": "total",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right, noExl"
                },
                {
                    "data": "prolabore",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right, noExl"
                },
                {
                    "data": "valor_prolabore",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right, noExl"
                },
                {
                    "data": "total_liquido",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right"
                }
            ],
            "language": {
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
            "footerCallback": function (row, data, start, end, display) {
                var api = this.api(), data;
                // Remove the formatting to get integer data for summation
                var intVal = function (i) {
                    return typeof i === 'string' ?
                        i.replace(/[\$,]/g, '') * 1 :
                        typeof i === 'number' ?
                            i : 0;
                };
                // Total de todas as paginas
                total = api
                    .column(6)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Total_prolabore de todas as paginas
                total_prolabore = api
                    .column(8)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Total_prolabore de todas as paginas
                total_liquido = api
                    .column(9)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Total da pagina exibida
                pageTotal = api
                    .column(1, {page: 'current'})
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Update footer
                $(api.column(6).footer()).html(
                    total.toLocaleString()
                );
                $(api.column(8).footer()).html(
                    total_prolabore.toLocaleString()
                );
                $(api.column(9).footer()).html(
                    total_liquido.toLocaleString()
                );
            }
        });
    }
});
$('#exportar').click(function () {
    var mes_atual = $('#C_mes').val();
    var categoria = $('#C_categoria').val();
   
    $.redirect('../Adm/pages/recibos/listagem_cheques.php',{ categoria: categoria, mes_atual: mes_atual, divisao: divisao, ordem: ordem }, "POST", "_blank");
});
$('#C_orderm_categoria').click(function () {
    table.columns( [ 2, 1 ] ).order( 'asc', 'asc' ).draw();
    ordem = "categoria";
}); 
$('#C_orderm_todos').click(function () {
    table.columns( [ 1 ] ).order( 'asc' ).draw();
    ordem = "todos";
}); 
$('#gerarpdf').click(function () {
    var mes_atual = $('#C_mes').val();
    var categoria = $('#C_categoria').val();
    var data      = $('#data').val();
    $.redirect('../Adm/pages/recibos/recibos_gerador_pdf.php',{ categoria: categoria, mes_atual: mes_atual, divisao: divisao, data: data }, "POST", "_blank");
});// .update é o botão alterar

$('#recibostx').click(function () {
    var mes_atual = $('#C_mes').val();
    var data      = $('#data').val();
    $.redirect('../Adm/pages/recibos/recibos_tx_pdf.php',{ mes_atual: mes_atual, data: data }, "POST", "_blank");
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
function remAcentos(v){
    var c='áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
    var s='aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
    var n = '';
    for(var x=0;x<v.length;x++){
        c.search(v.substr(x,1))>=0 ?
            n+=s.substr(c.search(v.substr(x,1)),1) :
            n+=v.substr(x,1);
    }
    return n;
}