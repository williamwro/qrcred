//script_divisao.js
var usuario_global;
var divisao;
var tabela_divisao;
$(document).ready(function(){

    $('#operation').val("Add");
    divisao = sessionStorage.getItem("divisao");
    usuario_global = sessionStorage.getItem("usuario_global");

    // econstroi uma datatabe no primeiro carregamento da tela
    tabela_divisao = $('#tabela_divisao').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": false,
        "bServerSide": false,
        "responsive": true,
        "autoWidth": true,
        "bJQueryUI": true,
        "bAutoWidth": false,
        "ajax": {
            "url": 'pages/divisao/divisao_datatable.php',
            "method": 'POST',
            "data":  '',
            "dataType": 'json'
        },
        "order": [[ 0, "asc" ]],
        "columns": [
            { "data": "id_divisao" },
            { "data": "nome" },
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
$(document).on('click','.update_divisao',function () {
    debugger;
    $("#C_codigo").prop( "disabled", true );
    //var cod_divisao = $(this).attr("id_divisao");
    var tdobj = $(this).closest('tr').find('td');
    var cod_divisao = tdobj[0].innerHTML;
    $("#rotulo_associado").html("Alterando");
    $.ajax({
        url: "pages/divisao/divisao_exibe.php",
        method: "POST",
        data: {cod_divisao : cod_divisao},
        dataType: "json",
        success:function (data) {
            //debugger;
            //$.fn.modal.Constructor.prototype.enforceFocus = function() {};
            $("#ModalEditaDivisao").modal("show");
            $("#C_codigo").val(data.id_divisao);
            $("#C_nome_divisao").val(data.nome);
            $("#C_cidade").val(data.cidade);
            $('#operation').val("Update");
        }
    })
});
$("#btnInserir").click(function(){
    $("#C_codigo").prop( "disabled", true );
    $("#frmdivisao")[0].reset();
    $("#rotulo_associado").html("Cadastrando");
    $.fn.modal.Constructor.prototype.enforceFocus = function() {};
    $("#ModalEditaDivisao").modal("show");
    $('#operation').val("Add");
    var d = new Date().toLocaleString("pt-BR", {timeZone: "America/Sao_Paulo"});
});
$("#btnSalvar").click(function(event){

   event.preventDefault();
   $("#C_codigo").prop( "disabled", false );
   $('#frmdivisao').validator('validate');
   var campo_vazio = validar();
   if (campo_vazio === "validou") {
       debugger;
       if( $('#operation').val() === "Add") {
           debugger;
           $.ajax({
               url: "pages/divisao/divisao_verifica_repitido.php",
               method: "POST",
               data: $('#frmdivisao').serialize(),
               success: function (data) {

                   if (data === "nao repitido") {

                       $.ajax({
                           url: "pages/divisao/divisao_salvar.php",
                           method: "POST",
                           data: $('#frmdivisao').serialize(),
                           success: function (data) {
                               $("#frmdivisao")[0].reset();
                               if (data === "atualizado") {
                                   $.notify({
                                           message: 'Salvo com Sucesso!'
                                       }, {
                                           type: 'success'
                                       }, {
                                           position: 'center'
                                       }
                                   );
                               } else if (data === "cadastrado") {

                                   $.notify({
                                           message: 'Cadastrado com Sucesso!'
                                       }, {
                                           type: 'success'
                                       }, {
                                           position: 'center'
                                       }
                                   );
                               }
                               $("#frmdivisao")[0].reset();
                               $("#ModalEditaDivisao").modal('hide');
                               tabela_divisao.ajax.reload();
                           }
                       });

                   } else if (data === "repitido") {
                       BootstrapDialog.show({
                           closable: false,
                           title: 'Atenção',
                           message: 'A divisão : '+$("#C_nome_divisao").val()+' já existe.',
                           buttons: [{
                               cssClass: 'btn-warning',
                               label: 'Ok',
                               action: function (dialogItself) {
                                   dialogItself.close();
                                   $("#C_nome_divisao").focus();
                               }
                           }]
                       });
                   }
               }
           });
       }else{
           $.ajax({
               url: "pages/divisao/divisao_salvar.php",
               method: "POST",
               data: $('#frmdivisao').serialize(),
               success: function (data) {
                   $("#frmdivisao")[0].reset();
                   if (data === "atualizado") {
                       $.notify({
                               message: 'Salvo com Sucesso!'
                           }, {
                               type: 'success'
                           }, {
                               position: 'center'
                           }
                       );
                   } else if (data === "cadastrado") {

                       $.notify({
                               message: 'Cadastrado com Sucesso!'
                           }, {
                               type: 'success'
                           }, {
                               position: 'center'
                           }
                       );
                   }
                   $("#frmdivisao")[0].reset();
                   $("#ModalEditaDivisao").modal('hide');
                   tabela_divisao.ajax.reload();
               }
           });
       }
   }else {
       debugger;
       var nome_campo;
       switch (campo_vazio) {
           case 'C_nome_divisao':
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
   tabela_divisao.columns.adjust().draw();
});
$('#tabela_divisao').on('click', 'tbody .btnexcluir', function () {
    debugger;
    var data_row = tabela_divisao.row($(this).closest('tr')).data();
    var cod_divisao = data_row.id_divisao;
    var nome = data_row.nome;
    var cidade = data_row.cidade;
    $.ajax({
        url: "pages/divisao/divisao_valid_excluir.php",
        method: "POST",
        dataType: "json",
        data: {"cod_divisao": cod_divisao},
        success: function (data) {
        debugger;
            if (data.Resultado === "nao existe conta") {
                BootstrapDialog.confirm({
                    message: '<table style="width: 100%;"><tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">CODIGO:</th><th style="background-color: #dddddd;"><b>' + cod_divisao + '</b></th>' +
                        '<tr><th style="text-align: right;padding: 8px;">NOME:</th><th><b>' + nome + '</th>' +
                        '<tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">CIDADE:</th><th style="background-color: #dddddd;"><b>' + cidade + '</th>',
                    title: 'Confirma a exclusão da divisão ?',
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
                                url: "pages/divisao/divisao_excluir.php",
                                method: "POST",
                                dataType: "json",
                                data: {"cod_divisao": cod_divisao},
                                success: function (data) {
                                    debugger;
                                    if (data.Resultado === "excluido") {

                                        //tabela_divisao.row( $button.parents('tr') ).remove().draw();
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
                                    tabela_divisao.ajax.reload();
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
                    message: 'Não é possível exluir, existem lançamentos para esta divisao!',
                    buttons: [{
                        cssClass: 'btn-warning',
                        label: 'Ok',
                        action: function(dialogItself){
                            dialogItself.close();
                            $("#C_nome_divisao").focus();
                        }
                    }]
                });
            }
        }
    });
});
function validar(){
    debugger;
    var nome       = $('#C_nome_divisao').val();
    var cidade     = $('#C_cidade').val();
    if (nome === ""){
        return $('# _divisao').attr('name');
    }else if (cidade === "") {
        return $('#C_cidade').attr('name');
    }else{
        return "validou";
    }
}