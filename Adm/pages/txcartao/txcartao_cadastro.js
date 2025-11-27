/**
 * Script para cadastro de Taxa de Cartão
 * Sistema QRCred
 */

// Variáveis globais para armazenar dados da sessão
var divisao;
var usuario_cod;
var usuario_global;

$(document).ready(function() {
    // Carrega dados da sessão
    divisao = sessionStorage.getItem("divisao");
    usuario_cod = sessionStorage.getItem("usuario_cod");
    usuario_global = sessionStorage.getItem("usuario_global");
    
    // Carrega os meses disponíveis
    carregarMeses();
    
    // Configura o evento de submit do formulário
    $("#formTaxaCartao").on("submit", function(e) {
        e.preventDefault();
        gravarTaxaCartao();
    });
    
    // Formata o campo valor para aceitar apenas números e vírgula
    $("#C_valor").on("keyup", function() {
        formatarValor(this);
    });
});

/**
 * Carrega os meses disponíveis do banco de dados
 */
function carregarMeses() {
    $.ajax({
        url: "../Adm/pages/txcartao/meses_conta.php",
        type: "GET",
        dataType: "json",
        data: {
            divisao: divisao
        },
        success: function(data) {
            if (data && data.length > 0) {
                var mes_corrente = data[0].mes_corrente;
                
                // Preenche o campo de mês (agora é input readonly) com o mês corrente
                $("#C_mes").val(mes_corrente);
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Erro ao carregar mês corrente',
                text: 'Não foi possível carregar o mês corrente.',
                confirmButtonText: 'OK'
            });
        }
    });
}

/**
 * Grava a taxa de cartão no banco de dados
 */
function gravarTaxaCartao() {
    // Obtém os valores do formulário
    var mes = $("#C_mes").val();
    var valor = $("#C_valor").val();
    var descricao = $("#C_descricao").val();
    
    // Validações
    if (!mes) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção',
            text: 'Por favor, selecione o mês.',
            confirmButtonText: 'OK'
        });
        return;
    }
    
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
    
    // Confirmação antes de gravar
    Swal.fire({
        title: 'Confirmar Lançamento',
        html: 'Deseja realmente lançar a taxa de <strong>R$ ' + valor + '</strong><br>' +
              'para todos os associados com lançamentos no mês de <strong>' + mes + '</strong>?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim, lançar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Desabilita o botão durante o processamento
            $("#btnGravar").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Processando...');
            
            // Envia os dados via AJAX
            $.ajax({
                url: "../Adm/pages/txcartao/gravar_taxa_cartao.php",
                type: "POST",
                dataType: "json",
                data: {
                    mes: mes,
                    valor: valor,
                    descricao: descricao,
                    divisao: divisao,
                    usuario_cod: usuario_cod
                },
                success: function(response) {
                    // Reabilita o botão
                    $("#btnGravar").prop("disabled", false).html('<i class="fa fa-save"></i> Gravar Taxa');
                    
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            html: '<strong>' + response.message + '</strong><br><br>' +
                                  'Registros inseridos: <strong>' + response.registros_inseridos + '</strong>',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Limpa apenas o campo de descrição, mantém mês e valor
                            // para facilitar múltiplos lançamentos
                        });
                        
                        // Exibe informações adicionais
                        $("#resultadoArea").show();
                        $("#resultadoMensagem").html(
                            '<div class="alert alert-success">' +
                            '<i class="fa fa-check-circle"></i> ' +
                            '<strong>Lançamento realizado com sucesso!</strong><br>' +
                            response.detalhes +
                            '</div>'
                        );
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: response.message,
                            confirmButtonText: 'OK'
                        });
                        
                        // Exibe erro
                        $("#resultadoArea").show();
                        $("#resultadoMensagem").html(
                            '<div class="alert alert-danger">' +
                            '<i class="fa fa-exclamation-circle"></i> ' +
                            '<strong>Erro ao processar:</strong><br>' +
                            response.message +
                            '</div>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    // Reabilita o botão
                    $("#btnGravar").prop("disabled", false).html('<i class="fa fa-save"></i> Gravar Taxa');
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro de Comunicação',
                        text: 'Não foi possível comunicar com o servidor. Tente novamente.',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}

/**
 * Formata o campo de valor para aceitar apenas números e vírgula
 * Formato: 0,00
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
    
    // Remove zeros à esquerda, exceto se for o único dígito antes da vírgula
    if (partes[0].length > 1 && partes[0][0] === '0') {
        partes[0] = partes[0].replace(/^0+/, '') || '0';
        valor = partes.join(',');
    }
    
    campo.value = valor;
}

/**
 * Formata valor para exibição em Real brasileiro
 */
function formatarMoeda(valor) {
    if (!valor) return "R$ 0,00";
    
    // Converte para número
    var numero = parseFloat(valor.toString().replace(',', '.'));
    
    // Formata
    return "R$ " + numero.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
