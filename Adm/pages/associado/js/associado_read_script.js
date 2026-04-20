var usuario_global;
var usuario_cod;
var divisao;
var divisao_nome;
var table_associados;
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

    // Obter dados do sessionStorage
    divisao = sessionStorage.getItem("divisao");
    divisao_nome = sessionStorage.getItem("divisao_nome");
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");

    //$("#operation").val("Add");
    $('#C_telres').mask('(99)9999-9999');
    $('#C_telcom').mask('(99)9999-9999');
    $('#C_cel_assoc').mask('(99)99999-9999');
    C_cep_assoc.mask("99.999-999");
    $('#C_cpf_assoc').mask('999.999.999-99');
    
    // Habilitar colar (Ctrl+V) no campo CPF
    $('#C_cpf_assoc').on('paste', function(e) {
        var pastedData = e.originalEvent.clipboardData.getData('text');
        // Remove caracteres não numéricos
        var cleanCPF = pastedData.replace(/\D/g, '');
        // Aplica a máscara
        if (cleanCPF.length === 11) {
            var formattedCPF = cleanCPF.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            $(this).val(formattedCPF);
            e.preventDefault();
            
            // Copiar apenas os 13 últimos dígitos do CPF para Matricula RH ao cadastrar novo associado
            if ($('#operation').val() === "Add") {
                var ultimos13 = cleanCPF.slice(-13);
                $('#C_matricula_assoc').val(ultimos13);
            }
        }
    });
    
    // Copiar apenas os 13 últimos dígitos do CPF para Matricula RH ao digitar (apenas em novo cadastro)
    $('#C_cpf_assoc').on('blur', function() {
        // Apenas copia se estiver cadastrando novo associado
        if ($('#operation').val() === "Add") {
            var cpf = $(this).val();
            if (cpf && cpf !== '') {
                // Remove caracteres não numéricos do CPF
                var cpfLimpo = cpf.replace(/\D/g, '');
                // Pega apenas os 13 últimos dígitos e copia para o campo Matricula RH
                var ultimos13 = cpfLimpo.slice(-13);
                $('#C_matricula_assoc').val(ultimos13);
            }
        }
    });
    $('#C_nascimento').mask('99/99/9999');
    $('#C_datadesfiliacao').mask('99/99/9999');
    $("#C_salario").maskMoney({
        prefix: "",
        decimal: ",",
        thousands: "."
    });
    $("#C_limite_assoc").maskMoney({
        prefix: "",
        decimal: ",",
        thousands: "."
    });

    d = new Date();
    curr_date = d.getDate();
    curr_month = d.getMonth()+1;
    curr_year = d.getFullYear();
    curr_date = pad(curr_date,2)
    curr_month = pad(curr_month,2)

    // SEGURANÇA: Menu Associado deve usar SEMPRE a divisão do usuário logado
    // Busca a divisão real do usuário na base de dados, ignorando filtros de outros menus
    $.ajax({
        url: "pages/associado/get_usuario_divisao.php",
        method: "POST",
        dataType: "json",
        data: {
            'usuario_cod': usuario_cod,
            'divisao': divisao
        },
        async: false, // Síncrono para garantir que divisao seja definida antes de continuar
        success: function(data) {
            if(data.success) {
                divisao = data.divisao.toString();
                console.log("Divisão do usuário carregada:", divisao);
            } else {
                divisao = divisao || "2"; // Usa divisão do sessionStorage ou padrão 2
                console.warn("Erro ao carregar divisão do usuário:", data.error);
            }
        },
        error: function() {
            divisao = divisao || "2"; // Usa divisão do sessionStorage ou padrão 2
            console.error("Erro na requisição para obter divisão do usuário");
        }
    });
    divisao_nome = sessionStorage.getItem("divisao_nome");
  

    $('#divisao').val(divisao);
    var detailRows = [];
    //$("#frmSenha_assoc")[0].reset();
    // Carregar dados em paralelo para melhor performance
    var requests = [
        $.getJSON("pages/associado/associado_situacao.php").fail(function() {
            console.warn('Erro ao carregar situações');
        }),
        $.getJSON("pages/associado/associado_tipos.php").fail(function() {
            console.warn('Erro ao carregar tipos');
        }),
        $.getJSON("pages/associado/associado_empregador.php", {"divisaox": divisao}).fail(function() {
            console.warn('Erro ao carregar empregadores');
        }),
        $.getJSON("pages/associado/associado_funcao.php").fail(function() {
            console.warn('Erro ao carregar funções');
        })
    ];

    // Processar respostas quando disponíveis
    requests[0].done(function(data) {
        $.each(data, function (index, value) {
            $('#C_situacao_assoc').append('<option value="' + value.codigo + '">' + value.nome + '</option>');
        });
    });

    requests[1].done(function(data) {
        $.each(data, function (index, value) {
            $('#C_tipo_assoc').append('<option value="' + value.id_tipo_associado + '">' + value.nome + '</option>');
        });
    });

    requests[2].done(function(data) {
        $('#C_empregador_assoc').empty().append('<option value="">Selecione um empregador</option>');
        $.each(data, function (index, value) {
            $('#C_empregador_assoc').append('<option value="' + value.id + '">' + value.nome + '</option>');
        });
        
        // Inicializar Select2 após carregar os dados
        setTimeout(function() {
            if (typeof $.fn.select2 !== 'undefined') {
                $('#C_empregador_assoc').select2({
                    placeholder: 'Selecione um empregador',
                    allowClear: true,
                    width: '100%'
                });
            }
        }, 50);
    });

    requests[3].done(function(data) {
        $.each(data, function (index, value) {
            $('#C_funcao').append('<option value="' + value.id + '">' + value.nome + '</option>');
        });
    });
    // Carregar secretarias com tratamento de erro
    $.getJSON("pages/associado/secretarias.php")
        .done(function(data) {
            $('#C_secretaria').append('<option value="0">Não definido</option>');
            $.each(data, function (index, value) {
                $('#C_secretaria').append('<option value="' + value.id_secretaria + '">' + value.nome_secretaria + '</option>');
            });
        })
        .fail(function() {
            console.warn('Erro ao carregar secretarias');
            $('#C_secretaria').append('<option value="0">Não definido</option>');
        });
    $('#tabela_producao_assoc tfoot th').each( function () {
        var title = $(this).text();
        if(title !== ""){
            $(this).html( '<input type="text" class="small" placeholder="Busca '+title+'" />' );
        }
    } );
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");
    
    // Inicializar tabela básica primeiro
    if (!$.fn.dataTable.isDataTable('#tabela_producao_assoc')) {
        table_associados = $('#tabela_producao_assoc').DataTable({
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            "processing": true,
            "serverSide": false,
            "paging": true,
            "deferRender": true,
            "data": [], // Vazio inicialmente
            "columns": [
                { "class":"details-control", "orderable":false, "data":null, "defaultContent": "" },
                { "data": "codigo" },
                { "data": "nome" },
                { "data": "endereco" },
                { "data": "bairro" },
                { "data": "nascimento" },
                { "data": "abreviacao" },
                { "data": "id_empregador" },
                { "data": "nome_situacao" },
                { "data": "botao" },
                { "data": "botaosenha" },
                { "data": "botaoexcluir" }
            ],
            "columnDefs": [
                { "targets": [ 7 ], "visible": false, "searchable": true }
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
    
  
    filtra_associado(0,divisao);// filtra todos
    
    if(usuario_cod === "13"){ // 13 == isabelle
        $('#C_limite_assoc').prop( "disabled", true );
        $('#C_salario').prop( "disabled", true );
        
    }else{
        $('#C_limite_assoc').prop( "disabled", false );
        $('#C_salario').prop( "disabled", false );
    } 
    $('#tabela_producao_assoc tbody').on('click', 'tr', function () {
        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
        } else {
            table_associados.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
        }
    });
    // Add event listener for opening and closing details
    $('#tabela_producao_assoc tbody').on( 'click', 'tr td.details-control', function () {

        var tr = $(this).closest('tr');
        var row = table_associados.row( tr );
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
    function limpa_formulario_cep() {
        // Limpa valores do formulário de cep.
        $("#C_nome_assoc").val("");
        $("#C_bairro_assoc").val("");
        $("#C_cidade_assoc").val("");
        $("#C_uf_assoc").val("");
    }
    //Quando o campo cep perde o foco.
    $("#C_cep_assoc").blur(function() {
        //Nova variável "cep" somente com dígitos.
        var cep = $(this).val().replace(/\D/g, '');

        //Verifica se campo cep possui valor informado.
        if (cep !== "") {

            //Expressão regular para validar o CEP.
            var validacep = /^[0-9]{8}$/;

            //Valida o formato do CEP.
            if(validacep.test(cep)) {

                //Preenche os campos com "..." enquanto consulta webservice.
                //$("#C_nome_assoc").val("");
                //$("#C_bairro_assoc").val("");
                //$("#C_cidade_assoc").val("");
                //$("#C_uf_assoc").val("");
                //$("#ibge").val("...");

                //Consulta o webservice viacep.com.br/
                $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {

                    if (!("erro" in dados)) {
                        //Atualiza os campos com os valores da consulta.
                        $("#C_uf_assoc").val(dados.uf).change();
                        $("#C_endereco_assoc").val(dados.logradouro);
                        $("#C_bairro_assoc").val(dados.bairro);
                        $("#C_cidade_assoc").val(dados.localidade);
                        validar();
                        //$("#ibge").val(dados.ibge);
                    } //end if.
                    else {
                        //CEP pesquisado não foi encontrado.
                        //limpa_formulario_cep();
                        alert("CEP não encontrado.");
                    }
                });
            } //end if.
            else {
                //cep é inválido.
                //limpa_formulario_cep();
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
        $("#C_uf_assoc").html(options);
        $('#C_uf_assoc').val($('#C_uf_assoc option').eq(11).val());

        $("#C_uf_assoc").change(function () {

            var options_cidades = '';
            var str = "";

            $("#C_uf_assoc option:selected").each(function () {
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
            $("#C_cidade_assoc").html(options_cidades);
        }).change();
    });
    $.getJSON( "../Adm/pages/conta/meses_conta.php",{ "origem": "ultimo_mes", "divisao": divisao }, function( data ) {
        $('#C_ultimo_mes').append('<option value="todos">---</option>');
       
        $.each(data, function (index, value) {
            if (value.abreviacao !== undefined){
                $('#C_ultimo_mes').append('<option value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
            }
        });
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
$(document).on('click','.update_assoc',function () {
   
    var cod_associado = $(this).attr("id");
    var tdobj = $(this).closest('tr').find('td');
    var empregador = table_associados.row($(this).parents('tr')).data()["id_empregador"];

    $("#rotulo_associado").html("Alterando");
    $.ajax({
        url: "pages/associado/associado_exibe.php",
        method: "POST",
        data: {cod_associado : cod_associado, empregador: empregador},
        dataType: "json",
        success:function (data) {
            $.fn.modal.Constructor.prototype.enforceFocus = function() {};
            $("#ModalEdita").modal("show");
            
            // Tornar Matrícula RH somente leitura ao editar
            $("#C_matricula_assoc").prop('readonly', true);
            
            $("#C_nome_assoc").val(data.nome);
            $("#C_endereco_assoc").val(data.endereco);
            if(data.data_filiacao){
                $("#C_datacadastro_assoc").val(data.data_filiacao);
            }else{
                $("#C_datacadastro_assoc").val(data.data_filiacao);
            }
            $("#C_complemento_assoc").val(data.complemento);
            $("#C_bairro_assoc").val(data.bairro);
            $("#C_numero_assoc").val(data.numero);
            $("#C_cpf_assoc").val(data.cpf);
            $("#C_rg_assoc").val(data.rg);
            $('[name=C_uf_assoc] option').filter(function() {
                return ($(this).text() === data.uf);
            }).prop('selected', true);
            $("#C_uf_assoc").val(data.uf).change();
            cidadex = data.cidade;
            cidadex = ucFirstAllWords(cidadex);
            $('[name=C_cidade_assoc] option').filter(function() {
                return ($(this).text() === cidadex);
            }).prop('selected', true);
            C_cep_assoc.val(data.cep);
            $("#C_telres").val(data.telres);
            $("#C_telcom").val(data.telcom);
            $("#C_cel_assoc").val(data.cel);
            $("#C_nascimento").val(data.nascimento);
            $("#C_salario").val(numeroParaMoeda(data.salario));
            $("#C_limite_assoc").val(numeroParaMoeda(data.limite));
            $("#C_limite_hidden").val(numeroParaMoeda(data.limite));
            $("#C_situacao_assoc").val(data.id_situacao);
            $("#C_situacao_original").val(data.id_situacao);
            $("#C_tipo_assoc").val(data.tipo);
            $("#C_tipo_original").val(data.tipo);
            // Definir valor do empregador (compatível com Select2)
            $("#C_empregador_assoc").val(data.empregador);
            
            // Se Select2 estiver ativo, usar método específico
            if ($("#C_empregador_assoc").hasClass('select2-hidden-accessible')) {
                $("#C_empregador_assoc").val(data.empregador).trigger('change');
                console.log('✅ Empregador definido via Select2:', data.empregador);
            } else {
                $("#C_empregador_assoc").val(data.empregador);
                console.log('✅ Empregador definido via select normal:', data.empregador);
            }
            
            $("#C_empregador_original").val(data.empregador);
            $("#C_funcao").val(data.codfuncao);
            $("#C_funcao_original").val(data.codfuncao);
            $("#C_Email_assoc").val(data.email);
            $("#C_parcelas_permitidas").val(data.parcelas_permitidas);
            $("#C_datadesfiliacao").val(data.data_desfiliacao);
            $("#C_obs").val(data.obs);
            $("#C_filiado").prop("checked", data.filiado);
            $("#SwitchCelular").prop("checked", data.celwatzap);
            $("#C_tem_cadastro_conta").val(data.tem_cadastro_conta);
            $("#C_secretaria").val(data.id_secretaria);
            $("#C_local").val(data.localizacao);
            //if(data.tem_cadastro_conta === true){
            //    $("#C_matricula_assoc").attr('disabled', 'true');
            //}else{
            //    $("#C_matricula_assoc").removeAttr('disabled');
            //}
            $("#C_matricula_assoc").val(data.codigo);
            $('#C_matricula_original').val(data.codigo);
           
       
            $("#C_ultimo_mes").val(data.ultimo_mes);
   
            $('#operation').val("Update");


            $('#frmassociado').validator('validate');
        }
    });
});
$("#btnInserir").click(function(){
    $("#frmassociado")[0].reset();
    $("#rotulo_associado").html("Cadastrando");
    $("#C_empregador_assoc").val(0);
    $("#C_empregador_original").val("0");
    $.fn.modal.Constructor.prototype.enforceFocus = function() {};
    $("#ModalEdita").modal("show");
    $('#operation').val("Add");
    var d = new Date().toLocaleString("pt-BR", {timeZone: "America/Sao_Paulo"});
    var d2 = d.substring(0,10);
    $('#C_datacadastro_assoc').val(d2);
    $('#C_uf_assoc').val($('#C_uf_assoc option').eq(11).val());
    $('#C_cidade_assoc').val($('#C_cidade_assoc option').eq(835).val());
    // Tornar Matrícula RH somente leitura ao cadastrar
    $("#C_matricula_assoc").prop('readonly', true);
});
$("#btnSalvar").click(function(event){
   waitingDialog.show('Gravando, aguarde ...');
   
   $("#btnSalvar").attr("disabled", true);
   $('#frmassociado').validator('validate');
   var campo_vazio = validar();
   if (campo_vazio === "validou") {

       if( $('#operation').val() === "Add") {
           $.ajax({
               url: "pages/associado/associado_verifica_repitido.php?divisao="+divisao,
               method: "POST",
               data: $('#frmassociado').serialize()+'&divisao='+divisao,
               dataType: "json",
               async: false,
               success: function (data) {
                    
                   if (data.resultado === "nao repitido") {

                       $.ajax({
                           url: "pages/associado/associado_salvar.php",
                           method: "POST",
                           data: $('#frmassociado').serialize()+'&divisao='+divisao+'&usuario_cod='+usuario_cod,
                           success: function (data) {
                               $("#frmassociado")[0].reset();
                               if (data === "atualizado") {
                                   Swal.fire({
                                       title: "Parabens!",
                                       text: "Associado atualizado com sucesso !",
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
                               $("#frmassociado")[0].reset();
                               $("#btnSalvar").attr("disabled", false);
                               waitingDialog.hide();
                               $("#ModalEdita").modal('hide');
                               table_associados.ajax.reload();
                           }
                       });
                   } else if (data.resultado === "repitido") {
                       BootstrapDialog.show({
                           closable: false,
                           title: 'Atenção',
                           message: 'A matricula : '+$("#C_matricula_assoc").val()+' já existe no empregador : '+$( "#C_empregador_assoc option:selected" ).text()+'.',
                           buttons: [{
                               cssClass: 'btn-warning',
                               label: 'Ok',
                               action: function (dialogItself) {
                                   dialogItself.close();
                                   $("#C_Senha_assoc").focus();
                               }
                           }]
                       });
                       $("#btnSalvar").attr("disabled", false);
                       waitingDialog.hide();
                   }
               }
           });
       }else{
           $.ajax({
               url: "pages/associado/associado_verifica_repitido.php",
               method: "POST",
               data: $('#frmassociado').serialize(),
               dataType: "json",
               success: function (data) {
                   if (data.resultado === "nao repitido") {
                       $.ajax({
                           url: "pages/associado/associado_salvar.php",
                           method: "POST",
                           data: $('#frmassociado').serialize()+'&divisao='+divisao+'&usuario_cod='+usuario_cod,
                           success: function (data) {
                               $("#frmassociado")[0].reset();
                               if (data === "atualizado") {
                                   Swal.fire({
                                       title: "Parabens!",
                                       text: "Associado atualizado com sucesso !",
                                       icon: "success",
                                       timer: 3000
                                   });
                               } else if (data === "cadastrado") {
                                   Swal.fire({
                                       title: "Parabens!",
                                       text: "Associado cadastrado com sucesso !",
                                       icon: "success",
                                   });
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
                                               //$("#C_Senha_assoc").focus();
                                           }
                                       }]
                                   });
                               } else {

                                   BootstrapDialog.show({
                                       closable: false,
                                       title: 'Atenção',
                                       message: 'Algum problema ocorreu na atualização, comunique o administrador.',
                                       buttons: [{
                                           cssClass: 'btn-danger',
                                           label: 'Ok',
                                           action: function (dialogItself) {
                                               dialogItself.close();
                                               //$("#C_Senha_assoc").focus();
                                           }
                                       }]
                                   });
                               }
                               $("#frmassociado")[0].reset();
                               $("#btnSalvar").attr("disabled", false);
                               waitingDialog.hide();
                               $("#ModalEdita").modal('hide');
                               table_associados.ajax.reload();
                           },
                           error: function (request, status, erro) {
                               alert("Problema ocorrido: " + status + "\nDescição: " + erro);
                               //Abaixo está listando os header do conteudo que você requisitou, só para confirmar se você setou os header e dataType corretos
                               alert("Informações da requisição: \n" + request.getAllResponseHeaders());
                               $("#btnSalvar").attr("disabled", false);
                               waitingDialog.hide();
                           }
                       });
                   } else if (data.resultado === "repitido") {
                       BootstrapDialog.show({
                           closable: false,
                           title: 'Atenção',
                           message: 'A matricula : '+$("#C_matricula_assoc").val()+' já existe no empregador : '+$( "#C_empregador_assoc option:selected" ).text()+'.',
                           buttons: [{
                               cssClass: 'btn-warning',
                               label: 'Ok',
                               action: function (dialogItself) {
                                   dialogItself.close();
                                   $("#C_Senha_assoc").focus();
                               }
                           }]
                       });
                       $("#btnSalvar").attr("disabled", false);
                       waitingDialog.hide();
                   }
               }
           });
       }
   }else {
       var nome_campo;
       switch (campo_vazio) {
           case 'C_nome_assoc':
               nome_campo = "Nome";
               break;
           case 'C_matricula_assoc':
               nome_campo = "Matricula";
               break;
           case 'C_nome_assoc':
               nome_campo = "Endereço";
               break;
           case 'C_numero_assoc':
               nome_campo = "Numero";
               break;
           case 'C_bairro_assoc':
               nome_campo = "Bairro";
               break;
           case 'C_cidade_assoc':
               nome_campo = "Cidade";
               break;
           case 'C_uf_assoc':
               nome_campo = "uf";
               break;
           case 'C_nascimento':
               nome_campo = "Data de Nascimento";
               break;
           case 'C_salario':
               nome_campo = "Salário";
               break;
           case 'C_limite_assoc':
               nome_campo = "Limite";
               break;
           case 'C_cpf_assoc':
               nome_campo = "CPF";
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
                   $("#btnSalvar").attr("disabled", false);
                   waitingDialog.hide();
               }
           }]
       });
   }
   table_associados.columns.adjust().draw();
});
$('#tabela_producao_assoc').on('click', 'tbody .btnsenha_assoc', function () {

    var data_row = table_associados.row($(this).closest('tr')).data();
    var cod_associado = data_row.codigo;
    var id_empregador = data_row.id_empregador;
    var id_associado = data_row.id;
    $("#frmSenha_assoc")[0].reset();
    $("#senha_gerada_display").val("");
    $("#ModalSenha").modal("show");
    $.ajax({
        url: "pages/associado/associado_exibe_usuario.php",
        method: "POST",
        data: {cod_associado: cod_associado, id_empregador: id_empregador, id_associado: id_associado},
        dataType: "json",
        success: function (data) {

            $("#cod_associado_senha").val(data.matricula);
            $("#id_associado").val(id_associado);
            $("#senha_associado").val(data.senha);
            $("#associado_rotulo").html(data.nome);
            $("#existe_senha").val(data.existesenha);
            $("#id_empregador_senha").val(id_empregador);
            $("#id_divisao_senha").val(data.id_divisao);
            
            // Exibir mensagem de status da senha
            var statusMsg = $("#status_senha_msg");
            if (data.existesenha == "1" || data.senha) {
                // Senha JÁ DEFINIDA - Limpar campos
                $("#C_Senha_assoc").val("");
                $("#C_Confirma_Senha_assoc").val("");
                
                statusMsg.html('<i class="glyphicon glyphicon-ok-circle"></i> A senha deste associado já está definida.');
                statusMsg.css({
                    'background-color': '#d4edda',
                    'color': '#155724',
                    'border': '1px solid #c3e6cb'
                });
            } else {
                // Senha NÃO DEFINIDA - Manter campos vazios
                $("#C_Senha_assoc").val("");
                $("#C_Confirma_Senha_assoc").val("");
                
                statusMsg.html('<i class="glyphicon glyphicon-exclamation-sign"></i> A senha deste associado ainda não foi definida.');
                statusMsg.css({
                    'background-color': '#fff3cd',
                    'color': '#856404',
                    'border': '1px solid #ffeaa7'
                });
            }
        }
    })
 });

// Função para gerar senha aleatória de 6 dígitos
$("#btn_gerar_senha").click(function() {
    var senhaAleatoria = '';
    for (var i = 0; i < 6; i++) {
        senhaAleatoria += Math.floor(Math.random() * 10);
    }
    
    // Exibir a senha gerada
    $("#senha_gerada_display").val(senhaAleatoria);
    
    // Preencher os campos de senha e confirmação
    $("#C_Senha_assoc").val(senhaAleatoria);
    $("#C_Confirma_Senha_assoc").val(senhaAleatoria);
    
    // Feedback visual
    $("#senha_gerada_display").css('color', '#28a745');
    setTimeout(function() {
        $("#senha_gerada_display").css('color', '#000');
    }, 1000);
});

// Função para copiar senha para área de transferência
$("#btn_copiar_senha").click(function() {
    var senhaGerada = $("#senha_gerada_display").val();
    
    if (senhaGerada && senhaGerada !== "" && senhaGerada !== "Clique no botão para gerar") {
        // Selecionar o input
        var inputSenha = document.getElementById("senha_gerada_display");
        inputSenha.select();
        inputSenha.setSelectionRange(0, 99999); // Para dispositivos móveis
        
        // Copiar usando execCommand
        try {
            var copiado = document.execCommand('copy');
            
            if (copiado) {
                // Feedback visual
                var btnCopiar = $("#btn_copiar_senha");
                var iconeCopiar = btnCopiar.find("i");
                
                // Mudar ícone e cor temporariamente
                iconeCopiar.removeClass("glyphicon-copy").addClass("glyphicon-ok");
                btnCopiar.css('background-color', '#28a745');
                
                // Mostrar tooltip ou mensagem
                Swal.fire({
                    title: 'Copiado!',
                    text: 'Senha ' + senhaGerada + ' copiada!',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
                
                // Restaurar ícone e cor após 2 segundos
                setTimeout(function() {
                    iconeCopiar.removeClass("glyphicon-ok").addClass("glyphicon-copy");
                    btnCopiar.css('background-color', '#17a2b8');
                }, 2000);
                
                // Remover seleção
                window.getSelection().removeAllRanges();
            }
        } catch (err) {
            Swal.fire({
                title: 'Erro!',
                text: 'Não foi possível copiar a senha',
                icon: 'error',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    } else {
        Swal.fire({
            title: 'Atenção!',
            text: 'Nenhuma senha foi gerada ainda',
            icon: 'warning',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }
});
$('#tabela_producao_assoc').on('click', 'tbody .btnexcluir', function () {

    var $button = $(this);
    var $row = $button.closest('tr');
    var data_row = table_associados.row($row).data();
    var cod_associado = data_row.codigo;
    var nome_associado = data_row.nome;
    var empregador = data_row.abreviacao;
    var id_empregador = data_row.id_empregador;
    var id_associado = data_row.id;
    var id_divisao = data_row.id_divisao;
    
    $.ajax({
        url: "pages/associado/associado_valid_excluir.php",
        method: "POST",
        dataType: "json",
        data: {
            "cod_associado": cod_associado, 
            "id_empregador": id_empregador, 
            "id_associado": id_associado, 
            "divisao": id_divisao
        },
        success: function (data) {

            if (data.Resultado === "pode excluir") {
                Swal.fire({
                    title: 'Confirma a exclusão do associado?',
                    html: '<table style="width: 100%; margin-top: 15px; table-layout: fixed;">' +
                        '<tr><th style="text-align: right; padding: 8px; background-color: #dddddd; width: 35%;">MATRÍCULA:</th>' +
                        '<th style="background-color: #dddddd; padding: 8px; word-break: break-word;"><b>' + cod_associado + '</b></th></tr>' +
                        '<tr><th style="text-align: right; padding: 8px; width: 35%;">NOME:</th>' +
                        '<th style="padding: 8px; word-break: break-word;"><b>' + nome_associado + '</b></th></tr>' +
                        '<tr><th style="text-align: right; padding: 8px; background-color: #dddddd; width: 35%;">EMPREGADOR:</th>' +
                        '<th style="background-color: #dddddd; padding: 8px; word-break: break-word;"><b>' + empregador + '</b></th></tr>' +
                        '</table>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#ffc107',
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Não',
                    allowOutsideClick: false,
                    width: '500px',
                    focusCancel: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Excluindo...',
                            text: 'Aguarde, processando exclusão',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        $.ajax({
                            url: "pages/associado/associado_excluir.php",
                            method: "POST",
                            dataType: "json",
                            data: {
                                "cod_associado": cod_associado, 
                                "id_empregador": id_empregador,
                                "id_associado": id_associado,
                                "divisao": id_divisao
                            },
                            success: function (data) {
                                if (data.Resultado === "excluido") {
                                    // Remove a linha da tabela
                                    table_associados.row($row).remove().draw();
                                    
                                    Swal.fire({
                                        title: 'Sucesso!',
                                        text: 'Associado excluído com sucesso!',
                                        icon: 'success',
                                        confirmButtonColor: '#28a745',
                                        confirmButtonText: 'Ok'
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Erro!',
                                        text: 'Não foi possível excluir o associado.',
                                        icon: 'error',
                                        confirmButtonColor: '#dc3545',
                                        confirmButtonText: 'Ok'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Erro!',
                                    text: 'Erro ao processar a exclusão.',
                                    icon: 'error',
                                    confirmButtonColor: '#dc3545',
                                    confirmButtonText: 'Ok'
                                });
                            }
                        });
                    }
                });
            } else if (data.Resultado === "existe impedimento") {
                Swal.fire({
                    title: 'Não é possível excluir!',
                    html: '<p>Existem registros vinculados a este associado:</p>' +
                          '<p style="color: #dc3545; font-weight: bold; margin-top: 10px;">' + data.Motivos + '</p>',
                    icon: 'error',
                    confirmButtonColor: '#ffc107',
                    confirmButtonText: 'Ok'
                });
            }
        },
        error: function() {
            Swal.fire({
                title: 'Erro!',
                text: 'Erro ao verificar dados do associado.',
                icon: 'error',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Ok'
            });
        }
    });
});
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
                        debugger;
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
    // Função para formatar valores monetários em Real brasileiro
    function formatarMoeda(valor) {
        if (!valor || valor === '' || valor === null || valor === undefined) {
            return 'R$ 0,00';
        }
        
        // Converter para número se for string
        var numero = typeof valor === 'string' ? parseFloat(valor.replace(/[^0-9.,]/g, '').replace(',', '.')) : parseFloat(valor);
        
        if (isNaN(numero)) {
            return 'R$ 0,00';
        }
        
        return numero.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    // Função para formatar telefone
    function formatarTelefone(telefone) {
        if (!telefone || telefone === '' || telefone === null) {
            return 'Não informado';
        }
        
        // Remove caracteres não numéricos
        var numeros = telefone.toString().replace(/\D/g, '');
        
        if (numeros.length === 11) {
            return numeros.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        } else if (numeros.length === 10) {
            return numeros.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        }
        
        return telefone;
    }
    
    // Função para formatar CPF
    function formatarCPF(cpf) {
        if (!cpf || cpf === '' || cpf === null) {
            return 'Não informado';
        }
        
        var numeros = cpf.toString().replace(/\D/g, '');
        
        if (numeros.length === 11) {
            return numeros.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }
        
        return cpf;
    }
    
    // Função para formatar CEP
    function formatarCEP(cep) {
        if (!cep || cep === '' || cep === null) {
            return 'Não informado';
        }
        
        var numeros = cep.toString().replace(/\D/g, '');
        
        if (numeros.length === 8) {
            return numeros.replace(/(\d{5})(\d{3})/, '$1-$2');
        }
        
        return cep;
    }
    
    return '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 20px; border-radius: 8px; margin: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">'+
           '<div style="border-bottom: 2px solid #007bff; margin-bottom: 15px; padding-bottom: 8px;">'+
           '<h6 style="color: #007bff; font-weight: bold; margin: 0; font-size: 14px;">'+
           '<i class="glyphicon glyphicon-user" style="margin-right: 8px;"></i>Detalhes do Associado'+
           '</h6>'+
           '</div>'+
           
           '<div style="display: flex; flex-wrap: wrap; gap: 15px;">'+
           
           '<!-- Seção Financeira -->'+
           '<div style="flex: 1; min-width: 200px; background: #fff; padding: 12px; border-radius: 6px; border-left: 4px solid #28a745;">'+
           '<h6 style="color: #28a745; font-weight: bold; margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase;">💰 Informações Financeiras</h6>'+
           '<div style="margin-bottom: 8px;">'+
           '<span style="font-weight: bold; color: #495057; font-size: 11px;">Salário:</span><br>'+
           '<span style="color: #28a745; font-weight: bold; font-size: 13px; text-align: right; display: block;">'+formatarMoeda(d.salario)+'</span>'+
           '</div>'+
           '<div>'+
           '<span style="font-weight: bold; color: #495057; font-size: 11px;">Limite:</span><br>'+
           '<span style="color: #007bff; font-weight: bold; font-size: 13px; text-align: right; display: block;">'+formatarMoeda(d.limite)+'</span>'+
           '</div>'+
           '</div>'+
           
           '<!-- Seção Endereço -->'+
           '<div style="flex: 1; min-width: 200px; background: #fff; padding: 12px; border-radius: 6px; border-left: 4px solid #17a2b8;">'+
           '<h6 style="color: #17a2b8; font-weight: bold; margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase;">📍 Endereço</h6>'+
           '<div style="margin-bottom: 8px;">'+
           '<span style="font-weight: bold; color: #495057; font-size: 11px;">CEP:</span><br>'+
           '<span style="color: #495057; font-size: 12px; font-family: monospace;">'+formatarCEP(d.cep)+'</span>'+
           '</div>'+
           '<div>'+
           '<span style="font-weight: bold; color: #495057; font-size: 11px;">Complemento:</span><br>'+
           '<span style="color: #495057; font-size: 12px;">'+(d.complemento || 'Não informado')+'</span>'+
           '</div>'+
           '</div>'+
           
           '<!-- Seção Contatos -->'+
           '<div style="flex: 1; min-width: 250px; background: #fff; padding: 12px; border-radius: 6px; border-left: 4px solid #ffc107;">'+
           '<h6 style="color: #ffc107; font-weight: bold; margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase;">📞 Contatos</h6>'+
           '<div style="margin-bottom: 6px;">'+
           '<span style="font-weight: bold; color: #495057; font-size: 11px;">Comercial:</span> '+
           '<span style="color: #495057; font-size: 12px;">'+formatarTelefone(d.telcom)+'</span>'+
           '</div>'+
           '<div>'+
           '<span style="font-weight: bold; color: #495057; font-size: 11px;">Residencial:</span> '+
           '<span style="color: #495057; font-size: 12px; font-weight: bold;">'+formatarTelefone(d.telres)+'</span>'+
           '</div>'+
           '</div>'+
           
           '<!-- Seção Documentos -->'+
           '<div style="flex: 1; min-width: 200px; background: #fff; padding: 12px; border-radius: 6px; border-left: 4px solid #6f42c1;">'+
           '<h6 style="color: #6f42c1; font-weight: bold; margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase;">📄 Documentos</h6>'+
           '<div style="margin-bottom: 8px;">'+
           '<span style="font-weight: bold; color: #495057; font-size: 11px;">CPF:</span><br>'+
           '<span style="color: #495057; font-size: 12px; font-family: monospace;">'+formatarCPF(d.cpf)+'</span>'+
           '</div>'+
           '<div>'+
           '<span style="font-weight: bold; color: #495057; font-size: 11px;">RG:</span><br>'+
           '<span style="color: #495057; font-size: 12px; font-family: monospace;">'+(d.rg || 'Não informado')+'</span>'+
           '</div>'+
           '</div>'+
           
           '</div>'+
           '</div>';
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
    var cpf        = $('#C_cpf_assoc').val();
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
    }else if (cpf === "") {
        return $('#C_cpf_assoc').attr('name');
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
    filtra_associado(cod_situacao,divisao);
});
$('#RadioFiliados').change(function(){
    cod_situacao = $('#RadioFiliados').val();
    filtra_associado(cod_situacao,divisao);// filtra filiados
});
$('#RadioDesfiliados').change(function(){
    cod_situacao = $('#RadioDesfiliados').val();
    filtra_associado(cod_situacao,divisao);// filtra desfiliados
});
$('#RadioFalecidos').change(function(){
    cod_situacao = $('#RadioFalecidos').val();
    filtra_associado(cod_situacao,divisao);// filtra falecidos
});

function filtra_associado(codigo,divisao){
    if ($.fn.dataTable.isDataTable('#tabela_producao_assoc')) {
        $('#tabela_producao_assoc').DataTable().destroy();
        $('#tabela_producao_assoc').find('tbody').empty();
    }
    table_associados = $('#tabela_producao_assoc').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": true,
        "serverSide": true,
        "paging": true,
        "deferRender": true,
        "ajax": {
            "url": 'pages/associado/associado_read2.php',
            "method": 'POST',
            "data":  { 'usuario_global': usuario_global, 'divisao': divisao, 'usuario_cod': usuario_cod, 'cod_situacao': codigo },
            "dataType": 'json'
        },
        "order": [[ 2, "asc" ]],
        "columns": [
            {
                "class":"details-control",
                "orderable":false,
                "data":null,
                "defaultContent": ""
            },
            { "data": "codigo" },
            { "data": "nome" },
            { "data": "endereco" },
            { "data": "bairro" },
            { "data": "nascimento" },
            { "data": "abreviacao" },
            { "data": "id_empregador" },
            { "data": "nome_situacao" },
            { "data": "botao" },
            { "data": "botaosenha" },
            { "data": "botaoexcluir" },
            { "data": "id" }
        ],
        "createdRow": function(row, aData, dataIndex ) {
           
            if (aData['nome_situacao'] === "ATIVO") {
                $(row).addClass("green");
            } else if (aData['nome_situacao'] === "DESFILIADO") {
                $(row).addClass("red");
            } else if (aData['nome_situacao'] === "FALECIDO") {
                $(row).addClass("black");
            }
        },
        "columnDefs": [
            {
                "targets": [ 7 ],
                "visible": false,
                "searchable": true,
            },
            {
                "targets": [ 12 ],
                "visible": false,
                "searchable": true,
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
function filtra_associado_sind(codigo,divisao){
    if ($.fn.dataTable.isDataTable('#tabela_producao_assoc')) {
        $('#tabela_producao_assoc').DataTable().destroy();
        $('#tabela_producao_assoc').find('tbody').empty();
    }
    table_associados = $('#tabela_producao_assoc').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": true,
        "serverSide": false,
        "paging": true,
        "deferRender": true,
        "deferLoading": true,
        "language": {
            "processing": "Carregando associados... Por favor, aguarde.",
            "loadingRecords": "Carregando registros...",
            "zeroRecords": "Nenhum associado encontrado",
            "emptyTable": "Nenhum dado disponível na tabela",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ associados",
            "infoEmpty": "Mostrando 0 a 0 de 0 associados",
            "paginate": {
                "next": "Próximo",
                "previous": "Anterior",
                "first": "Primeiro",
                "last": "Último"
            },
            "search": "Pesquisar",
            "lengthMenu": "_MENU_ resultados por página"
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