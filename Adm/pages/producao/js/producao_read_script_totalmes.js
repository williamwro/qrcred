$(document).ready(function(){

    divisao = sessionStorage.getItem("divisao");
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
                "url": '../Adm/pages/producao/producao_read2_totalmes.php',
                "method": 'POST',
                "data": {"divisao" : divisao},
                "dataType": 'json'
            },
            "columns": [
                { "data": "data" },
                { "data": "mes" },
                { "data": "valor",
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
                        i.replace(/[\$a,]/g, '')*1 :
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
    function moedaParaNumero(valor)
    {
        return isNaN(valor) == false ? parseFloat(valor) :   parseFloat(valor.replace("R$","").replace(".","").replace(",","."));
    }
    function numeroParaMoeda(n, c, d, t)
    {
        c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "," : d, t = t == undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
        return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
    }
});
