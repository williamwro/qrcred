var table;
var mes_selecionado;
var $C_mes = $('#C_mes');
var $C_empregador = $('#C_empregador');
var $C_tipo = $('#C_tipo');
var mescorrente = "";
var $tabela_dados = $('#tabela_dados');
var total_farmacia = 0;
var total_compras = 0;
var total_unimed = 0;
var divisao = 0;
var card1;
var card2;
var card3;
var card4;
var card5;
var card6;
$(document).ready(function(){

    waitingDialog.show('Carregando, aguarde ...');
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


    $.getJSON( "../Adm/pages/arquivos/meses_conta.php",{ "origem": "convenio" }, function( data ) {
        $.each(data, function (index, value) {
            if (value.mes_corrente !== undefined) {
                mescorrente = value.mes_corrente;
            }
            if (value.abreviacao !== undefined) {
                if (mescorrente === value.abreviacao) {
                    $C_mes.append('<option selected value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                } else {
                    $C_mes.append('<option value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                }
            }
        });
    });
    $C_empregador.empty();
    $C_empregador.append('<option data-subtext="" value=""></option>');
    $.getJSON( "../Adm/pages/arquivos/producao_empregador.php",{"divisao": divisao}, function( data ) {
        $.each(data, function (index, value) {
            $C_empregador.append('<option data-subtext="' + value.abreviacao + '" value="' + value.id + '">' + value.nome + '</option>');
        });
    });
    $C_tipo.attr({"title":"Escollha o tipo"});
    $C_tipo.append('<option value=""></option>');
    $.getJSON( "../Adm/pages/producao/producao_tipo.php", function( data ) {
        $.each(data, function (index, value) {
            $C_tipo.append('<option value="' + value.codigo + '">' + value.nome + '</option>');
        });
    });
    waitingDialog.hide();
});
$C_mes.change(function () {
    if ($C_mes.val() !== "" && $C_empregador.val() !== ""){
        waitingDialog.show('Carregando, aguarde ...');
        // constroi uma datatabe no primeiro carregamento da tela

        mes_selecionado = $(this).children("option:selected").val();
        carregar_grid();
        waitingDialog.hide();
    }
});
$C_empregador.change(function () {
    if ($C_mes.val() !== "" && $C_empregador.val() !== "") {
        waitingDialog.show('Carregando, aguarde ...',);
        // constroi uma datatabe no primeiro carregamento da tela

        carregar_grid();
        waitingDialog.hide();
    }
});
$C_tipo.change(function () {
    if ($C_mes.val() !== "" && $C_empregador.val() !== "" ){
        waitingDialog.show('Carregando, aguarde ...');
        // constroi uma datatabe no primeiro carregamento da tela

        carregar_grid();
        waitingDialog.hide();
    }
});
$("#gerararquivo").click(function () {

    if (divisao_nome === "Casserv") {
        mes_selecionado = $('#C_mes').val();
        if( $('#C_empregador').val() === "1" ||  $('#C_empregador').val() === "8" ) { // 1 = PMV PREFEITURA MUNICIPAL
            var data = table.rows().data();
            var texto = '';
            var obj = {};
            obj.dados = [];
            var d = new Date();
            var dataHora = (d.toLocaleString());
            dataHora.substring(0, 10);
            var farmacia = 0;
            var compras = 0;
            var financeira = 0;
            var unimed = 0;
            var financeira2 = 0;
            var financeira3 = 0;
            var linha = '';
            if (table.rows().count() > 0) {
                data.each(function (value, index) {
                    if (value.nome_tipo === 'FARMACIA') {//farmacia
                        farmacia = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0439' + farmacia;
                    } else if (value.nome_tipo === 'COMPRAS') {//compras
                        compras = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0354' + compras;
                    } else if (value.nome_tipo === 'FINANCEIRA') {//financeira
                        financeira = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0313' + financeira;
                    } else if (value.nome_tipo === 'UNIMED') {//unimed
                        unimed = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0495' + unimed;
                    } else if (value.nome_tipo === 'FINANCEIRA2') {//financeira2
                        financeira2 = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0317' + financeira2;
                    } else if (value.nome_tipo === 'FINANCEIRA3') {//financeira3
                        financeira3 = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        if ($C_empregador.value === 'PREFEITURA MUNICIPAL') {
                            linha += value.associado + '0350' + financeira3;
                        } else if ($C_empregador.value === 'INPREV') {
                            linha += value.associado + '0350' + financeira3;
                        }
                    }
                    farmacia = 0;
                    compras = 0;
                    financeira = 0;
                    unimed = 0;
                    financeira2 = 0;
                    financeira3 = 0;
                });
                let blob = new Blob([linha], {type: "text/plain;charset=utf-8"});
                saveAs(blob, divisao_nome + "_" + $('#C_empregador').val() + "_" + mes_selecionado + "_VALORES_" + dataHora.substring(0, 10));
            }
        }else if( $('#C_empregador').val() === "3" ) { // 3 = FH - FUNDACAO HOSPITALAR
            var data = table.rows().data();
            var texto = '';
            var obj = {};
            obj.dados = [];
            var d = new Date();
            var dataHora = (d.toLocaleString());
            var data_short = dataHora.substring(0, 10);
            var data_vetor = data_short.split("/");
            var mes = data_vetor[1];
            var ano = data_vetor[2];
            var farmacia = 0;
            var compras = 0;
            var linha = '';
            mes_selecionado = $('#C_mes').val();
            if (table.rows().count() > 0) {
                data.each(function (value, index) {
                    if (value.nome_tipo === 'FARMACIA') {//farmacia
                        farmacia = ("        " + (parseFloat(value.total).toFixed(2).replace(',', ''))).slice(-8);
                        linha += '"' + ano + '","' + mes + '","' + value.associado + '","4293","' + farmacia + '","01"' + "\r\n";
                    } else if (value.nome_tipo === 'COMPRAS') {//compras
                        compras = ("        " + (parseFloat(value.total).toFixed(2).replace(',', ''))).slice(-8);
                        linha += '"' + ano + '","' + mes + '","' + value.associado + '","4292","' + compras + '","01"'+ "\r\n";
                    } else if (value.nome_tipo === 'UNIMED') {//compras
                        compras = ("        " + (parseFloat(value.total).toFixed(2).replace(',', ''))).slice(-8);
                        linha += '"' + ano + '","' + mes + '","' + value.associado + '","448","' + compras + '","01"'+ "\r\n";
                    }
                    farmacia = 0;
                    compras = 0;
                });

                let blob = new Blob([linha], {type: "text/plain;charset=utf-8"});
                saveAs(blob, divisao_nome + "_" + mes_selecionado + "_VALORES_" + dataHora.substring(0, 10)+".txt");
            }
        }
    }else if (divisao_nome === "Sindicato"){
        mes_selecionado = $('#C_mes').val();
        if( $('#C_empregador').val() === "10" ) { // 1 = PMV PREFEITURA MUNICIPAL
            var data = table.rows().data();
            var texto = '';
            var obj = {};
            obj.dados = [];
            var d = new Date();
            var dataHora = (d.toLocaleString());
            dataHora.substring(0, 10);
            var farmacia = 0;
            var compras = 0;
            var financeira = 0;
            var unimed = 0;
            var financeira2 = 0;
            var financeira3 = 0;
            var linha = '';
            if (table.rows().count() > 0) {
                data.each(function (value, index) {
                    if (value.nome_tipo === 'FARMACIA') {//farmacia
                        farmacia = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0350' + farmacia;
                    } else if (value.nome_tipo === 'COMPRAS') {//compras
                        compras = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0355' + compras;
                    } else if (value.nome_tipo === 'FINANCEIRA') {//financeira
                        financeira = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0313' + financeira;
                    } else if (value.nome_tipo === 'UNIMED') {//unimed
                        unimed = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0495' + unimed;
                    } else if (value.nome_tipo === 'FINANCEIRA2') {//financeira2
                        financeira2 = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0317' + financeira2;
                    } else if (value.nome_tipo === 'FINANCEIRA3') {//financeira3
                        financeira3 = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                    }
                    farmacia = 0;
                    compras = 0;
                    financeira = 0;
                    unimed = 0;
                    financeira2 = 0;
                    financeira3 = 0;
                });
                let blob = new Blob([linha], {type: "text/plain;charset=utf-8"});
                saveAs(blob, divisao_nome + "_" + $('#C_empregador').val() + "_" + mes_selecionado + "_VALORES_" + dataHora.substring(0, 10));
            }
        }else if( $('#C_empregador').val() === "12" ) { // 3 = FH - FUNDACAO HOSPITALAR
            var data = table.rows().data();
            var texto = '';
            var obj = {};
            obj.dados = [];
            var d = new Date();
            var dataHora = (d.toLocaleString());
            var data_short = dataHora.substring(0, 10);
            var data_vetor = data_short.split("/");
            var mes = data_vetor[1];
            var ano = data_vetor[2];
            var farmacia = 0;
            var compras = 0;
            var linha = '';
            mes_selecionado = $('#C_mes').val();
            if (table.rows().count() > 0) {
                data.each(function (value, index) {
                    if (value.nome_tipo === 'FARMACIA') {//farmacia
                        farmacia = ("        " + (parseFloat(value.total).toFixed(2).replace(',', ''))).slice(-8);
                        linha += '"' + ano + '","' + mes + '","' + value.associado + '","D350","' + farmacia + '","01"' + "\r\n";
                    } else if (value.nome_tipo === 'COMPRAS') {//compras
                        compras = ("        " + (parseFloat(value.total).toFixed(2).replace(',', ''))).slice(-8);
                        linha += '"' + ano + '","' + mes + '","' + value.associado + '","D448","' + compras + '","01"'+ "\r\n";
                    } else if (value.nome_tipo === 'UNIMED') {//compras
                        compras = ("        " + (parseFloat(value.total).toFixed(2).replace(',', ''))).slice(-8);
                        linha += '"' + ano + '","' + mes + '","' + value.associado + '","D448","' + compras + '","01"'+ "\r\n";
                    }
                    farmacia = 0;
                    compras = 0;
                });

                let blob = new Blob([linha], {type: "text/plain;charset=utf-8"});
                saveAs(blob, divisao_nome + "_" + mes_selecionado + "_VALORES_" + dataHora.substring(0, 10)+".txt");
            }
        }
    }
});
$('#relatoriofinal').click(function () {
    var mes_atual  = $('#C_mes').val();
    var empregador = $('#C_empregador').val();
    var tipo = $('#C_tipo').val();
    $.redirect('../Adm/pages/arquivos/relatorio_final.php',{ mes_atual: mes_atual, empregador: empregador, tipo: tipo, divisao: divisao, card1: card1, card2: card2, card3: card3, card4: card4, card5: card5, card6: card6}, "POST", "_blank");
});
$('#btnImprimirTodosExtratos').click(function () {
    var mes_atual  = $('#C_mes').val();
    var empregador = $('#C_empregador').val();
    if($('#C_empregador').val() != "") {
        debugger;
        $.redirect('../Adm/pages/arquivos/conta_imprimir_todos_pdf.php',{ mes_atual: mes_atual, empregador: empregador, card1: card1, card2: card2, card3: card3, card4: card4, card5: card5, card6: card6}, "POST", "_blank");
    }else{
        BootstrapDialog.show({
            closable: false,
            title: 'Atenção',
            message: 'Escolha o empregador PMV',
            buttons: [{
                cssClass: 'btn-danger',
                label: 'Ok',
                action: function (dialogItself) {
                    dialogItself.close();
                    //$("#C_Senha").focus();
                }
            }]
        });
     }

});
function carregar_grid() {
    total_compras = 0;
    total_farmacia = 0;
    total_unimed = 0;
    if ( $.fn.dataTable.isDataTable( '#tabela_dados' ) ) {
        table.destroy();
        table = $tabela_dados.DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            serverSide: false,
            responsive: true,
            autoWidth: true,
            JQueryUI: true,
            searching: true,
            order: [[1, "asc"]],
            ajax: {
                url: '../Adm/pages/arquivos/selecionar_dados.php',
                method: 'POST',
                data: function (data) {
                    data.mes = $("#C_mes").val();
                    data.empregador = $("#C_empregador").val();
                    data.tipo = $("#C_tipo").val();
                    data.divisao = divisao;
                    data.card1 = card1;
                    data.card2 = card2;
                    data.card3 = card3;
                    data.card4 = card4;
                    data.card5 = card5;
                    data.card6 = card6;
                },
                dataType: 'json'
            },
            columns: [
                {data: "associado"},
                {data: "nome"},
                {data: "nome_tipo"},
                {
                    data: "total",
                    render: $.fn.dataTable.render.number(',', '.', 2),
                    className: "text-right"
                }
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
            },
            createdRow: function (row, data, index){
                // Now that we have the data and not the HTML it is cleaner

                if (data["nome_tipo"] === "COMPRAS") {

                    $('td', row).css('background-color', '#a9f5b4');
                    total_compras += parseFloat(data["total"].replace(",", "."));

                }else if (data["nome_tipo"] === "FARMACIA") {

                    $('td', row).css('background-color', '#a3a9f5');
                    total_farmacia += parseFloat(data["total"].replace(",", "."));

                }else if (data["nome_tipo"] === "UNIMED") {

                    $('td', row).css('background-color', '#f5eea0');
                    total_unimed += parseFloat(data["total"].replace(",", "."));

                }
                $('.somacompras').html(total_compras.toFixed(2).toLocaleString());
                $('.somafarmacia').html((total_farmacia).toFixed(2).toLocaleString());
                $('.somaunimed').html((total_unimed).toFixed(2).toLocaleString());
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
                // Total geral
                total = api
                    .column( 3 )
                    .data()
                    .reduce( function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0 );
                // Update footer
                $(  api.column( 3 ).footer() ).html(
                    total.toLocaleString()
                );
            }
        });
    }else{
        table = $tabela_dados.DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            serverSide: false,
            responsive: true,
            autoWidth: true,
            JQueryUI: true,
            searching: true,
            order: [[1, "asc"]],
            ajax: {
                url: '../Adm/pages/arquivos/selecionar_dados.php',
                method: 'POST',
                data: function (data) {
                    data.mes = $("#C_mes").val();
                    data.empregador = $("#C_empregador").val();
                    data.tipo = $("#C_tipo").val();
                    data.divisao = divisao;
                    data.card1 = card1;
                    data.card2 = card2;
                    data.card3 = card3;
                    data.card4 = card4;
                    data.card5 = card5;
                    data.card6 = card6;
                },
                dataType: 'json'
            },
            columns: [
                {data: "associado"},
                {data: "nome"},
                {data: "nome_tipo"},
                {
                    data: "total",
                    render: $.fn.dataTable.render.number(',', '.', 2),
                    className: "text-right"
                },
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
            },
            createdRow: function (row, data, index){
                // Now that we have the data and not the HTML it is cleaner
      
                if (data["nome_tipo"] === "COMPRAS") {

                    $('td', row).css('background-color', '#a9f5b4');
                    total_compras += parseFloat(data["total"].replace(",", "."));

                }else if (data["nome_tipo"] === "FARMACIA") {

                    $('td', row).css('background-color', '#f59a9a');
                    total_farmacia += parseFloat(data["total"].replace(",", "."));

                }else if (data["nome_tipo"] === "UNIMED") {

                    $('td', row).css('background-color', '#f5eea0');
                    total_unimed += parseFloat(data["total"].replace(",", "."));

                }
                $('.somacompras').html(total_compras.toLocaleString());
                $('.somafarmacia').html((total_farmacia).toLocaleString());
                $('.somaunimed').html((total_unimed).toLocaleString());
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
                // Total geral
                total = api
                    .column( 3 )
                    .data()
                    .reduce( function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0 );
                // Update footer
                $(  api.column( 3 ).footer() ).html(
                    total.toLocaleString()
                );
            }
        });
    }
}