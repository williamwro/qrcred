var usuario_global;
var usuario_cod;
var divisao;
var divisao_nome;
var tabela_antecipacao;
var C_cep_assoc = $("#C_cep_assoc");
var cidadex;
var d = new Date();
var curr_date = d.getDate();
var curr_month = d.getMonth()+1;
var curr_year = d.getFullYear();
var controle = false;
var card1;
var card2;
var card3;
var card4;
var card5;
var card6;

$(document).ready(function(){

    d = new Date();
    curr_date = d.getDate();
    curr_month = d.getMonth()+1;
    curr_year = d.getFullYear();
    curr_date = pad(curr_date,2)
    curr_month = pad(curr_month,2)

    divisao = sessionStorage.getItem("divisao");
    divisao_nome = sessionStorage.getItem("divisao_nome");

    $('#divisao').val(divisao);
    var detailRows = [];
   
    $('#C_aprovado').append('<option value="' + 1 + '"> Analisando </option>');
    $('#C_aprovado').append('<option value="' + 2 + '"> Aprovado </option>');
    $('#C_aprovado').append('<option value="' + 3 + '"> Reprovado </option>');

    var naodefinico = "Não definido"
   
    $('#tabela_antecipacao_assoc tfoot th').each( function () {
        var title = $(this).text();
        if(title !== ""){
            $(this).html( '<input type="text" class="small" placeholder="Busca '+title+'" />' );
        }
    } );
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");
    debugger;
    if(divisao === "1"){ //QRCRED
        filtra_antecipacao(null,divisao);// filtra todos
    }
   
    $('#tabela_antecipacao_assoc tbody').on('click', 'tr', function () {
        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
        } else {
            tabela_antecipacao.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
        }
    });
    // Add event listener for opening and closing details
    $('#tabela_antecipacao_assoc tbody').on( 'click', 'tr td.details-control', function () {

        var tr = $(this).closest('tr');
        var row = tabela_antecipacao.row( tr );
        var idx = $.inArray( tr.attr('id'), detailRows );

        if ( row.child.isShown() ) {
            tr.removeClass( 'details' );
            row.child.hide();

            // Remove from the 'open' array
            detailRows.splice( idx, 1 );
        }
        else {
            tr.addClass( 'details' );
            row.child( format( row.data() ) ).show();

            // Add to the 'open' array
            if ( idx === -1 ) {
                detailRows.push( tr.attr('id') );
            }
        }
    });
});
$("#C_nome_assoc").keypress(function(event) {
    var character = String.fromCharCode(event.keyCode);
    return isValid(character);
});
function isValid(str) {
    return !/[~`!@#$%\^&*()+=\-\[\]\\'´.;,/{}|\\":<>\?]/g.test(str);
}
$('#C_matricula_assoc').on('keypress', function (event) {
    var regex = new RegExp("^[0-9]+$");
    var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
    if (!regex.test(key)) {
        event.preventDefault();
        return false;
    }
});
$(document).on('click','.update_antecipacao',function () {
   debugger;
    var id_entecipacao = tabela_antecipacao.row($(this).parents('tr')).data()["id"];
    var cod_associado = tabela_antecipacao.row($(this).parents('tr')).data()["matricula"];
    var tdobj = $(this).closest('tr').find('td');
    var empregador = tabela_antecipacao.row($(this).parents('tr')).data()["id_empregador"];

    $("#rotulo_antecipado").html("Alterando");
    $.ajax({
        url: "pages/antecipacao/antecipacao_exibe.php",
        method: "POST",
        data: {cod_associado : cod_associado, empregador: empregador, id_entecipacao: id_entecipacao},
        dataType: "json",
        success:function (data) {
            $.fn.modal.Constructor.prototype.enforceFocus = function() {};
            $("#ModalEditaAntecipado").modal("show");
            
            $("#C_nome_antecipacao").val(data.nome);
            $("#C_matricula_antecipacao").val(data.matricula);
            $("#C_empregador_antecipacao").val(data.nome_empregador);
            $("#C_id_empregador_antecipacao").val(data.id_empregador);
            $("#C_mes").val(data.mes);
            $("#C_datasolicitacao").val(data.data_solicitacao);
            $("#C_cel_antecipacao").val(data.celular);
            debugger;
            $('[name="C_aprovado"] option').prop('selected', false); // desmarcar todas as opções primeiro
            if (data.aprovado === null) { //Analisando
                $('[name=C_aprovado] option[value="1"]').prop('selected', true);
            } else if (data.aprovado === true) { //Aprovado
                $('[name=C_aprovado] option[value="2"]').prop('selected', true);
            } else if (data.aprovado === false) { //Reprovado
                $('[name=C_aprovado] option[value="3"]').prop('selected', true);
            }
            $("#C_valor_antecipacao").val(parseFloat(data.valor).toFixed(2).replace(".", ","));
        }
    });
});
$("#btnInserir").click(function(){
    $("#frmantecipado")[0].reset();
    $("#rotulo_antecipado").html("Cadastrando");
    $("#C_empregador_antecipacao").val(0);
    $.fn.modal.Constructor.prototype.enforceFocus = function() {};
    $("#ModalEditaAntecipado").modal("show");
    $('#operation').val("Add");
    var d = new Date().toLocaleString("pt-BR", {timeZone: "America/Sao_Paulo"});
    var d2 = d.substring(0,10);
    $('#C_datacadastro_assoc').val(d2);
    $('#C_uf_assoc').val($('#C_uf_assoc option').eq(11).val());
    $('#C_cidade_assoc').val($('#C_cidade_assoc option').eq(835).val());
    $("#C_matricula_assoc").removeAttr('disabled');
});
$("#btnSalvar").click(function(event){
   waitingDialog.show('Gravando, aguarde ...');
   
   $("#btnSalvar").attr("disabled", true);
  
      
    $.ajax({
        url: "pages/antecipacao/antecipacao_salvar.php",
        method: "POST",
        data: $('#frmantecipado').serialize()+'&divisao='+divisao+'&usuario_cod='+usuario_cod,
        success: function (data) {
            $("#frmantecipado")[0].reset();
            if (data === "atualizado") {
                Swal.fire({
                    title: "Parabens!",
                    text: "Antecipação atualizada com sucesso !",
                    icon: "success",
                    showConfirmButton: false,
                    timer: 1500
                });
            } else if (data === "cadastrado") {
                Swal.fire({
                    title: "Parabens!",
                    text: "Associado cadastrado com sucesso !",
                    icon: "success",
                });
            } else if (data === "Seu usuario não tem permissão!") {
                Swal.fire({
                    title: "Atenção!",
                    text: "Seu usuário não tem permissão.",
                    icon: "error",
                });
            }
            $("#frmantecipado")[0].reset();
            $("#btnSalvar").attr("disabled", false);
            waitingDialog.hide();
            $("#ModalEditaAntecipado").modal('hide');
            tabela_antecipacao.ajax.reload();
        }
    });
    tabela_antecipacao.columns.adjust().draw();
});
$('#tabela_antecipacao_assoc').on('click', 'tbody .btnsenha_assoc', function () {

    var data_row = tabela_antecipacao.row($(this).closest('tr')).data();
    var cod_associado = data_row.codigo;
    var id_empregador = data_row.id_empregador;
    $("#frmSenha_assoc")[0].reset();
    $("#ModalSenha").modal("show");
    $.ajax({
        url: "pages/associado/associado_exibe_usuario.php",
        method: "POST",
        data: {cod_associado: cod_associado, id_empregador: id_empregador},
        dataType: "json",
        success: function (data) {

            $("#cod_associado_senha").val(data.matricula);
            $("#senha_associado").val(data.senha);
            $("#C_Senha_assoc").val(data.senha);
            $("#associado_rotulo").html(data.nome);
            $("#existe_senha").val(data.existesenha);
            $("#id_empregador_senha").val(id_empregador);
        }
    })
 });
/*$('#tabela_antecipacao_assoc').on('click', 'tbody .btnexcluir', function () {

    var data_row = tabela_antecipacao.row($(this).closest('tr')).data();
    var cod_associado = data_row.codigo;
    var nome_associado = data_row.nome;
    var empregador = data_row.abreviacao;
    var id_empregador = data_row.id_empregador;
    $.ajax({
        url: "pages/associado/associado_valid_excluir.php",
        method: "POST",
        dataType: "json",
        data: {"cod_associado": cod_associado, "id_empregador": id_empregador},
        success: function (data) {

            if (data.Resultado === "nao existe conta") {
                BootstrapDialog.confirm({
                    message: '<table style="width: 100%;"><tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">MATRICULA:</th><th style="background-color: #dddddd;"><b>' + cod_associado + '</b></th>' +
                        '<tr><th style="text-align: right;padding: 8px;">NOME:</th><th><b>' + nome_associado + '</th>' +
                        '<tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">EMPREGADOR:</th><th style="background-color: #dddddd;"><b>' + empregador + '</th>',
                    title: 'Confirma a exclusão do associado ?',
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
                                url: "pages/associado/associado_excluir.php",
                                method: "POST",
                                dataType: "json",
                                data: {"cod_associado": cod_associado, "id_empregador": id_empregador},
                                success: function (data) {

                                    if (data.Resultado === "excluido") {

                                        //tabela_antecipacao.row( $button.parents('tr') ).remove().draw();
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
                                                    //$("#C_Senha_assoc").focus();
                                                    tabela_antecipacao.ajax.reload();
                                                }
                                            }]
                                        });
                                    }else{
                                        alert("Não Excluiu");
                                        waitingDialog.hide();
                                    }
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
                    message: 'Não é possível exluir, existem lançamentos para este associado!',
                    buttons: [{
                        cssClass: 'btn-warning',
                        label: 'Ok',
                        action: function(dialogItself){
                            dialogItself.close();
                            $("#C_Senha_assoc").focus();
                        }
                    }]
                });
            }
        }
    });
});*/
$("#btnsalvarsenha").click(function(event){
    var senha = $("#C_Senha_assoc").val();
    var confirmasenha = $("#C_Confirma_Senha_assoc").val();
    if(senha !== ""){
        if(confirmasenha !== ""){
            if(senha === confirmasenha){
                $.ajax({
                    url:"pages/associado/associado_salvar_senha.php",
                    method: "POST",
                    data: $('#frmSenha_assoc').serialize(),
                    success:function (data) {
                        if (data === "senha_fazia"){
                            BootstrapDialog.show({
                                closable: false,
                                title: 'Atenção',
                                message: 'Informe a senha!',
                                buttons: [{
                                    cssClass: 'btn-warning',
                                    label: 'Ok',
                                    action: function(dialogItself){
                                        dialogItself.close();
                                        $("#C_Senha_assoc").focus();
                                    }
                                }]
                            });
                        }else if (data === "senha_divergente") {
                            BootstrapDialog.show({
                                closable: false,
                                title: 'Atenção',
                                message: 'Senha e Confirma estão diferentes !',
                                buttons: [{
                                    cssClass: 'btn-warning',
                                    label: 'Ok',
                                    action: function(dialogItself){
                                        dialogItself.close();
                                        $("#C_Senha_assoc").focus();
                                    }
                                }]
                            });
                        }else if (data === "atualizado") {
                            Swal.fire({
                                title: "Parabens!",
                                text: "Senha atualizada com sucesso !",
                                icon: "success",
                                timer: 3000
                            });
                            $("#ModalSenha").modal('hide');
                        }else if(data === "cadastrado"){
                            Swal.fire({
                                title: "Parabens!",
                                text: "Senha cadastrada com sucesso !",
                                icon: "success",
                                timer: 3000
                            });
                            $("#ModalSenha").modal('hide');
                        } else if (data === "Seu usuario não tem permissão!") {
                            BootstrapDialog.show({
                                closable: false,
                                title: 'Atenção',
                                message: 'Atualização cancelada, seu usuario não tem permissão!',
                                buttons: [{
                                    cssClass: 'btn-danger',
                                    label: 'Ok',
                                    action: function (dialogItself) {
                                        dialogItself.close();
                                        $("#ModalSenha").modal('hide');
                                    }
                                }]
                            });
                        }
                    }
                })
            }else{
                BootstrapDialog.show({
                    closable: false,
                    title: 'Atenção',
                    message: 'As senha não sao iguais!',
                    buttons: [{
                        cssClass: 'btn-warning',
                        label: 'Ok',
                        action: function(dialogItself){
                            dialogItself.close();
                            $("#C_Senha_assoc").focus();
                        }
                    }]
                });
            }
        }else{
            BootstrapDialog.show({
                closable: false,
                title: 'Atenção',
                message: 'Digite a confirmação da senha!!',
                buttons: [{
                    cssClass: 'btn-warning',
                    label: 'Ok',
                    action: function(dialogItself){
                        dialogItself.close();
                        $("#C_Confirma_Senha_assoc").focus();
                    }
                }]
            });
        }
    }else{
        BootstrapDialog.show({
            type: [BootstrapDialog.TYPE_DANGER],
            closable: false,
            title: 'Atenção',
            message: 'Digite a senha!!',
            buttons: [{
                cssClass: 'btn-warning',
                label: 'Ok',
                action: function(dialogItself){
                    dialogItself.close();
                    $("#C_Senha_assoc").focus();
                }
            }]
        });
    }
});
$(document).on('click','.btnextrato',function () {
    var caminho = "pages/associado_extrato/extrato_associado_read.php";
    var matricula = $(this).attr("id");
    //********pega o dado da segunda coluna com o nome do associado**
    var tdobj = $(this).closest('tr').find('td');
    var nome = tdobj[2].innerHTML;
    //***************************************************************
    //********pega o dado da segunda coluna com o nome do empregador**
    var tdobjemp = $(this).closest('tr').find('td');
    var empregador = tdobjemp[6].innerHTML;
    //***************************************************************

    $.redirect('index.php',{ caminho: caminho, matricula: matricula, nome: nome, empregador: empregador});
});

// Array to track the ids of the details displayed rows



// On each draw, loop over the `detailRows` array and show any child rows
/* table.on( 'draw', function () {
    $.each( detailRows, function ( i, id ) {
        $('#'+id+' td.details-control').trigger( 'click' );
    } );
} );*/
function moedaParaNumero(valor)
{
    return isNaN(valor) === false ? parseFloat(valor) :   parseFloat(valor.replace("R$","").replace(".","").replace(",","."));
}
function numeroParaMoeda(n, c, d, t)
{
    c = isNaN(c = Math.abs(c)) ? 2 : c, d = d === undefined ? "," : d, t = t === undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}
function format ( d ) {
     return'<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
                '<tr>'+
                '<td>Salario :</td>'+
                '<td>'+d.salario+'</td>'+
                '</tr>'+
                '<tr>'+
                '<td>Limite  :</td>'+
                '<td>'+d.limite+'</td>'+
                '</tr>'+
                '<tr>'+
                '<td>Cep     :</td>'+
                '<td>'+d.cep+'</td>'+
                '</tr>'+
                '<tr>'+
                '<td>TelRes  :</td>'+
                '<td>'+d.telres+'</td>'+
                '</tr>'+
                '<tr>'+
                '<td>TelCom  :</td>'+
                '<td>'+d.telcom+'</td>'+
                '</tr>'+
                '<tr>'+
                '<td>CPF  :</td>'+
                '<td>'+d.cpf+'</td>'+
                '</tr>'+
                '<tr>'+
                '<td>RG  :</td>'+
                '<td>'+d.rg+'</td>'+
                '</tr>'+
                 '<tr>'+
                 '<td>Complemento  :</td>'+
                 '<td>'+d.complemento+'</td>'+
                 '</tr>'+
           '</table>';

}
function validar(){

    var nome       = $('#C_nome_assoc').val();
    var matricula  = $('#C_matricula_assoc').val();
    var endereco   = $('#C_nome_assoc').val();
    var numero     = $('#C_numero_assoc').val();
    var bairro     = $('#C_bairro_assoc').val();
    var cidade     = $('#C_cidade_assoc').val();
    var uf         = $('#C_uf_assoc').val();
    var nascimento = $('#C_nascimento').val();
    var salario    = $('#C_salario').val();
    var limite     = $('#C_limite_assoc').val();
    if (nome === ""){
        return $('#C_nome_assoc').attr('name');
    }else if (matricula === "") {
        return $('#C_matricula_assoc').attr('name');
    }else if (endereco === "") {
        return $('#C_nome_assoc').attr('name');
    }else if (numero === "") {
        return $('#C_numero_assoc').attr('name');
    }else if (bairro === "") {
        return $('#C_bairro_assoc').attr('name');
    }else if (cidade === "") {
        return $('#C_cidade_assoc').attr('name');
    }else if (uf === "") {
        return $('#C_uf_assoc').attr('name');
    }else if (nascimento === "") {
        return $('#C_nascimento').attr('name');
    }else if (salario === "") {
        return $('#C_salario').attr('name');
    }else if (limite === "") {
        return $('#C_limite_assoc').attr('name');
    }else{
        return "validou";
    }
}
function ucFirstAllWords( str )
{   
    if(str != null){
        var pieces = str.split(" ");
        for ( var i = 0; i < pieces.length; i++ )
        {
            var j = pieces[i].charAt(0).toUpperCase();
            pieces[i] = j + pieces[i].substr(1).toLowerCase();
        }
        return pieces.join(" ");
    } 
}
$('#RadioTodos').change(function(){
    cod_situacao = $('#RadioTodos').val();
    filtra_antecipacao(cod_situacao,divisao);
    if(divisao === "1"){ //QRCRED
        filtra_antecipacao(cod_situacao,divisao);// filtra todos
    }
});
$('#RadioAnalisando').change(function(){
    debugger;
    cod_situacao = $('#RadioAnalisando').val();
    if(divisao === "1"){ //QRCRED
        filtra_antecipacao(cod_situacao,divisao);// filtra todos
    }
});
$('#RadioAprovados').change(function(){
    cod_situacao = $('#RadioAprovados').val();
    if(divisao === "1"){ //QRCRED
        filtra_antecipacao(cod_situacao,divisao);// filtra todos
    }
});
$('#RadioNaoAprovados').change(function(){
    cod_situacao = $('#RadioNaoAprovados').val();
    if(divisao === "1"){ //QRCRED
        filtra_antecipacao(cod_situacao,divisao);// filtra todos
    }
});


function filtra_antecipacao(codigo,divisao){
    tabela_antecipacao = $('#tabela_antecipacao_assoc').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "destroy": true,
        "processing": false,
        "serverSide": false,
        "paging": true,
        "deferRender": true,
        autoWidth: false,
        "ajax": {
            "url": 'pages/antecipacao/antecipacao_read2.php',
            "method": 'POST',
            "data":  { 'usuario_global': usuario_global, 'divisao': divisao, 'id_situacao': codigo },
            "dataType": 'json'
        },
        "order": [[ 2, "asc" ]],
        "columns": [
            { "data": "matricula" },
            { "data": "nome" },
            { "data": "id_empregador" },
            { "data": "nome_empregador" },
            { "data": "mes" },
            { "data": "data_solicitacao" },
            { 
                "data": "valor",
                render: $.fn.dataTable.render.number( '.', ',', 2, 'R$ ' )
            },
            { "data": "aprovado" },
            { "data": "data_aprovacao" },
            { "data": "celular" },
            { "data": "botao" },
            { "data": "botaoexcluir" }
        ],
        "columnDefs": [
            {
                "targets": [ 2 ],
                "visible": false,
                "searchable": false,
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
        "pagingType": "full_numbers"
    });
}

$("#C_situacao_assoc").change(function () {
    
    if(controle === false) {
        if($("#C_situacao_assoc").val() === "2" || $("#C_situacao_assoc").val() === "3"){//desfiliado or falecido
            $("#C_datadesfiliacao").val(curr_date + "/" + curr_month + "/" + curr_year);
            $("#C_filiado").prop("checked", false);

        }else{
            $("#C_datadesfiliacao").val('');
            $("#C_filiado").prop("checked", true);

        }
    }else{
        controle = false;
    }
})
$('#C_filiado').change(function() {
    
    controle = true;
    if ($(this).is(':checked')) {
        $("#C_datadesfiliacao").val('');
        $("#C_situacao_assoc").val('1').change();
        //$("#C_filiado").prop("checked", true);
    } else {
        $("#C_datadesfiliacao").val(curr_date + "/" + curr_month + "/" + curr_year);
        $("#C_situacao_assoc").val('2').change();
        //$("#C_filiado").prop("checked", false);
    }
});
function pad (str, max) {
    str = str.toString();
    str = str.length < max ? pad("0" + str, max) : str; // zero à esquerda
    str = str.length > max ? str.substr(0,max) : str; // máximo de caracteres
    return str;
}