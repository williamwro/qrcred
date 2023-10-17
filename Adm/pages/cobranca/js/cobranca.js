var divisao;
var collapsedGroups = {};
var table;
var editor;
var cod_convenio;
var valor_residuo;
var valor_porcentagem;
var acrescimo;
var sum = 0;
var sumnr = 0;
var sumnr2 = 0;
var tipo_rel;
var datainicial = $('#datainicial');
var datafinal   =  $('#datafinal');
$(document).ready(function(){
    var mescorrente = "";
    divisao = sessionStorage.getItem("divisao");
    datainicial.mask('99/99/9999');
    datafinal.mask('99/99/9999');
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
    $('#C_categoria').attr({"title":"Escollha a categoria"});
    $('#C_categoria').append('<option value="">Todas a categoria</option>');
    $.getJSON( "../Adm/pages/cobranca/categoria_recibo.php", function( data ) {
        $.each(data, function (index, value) {
            $('#C_categoria').append('<option value="' + value.id_categoria_recibo + '">' + remAcentos(value.nome) + '</option>');
        });
    });
    filtra_associado("abertos");
    $(function () {
        datainicial.datepicker({
            dateFormat: 'dd/mm/yy',
            dayNames: ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo'],
            dayNamesMin: ['D','S','T','Q','Q','S','S','D'],
            dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb','Dom'],
            monthNames: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
            monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez']
        });
        datafinal.datepicker({
            dateFormat: 'dd/mm/yy',
            dayNames: ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo'],
            dayNamesMin: ['D','S','T','Q','Q','S','S','D'],
            dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb','Dom'],
            monthNames: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
            monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez']
        });
    });
    datainicial.on("dp.change", function (e) {
        datainicial.val(data("datainicial").minDate(e.date));
    });
});
$('#tabela_producao').on( 'click', 'tbody td:not(span:first-child)', function () {

    var cellContent = table
        .cell( this )
        .data();
    if(cellContent === undefined){
        var valorcobrado = $(this).closest('tr').find('td')[5].innerHTML;
        clip(valorcobrado);
        Swal.fire({
            position: 'top-end',
            icon: 'success',
            title: valorcobrado+' copiado com sucesso!',
            showConfirmButton: false,
            timer: 1500
        });
    } else if(cellContent === "") {
        var rowIdx = table
            .cell( this )
            .index().column;

        var data_row = table.row($(this).closest('tr')).data();
        if (rowIdx === 8) {
            cod_convenio = data_row.id;
            valor_residuo = data_row.residuo;
            valor_porcentagem = data_row.valor_prolabore;
            acrescimo = data_row.acrescimo;
            $('#inputValor').val(valor_residuo);
            $("#ModalAtualizaResiduo").modal({show: true});

        }else if (rowIdx === 10) { //copia valor a cobrar

            clip(parseFloat(cellContent).toFixed(2).replace('.',','));
            cellContent = parseFloat(cellContent).toFixed(2);
            $.notify({
                    message: 'Valor '+cellContent+' copiado !'
                }, {
                    type: 'success'
                }, {
                    position: 'center'
                }
            );

        }else if (rowIdx === 4) { //copia e-mail

            clip(cellContent);
            Swal.fire({
                position: 'top-end',
                icon: 'success',
                title: 'E-mail '+cellContent+' copiado com sucesso!',
                showConfirmButton: false,
                timer: 1500
            });
        }
    } else {
        var rowIdx = table
            .cell( this )
            .index().column;

        var data_row = table.row($(this).closest('tr')).data();
        if (rowIdx === 8) {
            cod_convenio = data_row.id;
            valor_residuo = data_row.residuo;
            valor_porcentagem = data_row.valor_prolabore;
            acrescimo = data_row.acrescimo;
            $('#inputValor').val(valor_residuo);
            $("#ModalAtualizaResiduo").modal({show: true});

        }else if (rowIdx === 10) { //copia valor a cobrar

            clip(parseFloat(cellContent).toFixed(2).replace('.',','));
            cellContent = parseFloat(cellContent).toFixed(2);
            $.notify({
                    message: 'Valor '+cellContent+' copiado !'
                }, {
                    type: 'success'
                }, {
                    position: 'center'
                }
            );

        }else if (rowIdx === 4) { //copia e-mail

            clip(cellContent);
            Swal.fire({
                position: 'top-end',
                icon: 'success',
                title: 'E-mail '+cellContent+' copiado com sucesso!',
                showConfirmButton: false,
                timer: 1500
            });
        }
    }
});
function clip(text) {
    var copyElement = document.createElement('input');
    copyElement.setAttribute('type', 'text');
    copyElement.setAttribute('value', text);
    copyElement = document.body.appendChild(copyElement);
    copyElement.select();
    document.execCommand('copy');
    copyElement.remove();
}
$('#btnAtualizar').click(function () {
    valor_residuo = $('#inputValor').val();
    valor_residuo = valor_residuo.replace(",", ".");
    valor_residuo = parseFloat(valor_residuo);
    $.ajax({
        url: "pages/cobranca/update_residuo.php",
        method: "POST",
        dataType: "json",
        async:false,
        data: {"id": cod_convenio,"valor": valor_residuo,"valor_porcentagem":valor_porcentagem,"acrescimo":acrescimo},
        success: function (data) {

            if(data.resultado === "atualizado") {
                table.ajax.reload();
            }
            $("#ModalAtualizaResiduo").hide();
        }
    });
});
$('#tabela_producao').on('change', 'tbody input.editor-active-pg', function () {

    var controle  = $(this).prop('checked');//mostra se checked é true or false
    var id        = $(this).closest('tr').find('td').eq(2).text();
    var valor_cob = parseFloat($(this).closest('tr').find('td').eq(10).text().replace(",", "."));
    if(controle === true) {
        sum += valor_cob;
        if (valor_cob > 19.99){
            sumnr -= parseFloat(valor_cob);
        }else{
            sumnr2 -= parseFloat(valor_cob);
        }
    } else {
        sum -= valor_cob;
        if (valor_cob > 19.99){
            sumnr += parseFloat(valor_cob);
        }else{
            sumnr2 += parseFloat(valor_cob);
        }
    }
    $('.soma').html(sum.toFixed(2).toLocaleString());
    $('.somanaorecebidos').html((-1*sumnr).toLocaleString());
    $('.somanaorecebidos2').html((-1*sumnr2).toLocaleString());
    //$(this).hasClass('selected')
    //$(this).toggleClass('selected');

    $.ajax({
        url: "pages/cobranca/update_pg.php",
        method: "POST",
        dataType: "json",
        async:false,
        data: {"id": id,"controle": controle},
        success: function (data) {

            if(data.resultado === "atualizado") {
                //table.ajax.reload();
                Swal.fire({
                    position: 'top-end',
                    icon: 'success',
                    title: 'Atualizado !',
                    showConfirmButton: false,
                    timer: 1500
                });
            }else if(data.resultado === "nao atualizado") {
                Swal.fire({
                    title: "Atenção!",
                    text: "Não atualizou.",
                    icon: "error",
                });
            }
            $("#ModalAtualizaResiduo").hide();
        }
    });
});
$('#tabela_producao').on('change', 'tbody input.editor-active', function () {

    var controle = $(this).prop('checked');//mostra se checked é true or false
    var id       = $(this).closest('tr').find('td').eq(2).text();
    $.ajax({
        url: "pages/cobranca/update_envio.php",
        method: "POST",
        dataType: "json",
        async:false,
        data: {"id": id,"controle": controle},
        success: function (data) {

            if(data.resultado === "atualizado") {
                table.ajax.reload();
            }
            $("#ModalAtualizaResiduo").hide();
        }
    });
});
$('#btnExibir').click(function () {
    filtra_associado("todos");
});
$('#tabela_producao tbody').on('click', 'tr.dtrg-group', function () {

    var name = $(this).data('name');
    collapsedGroups[name] = !collapsedGroups[name];
    table.draw(false);
});
$('#exportar').click(function () {
    $('#tabela_producao').table2excel({
        exclude: ".noExl",
        name:"cobranca",
        filename:"cobranca"+Date(),//do not include extension
        fileext:".xlsx",
        exclude_img:true,
        exclude_links:true,
        exclude_inputs:true
    });
});
$('#btnImportar').click(function () {

    var mes = $("#C_mes").val();//mostra se checked é true or false
    $.ajax({
        url: "pages/cobranca/importar_cobranca.php",
        method: "POST",
        dataType: "json",
        data: {"mes": mes,"divisao": divisao},
        success: function (data) {

            if(data.resultado === "atualizado") {
                table.ajax.reload();
            }
            $("#ModalAtualizaResiduo").hide();
            BootstrapDialog.show({
                closable: false,
                title: 'Atenção',
                message: 'Importado com Sucesso!!!',
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
$('#input_check_todos').change(function(){
    debugger
    cod_situacao = $('#input_check_todos').val();
    filtra_associado(cod_situacao);
});
$('#input_check_abertos').change(function(){
    cod_situacao = $('#input_check_abertos').val();
    filtra_associado(cod_situacao);
});
$('#input_check_pagos').change(function(){
    cod_situacao = $('#input_check_pagos').val();
    filtra_associado(cod_situacao);
});
function filtra_associado(tipo){
    // Activate an inline edit on click of a table cell

    $("#tabela_producao").show();
    // constroi uma datatabe no primeiro carregamento da tela
    sum = 0;
    sumnr = 0;
    sumnr2 = 0;
    table = $('#tabela_producao').DataTable({
        "destroy": true,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": false,
        "serverSide": false,
        "responsive": true,
        "autoWidth": true,
        "paging": false,
        "ajax": {
            "url": '../Adm/pages/cobranca/cobranca.php',
            "method": 'POST',
            "data": function (data) {
                data.tipo = tipo;
                data.datainicial = $("#datainicial").val();
                data.datafinal   = $("#datafinal").val();
            },
            "dataType": 'json'
        },
        "order": [[3, "asc"]],
        "columns": [
            {
                "data": "pago",
                render: function (data, type, row) {
                    if (type === 'display') {
                        return '<input type="checkbox" ' + ((data === '1') ? 'checked' : '') + ' id=' + row.id + ' class="editor-active-pg" />';
                    }
                    return data;
                },
                width: '5px',
                className: "dt-body-center"
            },
            {
                "data": "mes",
                "width": '30px'
            },
            {
                "data": "id",
                "width": '15px'
            },
            {
                "data": "razaosocial",
                "visible": false
            },
            {"data": "email"},
            {
                "data": "total",

                className: "text-right"
            },
            {
                "data": "prolabore1",

                className: "text-right"
            },
            {
                "data": "prolabore2",

                className: "text-right"
            },
            {
                "data": "valor_prolabore",

                className: "text-right"
            },
            {
                "data": "residuo",

                className: "text-right"
            },
            {
                "data": "acrescimo",

                className: "text-right"
            },
            {
                "data": "total_cobranca",

                className: "text-right"
            },
            {
                "data": "enviado",
                render: function (data, type, row) {
                    if (type === 'display') {
                        return '<input type="checkbox" ' + ((data === '1') ? 'checked' : '') + ' id=' + row.id + ' class="editor-active" />';
                    }
                    return data;
                },
                width: '5px',
                className: "dt-body-center"
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
        createdRow: function (row, data, index) {
            // Now that we have the data and not the HTML it is cleaner
            var sumnrx = 0;
            if (data["pago"] === "1") {
                $('td', row).css('background-color', '#c0f5ac');
                sum += parseFloat(data["total_cobranca"].replace(",", "."));
                $('.soma').html(sum.toFixed(2).toLocaleString());
            } else {
                sumnrx = parseFloat(data["total_cobranca"].replace(",", "."));
                if (sumnrx > 19.99) {
                    sumnr -= parseFloat(data["total_cobranca"].replace(",", "."));
                } else {
                    sumnr2 -= parseFloat(data["total_cobranca"].replace(",", "."));
                }
                $('.soma').html();
            }
            $('.soma').html(sum.toFixed(2).toLocaleString());
            $('.somanaorecebidos').html((-1 * sumnr).toFixed(2).toLocaleString());
            $('.somanaorecebidos2').html((-1 * sumnr2).toFixed(2).toLocaleString());
        },
        'rowCallback': function (row, data, index) {
            $(row).find('td:eq(3)').css('color', 'red');
            $(row).find('td:eq(3)').css('background-color', '#fffbcf');
        },
        select: {
            style: 'multi'
        },
        "pagingType": "full_numbers",
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
                .column(5)
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
            // Txbanco de todas as paginas
            total_residuo = api
                .column(9)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
            // Acrescimo de todas as paginas
            total_acrescimo = api
                .column(10)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
            // Acrescimo de todas as paginas
            total_cobranca = api
                .column(11)
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
            $(api.column(5).footer()).html(
                total.toLocaleString()
            );
            $(api.column(8).footer()).html(
                total_prolabore.toLocaleString()
            );
            $(api.column(9).footer()).html(
                total_residuo.toLocaleString()
            );
            $(api.column(10).footer()).html(
                total_acrescimo.toLocaleString()
            );
            $(api.column(11).footer()).html(
                total_cobranca.toLocaleString()
            );
        },
        rowGroup: {
            dataSrc: function (row) {
                return row.razaosocial;
            },
            endRender: function (rows, group) {
               
                var totalcobranca = rows
                    .data()
                    .pluck('total_cobranca')
                    .reduce(function (a, b) {
                        return a + b * 1;
                    }, 0);
                totalcobranca = $.fn.dataTable.render.number('.', ',', 2).display(totalcobranca);

                var valorprolabore = rows
                    .data()
                    .pluck('valor_prolabore')
                    .reduce(function (a, b) {
                        return a + b * 1;
                    }, 0);
                valorprolabore = $.fn.dataTable.render.number('.', ',', 2).display(valorprolabore);

                return $('<tr/>')
                    .append('<td colspan="6"></td>')
                    .append('<td style="text-align: right;">' + valorprolabore + '</td>')
                    .append('<td/>')
                    .append('<td/>')
                    .append('<td/>')
                    .append('<td style="text-align: right;" name="totalcobranca" id="totalcobranca">' + totalcobranca + '</td>')
                    .append('<td/>');
            }
        }
    });
}