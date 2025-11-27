var table;
var usuarioglobal;
var table_associados;
var cidadex;
var strx="";
// Array para armazenar especialidades temporárias durante cadastro
var especialidadesTemporarias = [];
// Array para armazenar todos os profissionais carregados
var todosProfissionais = [];
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
    var divisao = sessionStorage.getItem("divisao");
    var divisao_nome = sessionStorage.getItem("divisao_nome");
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


    $("#btnInserir").show();
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
    // Load divisions that have convenios
    function loadFiltroDivisao() {
        $('#filtro_divisao').empty().append('<option value="">Todas as Regiões</option>');
        $.getJSON( "pages/convenio/convenio_divisoes.php", function( data ) {
            $.each(data, function (index, value) {
                $('#filtro_divisao').append('<option value="' + value.id_divisao + '">' + value.nome + ' - ' + value.cidade + '</option>');
            });
            
            // After loading divisions, set the selected value from sessionStorage
            var savedDivisao = sessionStorage.getItem("divisao");
            if (savedDivisao && savedDivisao !== '') {
                $('#filtro_divisao').val(savedDivisao);
                console.log('Loaded division filter from sessionStorage:', savedDivisao);
            }
        });
    }
    
    loadFiltroDivisao();
    
    // econstroi uma datatabe no primeiro carregamento da tela
    table = $('#tabela_producao').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": true,
        "serverSide": false,
        "responsive": false,
        "autoWidth": false,
        "deferRender": true,
        "destroy": true,
        "language": {
            "processing": "Carregando dados... Por favor, aguarde.",
            "loadingRecords": "Carregando registros...",
            "zeroRecords": "Nenhum registro encontrado",
            "emptyTable": "Nenhum dado disponível na tabela",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros no total)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primeiro",
                "last": "Último", 
                "next": "Próximo",
                "previous": "Anterior"
            },
            "lengthMenu": "Mostrar _MENU_ registros por página"
        },
        "ajax": {
            "url": 'pages/convenio/convenio_read2.php',
            "method": 'POST',
            "data": function(d) {
                // Capture sessionStorage values for filtering
                var divisao = sessionStorage.getItem("divisao");
                var divisao_nome = sessionStorage.getItem("divisao_nome");
                
                console.log('DataTable data function - divisao from sessionStorage:', divisao);
                console.log('DataTable data function - divisao_nome from sessionStorage:', divisao_nome);
                
                // Add division filter if it exists
                if (divisao && divisao !== '') {
                    d.divisao = divisao;
                    console.log('Added divisao to request:', divisao);
                }
                if (divisao_nome && divisao_nome !== '') {
                    d.divisao_nome = divisao_nome;
                    console.log('Added divisao_nome to request:', divisao_nome);
                }
                
                console.log('Final data object being sent:', d);
                return d;
            },
            "dataType": 'json',
            "timeout": 30000, // Timeout de 30 segundos
            "error": function(xhr, error, thrown) {
                console.error('Erro no carregamento dos dados:', error, thrown);
                
                // Mostrar mensagem de erro personalizada
                var errorMsg = '';
                if (error === 'timeout') {
                    errorMsg = 'Tempo limite excedido. Verifique sua conexão com a internet e tente novamente.';
                } else if (error === 'parsererror') {
                    errorMsg = 'Erro ao processar os dados recebidos do servidor.';
                } else if (xhr.status === 0) {
                    errorMsg = 'Sem conexão com a internet. Verifique sua conectividade.';
                } else if (xhr.status === 404) {
                    errorMsg = 'Recurso não encontrado no servidor.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Erro interno do servidor. Tente novamente em alguns instantes.';
                } else {
                    errorMsg = 'Erro de conectividade. Verifique sua conexão e tente novamente.';
                }
                
                // Exibir alerta para o usuário
                alert('⚠️ Problema no carregamento dos dados:\n\n' + errorMsg);
                
                // Opcional: Tentar recarregar automaticamente após alguns segundos
                setTimeout(function() {
                    if (confirm('Deseja tentar recarregar os dados automaticamente?')) {
                        table.ajax.reload();
                    }
                }, 2000);
            },
            "dataSrc": function(json) {
                // Verificar se há mensagem personalizada do servidor
                if (json.empty_division && json.message) {
                    // Exibir mensagem informativa para o usuário
                    setTimeout(function() {
                        Swal.fire({
                            title: "Informação",
                            text: json.message,
                            icon: "info",
                            confirmButtonText: "OK"
                        });
                    }, 100);
                }
                
                // Retornar os dados para o DataTable
                return json.data || [];
            }
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
            { "data": "cidade" },
            { "data": "telefone" },
            { "data": "data _cadastro" },
            { "data": "cnpj" },
            { "data": "cpf" },
            { "data": "botaover" },
            { "data": "botao" },
            { "data": "botaosenha" },
            { "data": "botaobanco" }
        ],
        columnDefs:[{
            targets: 7,
            render: function (data) {
                if (data === null) {
                    return '';
                } else {
                    return moment(data).format('DD/MM/YYYY');
                }
            }
        }],
        "pagingType": "full_numbers"
    });
    
    // Handle division filter change - AFTER DataTable is initialized
    $('#filtro_divisao').on('change', function() {
        var selectedDivisao = $(this).val();
        var selectedText = $(this).find('option:selected').text();
        
        console.log('Division filter changed:', selectedDivisao, selectedText);
        console.log('Table object:', table);
        
        // Update sessionStorage with selected division
        if (selectedDivisao && selectedDivisao !== '') {
            sessionStorage.setItem("divisao", selectedDivisao);
            sessionStorage.setItem("divisao_nome", selectedText);
            console.log('SessionStorage updated - divisao:', selectedDivisao);
        } else {
            // Clear sessionStorage if "Todas as divisões" is selected
            sessionStorage.removeItem("divisao");
            sessionStorage.removeItem("divisao_nome");
            console.log('SessionStorage cleared');
        }
        
        // Verify sessionStorage values
        console.log('Current sessionStorage divisao:', sessionStorage.getItem("divisao"));
        console.log('Current sessionStorage divisao_nome:', sessionStorage.getItem("divisao_nome"));
        
        // Try multiple reload approaches
        console.log('Reloading table with filter...');
        if (table && table.ajax) {
            table.ajax.reload(null, false); // Don't reset paging
            console.log('Table reload called successfully');
        } else {
            console.error('Table or table.ajax not available');
        }
    });
    
    // Customizar input de busca do DataTable
    setTimeout(function() {
        var searchInput = $('input[aria-controls="tabela_producao"]');
        if (searchInput.length) {
            // Aumentar largura em 50%
            var currentWidth = searchInput.width();
            searchInput.css({
                'width': (currentWidth * 1.5) + 'px',
                'position': 'relative',
                'padding-right': '25px'
            });
            
            // Adicionar botão X para limpar
            var clearButton = $('<span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999; font-size: 14px; z-index: 10;">×</span>');
            
            // Envolver o input em um container relativo
            searchInput.wrap('<div style="position: relative; display: inline-block;"></div>');
            searchInput.parent().append(clearButton);
            
            // Funcionalidade do botão X
            clearButton.on('click', function() {
                searchInput.val('').trigger('keyup');
                searchInput.focus();
            });
            
            // Mostrar/esconder X baseado no conteúdo
            searchInput.on('input keyup', function() {
                if ($(this).val().length > 0) {
                    clearButton.show();
                } else {
                    clearButton.hide();
                }
            });
            
            // Inicialmente esconder o X
            clearButton.hide();
        }
    }, 100);
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
        $('#C_uf').val($('#C_uf option').eq(13).val());// MT

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
    
    // Function to refresh table when sessionStorage division values change
    window.refreshConvenioTable = function() {
        if (table) {
            table.ajax.reload();
        }
    };
    
    // Optional: Listen for storage events to auto-refresh when sessionStorage changes
    window.addEventListener('storage', function(e) {
        if (e.key === 'divisao' || e.key === 'divisao_nome') {
            refreshConvenioTable();
        }
    });
});
$('#gerarpdf').click(function () {
    $.redirect('pages/convenio/gerador_pdf_convenios.php',{"divisao": divisao,"divisao_nome": divisao_nome},"POST", "_blank");
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
            $("#C_inscmunicipal").val(data.insc_mun);
            $("#C_email").val(data.email);
            $("#C_email2").val(data.email2);
            $("#C_tipo").val(data.tipo);
            $("#C_tipoempresa").val(data.tipoempresa);
            $("#C_aprova_convenio").prop("checked", data.lista_site);
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
            $("#C_inscmunicipal").prop('disabled', false);
            $("#C_email").prop('disabled', false);
            $("#C_email2").prop('disabled', false);
            $("#C_tipo").prop('disabled', false);
            $("#C_tipoempresa").prop('disabled', false);
            $('#C_parcelamento').prop('disabled', false);
            /*$.each($('#frmconvenio').serializeArray(), function(index, value){
                $('#' + value.name + '').prop('disabled', false);
            });*/
            
            // Para convênio existente, carregar especialidades vinculadas
            setTimeout(function() {
                carregarEspecialidadesVinculadas(cod_convenio);
                $('#btnAbrirModalProfissionais').prop('disabled', false);
                especialidadesTemporarias = []; // Limpar array temporário
            }, 500);
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
    $("#C_inscmunicipal").prop('disabled', false);
    $("#C_email").prop('disabled', false);
    $("#C_email2").prop('disabled', false);
    $("#C_tipo").prop('disabled', false);
    $("#C_tipoempresa").prop('disabled', false);
    $('#C_parcelamento').prop('disabled', false);
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
    $('#C_uf').val($('#C_uf option').eq(13).val());
    $('#C_cidade').val($('#C_cidade option').eq(835).val());
    
    // Para novo convênio, habilitar seção de profissionais
    setTimeout(function() {
        $('#btnAbrirModalProfissionais').prop('disabled', false);
        $('#lista_especialidades').html('<div class="tag-placeholder">Nenhum profissional selecionado</div>');
        especialidadesTemporarias = []; // Limpar array
    }, 500);
});
$("#btnSalvar").click(function(event){
    event.preventDefault();
    $('#frmassociado').validator('validate');
    var campo_vazio = validar();
    if (campo_vazio === "validou") {
        
        // Preparar dados para envio
        var formData = $('#frmconvenio').serialize();
        
        // Se for novo convênio, adicionar divisao do sessionStorage
        if($('#operation').val() === "Add") {
            var divisao = sessionStorage.getItem("divisao");
            if(divisao && divisao !== '') {
                formData += '&divisao=' + encodeURIComponent(divisao);
                console.log("DEBUG: Adicionado divisao ao formData:", divisao);
            }
        }
        
        // Se for novo convênio e há especialidades, adicionar ao envio
        if($('#operation').val() === "Add" && especialidadesTemporarias.length > 0) {
            var especialidadesIds = especialidadesTemporarias.map(esp => esp.id);
            console.log("DEBUG: Especialidades temporárias:", especialidadesTemporarias);
            console.log("DEBUG: IDs das especialidades:", especialidadesIds);
            console.log("DEBUG: JSON das especialidades:", JSON.stringify(especialidadesIds));
            formData += '&especialidades=' + encodeURIComponent(JSON.stringify(especialidadesIds));
            console.log("DEBUG: FormData final:", formData);
        } else {
            console.log("DEBUG: Não é operação Add ou não há especialidades temporárias");
            console.log("DEBUG: Operation:", $('#operation').val());
            console.log("DEBUG: Especialidades temporárias length:", especialidadesTemporarias.length);
        }
        
        $.ajax({
            url:"pages/convenio/convenio_salvar.php",
            method: "POST",
            data: formData,
            dataType: "json",
            success:function (data) {
                $("#frmconvenio")[0].reset();
                especialidadesTemporarias = []; // Limpar especialidades temporárias
                
                // Response is already parsed as JSON
                var response = data;
                
                // Check for document already exists error
                if (response.documento_existente) {
                    Swal.fire({
                        title: "Erro!",
                        text: response.message,
                        icon: "error",
                        confirmButtonText: "OK"
                    });
                    return;
                }
                
                if (response.message === "atualizado") {
                    Swal.fire({
                        title: "Parabens!",
                        text: "Salvo com Sucesso !",
                        icon: "success",
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        $("#ModalEditaConvemio").modal('hide');
                        table.ajax.reload();
                    });
                }else if(response.message === "cadastrado"){
                    Swal.fire({
                        title: "Parabens!",
                        text: "Cadastrado com Sucesso !",
                        icon: "success",
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        $("#ModalEditaConvemio").modal('hide');
                        table.ajax.reload();
                    });
                }
                
                // Reload filtro_divisao if requested
                if (response.reload_filtro) {
                    loadFiltroDivisao();
                }
                
            },
            error: function(xhr, status, error) {
                console.error("Erro ao salvar convênio:", error);
                console.error("Status:", status);
                console.error("Response:", xhr.responseText);
                Swal.fire({
                    title: "Erro!",
                    text: "Erro ao salvar os dados. Verifique o console para mais detalhes.",
                    icon: "error",
                    confirmButtonText: "OK"
                });
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
            $("#C_inscmunicipal").val(data.insc_mun);
            $("#C_email").val(data.email);
            $("#C_email2").val(data.email2);
            $("#C_tipo").val(data.tipo);
            $("#C_tipon").val(data.tipo);
            $("#C_tipon").prop('disabled', true);
            $("#C_tipoempresa").val(data.tipoempresa);
            $('#C_parcelamento').val(data.parcelas);
            $('#operation').val("Update");
            $('#ModalEditaLabel').html("Convenio <small>Visualização</small>");
            $('#btnSalvar').hide();
            
            // Carregar profissionais após carregar os dados do convênio
            setTimeout(function() {
                // Habilitar controles de profissionais para convênio existente
                $('#btnAbrirModalProfissionais').prop('disabled', false);
                carregarEspecialidadesVinculadas(data.codigo);
            }, 500);
        }
    });

    $.each($('#frmconvenio').serializeArray(), function(index, value){
        $('[name="' + value.name + '"]').attr('disabled', 'disabled');
    });

});
function validar(){

    var razaosocial  = $('#C_razaosocial').val();
    var nomefantasia = $('#C_nomefantasia').val();
    //var endereco     = $('#C_endereco').val();
    //var numero       = $('#C_numero').val();
    //var bairro       = $('#C_bairro').val();
    //var cidade       = $('#C_cidade').val();
    //var uf           = $('#C_uf').val();

    if (razaosocial === ""){
        return $('#C_razaosocial').attr('name');
    }else if (nomefantasia === "") {
        return $('#C_nomefantasia').attr('name');
    }//else if (endereco === "") {
    //    return $('#C_endereco').attr('name');
    //}else if (numero === "") {
    //    return $('#C_numero').attr('name');
    //}else if (bairro === "") {
    //    return $('#C_bairro').attr('name');
    //}else if (cidade === "") {
    //    return $('#C_cidade').attr('name');
    //}else if (uf === "") {
    //    return $('#C_uf').attr('name');
    else{
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


// Funções para gerenciar especialidades do convênio
function carregarEspecialidadesDisponiveis() {
    console.log("Carregando profissionais disponíveis...");
    $.ajax({
        url: "pages/convenio/convenio_profissionais_listar.php",
        method: "GET",
        dataType: "json",
        success: function(data) {
            console.log("Profissionais recebidos:", data);
            $('#C_especialidade_select').empty();
            $('#C_especialidade_select').append('<option value="">Selecione um profissional...</option>');
            $.each(data, function(index, value) {
                var displayText = value.nome_profissional || '';
                if(value.especialidades) {
                    displayText += ' | Especialidades: ' + value.especialidades;
                }
                if(value.tipo_estabelecimento) {
                    displayText += ' | Tipo: ' + value.tipo_estabelecimento;
                }
                $('#C_especialidade_select').append('<option value="' + value.id + '">' + displayText + '</option>');
            });
            console.log("Profissionais carregados no select");
        },
        error: function(xhr, status, error) {
            console.log("Erro ao carregar profissionais:", error);
            console.log("Response:", xhr.responseText);
        }
    });
}

function carregarEspecialidadesVinculadas(codConvenio) {
    console.log("=== carregarEspecialidadesVinculadas chamada ===");
    console.log("codConvenio:", codConvenio);
    
    $.ajax({
        url: "pages/convenio/convenio_especialidades_vinculadas.php",
        method: "POST",
        data: {cod_convenio: codConvenio},
        dataType: "json",
        success: function(data) {
            console.log("Dados recebidos de especialidades vinculadas:", data);
            $('#lista_especialidades').empty();
            if(data.length > 0) {
                console.log("Criando lista com", data.length, "especialidades");
                var listHtml = '<table style="width: 100%; border-collapse: collapse;">' +
                    '<thead>' +
                    '<tr style="background-color: #f5f5f5; font-weight: bold; font-size: 12px;">' +
                    '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Profissional</th>' +
                    '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Especialidade</th>' +
                    '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Tipo Especialidade</th>' +
                    '<th style="padding: 8px; border: 1px solid #ddd; text-align: center; width: 80px;">Ação</th>' +
                    '</tr>' +
                    '</thead>' +
                    '<tbody>';
                $.each(data, function(index, value) {
                    listHtml += '<tr style="border-bottom: 1px solid #eee;">' +
                        '<td style="padding: 6px 8px; border: 1px solid #ddd; font-size: 11px;">' + (value.nome_profissional || 'N/A') + '</td>' +
                        '<td style="padding: 6px 8px; border: 1px solid #ddd; font-size: 11px;">' + (value.nome_especialidade || 'N/A') + '</td>' +
                        '<td style="padding: 6px 8px; border: 1px solid #ddd; font-size: 11px;">' + (value.tipo_especialidade || 'N/A') + '</td>' +
                        '<td style="padding: 6px 8px; border: 1px solid #ddd; text-align: center;">' +
                        '<button type="button" class="btn btn-xs btn-danger" onclick="removerEspecialidade(' + value.id + ')" title="Remover especialidade" style="padding: 2px 6px;">' +
                        '×' +
                        '</button>' +
                        '</td>' +
                        '</tr>';
                });
                listHtml += '</tbody></table>';
                $('#lista_especialidades').html(listHtml);
                console.log("Lista HTML criada:", listHtml);
            } else {
                $('#lista_especialidades').html('<div class="tag-placeholder" style="color: #999; font-style: italic; padding: 10px; text-align: center;">Nenhuma especialidade vinculada</div>');
                console.log("Nenhuma especialidade encontrada");
            }
            console.log("=== Fim carregarEspecialidadesVinculadas ===");
        },
        error: function(xhr, status, error) {
            console.log("Erro ao carregar especialidades vinculadas:", error);
            console.log("Response text:", xhr.responseText);
        }
    });
}

function adicionarEspecialidadeTemporaria() {
    var codEspecialidade = $('#C_especialidade_select').val();
    var nomeEspecialidade = $('#C_especialidade_select option:selected').text();
    
    if(!codEspecialidade) {
        alert('Selecione uma especialidade!');
        return;
    }
    
    // Verificar se já foi adicionada
    if(especialidadesTemporarias.find(esp => esp.id == codEspecialidade)) {
        alert('Esta especialidade já foi selecionada!');
        return;
    }
    
    // Adicionar ao array temporário
    especialidadesTemporarias.push({
        id: codEspecialidade,
        nome: nomeEspecialidade
    });
    
    // Atualizar interface
    atualizarListaEspecialidadesTemporarias();
    $('#C_especialidade_select').val('');
    
            Swal.fire({
            title: "Profissional Adicionado!",
            text: "Salve o convênio para confirmar.",
            icon: "info",
            showConfirmButton: false,
            timer: 2000
        });
}

function atualizarListaEspecialidadesTemporarias() {
    console.log("=== atualizarListaEspecialidadesTemporarias chamada ===");
    console.log("especialidadesTemporarias:", especialidadesTemporarias);
    console.log("Elemento #lista_especialidades existe:", $('#lista_especialidades').length > 0);
    
    $('#lista_especialidades').empty();
    if(especialidadesTemporarias.length > 0) {
        var listHtml = '<table style="width: 100%; border-collapse: collapse;">' +
            '<thead>' +
            '<tr style="background-color: #f5f5f5; font-weight: bold; font-size: 12px;">' +
            '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Profissional</th>' +
            '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Especialidade</th>' +
            '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Tipo Especialidade</th>' +
            '<th style="padding: 8px; border: 1px solid #ddd; text-align: center; width: 80px;">Ação</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>';
        $.each(especialidadesTemporarias, function(index, value) {
            // Separar o nome composto (nome - especialidades (tipo))
            var nomeCompleto = value.nome;
            var partes = nomeCompleto.split(' - ');
            var nomeProfissional = partes[0] || 'N/A';
            var resto = partes[1] || '';
            
            // Separar especialidades e tipo (formato: "especialidades (tipo)")
            var especialidades = 'N/A';
            var tipoEspecialidade = 'N/A';
            
            if(resto) {
                var match = resto.match(/^(.+?)\s*\(([^)]+)\)$/);
                if(match) {
                    especialidades = match[1].trim();
                    tipoEspecialidade = match[2].trim();
                } else {
                    especialidades = resto;
                }
            }
            
            listHtml += '<tr style="border-bottom: 1px solid #eee;">' +
                '<td style="padding: 6px 8px; border: 1px solid #ddd; font-size: 11px;">' + nomeProfissional + '</td>' +
                '<td style="padding: 6px 8px; border: 1px solid #ddd; font-size: 11px;">' + especialidades + '</td>' +
                '<td style="padding: 6px 8px; border: 1px solid #ddd; font-size: 11px;">' + tipoEspecialidade + '</td>' +
                '<td style="padding: 6px 8px; border: 1px solid #ddd; text-align: center;">' +
                '<button type="button" class="btn btn-xs btn-danger" onclick="removerEspecialidadeTemporaria(' + index + ')" title="Remover profissional" style="padding: 2px 6px;">' +
                '×' +
                '</button>' +
                '</td>' +
                '</tr>';
        });
        listHtml += '</tbody></table>';
        $('#lista_especialidades').html(listHtml);
        console.log("Lista HTML gerada:", listHtml);
    } else {
        $('#lista_especialidades').html('<div class="tag-placeholder" style="color: #999; font-style: italic; padding: 10px; text-align: center;">Nenhum profissional selecionado</div>');
        console.log("Exibindo placeholder: nenhum profissional selecionado");
    }
    console.log("=== Fim atualizarListaEspecialidadesTemporarias ===");
}

function removerEspecialidadeTemporaria(index) {
    especialidadesTemporarias.splice(index, 1);
    atualizarListaEspecialidadesTemporarias();
}

function adicionarEspecialidade() {
    if($('#operation').val() === "Add") {
        // Novo convênio - adicionar temporariamente
        adicionarEspecialidadeTemporaria();
    } else {
        // Convênio existente - adicionar diretamente no banco
        var codConvenio = $('#C_codigo').val();
        var codEspecialidade = $('#C_especialidade_select').val();
        
        console.log("Código do convênio:", codConvenio);
        console.log("Código da especialidade:", codEspecialidade);
        
        if(!codConvenio) {
            alert('Código do convênio não encontrado!');
            return;
        }
        
        if(!codEspecialidade) {
            alert('Selecione uma especialidade!');
            return;
        }
        
        // Verificar se o convênio existe no banco antes de adicionar especialidade
        $.ajax({
            url: "pages/convenio/convenio_verificar_existe.php",
            method: "POST",
            data: {cod_convenio: codConvenio},
            dataType: "json",
            success: function(response) {
                console.log("Verificação do convênio:", response);
                if(response.existe) {
                    // Convênio existe, pode adicionar especialidade
                    $.ajax({
                        url: "pages/convenio/convenio_especialidades_adicionar.php",
                        method: "POST",
                        data: {
                            cod_convenio: codConvenio,
                            cod_especialidade: codEspecialidade
                        },
                        success: function(data) {
                            console.log("Resposta do servidor:", data);
                            if(data.trim() === 'adicionada') {
                                $('#C_especialidade_select').val('');
                                carregarEspecialidadesVinculadas(codConvenio);
                                                    Swal.fire({
                        title: "Sucesso!",
                        text: "Especialidade adicionada com sucesso!",
                        icon: "success",
                        showConfirmButton: false,
                        timer: 1500
                    });
                            } else if(data.trim() === 'ja_vinculada') {
                                alert('Esta especialidade já está vinculada ao convênio!');
                            } else {
                                alert('Erro ao adicionar especialidade: ' + data);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log("Erro ao adicionar especialidade:", error);
                            alert('Erro ao adicionar especialidade!');
                        }
                    });
                } else {
                    alert('Convênio não encontrado no banco de dados! Código: ' + codConvenio);
                }
            },
            error: function(xhr, status, error) {
                console.log("Erro ao verificar convênio:", error);
                alert('Erro ao verificar convênio!');
            }
        });
    }
}

function removerEspecialidade(id) {
    if(confirm('Tem certeza que deseja remover esta especialidade?')) {
        $.ajax({
            url: "pages/convenio/convenio_especialidades_remover.php",
            method: "POST",
            data: {id: id},
            success: function(data) {
                if(data.trim() === 'removida') {
                    var codConvenio = $('#C_codigo').val();
                    carregarEspecialidadesVinculadas(codConvenio);
                    Swal.fire({
                        title: "Sucesso!",
                        text: "Especialidade removida com sucesso!",
                        icon: "success",
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    alert('Erro ao remover especialidade: ' + data);
                }
            },
            error: function(xhr, status, error) {
                console.log("Erro ao remover especialidade:", error);
                alert('Erro ao remover especialidade!');
            }
        });
    }
}

// Funções para modal de profissionais
function abrirModalProfissionais() {
    console.log("Abrindo modal de profissionais...");
    $('#filtro_profissionais').val(''); // Limpar campo de busca
    carregarProfissionaisModal();
    $('#ModalSelecionarProfissionais').modal('show');
}

function carregarProfissionaisModal() {
    console.log("Carregando profissionais no modal...");
    $.ajax({
        url: "pages/convenio/convenio_profissionais_listar.php",
        method: "GET",
        dataType: "json",
        success: function(data) {
            console.log("Profissionais recebidos:", data);
            todosProfissionais = data; // Armazenar dados originais
            exibirProfissionaisModal(data);
            console.log("Profissionais carregados na tabela");
        },
        error: function(xhr, status, error) {
            console.log("Erro ao carregar profissionais:", error);
            alert('Erro ao carregar profissionais!');
        }
    });
}

function exibirProfissionaisModal(profissionais) {
    // Remover width:100% e table-layout:fixed que podem estar interferindo
    $('#tabela_profissionais').removeAttr('style');
    
    // Adicionar CSS customizado no head para forçar largura da primeira coluna
    if (!$('#custom-table-style').length) {
        $('head').append(`
            <style id="custom-table-style">
                #tabela_profissionais {
                    width: auto !important;
                    table-layout: auto !important;
                }
                #tabela_profissionais th:first-child,
                #tabela_profissionais td:first-child {
                    width: 25px !important;
                    min-width: 25px !important;
                    max-width: 25px !important;
                    padding: 2px 4px !important;
                    text-align: center !important;
                }
                #tabela_profissionais th:nth-child(3),
                #tabela_profissionais td:nth-child(3) {
                    max-width: 200px !important;
                    word-wrap: break-word !important;
                    white-space: normal !important;
                    overflow-wrap: break-word !important;
                    word-break: break-word !important;
                }
                #tabela_profissionais th:not(:first-child):not(:nth-child(3)),
                #tabela_profissionais td:not(:first-child):not(:nth-child(3)) {
                    width: auto !important;
                    white-space: normal !important;
                    word-wrap: break-word !important;
                }
            </style>
        `);
    }
    
    $('#tbody_profissionais').empty();
    $.each(profissionais, function(index, value) {
        var row = '<tr>' +
            '<td style="width: 25px !important; min-width: 25px !important; max-width: 25px !important; padding: 2px 4px !important; text-align: center !important; vertical-align: middle !important;"><input type="checkbox" value="' + value.id + '" class="checkbox-profissional" style="margin: 0 !important;"></td>' +
            '<td>' + (value.nome_profissional || '') + '</td>' +
            '<td>' + (value.especialidades || 'Não informado') + '</td>' +
            '<td>' + (value.nome_tipo || 'Não informado') + '</td>' +
            '</tr>';
        $('#tbody_profissionais').append(row);
    });
}

function filtrarProfissionais(filtro) {
    if (!filtro || filtro.trim() === '') {
        // Se não há filtro, mostrar todos
        exibirProfissionaisModal(todosProfissionais);
        return;
    }
    
    var profissionaisFiltrados = todosProfissionais.filter(function(profissional) {
        var nome = (profissional.nome_profissional || '').toLowerCase();
        var especialidades = (profissional.especialidades || '').toLowerCase();
        var tipo = (profissional.nome_tipo || '').toLowerCase();
        
        return nome.includes(filtro) || 
               especialidades.includes(filtro) || 
               tipo.includes(filtro);
    });
    
    exibirProfissionaisModal(profissionaisFiltrados);
}

function adicionarProfissionaisSelecionados() {
    // Evitar múltiplos cliques simultâneos
    if (window.adicionandoProfissionais) {
        console.log("Adição já em andamento, ignorando...");
        return;
    }
    
    window.adicionandoProfissionais = true;
    
    var profissionaisSelecionados = [];
    $('#tbody_profissionais input[type="checkbox"]:checked').each(function() {
        var id = $(this).val();
        var nome = $(this).closest('tr').find('td').eq(1).text();
        var especialidades = $(this).closest('tr').find('td').eq(2).text();
        var tipo = $(this).closest('tr').find('td').eq(3).text();
        profissionaisSelecionados.push({
            id: id,
            nome: nome + ' - ' + especialidades + ' (' + tipo + ')'
        });
    });
    
    if(profissionaisSelecionados.length === 0) {
        alert('Selecione pelo menos um profissional!');
        window.adicionandoProfissionais = false;
        return;
    }
    
    console.log("Profissionais selecionados:", profissionaisSelecionados);
    console.log("Valor do operation:", $('#operation').val());
    console.log("Operation === 'Add'?", $('#operation').val() === "Add");
    
    if($('#operation').val() === "Add") {
        // Novo convênio - adicionar temporariamente
        console.log("Adicionando profissionais temporariamente...");
        console.log("Profissionais selecionados:", profissionaisSelecionados);
        console.log("Array especialidadesTemporarias antes:", especialidadesTemporarias);
        
        var profissionaisNovos = 0;
        $.each(profissionaisSelecionados, function(index, profissional) {
            // Verificação mais rigorosa para evitar duplicatas
            var jaExiste = especialidadesTemporarias.some(function(esp) {
                return esp.id === profissional.id || esp.id == profissional.id;
            });
            
            if(!jaExiste) {
                especialidadesTemporarias.push({
                    id: profissional.id,
                    nome: profissional.nome
                });
                profissionaisNovos++;
                console.log("Profissional adicionado:", profissional);
            } else {
                console.log("Profissional já existe:", profissional);
            }
        });
        
        console.log("Array especialidadesTemporarias depois:", especialidadesTemporarias);
        console.log("Chamando atualizarListaEspecialidadesTemporarias...");
        atualizarListaEspecialidadesTemporarias();
        
        // Limpar seleções dos checkboxes
        $('#tbody_profissionais input[type="checkbox"]').prop('checked', false);
        
        $('#ModalSelecionarProfissionais').modal('hide');
        
        if(profissionaisNovos > 0) {
            Swal.fire({
                title: "Profissionais Adicionados!",
                text: profissionaisNovos + " profissional(is) adicionado(s)! Salve o convênio para confirmar.",
                icon: "info",
                showConfirmButton: false,
                timer: 2000
            });
        } else {
            Swal.fire({
                title: "Atenção!",
                text: "Todos os profissionais selecionados já foram adicionados anteriormente.",
                icon: "warning",
                showConfirmButton: false,
                timer: 2000
            });
        }
        
        // Liberar flag de proteção
        window.adicionandoProfissionais = false;
    } else {
        // Convênio existente - adicionar diretamente no banco
        console.log("Fluxo: Convênio existente - adicionando diretamente no banco");
        var codConvenio = $('#C_codigo').val();
        console.log("Código do convênio:", codConvenio);
        var profissionaisIds = profissionaisSelecionados.map(p => p.id);
        console.log("IDs dos profissionais:", profissionaisIds);
        
        // Adicionar cada profissional individualmente
        var profissionaisAdicionados = 0;
        var profissionaisComSucesso = 0;
        var totalProfissionais = profissionaisIds.length;
        
        $.each(profissionaisIds, function(index, profissionalId) {
            $.ajax({
                url: "pages/convenio/convenio_especialidades_adicionar.php",
                method: "POST",
                data: {
                    cod_convenio: codConvenio,
                    cod_profissional: profissionalId
                },
                success: function(data) {
                    console.log("Resposta do servidor para profissional", profissionalId, ":", data);
                    profissionaisAdicionados++;
                    
                    if(data.trim() === 'adicionada') {
                        profissionaisComSucesso++;
                    }
                    
                    // Só executa uma vez quando todos foram processados
                    if(profissionaisAdicionados === totalProfissionais) {
                        console.log("Todos profissionais processados, chamando carregarEspecialidadesVinculadas para convênio:", codConvenio);
                        carregarEspecialidadesVinculadas(codConvenio);
                        
                        // Limpar seleções dos checkboxes
                        $('#tbody_profissionais input[type="checkbox"]').prop('checked', false);
                        
                        $('#ModalSelecionarProfissionais').modal('hide');
                        
                        if(profissionaisComSucesso > 0) {
                            Swal.fire({
                                title: "Sucesso!",
                                text: profissionaisComSucesso + " profissional(is) adicionado(s) com sucesso!",
                                icon: "success",
                                showConfirmButton: false,
                                timer: 1500
                            });
                        } else {
                            Swal.fire({
                                title: "Atenção!",
                                text: "Todos os profissionais selecionados já estavam vinculados ao convênio.",
                                icon: "warning",
                                showConfirmButton: false,
                                timer: 2000
                            });
                        }
                        
                        // Liberar flag de proteção
                        window.adicionandoProfissionais = false;
                    }
                },
                error: function(xhr, status, error) {
                    console.log("Erro ao adicionar profissional", profissionalId, ":", error);
                    console.log("Response text:", xhr.responseText);
                    profissionaisAdicionados++;
                    
                    if(profissionaisAdicionados === totalProfissionais) {
                        alert('Erro ao adicionar alguns profissionais!');
                        // Liberar flag de proteção em caso de erro
                        window.adicionandoProfissionais = false;
                    }
                }
            });
        });
        
        // Timeout de segurança para liberar flag caso algo dê errado
        setTimeout(function() {
            if (window.adicionandoProfissionais) {
                console.log("Liberando flag por timeout de segurança");
                window.adicionandoProfissionais = false;
            }
        }, 10000);
    }
}

// Event listeners para profissionais
$(document).on('click', '#btnAbrirModalProfissionais', function() {
    abrirModalProfissionais();
});

$(document).on('click', '#btnConfirmarSelecao', function() {
    adicionarProfissionaisSelecionados();
});



$(document).on('input', '#filtro_profissionais', function() {
    var filtro = $(this).val().toLowerCase();
    filtrarProfissionais(filtro);
});