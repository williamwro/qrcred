//script_empregador.js
var usuario_global;
var divisao;
var tabela_empregador;
$(document).ready(function(){

    $('#operation').val("Add");
    divisao = sessionStorage.getItem("divisao");
    usuario_global = sessionStorage.getItem("usuario_global");
    $.getJSON( "pages/empregador/divisao.php", function( data ) {
        $.each(data, function (index, value) {
            $('#C_divisao').append('<option value="' + value.id_divisao + '">' + value.nome + '</option>');
        });
    });
    // econstroi uma datatabe no primeiro carregamento da tela
    tabela_empregador = $('#tabela_empregador').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": false,
        "bServerSide": false,
        "responsive": true,
        "autoWidth": true,
        "bJQueryUI": true,
        "bAutoWidth": false,
        "ajax": {
            "url": 'pages/empregador/datatable.php',
            "method": 'POST',
            "data": function (data) {
                data.divisao = divisao;
            },
            "dataType": 'json'
        },
        "order": [[ 0, "asc" ]],
        "columns": [
            { "data": "id" },
            { "data": "nome" },
            { "data": "responsavel" },
            { "data": "telefone" },
            { "data": "abreviacao" },
            { "data": "nome_divisao" },
            { "data": "cidade" },
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
});
$(document).on('click','.update_emp',function () {

    $("#C_codigo_empregador").prop( "disabled", true );
    //var cod_divisao = $(this).attr("id_divisao");
    var tdobj = $(this).closest('tr').find('td');
    var cod_empregador = tdobj[0].innerHTML;
    var nome = tdobj[1].innerHTML;
    var divisao = tdobj[5].innerHTML;
    $("#rotulo_associado").html("Alterando");
    debugger;
    $.ajax({
        url: "pages/empregador/exibe.php",
        method: "POST",
        data: {cod_empregador : cod_empregador,nome : nome,divisao : divisao},
        dataType: "json",
        success:function (data) {

            $.fn.modal.Constructor.prototype.enforceFocus = function() {};
            $("#ModalEditaEmpregador").modal("show");
            $("#C_codigo_empregador").val(data.id);
            $("#C_nome_empregador").val(data.nome);
            $("#C_nome_original").val(data.nome);
            $("#C_responsavel").val(data.responsavel);
            $("#C_telefone").val(data.telefone);
            $("#C_abreviacao").val(data.abreviacao);
            $("#C_divisao").val(data.divisao);
            $('#operation').val("Update");
        }
    })
});
$("#btnInserir").click(function(){
    $("#C_codigo_empregador").prop( "disabled", true );
    $("#frmFormularioEmpregador")[0].reset();
    $("#rotulo_associado").html("Cadastrando");
    $.fn.modal.Constructor.prototype.enforceFocus = function() {};
    $("#ModalEditaEmpregador").modal("show");
    $('#operation').val("Add");
    var d = new Date().toLocaleString("pt-BR", {timeZone: "America/Sao_Paulo"});
});
$("#btnSalvar").click(function(event){

   event.preventDefault();
   $("#C_codigo_empregador").prop( "disabled", false );
   $('#frmFormularioEmpregador').validator('validate');
   var divisao = $("#C_divisao").val();
   var campo_vazio = validar();
   if (campo_vazio === "validou") {

       if( $('#operation').val() === "Add") {

           $.ajax({
               url: "pages/empregador/verifica_repitido.php",
               method: "POST",
               data: $('#frmFormularioEmpregador').serialize(),
               success: function (data) {

                   if (data === "nao repitido") {

                       $.ajax({
                           url: "pages/empregador/salvar.php",
                           method: "POST",
                           data: $('#frmFormularioEmpregador').serialize(),
                           success: function (data) {
                               $("#frmFormularioEmpregador")[0].reset();
                               if (data === "atualizado") {

                                    Swal.fire({
                                        title: "Parabens!",
                                        text: "Empregador atualizado com sucesso !",
                                        icon: "success",
                                        showConfirmButton: false,
                                        timer: 1500
                                    });

                               } else if (data === "cadastrado") {

                                    Swal.fire({
                                        title: "Parabens!",
                                        text: "Empregador cadastrado com sucesso !",
                                        icon: "success",
                                        showConfirmButton: false,
                                        timer: 1500
                                    });

                               }
                               $("#frmFormularioEmpregador")[0].reset();
                               $("#ModalEditaEmpregador").modal('hide');
                               tabela_empregador.ajax.reload();
                               tabela_empregador.columns.adjust().draw();
                           }
                       });

                   } else if (data === "repitido") {
                       BootstrapDialog.show({
                           closable: false,
                           title: 'Atenção',
                           message: 'O empregador : '+$("#C_nome_empregador").val()+' já existe na divisão: '+$( "#C_divisao option:selected" ).text()+'.',
                           buttons: [{
                               cssClass: 'btn-warning',
                               label: 'Ok',
                               action: function (dialogItself) {
                                   dialogItself.close();
                                   $("#C_nome_empregador").focus();
                               }
                           }]
                       });
                   }
               }
           });
       }else{
           $.ajax({
               url: "pages/empregador/salvar.php",
               method: "POST",
               data: $('#frmFormularioEmpregador').serialize(),
               success: function (data) {
                   $("#frmFormularioEmpregador")[0].reset();
                   debugger;
                   if (data === "atualizado") {

                        Swal.fire({
                            title: "Parabens!",
                            text: "Empregador atualizado com sucesso !",
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1500
                        });

                   } else if (data === "cadastrado") {

                        Swal.fire({
                            title: "Parabens!",
                            text: "Empregador cadastrado com sucesso !",
                            icon: "success",
                        });

                   }
                   $("#frmFormularioEmpregador")[0].reset();
                   $("#ModalEditaEmpregador").modal('hide');
                   tabela_empregador.ajax.reload();
                   tabela_empregador.columns.adjust().draw();
               }
           });
       }
   }else {

       var nome_campo;
       switch (campo_vazio) {
           case 'C_nome_empregador':
               nome_campo = "Nome";
               break;
           case 'C_cidade':
               nome_campo = "C_cidade";
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
   tabela_empregador.columns.adjust().draw();
});
$('#tabela_empregador').on('click', 'tbody .btnexcluir', function () {

    var data_row = tabela_empregador.row($(this).closest('tr')).data();
    var cod_empregador = data_row.id;
    var nome = data_row.nome;
    var divisao = data_row.nome_divisao;
    $.ajax({
        url: "pages/empregador/valid_excluir.php",
        method: "POST",
        dataType: "json",
        data: {"cod_empregador": cod_empregador},
        success: function (data) {

            if (data.Resultado === "nao existe conta") {
                BootstrapDialog.confirm({
                    message: '<table style="width: 100%;"><tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">CODIGO:</th><th style="background-color: #dddddd;"><b>' + cod_empregador + '</b></th>' +
                        '<tr><th style="text-align: right;padding: 8px;">NOME:</th><th><b>' + nome + '</th>' +
                        '<tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">DIVISÃO:</th><th style="background-color: #dddddd;"><b>' + divisao + '</th>',
                    title: 'Confirma a exclusão do empregador ?',
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
                                url: "pages/empregador/excluir.php",
                                method: "POST",
                                dataType: "json",
                                data: {"cod_empregador": cod_empregador},
                                success: function (data) {

                                    if (data.Resultado === "excluido") {

                                        //tabela_empregador.row( $button.parents('tr') ).remove().draw();
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
                                                    //$("#C_Senha").focus();
                                                }
                                            }]
                                        });
                                    }else{
                                        alert("Não Excluiu");
                                        waitingDialog.hide();
                                    }
                                    tabela_empregador.ajax.reload();
                                }
                            });
                        } else {
                            //alert('No');
                        }
                    }
                });
            }else if (data.Resultado === "existe conta") {
                BootstrapDialog.show({
                    closable: false,
                    title: 'Atenção',
                    message: 'Não é possível exluir, existem lançamentos para este empregador!',
                    buttons: [{
                        cssClass: 'btn-warning',
                        label: 'Ok',
                        action: function(dialogItself){
                            dialogItself.close();
                            $("#C_nome_empregador").focus();
                        }
                    }]
                });
            }
        }
    });
});
function validar(){

    var nome       = $('#C_nome_empregador').val();
    var abreviacao = $('#C_abreviacao').val();
    var divisao    = $('#C_divisao').val();
    if (nome === ""){
        return $('#C_nome_empregador').attr('name');
    }else if (abreviacao === "") {
        return $('#C_abreviacao').attr('name');
    }else if (divisao === "") {
        return $('#C_divisao').attr('name');
    }else{
        return "validou";
    }
}