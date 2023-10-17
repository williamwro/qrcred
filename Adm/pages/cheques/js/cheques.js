var divisao;
var collapsedGroups = {};
var table;
var editor;
var cod_convenio;
var valor_residuo;
var valor_porcentagem;
var acrescimo;
var valor = 0;
var val_alicota = 0;
var liquido = 0;
var tipo_rel;
var datainicial = $('#datainicial');
var datafinal   =  $('#datafinal');
var controle;
var usuarioglobal;
var usuario_cod;
var marcar;$(document).ready(function(){
    var mescorrente = "";
    divisao = sessionStorage.getItem("divisao");
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");
    datainicial.mask('99/99/9999');
    datafinal.mask('99/99/9999');
    $('#C_empregador').html("<option> Carregando ... </option>");
    $.getJSON( "../Adm/pages/cheques/meses_conta.php", function( data ) {
        $.each(data, function (index, value1) {
            $('#C_mes').append('<option value="' + value1.abreviacao + '">' + value1.abreviacao + '</option>');
        });
    });
    $.getJSON( "../Adm/pages/cheques/mes_controle.php", function( data ) {
        $.each(data, function (index, value) {

            $('#C_mes2').append('<option value="' + value.mes + '">' + value.mes + '</option>');
        });
    });
    $('#C_categoria').attr({"title":"Escollha a categoria"});
    $('#C_categoria').append('<option value="">Todas as categorias</option>');
    $.getJSON( "../Adm/pages/cobranca/categoria_recibo.php", function( data ) {
        $.each(data, function (index, value) {
            $('#C_categoria').append('<option value="' + value.id_categoria_recibo + '">' + remAcentos(value.nome) + '</option>');
        });
    });
    filtra_associado();

});
$('#tabela_cheques').on('change', 'tbody input.editor-active-pg', function () {


    var idlinha = $(this).closest('tr').find('td')[1].innerHTML;
    controle  = $(this).prop('checked');
    $.ajax({
        url: "pages/cheques/update_linha.php",
        method: "POST",
        dataType: "json",
        async:false,
        data: {"id": idlinha,"controle": controle},
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
            //$("#ModalAtualizaResiduo").hide();
        }
    });

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
/*$('#tabela_cheques').on('change', 'tbody input.editor-active-pg', function () {
   /!*
    var controle  = $(this).prop('checked');//mostra se checked é true or false
    var id        = $(this).closest('tr').find('td').eq(2).text();
    var valor_cob = parseFloat($(this).closest('tr').find('td').eq(10).text().replace(",", "."));

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
    });*!/
});*/
$('#tabela_cheques').on('change', 'tbody input.editor-active', function () {

    var controle = $(this).prop('checked');//mostra se checked é true or false
    var id       = $(this).closest('tr').find('td').eq(2).text();
    $.ajax({
        url: "pages/cheques/update_linha.php",
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

    $.ajax({
        url: "pages/cheques/grava_taxas.php",
        method: "POST",
        dataType: "json",
        data: {"mes": $('#C_mes').val()},
        async: false,
        success: function (data) {
        }
    });
    filtra_associado();
});
$('#tabela_cheques tbody').on('click', 'tr.dtrg-group', function () {

    var name = $(this).data('name');
    collapsedGroups[name] = !collapsedGroups[name];
    table.draw(false);
});
$('#excluir').click(function () {

    var mes = $("#C_mes").val();//mostra se checked é true or false
    $('#excluir').attr("disabled", true);
    $('#excluir').html('<i class="fa fa-circle-o-notch fa-spin" id="spinxy"></i> loading...');
    BootstrapDialog.confirm({
        message: '<table style="width: 100%;">' +
            '<tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">MÊS:</th><th style="background-color: #dddddd;"><b>' + mes + '</b></th>',
        title: 'Confirma a exclusão do mes ?',
        type: BootstrapDialog.TYPE_PRIMARY,
        closable: true,
        draggable: true,
        btnCancelLabel: 'Não',
        btnOKLabel: 'Sim',
        btnOKClass: 'btn btn-success',
        btnCancelClass: 'btn btn-warning',
        callback: function (result) {
            if (result) {
                waitingDialog.show('Excluindo, aguarde ...');
                $.ajax({
                    url: "pages/cheques/excluir_cheques.php",
                    method: "POST",
                    dataType: "json",
                    data: {"mes": mes},
                    async: false,
                    success: function (data) {

                        waitingDialog.hide();
                        $("#ModalAtualizaResiduo").hide();
                        BootstrapDialog.show({
                            closable: false,
                            title: 'Atenção',
                            message: 'Excluido com Sucesso!!!',
                            buttons: [{
                                cssClass: 'btn-primary',
                                label: 'Ok',
                                action: function (dialogItself) {
                                    dialogItself.close();
                                    //$("#C_Senha").focus();
                                }
                            }]
                        });
                        table.ajax.reload();
                        filtra_associado();
                    }
                });
            } else {
                //alert('No');
            }
        }
    });
    $('#excluir').attr("disabled", false);
    $('#spinxy').remove();
    $('#excluir').html("Excluir");
});
$('#btnExecutar').click(function () {

    var mes = $("#C_mes2").val();//mostra se checked é true or false
    $('#btnExecutar').attr("disabled", true);
    $('#btnExecutar').html('<i class="fa fa-circle-o-notch fa-spin" id="spinx"></i> loading...');
    $.ajax({
        url: "pages/cheques/importar_cheques.php",
        method: "POST",
        dataType: "json",
        data: {"mes": mes,"divisao": divisao},
        async: false,
        success: function (data) {

            if(data.resultado === "importado") {

                table.ajax.reload();
                $('#C_mes').empty();
                $.getJSON( "../Adm/pages/cheques/meses_conta.php",function( data ) {
                    $.each(data, function (index, value) {
                        $('#C_mes').append('<option value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                    });
                });
                $("#ModalAtualizaResiduo").hide();
                BootstrapDialog.show({
                    closable: false,
                    title: 'Atenção',
                    message: 'Importado com Sucesso !!!',
                    buttons: [{
                        cssClass: 'btn-primary',
                        label: 'Ok',
                        action: function (dialogItself) {
                            dialogItself.close();
                            //$("#C_Senha").focus();
                        }
                    }]
                });
            }else{

                $("#ModalAtualizaResiduo").hide();
                BootstrapDialog.show({
                    closable: false,
                    title: 'Atenção',
                    message: 'Mês já esta importado !!!',
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
            $('#btnExecutar').attr("disabled", false);
            $('#spinx').remove();
            $('#btnExecutar').html("Executar");
        }
    });
});
$('#btnMarcarTodos').click(function () {

    //if($('#C_categoria').val() != "") {

        if ($('#btnMarcarTodos').val() === "Marcar todos") {
            marcar = true;
            $('#btnMarcarTodos').val("Desmarcar todos");
        } else {
            marcar = false;
            $('#btnMarcarTodos').val("Marcar todos");
        }
        var categoria = $("#C_categoria").val();
        var mes = $("#C_mes").val();//mostra se checked é true or false
        $.ajax({
            url: "pages/cheques/marcar_todos.php",
            method: "POST",
            dataType: "json",
            data: {"mes": mes, "marcar": marcar, "categoria": categoria},
            async: false,
            success: function (data) {

                if (data.resultado === "marcado") {
                    table.ajax.reload();
                    $("#ModalAtualizaResiduo").hide();
                    BootstrapDialog.show({
                        closable: false,
                        title: 'Atenção',
                        message: 'Marcados com Sucesso!!!',
                        buttons: [{
                            cssClass: 'btn-primary',
                            label: 'Ok',
                            action: function (dialogItself) {
                                dialogItself.close();
                                //$("#C_Senha").focus();
                            }
                        }]
                    });
                } else {
                    table.ajax.reload();
                    $("#ModalAtualizaResiduo").hide();
                    BootstrapDialog.show({
                        closable: false,
                        title: 'Atenção',
                        message: 'Desmarcados com Sucesso!!!',
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
            }
        });
   /* }else{
        BootstrapDialog.show({
            closable: false,
            title: 'Atenção',
            message: 'Não é possivel marcar todas as categorias, escolha uma.',
            buttons: [{
                cssClass: 'btn-danger',
                label: 'Ok',
                action: function (dialogItself) {
                    dialogItself.close();
                    //$("#C_Senha").focus();
                }
            }]
        });
    }*/
});
$('#printtx').click(function () {
    $.ajax({
        url: "pages/cheques/grava_taxas.php",
        method: "POST",
        dataType: "json",
        data: {"mes": mes},
        async: false,
        success: function (data) {

        }
    });
    //var Shell = new ActiveXObject("WScript.Shell");
    //var cheques = "\"C:\\Users\\Administrador.WIN-BNQ2TVGCVKC\\Documents\\cheques\\cheques_novo.exe\" ";
    //C:\\Users\\Administrador.WIN-BNQ2TVGCVKC\\Documents\\cheques\\cheques_novo.exe
    //Shell.Run(cheques);

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
    filtra_associado();
});
$('#input_check_abertos').change(function(){
    filtra_associado();
});
$('#input_check_pagos').change(function(){
    filtra_associado();
});
function filtra_associado(){
    // Activate an inline edit on click of a table cell
    
    $("#tabela_cheques").show();
    // constroi uma datatabe no primeiro carregamento da tela
    valor = 0;
    val_alicota = 0;
    liquido = 0;
    if (usuario_cod == 1) {
        table = $('#tabela_cheques').DataTable({
            "destroy": true,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            "processing": false,
            "serverSide": false,
            "responsive": true,
            "autoWidth": true,
            "paging": false,
            "ajax": {
                "url": '../Adm/pages/cheques/cheques.php',
                "method": 'POST',
                "data": function (data) {
                    data.categoria = $("#C_categoria").val();
                    data.mes = $("#C_mes").val();
                },
                "dataType": 'json'
            },
            "columns": [
                {
                    "data": "prtch",
                    render: function (data, type, row) {
                        if (type === 'display') {
                            return '<input type="checkbox" ' + ((data === '1') ? 'checked' : '') + ' id=' + row.id_new + ' class="editor-active-pg" />';
                        }
                        return data;
                    },
                    width: '5px',
                    className: "dt-body-center"
                },
                {
                    "data": "id_new"

                },
                {
                    "data": "razaosocial"

                },
                {
                    "data": "total",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right"
                },
                {
                    "data": "prolabore",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right"
                },
                {
                    "data": "valor_prolabore",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right"
                },
                {
                    "data": "total_liquido",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right"
                }
            ],
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
                valor = api
                    .column(3)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Total_prolabore de todas as paginas
                val_alicota = api
                    .column(5)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Txbanco de todas as paginas
                liquido = api
                    .column(6)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Update footer
                $(api.column(3).footer()).html(
                    valor.toLocaleString()
                );
                $(api.column(5).footer()).html(
                    val_alicota.toLocaleString()
                );
                $(api.column(6).footer()).html(
                    liquido.toLocaleString()
                );
            }
        });
    }else{
        table = $('#tabela_cheques').DataTable({
            "destroy": true,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            "processing": false,
            "serverSide": false,
            "responsive": true,
            "autoWidth": true,
            "paging": false,
            "ajax": {
                "url": '../Adm/pages/cheques/cheques.php',
                "method": 'POST',
                "data": function (data) {
                    data.categoria = $("#C_categoria").val();
                    data.mes = $("#C_mes").val();
                },
                "dataType": 'json'
            },
            "columnDefs": [
                {"targets": [3, 4, 5], "visible": false, "searchable": false}
            ],
            "columns": [
                {
                    "data": "prtch",
                    render: function (data, type, row) {
                        if (type === 'display') {
                            return '<input type="checkbox" ' + ((data === '1') ? 'checked' : '') + ' id=' + row.id_new + ' class="editor-active-pg" />';
                        }
                        return data;
                    },
                    width: '5px',
                    className: "dt-body-center"
                },
                {
                    "data": "id_new"

                },
                {
                    "data": "razaosocial"

                },
                {
                    "data": "total",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right"
                },
                {
                    "data": "prolabore",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right"
                },
                {
                    "data": "valor_prolabore",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right"
                },
                {
                    "data": "total_liquido",
                    render: $.fn.dataTable.render.number('.', ',', 2),
                    className: "text-right"
                }
            ],
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
                valor = api
                    .column(3)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Total_prolabore de todas as paginas
                val_alicota = api
                    .column(5)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Txbanco de todas as paginas
                liquido = api
                    .column(6)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                // Update footer
                $(api.column(3).footer()).html(
                    valor.toLocaleString()
                );
                $(api.column(5).footer()).html(
                    val_alicota.toLocaleString()
                );
                $(api.column(6).footer()).html(
                    liquido.toLocaleString()
                );
            }
        });
    }

}
