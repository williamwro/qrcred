    $(document).ready(function(){
        var $a = jQuery.noConflict();


            $a("#tabela_producao").show();
            var table;
            // constroi uma datatabe no primeiro carregamento da tela
            table = $a('#tabela_producao').DataTable({
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
                    "data": "",
                    "dataType": 'json'
                },
                "order": [[ 0, "asc" ]],
                "columns": [
                    { "data": "mes" },
                    { "data": "valor",
                        render: $a.fn.dataTable.render.number( ',', '.', 2 )
                    }
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
                    $a(  api.column( 1 ).footer() ).html(
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
