/**
 * Script para gerenciar valores de taxa de cartão
 * Sistema QRCred - Versão Simplificada (um registro por divisão)
 */

// Variáveis globais
var divisao;
var divisao_nome;
var usuario_cod;
var usuario_global;

$(document).ready(function() {
    // Carrega dados da sessão
    divisao = sessionStorage.getItem("divisao");
    divisao_nome = sessionStorage.getItem("divisao_nome");
    usuario_cod = sessionStorage.getItem("usuario_cod");
    usuario_global = sessionStorage.getItem("usuario_global");
    
    console.log("Divisão carregada:", divisao);
    console.log("Divisão nome:", divisao_nome);
    
    // Carrega o registro da divisão (se existir)
    carregarTaxaDivisao();
    
    // Evento de submit do formulário
    $("#formValorTaxa").on("submit", function(e) {
        e.preventDefault();
        salvarTaxa();
    });
    
    // Formata o campo valor
    $("#valor").on("keyup", function() {
        formatarValor(this);
    });
});

/**
 * Carrega a taxa da divisão logada
 */
function carregarTaxaDivisao() {
    console.log("Iniciando carregamento da taxa para divisão:", divisao);
    
    $.ajax({
        url: "../Adm/pages/txcartao/valor_taxa_read.php",
        type: "POST",
        dataType: "json",
        data: {
            divisao: divisao
        },
        success: function(data) {
            console.log("Dados recebidos do servidor:", data);
            
            if (data && data.length > 0) {
                // Já existe taxa cadastrada - modo de edição
                var taxa = data[0];
                console.log("Taxa encontrada:", taxa);
                
                $("#operation").val("update");
                $("#id").val(taxa.id);
                $("#valor").val(taxa.valor);
                $("#descricao").val(taxa.descricao);
                $("#btnTexto").text("Atualizar");
                $("#formTitle").html('<i class="fa fa-edit"></i> Editar Taxa de Cartão');
                
                console.log("Campos preenchidos - Valor:", taxa.valor, "Descrição:", taxa.descricao);
            } else {
                // Não existe taxa - modo de cadastro
                console.log("Nenhuma taxa encontrada - modo cadastro");
                
                $("#operation").val("insert");
                $("#id").val("");
                $("#valor").val("");
                $("#descricao").val("");
                $("#btnTexto").text("Cadastrar");
                $("#formTitle").html('<i class="fa fa-plus-circle"></i> Cadastrar Taxa de Cartão');
            }
        },
        error: function(xhr, status, error) {
            console.error("Erro ao carregar taxa:", error);
            console.error("Status:", status);
            console.error("Response:", xhr.responseText);
            
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Não foi possível carregar os dados da taxa.',
                confirmButtonText: 'OK'
            });
        }
    });
}

/**
 * Salva ou atualiza uma taxa
 */
function salvarTaxa() {
    var operation = $("#operation").val();
    var id = $("#id").val();
    var valor = $("#valor").val();
    var descricao = $("#descricao").val();
    
    // Validações
    if (!valor) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção',
            text: 'Por favor, informe o valor.',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    if (!descricao) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção',
            text: 'Por favor, informe a descrição.',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Confirmação
    var mensagem = operation === 'insert' 
        ? 'Deseja cadastrar esta taxa de cartão?' 
        : 'Deseja atualizar esta taxa de cartão?';
    
    Swal.fire({
        title: 'Confirmar',
        text: mensagem,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim, salvar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Desabilita o botão durante o processamento
            var textoOriginal = $("#btnTexto").text();
            $("#btnSalvar").prop("disabled", true);
            $("#btnTexto").text("Processando...");
            
            // Envia os dados
            $.ajax({
                url: "../Adm/pages/txcartao/valor_taxa_salvar.php",
                type: "POST",
                dataType: "json",
                data: {
                    operation: operation,
                    id: id,
                    divisao: divisao,
                    valor: valor,
                    descricao: descricao
                },
                success: function(response) {
                    // Reabilita o botão
                    $("#btnSalvar").prop("disabled", false);
                    $("#btnTexto").text(textoOriginal);
                    
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: response.message,
                            confirmButtonText: 'OK'
                        });
                        
                        // Recarrega os dados da divisão
                        carregarTaxaDivisao();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: response.message,
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    // Reabilita o botão
                    $("#btnSalvar").prop("disabled", false);
                    $("#btnTexto").text(textoOriginal);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro de Comunicação',
                        text: 'Não foi possível comunicar com o servidor.',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}


/**
 * Formata o campo de valor
 */
function formatarValor(campo) {
    var valor = campo.value;
    
    // Remove tudo que não é número ou vírgula
    valor = valor.replace(/[^\d,]/g, '');
    
    // Remove vírgulas duplicadas
    var partes = valor.split(',');
    if (partes.length > 2) {
        valor = partes[0] + ',' + partes.slice(1).join('');
    }
    
    // Limita casas decimais a 2
    if (partes.length === 2 && partes[1].length > 2) {
        valor = partes[0] + ',' + partes[1].substring(0, 2);
    }
    
    // Remove zeros à esquerda
    if (partes[0].length > 1 && partes[0][0] === '0') {
        partes[0] = partes[0].replace(/^0+/, '') || '0';
        valor = partes.join(',');
    }
    
    campo.value = valor;
}

