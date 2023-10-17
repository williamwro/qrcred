    $(document).ready(function(){
        var $a = jQuery.noConflict();
        var mescorrente = "";
        $a('#C_empregador').html("<option> Carregando ... </option>");
        $a.getJSON( "../Adm/pages/producao/meses_conta.php", function( data ) {
            $a.each(data, function (index, value) {
      
                 if (value.mes_corrente !== undefined) {
                     mescorrente = value.mes_corrente;
                 }
                 if (value.ABREVIACAO !== undefined) {
                    if (mescorrente == value.ABREVIACAO) {
                        $a('#C_mes').append('<option selected value="' + value.ABREVIACAO + '">' + value.ABREVIACAO + '</option>').selectpicker('refresh');
                    } else {
                        $a('#C_mes').append('<option value="' + value.ABREVIACAO + '">' + value.ABREVIACAO + '</option>').selectpicker('refresh');
                    }
                }
            });
        });
        $a('#C_tipo').attr({"title":"Escollha o tipo"});
        $a('#C_tipo').append('<option value=""></option>');
        $a.getJSON( "../Adm/pages/producao/producao_tipo.php", function( data ) {
            $a.each(data, function (index, value) {
                $a('#C_tipo').append('<option value="' + value.Codigo + '">' + value.Nome + '</option>').selectpicker('refresh');
            });
        });


        $a('#C_empregador').empty();
        $a('#C_empregador').append('<option value=""></option>');
        $a.getJSON( "../Adm/pages/producao/producao_empregador.php", function( data ) {
            $a.each(data, function (index, value) {
                $a('#C_empregador').append('<option data-subtext="' + value.ABREVIACAO + '" value="' + value.Id + '">' + value.Nome + '</option>').selectpicker('refresh');
            });
        });
        $a("#C_empregador").html("<option></option>");
        $a('#C_empregador').attr({"title":"Escollha o empregador"});
        $a(".selectpicker").selectpicker({
            liveSearch: true,
            showSubtext: true
        });
        var total_por_convenio = "convenio";
        var total_por_empregador = "empregador";
        $a('#C_subtipo').attr({"title":""});
        $a("#C_subtipo").html("<option></option>");
        $a('#C_subtipo').append('<option value="' + total_por_convenio + '">' + total_por_convenio + '</option>').selectpicker('refresh');
        $a('#C_subtipo').append('<option value="' + total_por_empregador + '">' + total_por_empregador + '</option>').selectpicker('refresh');

        $a('#btnExibir').click(function () {
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
                    "url": '../Adm/pages/producao/producao_read2_totais.php',
                    "method": 'POST',
                    "data":  function(data) {
                        data.cod_tipo = $a("#C_tipo").val();
                        data.mes = $a("#C_mes").val();
                        data.empregador = $a("#C_empregador").val();
                        data.cod_subtipo = $a("#C_subtipo").val();
                    },
                    "dataType": 'json'
                },
                "order": [[ 0, "asc" ]],
                "columns": [
                    { "data": "Descricao" },
                    { "data": "Total",
                        render: $a.fn.dataTable.render.number( ',', '.', 2 )
                    }
                ],
                "pagingType": "full_numbers",
                /*"language": {
                    url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Portuguese-Brasil.json",
                    "decimal": ",",
                    "thousands": "."
                },*/

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
        });
        $a('#gerarpdf').click(function () {
            var cod_tipo   = $a('#C_tipo').val();
            var mes_atual  = $a('#C_mes').val();
            var empregador = $a('#C_empregador').val();
            var subtipo    = $a('#C_subtipo').val();
            $a.redirect('../Adm/pages/producao/producao_gerador_pdf_totais.php',{ cod_tipo: cod_tipo, mes_atual: mes_atual, empregador: empregador, subtipo: subtipo});
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

    });
