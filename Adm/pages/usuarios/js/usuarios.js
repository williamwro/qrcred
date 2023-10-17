var usuario_global;
var divisao;
var table_usuario;
var table_permissoes;
var cod_caserv;
var cod_usuario;
var cod_parentesco_inicio;
$(document).ready(function(){
    waitingDialog.show('Carregando, aguarde ...');
    $('#operation').val("Add");
    $('#operationdepe').val("Add");
    divisao = sessionStorage.getItem("divisao");
    $.getJSON( "pages/usuarios/situacao.php", function( data ) {
        $.each(data, function (index, value) {
            if(value.nome === "LIBERADO"){
                $('#C_situacao').append('<option selected value="' + value.id_situacao + '">' + value.nome + '</option>');
            }else{
                $('#C_situacao').append('<option value="' + value.id_situacao + '">' + value.nome + '</option>');
            }
        });
    });
    $.getJSON( "pages/usuarios/divisao.php", function( data ) {
        $.each(data, function (index, value) {
            $('#C_divisao').append('<option value="' + value.id_divisao + '">' + value.nome + '</option>');
        });
    });
    $('#C_status').append("<option value=1>LIBERADO</option>");
    $('#C_status').append("<option value=0>BLOQUEADO</option>");
    $('#tabela_usuario tfoot th').each( function () {
        var title = $(this).text();
        if(title !== ""){
            $(this).html( '<input type="text" class="small" placeholder="Busca '+title+'" />' );
        }
    } );
    usuario_global = sessionStorage.getItem("usuario_global");
    // econstroi uma datatabe no primeiro carregamento da tela
    table_usuario = $('#tabela_usuario').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": true,
        "bServerSide": false,
        "responsive": true,
        "autoWidth": true,
        "bJQueryUI": true,
        "bAutoWidth": false,
        "ajax": {
            "url": 'pages/usuarios/usuarios.php',
            "method": 'POST',
            "data":  { 'usuario_global': usuario_global, 'divisao': divisao },
            "dataType": 'json'
        },
        "deferRender": true,
        "order": [[ 2, "asc" ]],
        "columns": [
            { "data": "codigo" },
            { "data": "username" },
            { "data": "nome" },
            { "data": "lastname" },
            { "data": "email" },
            { "data": "nome_divisao" },
            { "data": "badges" },
            { "data": "botao" },
            { "data": "botaoexcluir" }
        ],
        "language": {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Portuguese-Brasil.json",
            "decimal": ",",
            "thousands": "."
        },
        "pagingType": "full_numbers"
    });
    waitingDialog.hide();
});
function tabledependentes() {
    var operation  =$('#operation').val();
    if ( $.fn.dataTable.isDataTable( '#tabela_permissoes' ) ) {
        table_permissoes.destroy();
        table_permissoes = $('#tabela_permissoes').DataTable({
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            "processing": false,
            "bServerSide": false,
            "responsive": true,
            "autoWidth": true,
            "bJQueryUI": true,
            "bAutoWidth": false,
            "paging":false,
            "searching":false,
            "fixedColumns": true,
            "ajax": {
                "url": 'pages/usuarios/usuarios_permissoes.php',
                "method": 'POST',
                "data": {'cod_usuario': cod_usuario, 'operation': operation},
                "dataType": 'json'
            },
            "columns": [
                {"data": "menu_item_id"},
                {"data": "menu_parent_id"},
                {"data": "url"},
                {"data": "description"},
                {"data": "status_usuario"},
                {"data": "badges"},
                {"data": "botao"}
            ],
            columnDefs: [
                { "targets": [ 1 ], "visible": false, "searchable": false },
                { "targets": [ 2 ], "visible": false, "searchable": false },
                { "targets": [ 4 ], "visible": false, "searchable": false }
            ],
            "language": {
                url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Portuguese-Brasil.json",
                "decimal": ",",
                "thousands": "."
            },
            "pagingType": "full_numbers"
        });
    }
    else {
        table_permissoes = $('#tabela_permissoes').DataTable({

            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            "processing": false,
            "bServerSide": false,
            "responsive": true,
            "autoWidth": true,
            "bJQueryUI": true,
            "bAutoWidth": false,
            "paging":false,
            "searching":false,
            "fixedColumns": true,
            "ajax": {
                "url": 'pages/usuarios/usuarios_permissoes.php',
                "method": 'POST',
                "data": {'cod_usuario': cod_usuario, 'operation': operation},
                "dataType": 'json'
            },
            "columns": [
                {"data": "menu_item_id"},
                {"data": "menu_parent_id"},
                {"data": "url"},
                {"data": "description"},
                {"data": "status_usuario"},
                {"data": "badges"},
                {"data": "botao"}
            ],
            columnDefs: [
                { "targets": [ 1 ], "visible": false, "searchable": false },
                { "targets": [ 2 ], "visible": false, "searchable": false },
                { "targets": [ 4 ], "visible": false, "searchable": false }
            ],
            "language": {
                url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Portuguese-Brasil.json",
                "decimal": ",",
                "thousands": "."
            },
            "pagingType": "full_numbers"
        });
    }
}
$(document).on('click','.update',function () {
    var linha      = $(this).closest('tr').find('td');
    cod_usuario     = linha[0].innerHTML;
    $("#rotulo_associado").html("Alterando");

    $.ajax({
        url: "pages/usuarios/usuario_exibe.php",
        method: "POST",
        data: {codigo_usuario : cod_usuario},
        dataType: "json",
        success:function (data) {

            $.fn.modal.Constructor.prototype.enforceFocus = function() {};

            $("#ModalEdita").modal("show");
            $('.nav-tabs a:first').tab('show');

            $("#C_codigo").val(data.codigo);
            $("#C_nome").val(data.nome);
            $("#C_sobrenome").val(data.lastname);
            $("#C_user").val(data.username);
            //$("#C_senha").val(data.senha);
            $("#C_Email").val(data.email);
            $("#C_situacao").val(data.situacao);
            $("#C_divisao").val(data.divisao);
            cod_caserv = data.codigo_isa;
            $('#operation').val("Update");
            $('#frmusuarios').validator('validate');

            tabledependentes();
        }
    });
});
$("#btnInserir").click(function(){
    $("#frmusuarios")[0].reset();
    $("#rotulo_associado").html("Cadastrando");
    $("#C_codigo").prop( "disabled", true );
    $.fn.modal.Constructor.prototype.enforceFocus = function() {};
    $('#myTab a[href="#dados"]').tab('show');
    $("#ModalEdita").modal("show");
    $('#operation').val("Add");
});
$("#btnSalvar").click(function(event){

    event.preventDefault();

    $('#frmusuarios').validator('validate');
    var campo_vazio = validar();
    $("#C_codigo").prop( "disabled", false );

    if (campo_vazio === "validou") {
        if( $('#operation').val() === "Add") {
            $.ajax({
                url: "pages/usuarios/usuarios_verifica_repitido.php",
                method: "POST",
                data: $('#frmusuarios').serialize(),
                success: function (data) {

                    if (data === "nao repitido") {

                        $.ajax({
                            url: "pages/usuarios/usuario_salvar.php",
                            method: "POST",
                            data: $('#frmusuarios').serialize(),
                            dataType: "json",
                            success: function (data) {

                                if (data.resultado === "atualizado") {
                                    Swal.fire({
                                        title: "Parabens!",
                                        text: "Salvo com sucesso !",
                                        icon: "success",
                                        showConfirmButton: false,
                                        timer: 1500
                                    });
                                } else if (data.resultado === "cadastrado") {
                                    Swal.fire({
                                        title: "Parabens!",
                                        text: "Cadastrado com sucesso !",
                                        icon: "success",
                                        showConfirmButton: false,
                                        timer: 1500
                                    });

                                    if(data.result_email === "Email enviado"){
                                        BootstrapDialog.show({
                                            closable: false,
                                            title: 'Atenção',
                                            message: 'Email enviado com sucesso, contendo link para o usuário definir a senha.',
                                            buttons: [{
                                                cssClass: 'btn-warning',
                                                label: 'Ok',
                                                action: function (dialogItself) {
                                                    dialogItself.close();
                                                }
                                            }]
                                        });
                                    }else if(data.result_email === "Erro") {
                                        BootstrapDialog.show({
                                            closable: false,
                                            title: 'Atenção',
                                            message: 'Email ou senha incorretos.',
                                            buttons: [{
                                                cssClass: 'btn-warning',
                                                label: 'Ok',
                                                action: function (dialogItself) {
                                                    dialogItself.close();
                                                }
                                            }]
                                        });
                                    }else if(data.result_email === "Erroenvio") {
                                        BootstrapDialog.show({
                                            closable: false,
                                            title: 'Atenção',
                                            message: 'Mensagem não pode ser enviada. Mailer Error: '.data.result_email,
                                            buttons: [{
                                                cssClass: 'btn-warning',
                                                label: 'Ok',
                                                action: function (dialogItself) {
                                                    dialogItself.close();
                                                }
                                            }]
                                        });
                                    }
                                }
                                $("#frmusuarios")[0].reset();
                                $("#ModalEdita").modal('hide');
                                table_usuario.ajax.reload();
                            }
                        });
                    } else if (data === "repitido") {
                        BootstrapDialog.show({
                            closable: false,
                            title: 'Atenção',
                            message: 'O nome do User ou E-mail : '+$("#C_user").val()+' já existe. Informe outro',
                            buttons: [{
                                cssClass: 'btn-warning',
                                label: 'Ok',
                                action: function (dialogItself) {
                                    dialogItself.close();
                                    $("#C_user").focus();
                                }
                            }]
                        });
                    }
                }
            });
        }else{
            $.ajax({
                url: "pages/usuarios/usuario_salvar.php",
                method: "POST",
                data: $('#frmusuarios').serialize(),
                dataType: "json",
                success: function (data) {
                    $("#frmusuarios")[0].reset();
                    if (data.resultado === "atualizado") {
                        Swal.fire({
                            title: "Parabens!",
                            text: "Salvo com sucesso !",
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else if (data.resultado === "cadastrado") {
                        Swal.fire({
                            title: "Parabens!",
                            text: "Cadastrado com sucesso !",
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                    $("#frmusuarios")[0].reset();
                    $("#ModalEdita").modal('hide');
                    table_usuario.ajax.reload();
                }
            });
        }
    }else {
        var nome_campo;
        switch (campo_vazio) {
            case 'C_nome':
                nome_campo = "Nome";
                break;
            case 'C_sobrenome':
                nome_campo = "Sobrenome";
                break;
            case 'C_user':
                nome_campo = "User";
                break;
            case 'C_Email':
                nome_campo = "Email";
                break;
        }
        BootstrapDialog.show({
            closable: false,
            title: 'Atenção',
            message: 'O campo ' + nome_campo + ' é obrigatório !!!',
            buttons: [{
                cssClass: 'btn-warning',
                label: 'Ok',
                action: function (dialogItself) {
                    dialogItself.close();
                    $("#" + campo_vazio).focus();
                }
            }]
        });
    }
    table_usuario.columns.adjust().draw();
});
$('#tabela_usuario').on('click', 'tbody .btnexcluir', function () {

    var data_row = table_usuario.row($(this).closest('tr')).data();
    var cod_usuario = data_row.codigo;
    var nome_usuario = data_row.nome;
    $.ajax({
        url: "pages/usuarios/usuarios_valid_excluir.php",
        method: "POST",
        dataType: "json",
        data: {"cod_usuario": cod_usuario},
        success: function (data) {
            
            if (data.Resultado === "nao existe conta") {
                BootstrapDialog.confirm({
                    message: '<table style="width: 100%;"><tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">CODIGO:</th><th style="background-color: #dddddd;"><b>' + cod_usuario + '</b></th>' +
                        '<tr><th style="text-align: right;padding: 8px;">NOME:</th><th><b>' + nome_usuario + '</th>',
                    title: 'Confirma a exclusão do usuário ?',
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
                                url: "pages/usuarios/usuarios_excluir.php",
                                method: "POST",
                                dataType: "json",
                                data: {"cod_usuario": cod_usuario},
                                success: function (data) {

                                    if (data.Resultado === "excluido") {

                                        table_usuario.row( $button.parents('tr') ).remove().draw();
                                        //alert("Excluido com sucesso");
                                        waitingDialog.hide();
                                        BootstrapDialog.show({
                                            closable: false,
                                            title: 'Atenção',
                                            message: 'Excluído com Sucesso!!!',
                                            buttons: [{
                                                cssClass: 'btn-warning',
                                                label: 'Ok',
                                                action: function (dialogItself) {
                                                    dialogItself.close();
                                                    table_usuario.ajax.reload();
                                                }
                                            }]
                                        });
                                    }else{
                                        alert("Não Excluiu");
                                        waitingDialog.hide();
                                    }
                                }
                            });
                            waitingDialog.hide();
                            table_usuario.ajax.reload();
                        } else {
                            //alert('No');
                        }
                    }
                });
            }else if (data.Resultado === "existe conta") {
                BootstrapDialog.show({
                    closable: false,
                    title: 'Atenção',
                    message: 'Não é possível exluir, existem lançamentos vinculados!',
                    buttons: [{
                        cssClass: 'btn-warning',
                        label: 'Ok',
                        action: function(dialogItself){
                            dialogItself.close();
                        }
                    }]
                });
            }
            table_usuario.ajax.reload();
        }
    });
});
// Array to track the ids of the details displayed rows
var detailRows = [];
$(document).on('click','.update2',function () {
    var linha    = $(this).closest('tr').find('td');
    codigo_menu  = linha[0].innerHTML;
    menu         = linha[1].innerText;
    status_user  = linha[2].innerText;
    $("#C_codigo_menu").prop( "disabled", true );
    $('#operationdep').val("Update");
    //$("#rotulo_associado").html("Alterando");
    $.ajax({
        url: "pages/usuarios/permissao_exibe.php",
        method: "POST",
        data: {"codigo_menu" : codigo_menu, 'status': status_user, 'codigo_ususario': cod_usuario, 'menu': menu},
        dataType: "json",
        success:function (data) {
            $.fn.modal.Constructor.prototype.enforceFocus = function() {};

            $("#ModalPermissao").modal("show");
            $("#C_codigo_menu").val(data.menu_item_id);
            $("#C_menu").val(data.menu_item_name);
            if(data.status_usuario === false){
                $("#C_status").val("0").change();
            }else{
                $("#C_status").val("1").change();
            }
            $("#C_cod_usuario_hiden").val(data.codigo_usuario);
        }
    });
});
$("#btnSalvarDep").click(function(event){
    event.preventDefault();
    var codigo_menu = $("#C_codigo_menu").val();
    var status = $("#C_status").val();
    $.ajax({
        url: "pages/usuarios/permissao_salvar.php",
        method: "POST",
        data:  {"codigo_menu": codigo_menu,"status": status,"codigo_usuario": cod_usuario},
        success: function (data) {
            BootstrapDialog.show({
                closable: false,
                title: 'Atenção',
                message: 'Salvo com sucesso.',
                buttons: [{
                    cssClass: 'btn-success',
                    label: 'Ok',
                    action: function (dialogItself) {
                        dialogItself.close();
                    }
                }]
            });
        }
    });
    table_permissoes.ajax.reload();
});
function moedaParaNumero(valor)
{
    return isNaN(valor) === false ? parseFloat(valor) :   parseFloat(valor.replace("R$","").replace(".","").replace(",","."));
}
function numeroParaMoeda(n, c, d, t)
{
    c = isNaN(c = Math.abs(c)) ? 2 : c, d = d === undefined ? "," : d, t = t === undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}
function validar(){
    var nome      = $('#C_nome').val();
    var sobrenome = $('#C_sobrenome').val();
    var user      = $('#C_user').val();
    var email     = $('#C_Email').val();
    if (nome === ""){
        return $('#C_nome').attr('name');
    }else if (sobrenome === "") {
        return $('#C_sobrenome').attr('name');
    }else if (user === "") {
        return $('#C_user').attr('name');
    }else if (email === "") {
        return $('#C_Email').attr('name');
    }else{
        return "validou";
    }
}
