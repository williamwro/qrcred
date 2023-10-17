var table;
var usuarioglobal;
var table_associados;
var cidadex;
var strx="";
function format ( d ) {
    return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
        '<tr>'+
        '<td>Cidade :</td>'+
        '<td>'+d.cidade+'</td>'+
        '</tr>'+
        '<tr>'+
        '<td>Bairro  :</td>'+
        '<td>'+d.bairro+'</td>'+
        '</tr>'+
        '<tr>'+
        '<td>CNPJ     :</td>'+
        '<td>'+d.cnpj+'</td>'+
        '</tr>'+
        '<tr>'+
        '<td>E-mail  :</td>'+
        '<td>'+d.email+'</td>'+
        '</tr>'+
        '<tr>'+
        '<td>Contato  :</td>'+
        '<td>'+d.contato+'</td>'+
        '</tr>'+
        '<tr>'+
        '<td>Registro  :</td>'+
        '<td>'+d.registro+'</td>'+
        '</tr>'+
        '<tr>'+
        '<td>CPF  :</td>'+
        '<td>'+d.cpf+'</td>'+
        '</tr>'+
        '<tr>'+
        '<td>Celular  :</td>'+
        '<td>'+d.cel+'</td>'+
        '</tr>'+
        '</table>';
}
$(document).ready(function(){
    $('#operation').val("Add");
    $('#C_tel1').mask('(99)9999-9999');
    $('#C_tel2').mask('(99)9999-9999');
    $('#C_cel').mask('(99)99999-9999');
    $("#C_cep").mask("99.999-999");
    $('#C_cnpj').mask('99.999.999/9999-99');
    $('#C_cpf').mask('999.999.999-99');
    var detailRows = [];
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");
    $("#C_prolabore").maskMoney({
        prefix: "",
        decimal: ",",
        thousands: "."
    });
    $("#C_prolabore2").maskMoney({
        prefix: "",
        decimal: ",",
        thousands: "."
    });
    $('#frmconvenio').validator();
    $("#frmSenha")[0].reset();
    $('#C_tipoempresa').append('<option value="1">FÍSICA</option>');
    $('#C_tipoempresa').append('<option value="2">JURÍDICA</option>');
    if (usuario_cod !== "1") {
        $("#btnInserir").hide();
    }else{
        $("#btnInserir").show();
    }
    //for (var $i = 1; $i <= 12; $i++) {
    //    $('#C_parcelamento').append('<option value="' + $i + '">' + $i + '</option>');
    //}
    $.getJSON( "pages/convenio/convenio_categorias.php", function( data ) {
        $.each(data, function (index, value) {
            $('#C_categoria').append('<option value="' + value.codigo + '">' + value.nome + '</option>');
        });
    });
    $.getJSON( "pages/convenio/convenio_tipos.php", function( data ) {
        $.each(data, function (index, value) {
            $('#C_tipo').append('<option value="' + value.codigo + '">' + value.nome + '</option>');
            $('#C_tipon').append('<option value="' + value.codigo + '">' + value.nome + '</option>');
        });
    });
    $.getJSON( "pages/convenio/convenio_categoria_recibo.php", function( data ) {
        $.each(data, function (index, value) {
            $('#C_categoriarecibo').append('<option value="' + value.id_categoria_recibo + '">' + value.nome + '</option>');
        });
    });
    $.getJSON( "pages/convenio/bancos.php", function( data ) {
        $('#C_Banco').append('<option value="' + 0 + '">' + "Escolha o banco" + '</option>');
        $.each(data, function (index, value) {
            $('#C_Banco').append('<option value="' + value.cod_banco + '">' + value.banco + '</option>'); 
        });
    });
    $.getJSON( "pages/convenio/conta_tipo.php", function( data ) {
        $('#C_Tipo_Conta').append('<option value="' + 0 + '">' + "Escolha o tipo conta" + '</option>');
        $.each(data, function (index, value) {
            $('#C_Tipo_Conta').append('<option value="' + value.id + '">' + value.tipo + '</option>');
        });
    });
    $.getJSON( "pages/convenio/tipo_pix.php", function( data ) {
        $('#C_Tipo_Pix').append('<option value="' + 0 + '">' + "Escolha o tipo pix" + '</option>');
        $.each(data, function (index, value) {
            $('#C_Tipo_Pix').append('<option value="' + value.id_chave_pix + '">' + value.nome_chave + '</option>');
        });
    });
    // econstroi uma datatabe no primeiro carregamento da tela
    table = $('#tabela_producao').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": false,
        "serverSide": false,
        "responsive": false,
        "autoWidth": false,
        "deferRender": true,
        "destroy": true,
        "ajax": {
            "url": 'pages/convenio/convenio_read2.php',
            "method": 'POST',
            "data": "",
            "dataType": 'json'
        },
        "order": [[ 1, "desc" ]],
        "columns": [
            {
                "class":"details-control",
                "orderable": false,
                "data": null,
                "defaultContent": ""
            },
            { "data": "codigo" },
            { "data": "razaosocial" },
            { "data": "nomefantasia" },
            { "data": "endereco" },
            { "data": "telefone" },
            { "data": "data _cadastro" },
            { "data": "botaover" },
            { "data": "botao" },
            { "data": "botaosenha" },
            { "data": "botaobanco" },
            {
                "data": "divulga",
                render: function (data, type, row) {
                    if (type === 'display') {
                        return '<label class="checkbox"><input type="checkbox" ' + ((data === 'S') ? 'checked' : '') + ' id=' + row.id + ' class="editor-divulga" /><span></span></label>';
                    }
                    return data;
                },
                width: '5px',
                className: "dt-body-center"
            },
            {
                "data": "desativado",
                render: function (data, type, row) {
                    if (type === 'display') {
                        return '<label class="checkbox"><input type="checkbox" ' + ((data === true) ? 'checked' : '') + ' id=' + row.id + ' class="editor-desativado" /><span></span></label>';
                    }
                    return data;
                },
                width: '5px',
                className: "dt-body-center"
            }
        ],
        columnDefs:[{
            targets: 6,
            render: function (data) {
                if (data === null) {
                    return '';
                } else {
                    return moment(data).format('DD/MM/YYYY');
                }
            }
        }],
        "pagingType": "full_numbers",
        "language": {
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
        }
    });

    if (usuario_cod !== "1") {
        table.column(8).visible(false);
        table.column(9).visible(false);
        table.column(10).visible(false);
        table.column(11).visible(false);
        table.column(12).visible(false);
       // table.column(13).visible(false);
    }else{
        table.column(8).visible(true);
        table.column(9).visible(true);
        table.column(10).visible(true);
        table.column(11).visible(true);
        table.column(12).visible(true);
        //table.column(13).visible(true);
    }
    $('#tabela_producao tbody').on('click', 'tr', function () {
        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
        } else {
            table.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
        }
    });
    // On each draw, loop over the `detailRows` array and show any child rows
    table.on( 'draw', function () {
        $.each( detailRows, function ( i, id ) {
            $('#'+id+' td.details-control').trigger( 'click' );
        } );
    } );
    // Add event listener for opening and closing details
    /*$('#tabela_producao tbody').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row( tr );
        if ( row.child.isShown() ) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        }
        else {
            // Open this row
            row.child( format(row.data()) ).show();
            tr.addClass('shown');
        }
    } );*/
    $('#tabela_producao tbody').on( 'click', 'tr td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row( tr );
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
    } );
    function limpa_formulario_cep() {
        // Limpa valores do formulário de cep.
        $("#C_endereco").val("");
        $("#C_bairro").val("");
        $("#C_cidade").val("");
        $("#C_uf").val("");
    }
    //Quando o campo cep perde o foco.
    $("#C_cep").blur(function() {

        //Nova variável "cep" somente com dígitos.
        var cep = $(this).val().replace(/\D/g, '');

        //Verifica se campo cep possui valor informado.
        if (cep !== "") {

            //Expressão regular para validar o CEP.
            var validacep = /^[0-9]{8}$/;

            //Valida o formato do CEP.
            if(validacep.test(cep)) {

                //Preenche os campos com "..." enquanto consulta webservice.
                $("#C_endereco").val("");
                $("#C_bairro").val("");
                $("#C_cidade").val("");
                $("#C_uf").val("");
                //$("#ibge").val("...");

                //Consulta o webservice viacep.com.br/
                $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {

                    if (!("erro" in dados)) {
                        //Atualiza os campos com os valores da consulta.
                        $("#C_uf").val(dados.uf).change();
                        $("#C_endereco").val(dados.logradouro);
                        $("#C_bairro").val(dados.bairro);
                        $("#C_cidade").val(dados.localidade);
                        validar();
                        //$("#ibge").val(dados.ibge);
                    } //end if.
                    else {
                        //CEP pesquisado não foi encontrado.
                        limpa_formulario_cep();
                        alert("CEP não encontrado.");
                    }
                });
            } //end if.
            else {
                //cep é inválido.
                limpa_formulario_cep();
                alert("Formato de CEP inválido.");
            }
        } //end if.
        else {
            //cep sem valor, limpa formulário.
            //limpa_formulario_cep();
        }
    });
    $.getJSON('pages/associado/estados_cidades.json', function (data) {
        var items = [];
        var options = '<option value="">escolha um estado</option>';
        $.each(data, function (key, val) {
            options += '<option value="' + val.sigla + '">' + val.sigla + '</option>';
        });
        $("#C_uf").html(options);
        $('#C_uf').val($('#C_uf option').eq(11).val());// MG

        $("#C_uf").change(function () {

            var options_cidades = '';
            var str = "";

            $("#C_uf option:selected").each(function () {
                str += $(this).text();
            });
            options_cidades = '<option value="">escolha a cidade</option>';
            $.each(data, function (key, val) {
                if(val.sigla === str) {
                    $.each(val.cidades, function (key_city, val_city) {
                        options_cidades += '<option value="' + val_city + '">' + val_city + '</option>';
                    });
                }
            });
            $("#C_cidade").html(options_cidades);
        }).change();
    });
});
$('#gerarpdf').click(function () {
    $.redirect('pages/convenio/gerador_pdf_convenios.php',"POST", "_blank");
});// .updateconvenio é o botão alterar
$(document).on('click','.updateconvenio',function () {
    $("#row_mostra").show();
    $("#row_nao_mostra").hide();
    var cod_convenio = $(this).attr("id");
    $.ajax({
        url:"pages/convenio/convenio_exibe.php",
        method: "POST",
        data: {cod_convenio : cod_convenio},
        dataType: "json",
        success:function (data) {
            //$.fn.modal.Constructor.prototype.enforceFocus = function() {};
            $("#ModalEditaConvemio").modal("show");


            $("#C_codigo").val(data.codigo);
            $("#C_razaosocial").val(data.razaosocial);
            $("#C_nomefantasia").val(data.nomefantasia);
            $("#C_numero").val(data.numero);
            $("#C_bairro").val(data.bairro);
            $('[name=C_uf] option').filter(function() {
                return ($(this).text() === data.uf);
            }).prop('selected', true);
            $("#C_uf").val(data.uf).change();
            cidadex = data.cidade;
            cidadex = ucFirstAllWords(cidadex);
            $('[name=C_cidade] option').filter(function() {
                return ($(this).text() === cidadex);
            }).prop('selected', true);
            $("#C_endereco").val(data.endereco);
            $("#C_cep").val(data.cep);
            $("#C_tel1").val(data.telefone);
            $("#C_datacadastro").val(data.data_cadastro);
            $("#C_tel2").val(data.fax);
            $("#C_cel").val(data.cel);
            $("#C_contato").val(data.contato);
            $("#C_prolabore").val(data.prolabore);
            $("#C_prolabore2").val(data.prolabore2);
            $("#C_cnpj").val(data.cnpj);
            $("#C_cpf").val(data.cpf);
            $("#C_Inscestadual").val(data.insc);
            $("#C_categoria").val(data.categoria);
            $("#C_categoriarecibo").val(data.categoriarecibo);
            $("#C_registro").val(data.registro);
            $("#C_ativo").prop("checked", data.situacao);
            $("#C_divulga").prop("checked", data.divulga);
            $("#C_inscmunicipal").val(data.insc_mun);
            $("#C_email").val(data.email);
            $("#C_email2").val(data.email2);
            $("#C_tipo").val(data.tipo);
            $("#C_tipoempresa").val(data.tipoempresa);
            $("#C_cobranca").prop("checked", data.cobranca);
            $("#C_desativado").prop("checked", data.desativado);
            $("#C_parc_ind").prop("checked", data.aceita_parce_individ);
            $('#C_parcelamento').val(data.parcelas);
            $('#operation').val("Update");

            $('#ModalEditaLabel').html("Convenio <small>Aterando</small>");
            $('#btnSalvar').show();


            $("#C_razaosocial").prop('disabled', false);
            $("#C_nomefantasia").prop('disabled', false);
            $("#C_numero").prop('disabled', false);
            $("#C_bairro").prop('disabled', false);
            $("#C_uf").prop('disabled', false);
            $("#C_cidade").prop('disabled', false);
            $("#C_endereco").prop('disabled', false);
            $("#C_cep").prop('disabled', false);
            $("#C_tel1").prop('disabled', false);
            $("#C_datacadastro").prop('disabled', false);
            $("#C_tel2").prop('disabled', false);
            $("#C_cel").prop('disabled', false);
            $("#C_contato").prop('disabled', false);
            $("#C_prolabore").prop('disabled', false);
            $("#C_prolabore2").prop('disabled', false);
            $("#C_cnpj").prop('disabled', false);
            $("#C_cpf").prop('disabled', false);
            $("#C_Inscestadual").prop('disabled', false);
            $("#C_categoria").prop('disabled', false);
            $("#C_categoriarecibo").prop('disabled', false);
            $("#C_registro").prop('disabled', false);
            $("#C_ativo").prop('disabled', false);
            $("#C_divulga").prop('disabled', false);
            $("#C_inscmunicipal").prop('disabled', false);
            $("#C_email").prop('disabled', false);
            $("#C_email2").prop('disabled', false);
            $("#C_tipo").prop('disabled', false);
            $("#C_tipoempresa").prop('disabled', false);
            $("#C_cobranca").prop('disabled', false);
            $("#C_desativado").prop('disabled', false);
            $("#C_parc_ind").prop("disabled", false);
            $('#C_parcelamento').prop('disabled', false);
            /*$.each($('#frmconvenio').serializeArray(), function(index, value){
                $('#' + value.name + '').prop('disabled', false);
            });*/
        }
    });
});
$("#btnInserir").click(function(){
    $("#C_razaosocial").prop('disabled', false);
    $("#C_nomefantasia").prop('disabled', false);
    $("#C_numero").prop('disabled', false);
    $("#C_bairro").prop('disabled', false);
    $("#C_uf").prop('disabled', false);
    $("#C_cidade").prop('disabled', false);
    $("#C_endereco").prop('disabled', false);
    $("#C_cep").prop('disabled', false);
    $("#C_tel1").prop('disabled', false);
    $("#C_datacadastro").prop('disabled', false);
    $("#C_tel2").prop('disabled', false);
    $("#C_cel").prop('disabled', false);
    $("#C_contato").prop('disabled', false);
    $("#C_prolabore").prop('disabled', false);
    $("#C_prolabore2").prop('disabled', false);
    $("#C_cnpj").prop('disabled', false);
    $("#C_cpf").prop('disabled', false);
    $("#C_Inscestadual").prop('disabled', false);
    $("#C_categoria").prop('disabled', false);
    $("#C_categoriarecibo").prop('disabled', false);
    $("#C_registro").prop('disabled', false);
    $("#C_ativo").prop('disabled', false);
    $("#C_divulga").prop('disabled', false);
    $("#C_inscmunicipal").prop('disabled', false);
    $("#C_email").prop('disabled', false);
    $("#C_email2").prop('disabled', false);
    $("#C_tipo").prop('disabled', false);
    $("#C_tipoempresa").prop('disabled', false);
    $("#C_cobranca").prop('disabled', false);
    $("#C_desativado").prop('disabled', false);
    $('#C_parcelamento').prop('disabled', false);
    $("#C_parc_ind").prop("disabled", false);
    $.each($('#frmconvenio').serializeArray(), function(index, value){
        $('#' + value.name + '').prop('disabled', false);
    });
    $("#row_mostra").show();
    $("#row_nao_mostra").hide();
    $("#frmconvenio")[0].reset();
    $("#ModalEditaConvemio").modal("show");
    $.getJSON( "pages/convenio/convenio_ultimo_codigo.php" ).done( function( data ) {
        $( "#C_codigo" ).val(data.codigo);
        $('#operation').val("Add");
    });

    var d = new Date();
    var curr_date = d.getDate();
    var curr_month = d.getMonth()+1;
    var curr_year = d.getFullYear();
    $('#C_datacadastro').val(curr_date + "/" + curr_month + "/" + curr_year);
    $('#C_uf').val($('#C_uf option').eq(11).val());
    $('#C_cidade').val($('#C_cidade option').eq(835).val());
});
$("#btnSalvar").click(function(event){
    event.preventDefault();
    $('#frmassociado').validator('validate');
    var campo_vazio = validar();
    if (campo_vazio === "validou") {
        $.ajax({
            url:"pages/convenio/convenio_salvar.php",
            method: "POST",
            data: $('#frmconvenio').serialize(),
            success:function (data) {
                //alert(data);
                $("#frmconvenio")[0].reset();
                if (data === "atualizado") {
                    Swal.fire({
                        title: "Parabens!",
                        text: "Salvo com Sucesso !",
                        icon: "success",
                        showConfirmButton: false,
                        timer: 1500
                    });
                }else if(data === "cadastrado"){
                    Swal.fire({
                        title: "Parabens!",
                        text: "Cadastrado com Sucesso !",
                        icon: "success",
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
                $("#ModalEditaConvemio").modal('hide');
                table.ajax.reload();
            }
        })
    }else {

        var nome_campo;
        switch (campo_vazio) {
            case 'C_razaosocial':
                nome_campo = "Razao social";
                break;
            case 'C_nomefantasia':
                nome_campo = "Nome fantasia";
                break;
            case 'C_endereco':
                nome_campo = "Endereço";
                break;
            case 'C_numero':
                nome_campo = "Numero";
                break;
            case 'C_bairro':
                nome_campo = "Bairro";
                break;
            case 'C_cidade':
                nome_campo = "Cidade";
                break;
            case 'C_uf':
                nome_campo = "UF";
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
    //table_associados.columns.adjust().draw();
});
$(document).on('click','.btnsenha',function () {
    var cod_convenio = $(this).attr("id");
    $.ajax({
        url: "pages/convenio/convenio_exibe_usuario.php",
        method: "POST",
        data: {cod_convenio: cod_convenio},
        dataType: "json",
        success: function (data) {

            $("#frmSenha")[0].reset();
            $("#ModalSenha").modal("show");
            $("#codigo_convenio").val(data.codigo);
            $("#senha_convenio").val(data.senha);
            $("#usuario_convenio").val(data.usuario);
            $("#usuario_texto").val(data.usuariotexto);
            $("#convenio_rotulo").html(data.razaosocial);
            $("#existe_senha").val(data.existesenha);
            $("#C_Usuario").val(data.usuariotexto);

        }
    });
});
$(document).on('click','.btnbanco',function () {
    var cod_convenio = $(this).attr("id");
    $.ajax({
        url: "pages/convenio/convenio_bancos.php",
        method: "POST",
        data: $('#frmBanco').serialize()+'&codigo_convenio='+cod_convenio,
        dataType: "json",
        success:function (data2) {
            $("#frmBanco")[0].reset();
            $("#ModalBanco").modal("show");
            $("#C_Codigo_Convenio").val(cod_convenio);
            $("#C_Nome_Convenio").val(data2[0].nome_fantasia);

            if(data2[0].cod_banco != undefined){
                $('[name=C_Banco] option').filter(function() {
                    return ($(this).text() === data2[0].cod_banco);
                }).prop('selected', true);
                $("#C_Banco").val(data2[0].cod_banco).change();
            }else{
                $('#C_Banco option').removeAttr('selected').filter("[value='Escolha o banco']").attr('selected', true)
            }

            $("#C_Conta").val(data2[0].conta);

            $("#C_Agencia").val(data2[0].agencia);

            if(data2[0].cod_tipo != undefined){
                $('[name=C_Tipo_Conta] option').filter(function() {
                    return ($(this).text() === data2[0].cod_tipo);
                }).prop('selected', true);
                $("#C_Tipo_Conta").val(data2[0].cod_tipo).change();
            }else{
                $('#C_Tipo_Conta option').removeAttr('selected').filter("[value='Escolha o tipo conta']").attr('selected', true)
            }

            if(data2[0].id_chave_pix != undefined){
                $('[name=C_Tipo_Pix] option').filter(function() {
                    return ($(this).text() === data2[0].id_chave_pix);
                }).prop('selected', true);
                $("#C_Tipo_Pix").val(data2[0].id_chave_pix).change();
            }else{
                $('#C_Tipo_Pix option').removeAttr('selected').filter("[value='Escolha o tipo pix']").attr('selected', true)
            }

            $("#C_Chave_Pix").val(data2[0].chave_pix);

        }
    });
});
$("#btnsalvarsenha").click(function(event){
    event.preventDefault();
    var senha = $("#C_Senha").val();
    var confirmasenha = $("#C_Confirma_Senha").val();
    if(senha !== ""){
        if(confirmasenha !== ""){
            if(senha === confirmasenha){
                $.ajax({
                    url:"pages/convenio/convenio_salvar_senha.php",
                    method: "POST",
                    data: $('#frmSenha').serialize(),
                    success:function (data) {
                        if (data === "solicita_usuario"){
                            BootstrapDialog.show({
                                closable: false,
                                title: 'Atenção',
                                message: 'Informe o usuário!',
                                buttons: [{
                                    cssClass: 'btn-warning',
                                    label: 'Ok',
                                    action: function(dialogItself){
                                        dialogItself.close();
                                        $("#C_Usuario").focus();
                                    }
                                }]
                            });
                        }else if (data === "atualizado") {
                            Swal.fire({
                                title: "Parabens!",
                                text: "Senha atualizada com Sucesso !",
                                icon: "success",
                                showConfirmButton: false,
                                timer: 1500
                            });
                            $("#ModalSenha").modal('hide');
                        }else if(data === "cadastrado"){
                            Swal.fire({
                                title: "Parabens!",
                                text: "Senha cadastrada com Sucesso !",
                                icon: "success",
                                showConfirmButton: false,
                                timer: 1500
                            });
                            $("#ModalSenha").modal('hide');
                        }
                    }
                });
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
                            $("#C_Senha").focus();
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
                        $("#C_Confirma_Senha").focus();
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
                    $("#C_Senha").focus();
                }
            }]
        });

    }
});
$("#btnsalvarbanco").click(function(event){
    event.preventDefault();
    debugger;
    $.ajax({
        url:"pages/convenio/convenio_salvar_banco.php",
        method: "POST",
        data: $('#frmBanco').serialize(),
        success:function (data) {
            if (data === "atualizado") {
                Swal.fire({
                    title: "Parabens!",
                    text: "Senha atualizada com Sucesso !",
                    icon: "success",
                    showConfirmButton: false,
                    timer: 1500
                });
                $("#ModalBanco").modal('hide');
            }else if(data === "cadastrado"){
                Swal.fire({
                    title: "Parabens!",
                    text: "Senha cadastrada com Sucesso !",
                    icon: "success",
                    showConfirmButton: false,
                    timer: 1500
                });
                $("#ModalBanco").modal('hide');
            }
        }
    });
    
});
$(document).on('click','.btnvisualiza',function () {
    $("#row_mostra").hide();
    $("#row_nao_mostra").show();
    var cod_convenio = $(this).attr("id");
    $.ajax({
        url:"pages/convenio/convenio_exibe.php",
        method: "POST",
        data: {cod_convenio : cod_convenio},
        dataType: "json",
        success:function (data) {
            $.fn.modal.Constructor.prototype.enforceFocus = function() {};
            $("#ModalEditaConvemio").modal("show");

            $("#C_codigo").val(data.codigo);
            $("#C_razaosocial").val(data.razaosocial);
            $("#C_nomefantasia").val(data.nomefantasia);
            $("#C_numero").val(data.numero);
            $("#C_bairro").val(data.bairro);
            $('[name=C_uf] option').filter(function() {
                return ($(this).text() === data.uf);
            }).prop('selected', true);
            cidadex = data.cidade;
            cidadex = ucFirstAllWords(cidadex);
            $('[name=C_cidade] option').filter(function() {
                return ($(this).text() === cidadex);
            }).prop('selected', true);
            $("#C_endereco").val(data.endereco);
            $("#C_cep").val(data.cep);
            $("#C_tel1").val(data.telefone);
            $("#C_datacadastro").val(data.data_cadastro);
            $("#C_tel2").val(data.fax);
            $("#C_cel").val(data.cel);
            $("#C_celn").val(data.cel);
            $("#C_contato").val(data.contato);
            $("#C_contaton").val(data.contato);
            $("#C_prolabore").val("");
            $("#C_prolabore2").val("");
            $("#row_mostra").hide();
            $("#row_nao_mostra").show();
            $("#C_cnpj").val(data.cnpj);
            $("#C_cpf").val(data.cpf);
            $("#C_Inscestadual").val(data.insc);
            $("#C_categoria").val(data.categoria);
            $("#C_categoriarecibo").val(data.categoriarecibo);
            $("#C_registro").val(data.registro);
            $("#C_ativo").prop("checked", data.situacao);
            $("#C_divulga").prop("checked", data.divulga);
            $("#C_inscmunicipal").val(data.insc_mun);
            $("#C_email").val(data.email);
            $("#C_email2").val(data.email2);
            $("#C_tipo").val(data.tipo);
            $("#C_tipon").val(data.tipo);
            $("#C_tipon").prop('disabled', true);
            $("#C_tipoempresa").val(data.tipoempresa);
            $("#C_cobranca").prop("checked", data.cobranca);
            $("#C_desativado").prop("checked", data.desativado);
            $("#C_parc_ind").prop("checked", data.aceita_parce_individ);
            $('#C_parcelamento').val(data.parcelas);
            $('#operation').val("Update");
            $('#ModalEditaLabel').html("Convenio <small>Visualização</small>");
            $('#btnSalvar').hide();
        }
    });

    $.each($('#frmconvenio').serializeArray(), function(index, value){
        $('[name="' + value.name + '"]').attr('disabled', 'disabled');
    });
    $("#C_desativado").prop('disabled', true);
    $("#C_parc_ind").prop('disabled', true);

});
function validar(){

    var razaosocial  = $('#C_razaosocial').val();
    var nomefantasia = $('#C_nomefantasia').val();
    var endereco     = $('#C_endereco').val();
    var numero       = $('#C_numero').val();
    var bairro       = $('#C_bairro').val();
    var cidade       = $('#C_cidade').val();
    var uf           = $('#C_uf').val();

    if (razaosocial === ""){
        return $('#C_razaosocial').attr('name');
    }else if (nomefantasia === "") {
        return $('#C_nomefantasia').attr('name');
    }else if (endereco === "") {
        return $('#C_endereco').attr('name');
    }else if (numero === "") {
        return $('#C_numero').attr('name');
    }else if (bairro === "") {
        return $('#C_bairro').attr('name');
    }else if (cidade === "") {
        return $('#C_cidade').attr('name');
    }else if (uf === "") {
        return $('#C_uf').attr('name');
    }else{
        return "validou";
    }
}
function ucFirstAllWords( str )
{

    if(str !== undefined){
        strx = str;
        var pieces = strx.split(" ");
        for ( var i = 0; i < pieces.length; i++ )
        {
            var j = pieces[i].charAt(0).toUpperCase();
            pieces[i] = j + pieces[i].substr(1).toLowerCase();
        }
        return pieces.join(" ");
    }
}
$('#tabela_producao').on('change', 'tbody input.editor-divulga', function () {

    var controle    = $(this).prop('checked');//mostra se checked é true or false
    var codconvenio = $(this).closest('tr').find('td').eq(1).text();
    $.ajax({
        url: "pages/convenio/update_divulga.php",
        method: "POST",
        dataType: "json",
        async:false,
        data: {"codconvenio": codconvenio,"controle": controle},
        success: function (data) {

            if(data.resultado === "atualizado") {
                table.ajax.reload();
            }
            $("#ModalAtualizaResiduo").hide();
        }
    });
});
$('#tabela_producao').on('change', 'tbody input.editor-desativado', function () {

    var controle = $(this).prop('checked');//mostra se checked é true or false
    var codconvenio  = $(this).closest('tr').find('td').eq(1).text();
    $.ajax({
        url: "pages/convenio/update_desativado.php",
        method: "POST",
        dataType: "json",
        async:false,
        data: {"codconvenio": codconvenio,"controle": controle},
        success: function (data) {
            
            if(data.resultado === "atualizado") {
                table.ajax.reload();
            }
            $("#ModalAtualizaResiduo").hide();
        }
    });
});