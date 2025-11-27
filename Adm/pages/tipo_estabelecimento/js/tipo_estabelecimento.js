var usuario_global;
var tipo_estabelecimento;
var tabela_tipo_estabelecimento;
$(document).ready(function(){

    $('#operation').val("Add");
    tipo_estabelecimento = sessionStorage.getItem("tipo_estabelecimento");
    usuario_global = sessionStorage.getItem("usuario_global");

    // econstroi uma datatabe no primeiro carregamento da tela
    tabela_tipo_estabelecimento = $('#tabela_tipo_estabelecimento').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": false,
        "bServerSide": false,
        "responsive": true,
        "autoWidth": true,
        "bJQueryUI": true,
        "bAutoWidth": false,
        "ajax": {
            "url": 'pages/tipo_estabelecimento/tipo_estabelecimento_datatable.php',
            "method": 'POST',
            "data":  '',
            "dataType": 'json'
        },
        "order": [[ 1, "asc" ]],
        "columns": [
            { "data": "id" },
            { "data": "nome_tipo" },
            { "data": "botao" },
            { "data": "botaoexcluir" }
        ],
        "language": {
            url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Portuguese-Brasil.json",
            "decimal": ",",
            "thousands": "."
        },
        "pagingType": "full_numbers"
    });
});
$(document).on('click','.update_tipo_estabelecimento',function () {
    debugger;
    $("#C_id").prop( "disabled", true );
    var tdobj = $(this).closest('tr').find('td');
    var id = tdobj[0].innerHTML;
    $("#rotulo_associado").html("Alterando");
    $.ajax({
        url: "pages/tipo_estabelecimento/tipo_estabelecimento_exibe.php",
        method: "POST",
        data: {id : id},
        dataType: "json",
        success:function (data) {
            $("#ModalEditaTipoEstabelecimento").modal("show");
            $("#C_id").val(data.id_tipo_estabelecimento);
            $("#C_nome_tipo").val(data.nome_tipo);
            $('#operation').val("Update");
        }
    })
});
$("#btnInserir").click(function(){
    $("#C_id").prop( "disabled", true );
    $("#frmtipoestabelecimento")[0].reset();
    $("#rotulo_associado").html("Cadastrando");
    $.fn.modal.Constructor.prototype.enforceFocus = function() {};
    $("#ModalEditaTipoEstabelecimento").modal("show");
    $('#operation').val("Add");
    var d = new Date().toLocaleString("pt-BR", {timeZone: "America/Sao_Paulo"});
});
$("#btnSalvar").click(function(event){

   event.preventDefault();
   $("#C_id").prop( "disabled", false );
   $('#frmtipoestabelecimento').validator('validate');
   var campo_vazio = validar();
   console.log("Campo vazio:", campo_vazio);
   console.log("Operation:", $('#operation').val());
   console.log("Nome tipo:", $('#C_nome_tipo').val());
   
   if (campo_vazio === "validou") {
       debugger;
       if( $('#operation').val() === "Add") {
           debugger;
           $.ajax({
               url: "pages/tipo_estabelecimento/tipo_estabelecimento_verifica_repitido.php",
               method: "POST",
               data: $('#frmtipoestabelecimento').serialize(),
               success: function (data) {
                   console.log("Resposta verifica repitido:", data);
                   if (data.trim() === "nao repitido") {

                       $.ajax({
                           url: "pages/tipo_estabelecimento/tipo_estabelecimento_salvar.php",
                           method: "POST",
                           data: $('#frmtipoestabelecimento').serialize(),
                           success: function (data) {
                               console.log("Resposta salvar:", data);
                               $("#frmtipoestabelecimento")[0].reset();
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
                               $("#frmtipoestabelecimento")[0].reset();
                               $("#ModalEditaTipoEstabelecimento").modal('hide');
                               tabela_tipo_estabelecimento.ajax.reload();
                           },
                           error: function(xhr, status, error) {
                               console.log("Erro ao salvar:", error);
                               console.log("Response:", xhr.responseText);
                           }
                       });

                   } else if (data === "repitido") {
                       BootstrapDialog.show({
                           closable: false,
                           title: 'Atenção',
                           message: 'O tipo : '+$("#C_nome_tipo").val()+' já existe.',
                           buttons: [{
                               cssClass: 'btn-warning',
                               label: 'Ok',
                               action: function (dialogItself) {
                                   dialogItself.close();
                                   $("#C_nome_tipo").focus();
                               }
                           }]
                       });
                   }
               },
               error: function(xhr, status, error) {
                   console.log("Erro ao verificar repitido:", error);
                   console.log("Response:", xhr.responseText);
               }
           });
       }else{
           $.ajax({
               url: "pages/tipo_estabelecimento/tipo_estabelecimento_salvar.php",
               method: "POST",
               data: $('#frmtipoestabelecimento').serialize(),
               success: function (data) {
                   console.log("Resposta salvar (update):", data);
                   $("#frmtipoestabelecimento")[0].reset();
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
                   $("#frmtipoestabelecimento")[0].reset();
                   $("#ModalEditaTipoEstabelecimento").modal('hide');
                   tabela_tipo_estabelecimento.ajax.reload();
               },
               error: function(xhr, status, error) {
                   console.log("Erro ao salvar (update):", error);
                   console.log("Response:", xhr.responseText);
               }
           });
       }
   }else {
       debugger;
       var nome_campo;
       switch (campo_vazio) {
           case 'C_nome_tipo':
               nome_campo = "Nome do Tipo";
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
   tabela_tipo_estabelecimento.columns.adjust().draw();
});
function validar() {
    var nomeTipo = $('#C_nome_tipo').val();
    if (nomeTipo === "") {
        return 'C_nome_tipo';
    }
    return 'validou';
} 