var usuario_global;
var usuario_cod;
var divisao;
var divisao_nome;
var table_associados_pendentes;

$(document).ready(function(){
    
    $('#C_telres').mask('(99)9999-9999');
    $('#C_telcom').mask('(99)9999-9999');
    $('#C_cel').mask('(99)99999-9999');
    $('#C_cep').mask('99.999-999');
    $('#C_cpf').mask('999.999.999-99');
    $('#C_nascimento').mask('99/99/9999');
    
    divisao = sessionStorage.getItem("divisao");
    divisao_nome = sessionStorage.getItem("divisao_nome");
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");
    
    // Carregar lista de empregadores
    carregarEmpregadores();
    
    // Inicializar a tabela DataTables
    table_associados_pendentes = $('#tabela_associados_pendentes').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json"
        },
        "processing": true,
        "responsive": true,
        "ajax": {
            "url": "pages/associados_pendentes/associados_pendentes_read.php",
            "type": "POST"
        },
        "columns": [
            { "data": "id" },
            { "data": "nome" },
            { "data": "endereco" },
            { "data": "bairro" },
            { "data": "nascimento" },
            { "data": "cpf" },
            { "data": "email" },
            { "data": "cel" },
            { "data": "codigo" },
            { "data": "empregador" },
            { "data": "alterar", "orderable": false },
            { "data": "aprovar", "orderable": false }
        ],
        "order": [[ 1, "asc" ]],  // Ordenar por nome (segunda coluna)
        "pageLength": 25
    });
    
    // Quando clicar no botão Alterar
    $(document).on('click', '.update', function(){
        var id = $(this).attr("id");
        
        $.ajax({
            url: "pages/associados_pendentes/associados_pendentes_exibe.php",
            method: "POST",
            data: {id: id},
            dataType: "json",
            success: function(data) {
                $('#ModalEdita').modal('show');
                $('#C_id').val(data.id);
                $('#C_nome').val(data.nome);
                $('#C_endereco').val(data.endereco);
                $('#C_numero').val(data.numero);
                $('#C_complemento').val(data.complemento);
                $('#C_bairro').val(data.bairro);
                $('#C_cidade').val(data.cidade);
                $('#C_uf').val(data.uf);
                $('#C_cep').val(data.cep);
                $('#C_nascimento').val(data.nascimento);
                $('#C_cpf').val(data.cpf);
                $('#C_rg').val(data.rg);
                $('#C_telres').val(data.telres);
                $('#C_telcom').val(data.telcom);
                $('#C_cel').val(data.cel);
                $('#C_email').val(data.email);
                $('#C_codigo').val(data.codigo);
                $('#C_empregador').val(data.empregador);
                $('#rotulo_associado').text('Alterando');
            }
        });
    });
    
    // Função para carregar os empregadores da API
    function carregarEmpregadores() {
        console.log("Iniciando carregamento de empregadores...");
        
        $.ajax({
            url: "/api_empregadores.php",
            method: "GET",
            dataType: "json",
            success: function(response) {
                console.log("Dados recebidos da API:", response);
                
                var select = $('#C_empregador');
                select.empty();
                select.append('<option value="">Selecione o empregador</option>');
                
                // Verifica se a resposta tem o formato esperado (success e data)
                if (response && response.success && Array.isArray(response.data)) {
                    // Itera sobre o array de dados
                    $.each(response.data, function(index, empregador) {
                        if (empregador && empregador.id !== undefined && empregador.nome !== undefined) {
                            select.append('<option value="' + empregador.id + '">' + empregador.nome + '</option>');
                        }
                    });
                    
                    console.log("Select preenchido com", select.find('option').length - 1, "empregadores");
                } else {
                    console.error("Formato de resposta inesperado:", response);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Erro ao carregar empregadores: formato de dados inesperado'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error("Erro ao carregar empregadores:", status, error);
                console.log("Resposta do servidor:", xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao carregar a lista de empregadores'
                });
            }
        });
    }
    
    // Quando clicar no botão Transferir para cadastro definitivo
    $('#btnTransferir').click(function(){
        if ($('#C_nome').val() == "" || $('#C_cpf').val() == "") {
            Swal.fire({
                icon: 'warning',
                title: 'Campos obrigatórios!',
                text: 'Nome e CPF são obrigatórios para transferir o cadastro.'
            });
            return false;
        }
        
        Swal.fire({
            title: 'Confirmar transferência',
            text: 'Tem certeza que deseja transferir este associado para o cadastro definitivo? Esta ação não pode ser desfeita.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, transferir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
            $.ajax({
                url: "pages/associados_pendentes/transferir_associado.php",
                method: "POST",
                data: {
                    id: $('#C_id').val()
                },
                dataType: "json",
                success: function(data) {
                    if(data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: data.message
                        });
                        $('#ModalEdita').modal('hide');
                        // Recarregar a tabela com os dados atualizados
                        table_associados_pendentes.ajax.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: data.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Erro na transferência:", status, error);
                    console.log("Resposta do servidor:", xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Erro ao transferir associado. Verifique o console para mais detalhes.'
                    });
                }
            });
            }
        });
    });
    
    // Quando clicar no botão Salvar do modal
    $('#btnSalvar').click(function(){
        if ($('#C_nome').val() == "" || $('#C_endereco').val() == "" || $('#C_numero').val() == "" || $('#C_bairro').val() == "" || $('#C_cidade').val() == "" || $('#C_uf').val() == "") {
            Swal.fire({
                icon: 'warning',
                title: 'Campos obrigatórios!',
                text: 'Por favor, preencha todos os campos obrigatórios.'
            });
            return false;
        }
        
        $.ajax({
            url: "pages/associados_pendentes/associados_pendentes_update.php",
            method: "POST",
            data: {
                id: $('#C_id').val(),
                nome: $('#C_nome').val(),
                endereco: $('#C_endereco').val(),
                numero: $('#C_numero').val(),
                complemento: $('#C_complemento').val(),
                bairro: $('#C_bairro').val(),
                cidade: $('#C_cidade').val(),
                uf: $('#C_uf').val(),
                cep: $('#C_cep').val(),
                nascimento: $('#C_nascimento').val(),
                cpf: $('#C_cpf').val(),
                rg: $('#C_rg').val(),
                telres: $('#C_telres').val(),
                telcom: $('#C_telcom').val(),
                cel: $('#C_cel').val(),
                email: $('#C_email').val(),
                codigo: $('#C_codigo').val(),
                empregador: $('#C_empregador').val()
            },
            dataType: "json",
            success: function(data) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: data.mensagem
                });
                $('#ModalEdita').modal('hide');
                // Recarregar a tabela com os dados atualizados
                table_associados_pendentes.ajax.reload();
            }
        });
    });
    
    // Quando clicar no botão Aprovar
    $(document).on('click', '.aprovar', function(){
        var id = $(this).attr("id");
        
        Swal.fire({
            title: 'Confirmar aprovação',
            text: 'Tem certeza que deseja aprovar este associado?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, aprovar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "pages/associados_pendentes/associados_pendentes_aprovar.php",
                    method: "POST",
                    data: {id: id},
                    dataType: "json",
                    success: function(data) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: data.mensagem
                        });
                        // Recarregar a tabela com os dados atualizados
                        table_associados_pendentes.ajax.reload();
                    }
                });
            }
        });
    });
    
    // Verificação de CEP quando o campo perde o foco
    $("#C_cep").blur(function() {
        var cep = $(this).val().replace(/\D/g, '');
        
        if (cep !== "") {
            var validacep = /^[0-9]{8}$/;
            
            if(validacep.test(cep)) {
                $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {
                    if (!("erro" in dados)) {
                        $("#C_uf").val(dados.uf).change();
                        $("#C_endereco").val(dados.logradouro);
                        $("#C_bairro").val(dados.bairro);
                        $("#C_cidade").val(dados.localidade);
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'CEP não encontrado!',
                            text: 'O CEP informado não foi encontrado.'
                        });
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'CEP inválido!',
                    text: 'Formato de CEP inválido.'
                });
            }
        }
    });

    // Criar uma pasta para o JS se não existir
    function criarPastaJs() {
        var pastaJs = "pages/associados_pendentes/js";
        
        $.ajax({
            url: pastaJs,
            type: 'HEAD',
            error: function() {
                // Não faz nada, pois no ambiente web não podemos criar pastas
            },
            success: function() {
                // Pasta já existe
            }
        });
    }
    
    criarPastaJs();
}); 