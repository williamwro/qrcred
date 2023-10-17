//script_funcao.js
var usuario_global;
var divisao;
var tabela_funcao;
$(document).ready(function(){

    $('#operation').val("Add");
    divisao = sessionStorage.getItem("divisao");
    usuario_global = sessionStorage.getItem("usuario_global");
    // econstroi uma datatabe no primeiro carregamento da tela
    tabela_funcao = $('#tabela_funcao').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": false,
        "bServerSide": false,
        "responsive": true,
        "autoWidth": true,
        "bJQueryUI": true,
        "bAutoWidth": false,
        "ajax": {
            "url": 'pages/funcao/datatable.php',
            "method": 'POST',
            "data":  '',
            "dataType": 'json'
        },
        "order": [[ 0, "asc" ]],
        "columns": [
            { "data": "id" },
            { "data": "nome" },
            { "data": "botao" },
        ],
        "language": {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Portuguese-Brasil.json",
            "decimal": ",",
            "thousands": "."
        },
        "pagingType": "full_numbers"
    });
});
$(document).on('click','.update_funcao',function () {
    debugger;
    $("#C_codigo_funcao").prop( "disabled", true );
    //var cod_divisao = $(this).attr("id_divisao");
    var tdobj = $(this).closest('tr').find('td');
    var cod_categoria = tdobj[0].innerHTML;
    var nome = tdobj[1].innerHTML;
    $("#rotulo_associado").html("Alterando");
    $.ajax({
        url: "pages/funcao/exibe.php",
        method: "POST",
        data: {cod_categoria : cod_categoria, nome : nome},
        dataType: "json",
        success:function (data) {
            $.fn.modal.Constructor.prototype.enforceFocus = function() {};
            $("#ModalEditaFuncao").modal("show");
            $("#C_codigo_funcao").val(data.codigo);
            $("#C_nome_funcao").val(data.nome);
            $('#operation').val("Update");
        }
    });
});
$("#btnInserir").click(function(){
    $("#C_codigo_funcao").prop( "disabled", true );
    $("#frmFormularioFuncao")[0].reset();
    $("#rotulo_associado").html("Cadastrando");
    $.fn.modal.Constructor.prototype.enforceFocus = function() {};
    $("#ModalEditaFuncao").modal("show");
    $('#operation').val("Add");
    var d = new Date().toLocaleString("pt-BR", {timeZone: "America/Sao_Paulo"});
});
$("#btnSalvar").click(function(event){
   event.preventDefault();
   $("#C_codigo_funcao").prop( "disabled", false );
   $('#frmFormularioFuncao').validator('validate');
   var campo_vazio = validar();
   if (campo_vazio === "validou") {
       debugger;
       if( $('#operation').val() === "Add") {
           debugger;
           $.ajax({
               url: "pages/funcao/verifica_repitido.php",
               method: "POST",
               data: $('#frmFormularioFuncao').serialize(),
               success: function (data) {

                   if (data === "nao repitido") {

                       $.ajax({
                           url: "pages/funcao/salvar.php",
                           method: "POST",
                           data: $('#frmFormularioFuncao').serialize(),
                           success: function (data) {
                               $("#frmFormularioFuncao")[0].reset();
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
                               $("#frmFormularioFuncao")[0].reset();
                               $("#ModalEditaFuncao").modal('hide');
                               tabela_funcao.ajax.reload();
                           },
                           error: function (request, status, erro) {
                           alert("Problema ocorrido: " + status + "\nDescição: " + erro);
                           //Abaixo está listando os header do conteudo que você requisitou, só para confirmar se você setou os header e dataType corretos
                           alert("Informações da requisição: \n" + request.getAllResponseHeaders());
                       },
                       });
                   } else if (data === "repitido") {
                       BootstrapDialog.show({
                           closable: false,
                           title: 'Atenção',
                           message: 'A categoria : '+$("#C_nome_funcao").val()+' já existe.',
                           buttons: [{
                               cssClass: 'btn-warning',
                               label: 'Ok',
                               action: function (dialogItself) {
                                   dialogItself.close();
                                   $("#C_nome_funcao").focus();
                               }
                           }]
                       });
                   }
               }
           });
       }else{
           $.ajax({
               url: "pages/funcao/salvar.php",
               method: "POST",
               data: $('#frmFormularioFuncao').serialize(),
               success: function (data) {
                   $("#frmFormularioFuncao")[0].reset();
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
                   $("#frmFormularioFuncao")[0].reset();
                   $("#ModalEditaFuncao").modal('hide');
                   tabela_funcao.ajax.reload();
               },
               error: function (request, status, erro) {
                   alert("Problema ocorrido: " + status + "\nDescição: " + erro);
                   //Abaixo está listando os header do conteudo que você requisitou, só para confirmar se você setou os header e dataType corretos
                   alert("Informações da requisição: \n" + request.getAllResponseHeaders());
               },
           });
       }
   }else {
       debugger;
       var nome_campo;
       switch (campo_vazio) {
           case 'C_nome_funcao':
               nome_campo = "Nome";
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
   tabela_funcao.columns.adjust().draw();
});
function validar(){
    var nome       = $('#C_nome_funcao').val();
    if (nome === ""){
        return $('#C_nome_funcao').attr('name');
    }else{
        return "validou";
    }
}