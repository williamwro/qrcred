var mescorrente = "";
var divisao_nome = "";
var usuario_cod = "";
var mes = "";
var pasta = "";
var path_arquivo = "";
var path_arquivo2 = "";
var C_empregador = $('#C_empregador');
var C_tipo = $('#C_tipo');
var arr = [];
var resultado = [];
var table;
var rowIdx;
var valor_novo;
var valor_velho;
var card1;
var card2;
var card3;
var card4;
var card5;
var card6;
var data_upload_x = new FormData();
$(document).ready(function(){

    divisao      = sessionStorage.getItem("divisao");
    divisao_nome = sessionStorage.getItem("divisao_nome");
    usuario_cod  = sessionStorage.getItem("usuario_cod");
    card1        = sessionStorage.getItem("card1");
    card2        = sessionStorage.getItem("card2");
    card3        = sessionStorage.getItem("card3");
    card4        = sessionStorage.getItem("card4");
    card5        = sessionStorage.getItem("card5");
    card6        = sessionStorage.getItem("card6");
    $('#divisao').val(divisao_nome);
    $.getJSON( "../Adm/pages/arquivos/meses_conta.php",{ "origem": "convenio" }, function( data ) {
        $.each(data, function (index, value) {
            if (value.mes_corrente !== undefined) {
                mescorrente = value.mes_corrente;
                $("#C_mes_hidden").val(value.mes_corrente);
                $('#mes').val(value.mes_corrente);
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
    C_tipo.attr({"title":"Escollha o tipo"});
    C_tipo.append('<option value="0">Todos</option>');
    $.getJSON( "../Adm/pages/producao/producao_tipo.php", function( data ) {
        $.each(data, function (index, value) {
            C_tipo.append('<option value="' + value.codigo + '">' + value.nome + '</option>');
        });
    });
    C_empregador.empty();
    C_empregador.append('<option data-subtext="" value=""></option>');
    $.getJSON( "../Adm/pages/arquivos/producao_empregador.php",{"divisao": divisao}, function( data ) {
        $.each(data, function (index, value) {
            C_empregador.append('<option data-subtext="' + value.abreviacao + '" value="' + value.id + '">' + value.nome + '</option>');
        });
    });
    $('#but_upload').attr('disabled', true);
    $('#btnExcluirArquivo').attr('disabled', true);
    $('#default_file').attr('disabled', false);
});
$('#tabela_dados').on('change', 'tbody input.dt-checkboxes', function () {

    //var controle = $(this).prop('checked');//mostra se checked é true or false
    //var id       = $(this).closest('tr').find('td').eq(1).text();
    var self = this;
    if (self.checked) {
        $(this).closest('tr').find('td').css('background-color', '#c0f5ac');
    }else{
        $(this).closest('tr').find('td').css('background-color', '#ffffff');
    }
});
function carrega_mes(){
    // load window system here
    var diretorio = 'uploads/'+divisao_nome+'/';
    var mes = $('#C_mes').val();
    var empregador = C_empregador.children("option:selected").text();
    var caminho = path_arquivo;
    var total = 0;
    if(mes !== null && empregador !== '') {
        $.ajax({
            url: '../Adm/pages/arquivos/carrega_arquivo.php',
            type: 'post',
            data: {origem: diretorio, mes: mes, empregador: empregador},
            success: function (response) {
                // Remove

                if (response.length > 0) {
                        if (response["result"] !== "nao achou") {
                            vetor = response[0].split('/');
                            $('#caminho_arquivo').html(vetor[11]);
                            $("#link_arquivo").attr("href", response[2]);
                            path_arquivo = response[0];
                            $('.progress-bar').width('100%');
                            $('.progress-bar').html('100%');
                            $('#tamanho_arquivo').html(response[1] + " bytes");
                            $('#default_file').val('');
                            $('#but_upload').attr('disabled', true);
                            $('#btnExcluirArquivo').attr('disabled', false);
                            $('#default_file').attr('disabled', true);
                            $('#row_tabela').show();

                            $.ajax({
                                url: '../Adm/pages/arquivos/conferencia.php',
                                type: 'post',
                                data: {caminho: path_arquivo, mes: mes, empregador: empregador},
                                dataType: 'json',
                                async: false,
                                success: function (data) {

                                    $.each(data, function (index) {
                                        total += data[index].total
                                    });
                                    $('#total_arquivo').html('Total do arquivo : ' + total.toLocaleString("pt-BR", {
                                        style: "decimal",
                                        currency: "BRL"
                                    }));
                                }
                            });
                        }else{
                            $('.progress-bar').width('0%');
                            $('.progress-bar').html('0%');
                            $('#default_file').attr('disabled', false);
                            $('#default_file').val('');
                            $('#row_tabela').hide();
                        }
                } else {
                    $('.progress-bar').width('0%');
                    $('.progress-bar').html('0%');
                    $('#default_file').attr('disabled', false);
                    $('#default_file').val('');
                    $('#row_tabela').hide();
                }
            }
        });
    }
}
$("#default_file").click(function(){

    cria_pasta();
    $('#total_arquivo').html('');
});
function cria_pasta(){
    mes = $("#C_mes").val();
    empregador = C_empregador.children("option:selected").text();
    if(empregador !== '') {
        $.ajax({
            url: '../Adm/pages/arquivos/criar_pasta.php',
            type: 'post',
            data: {divisao_nome: divisao_nome, mes: mes, empregador: empregador},
            dataType: 'json',
            async: true,
            success: function (response) {
                pasta = response.pasta;
                $('#caminho').val(pasta);
                $("#but_upload").attr('disabled', false);
                $('#row_tabela').show();
            },
        });
    }
}
$("#but_upload").click(function(){

    var mes2 = $('#C_mes').val();
    var empregador2 = C_empregador.children("option:selected").text();

    //var data = new FormData();
    var files  =$('#default_file')[0].files[0];

    data_upload_x.append("file", files);
    data_upload_x.append("mescorrente", mescorrente);
    data_upload_x.append("pasta",pasta);

    $.ajax({
        url: '../Adm/pages/arquivos/upload.php',
        type: 'post',
        data: data_upload_x,
        contentType: false,
        processData: false,
        async: false,
        success: function(response){
            if(response !== 0){

                var vetor = response.split('/');
                var arquivo = vetor[4];
                var tamanho = vetor[5];
                var response2 = vetor[0]+"/"+vetor[1]+"/"+vetor[2]+"/"+vetor[3];
                $("#link_arquivo").attr("href", '../Adm/pages/arquivos/'+response2);
                $('#path_arquivo').val(response2);
                $('#caminho_arquivo').html(arquivo);
                $('#tamanho_arquivo').html(tamanho+" bytes");
                $("#btnExcluirArquivo").attr('disabled', false);
                path_arquivo = response2;
                path_arquivo2 = 'C:\\xampp\\htdocs\\sind\\Adm\\pages\\arquivos\\'+vetor[0]+'\\'+vetor[1]+'\\'+vetor[2]+'\\'+vetor[3]+'\\'+arquivo;


            }else{
                alert('file not uploaded');
            }
        },
    });
    carrega_mes();
});
$('#btnExcluirArquivo').click(function(){

    var arquivo = path_arquivo;

    // Get image source
    var deleteFile = confirm("Deseja excluir este arquivo?");
    if (deleteFile === true) {
        // AJAX request
        $.ajax({
            url: '../Adm/pages/arquivos/remove_arquivo.php',
            type: 'post',
            data: {path: arquivo},
            success: function(response){
                // Remove

                if(response === '1'){
                    $('#caminho_arquivo').html('');
                    $('.progress-bar').width(0);
                    $('.progress-bar').html('');
                    $('#default_file').val('');
                    $('#but_upload').attr('disabled', true);
                    $('#btnExcluirArquivo').attr('disabled', true);
                    $('#tamanho_arquivo').html('');
                    $('#row_tabela').hide();
                    $('#default_file').attr('disabled', false);
                }
            }
        });
        table.clear().draw();
    }
});
$('#C_mes').change(function () {
    cria_pasta();
    carrega_mes();
});
C_empregador.change(function () {
    if($('#C_mes').val() !== "" && C_empregador.val() !== ""){
        $("#but_upload").attr('disabled', false);
        $('#default_file').attr('disabled', false);
        cria_pasta();
        carrega_mes();
    }else{
        $("#but_upload").attr('disabled', true);
        $('#default_file').attr('disabled', true);
    }
});
$('#gerararquivo').click(function(){
    //$('#ModalCarregando').modal('show');

    $(this).html('<span class="fa fa-refresh fa-spin" role="status" aria-hidden="true"></span>&nbsp&nbspCarregando...').addClass('disabled');
    $(this).addClass("disabled");
    var caminho = path_arquivo;
    var campos = [];
    var campos2 = {};
    var contador_result = 0;
    var vetor_todos = [];
    var myJSON;
    var myJSON2;
    var tipo;
    var nome_tipox;
    var mes;
    mes = $('#C_mes').val();
    empregador = C_empregador.val();
    tipo = C_tipo.val();
    $.ajax({
        url: '../Adm/pages/arquivos/selecionar_dados2.php',
        type: 'post',
        data: {mes: mes,empregador: empregador, tipo: tipo, divisao: divisao, 'card1': card1, 'card2': card2, 'card3': card3, 'card4': card4, 'card5': card5, 'card6': card6},
        dataType: 'json',
        async:false,
        success: function(data){
            arr = data;
        }
    });
    $.ajax({
        url: '../Adm/pages/arquivos/conferencia.php',
        type: 'post',
        data: {caminho: path_arquivo,mes: mes,empregador: empregador},
        dataType: 'json',
        async:false,
        success: function(data2){

            const findAssoc = function(data2, associado, tipo, total){
                const associadoretornado = data2.find(function (todo, index) {
                    return todo.associado === associado && todo.tipo === tipo;
                });
                return associadoretornado;
            };
            $.each(arr, function(index) {

                var associado = arr[index]['associado'];
                var nome      = arr[index]['nome'];
                var nome_tipo = arr[index]['nome_tipo'];
                var tipo      = arr[index]['codrg'];
                var total     = parseFloat(parseFloat(arr[index]['total']).toFixed(2));
                nome_tipox = nome_tipo;

                let result = findAssoc(data2,associado,tipo,total);
                if(result !== undefined) {
                    if (result['total']) {
                        if (total > result['total']) {
                            //descontou parcial
                            contador_result++;
                            var total_sobra = total - result['total'];
                            campos2 = [];
                            campos.push({
                                seleciona : false,
                                associado: associado,
                                nome: nome,
                                nome_tipo: nome_tipo,
                                tipo: result['tipo'],
                                gastou: total,
                                descontou: result['total'],
                                empregador: empregador,
                                usuario: usuario_cod,
                                mes: mes,
                                total: parseFloat(total_sobra).toFixed(2),
                            });
                            campos2 = campos;
                        }else if (total < result['total']) {
                            //descontou a mais

                            contador_result++;
                            var total_alem = total - result['total'];
                            campos2 = [];
                            campos.push({
                                seleciona : false,
                                associado: associado,
                                nome: nome,
                                nome_tipo: nome_tipo,
                                tipo: result['tipo'],
                                gastou: total,
                                descontou: result['total'],
                                empregador: empregador,
                                usuario: usuario_cod,
                                mes: mes,
                                total: parseFloat(total_alem).toFixed(2),
                            });
                            campos2 = campos;
                        }
                    }
                }else{
                    //descontou zero(0)
                    contador_result++;
                    campos2 = [];
                    campos.push({
                        seleciona : false,
                        associado: associado,
                        nome: nome,
                        nome_tipo: nome_tipo,
                        tipo: tipo,
                        gastou: total,
                        descontou: 0,
                        empregador: empregador,
                        usuario: usuario_cod,
                        mes: mes,
                        total: parseFloat(total).toFixed(2)
                    });
                    campos2 = campos;
                }
            });
        }
    });
    table = $('#tabela_dados').DataTable({
        dom: 'Bfrtip',
        destroy: true,
        deferRender: true,
        data: campos2,
        responsive: true,
        autoWidth: true,
        processing: false,
        paging: false,
        buttons: [
            {
                extend: 'print',
                footer: true,
                exportOptions: {
                    columns: [ 1, 2, 5, 6, 7 ]
                },
                title:  $('#C_empregador option:selected').html() + " - " + $('#C_tipo option:selected').html() + " NÃO DESCONTADO - " + mes,
                autoPrint: false,
                customize: function ( win ) {
                    $(win.document.body)
                        .css( 'font-size', '10pt' )
                        .prepend(
                            //'<img src="http://127.0.0.1/sind/Adm/pages/arquivos/logo_sind_pqno.png" style="position:absolute; top:0; left:0; width: 70px}" />'
                        );
                    $(win.document.body).find( 'table' )
                        .addClass( 'compact' )
                        .css( 'font-size', 'inherit' );
                }
            }
        ],
        order: [[2, "asc"]],
        keys: {
            blurable: false,
        },
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        columns: [
            {data: 'seleciona'},
            {data: 'associado'},
            {data: 'nome'},
            {data: 'nome_tipo'},
            {data: 'tipo'},
            {
                data: 'gastou',
                render: $.fn.dataTable.render.number(', ', '.', 2),
                className: "text-right"
            },
            {
                data: 'descontou',
                render: $.fn.dataTable.render.number(', ', '.', 2),
                className: "text-right"
            },
            {
                data: "total",
                render: $.fn.dataTable.render.number(', ', '.', 2),
                className: "text-right"
            },
            {data: 'empregador'},
            {data: 'usuario'},
            {data: 'mes'}
        ],
        columnDefs: [
            {"targets": [0], "visible": false, "searchable": false},
            {"targets": [4], "visible": false, "searchable": false},
            {"targets": [8], "visible": false, "searchable": false},
            {"targets": [9], "visible": false, "searchable": false},
            {"targets": [10], "visible": false, "searchable": false}
        ],
        fixedColumns: {
            'leftColumns': 0
        },
        select: {
            style: 'multi'
        },
        "language": {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Portuguese-Brasil.json",
            "loadingRecords": "Loading data..."
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
                .column(7)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
            totaldesconto = api
                .column(6)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
            totalgasto = api
                .column(5)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
            // Total da pagina exibida
            pageTotal = api
                .column(7, {page: 'current'})
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
            // Total compras

            totalCompras = api
                .column(7)
                .data()
                .reduce(function (a, b) {
                    // Find index of current value for accessing sumCondition value in same row
                    var cur_index = api.column(7).data().indexOf(b);
                    if (api.column(3).data()[cur_index] === "COMPRAS") {
                        return intVal(a) + intVal(b);
                    } else {
                        return intVal(a);
                    }
                }, 0);
            totalFarmacia = api
                .column(7)
                .data()
                .reduce(function (a, b) {
                    // Find index of current value for accessing sumCondition value in same row
                    var cur_index = api.column(7).data().indexOf(b);
                    if (api.column(3).data()[cur_index] === "FARMACIA") {
                        return intVal(a) + intVal(b);
                    } else {
                        return intVal(a);
                    }
                }, 0);
            totalUnimed = api
                .column(7)
                .data()
                .reduce(function (a, b) {
                    // Find index of current value for accessing sumCondition value in same row
                    var cur_index = api.column(7).data().indexOf(b);
                    if (api.column(3).data()[cur_index] === "UNIMED") {
                        return intVal(a) + intVal(b);
                    } else {
                        return intVal(a);
                    }
                }, 0);
            // Update footer
            $(api.column(3).footer()).html(
                'Totais : '
            );
            $(api.column(5).footer()).html(
                totalgasto.toLocaleString().fontcolor("green")
            );
            $(api.column(6).footer()).html(
                totaldesconto.toLocaleString().fontcolor("green")
            );
            $(api.column(7).footer()).html(
                total.toLocaleString().fontcolor("red")
            );
            var tot = totalCompras + totalFarmacia + totalUnimed;
            $("#comgasto").html(totalCompras.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
            $("#fargasto").html(totalFarmacia.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
            $("#unigasto").html(totalUnimed.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
            $("#totalgasto").html(tot.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
        }
    });
    //$('#ModalCarregando').modal('hide');
    $(this).html('Processar').removeClass("disabled");
});
$('#tabela_dados').on( 'click', 'tbody td:not(:first-child)', function () {
   
    rowIdx = table.cell( this ).index().row;
    var colIdx = table.cell( this ).index().column;
    var cellContent = table.cell( this ).data();
    var data_row = table.row($(this).closest('tr')).data();
    if (colIdx === 7) {
        //cod_convenio = data_row.id;
        valor_velho = data_row.total;
        $('#inputValor').val(valor_velho);
        $("#ModalAtualizaResiduo").modal({show: true});
    }
});
$('#btnAtualizar').click(function () {
    valor_novo = $('#inputValor').val();
    //$("td:contains("+valor_velho+")").text(valor_novo);
    $('#tabela_dados tbody tr').find("td:contains("+valor_velho+")").text(valor_novo);
    //jQuery.each($("body").find("table"), function() {
    //    this.innerHTML = this.innerHTML.split(valor_velho).join(valor_novo);
    //});
});
$('#btnExcluirSelecionados').click(function () {
    table.row('.selected').remove().draw( false );
});
$('#btnGravar').click(function () {
    var rowData = table.rows( { selected: true } ).data().toArray();
    var linhas = JSON.stringify(rowData);
    $.ajax({
        url: '../Adm/pages/arquivos/gravar_nao_descontado.php',
        type: 'post',
        data: {'data':linhas},
        dataType: 'json',
        async:false,
        success: function(data){
            arr = data;
            BootstrapDialog.show({
                closable: false,
                title: 'Atenção',
                message: 'Gravado com Sucesso!!!',
                buttons: [{
                    cssClass: 'btn-primary',
                    label: 'Ok',
                    action: function (dialogItself) {
                        dialogItself.close();
                        //$("#C_Senha").focus();
                    }
                }]
            });
        }
    });
});
$('#btnImprimir').click(function () {
    var mes_atual  = $('#C_mes').val();
    var empregador = $('#C_empregador').val();
    var tipo = $('#C_tipo').val();
    //var divisao    = $divisao;
    $.redirect('../Adm/pages/arquivos/relatorio_final.php',{ mes_atual: mes_atual, empregador: empregador, tipo: tipo}, "POST", "_blank");
});