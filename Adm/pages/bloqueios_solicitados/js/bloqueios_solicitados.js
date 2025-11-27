// bloqueios_solicitados.js - Gerenciamento de Bloqueios Solicitados
var table_solicitacoes;
var divisao;
var usuario_global;

$(document).ready(function(){
    // Obter divisão e usuário do sessionStorage (padrão do sistema)
    divisao = sessionStorage.getItem("divisao");
    usuario_global = sessionStorage.getItem("usuario_global");
    $('#divisao').val(divisao);
    
    // Carregar solicitações ao abrir a tela (filtro padrão: Todos)
    $('#filtro_situacao_bloqueio').val('todos');
    carrega_solicitacoes_bloqueio('todos');

    // Evento de mudança do filtro de situação
    $("#filtro_situacao_bloqueio").change(function() {
        var situacao = $(this).val();
        carrega_solicitacoes_bloqueio(situacao);
    });
});

// Função para carregar as solicitações de bloqueio
function carrega_solicitacoes_bloqueio(situacao) {
    if ($.fn.dataTable.isDataTable('#tab_solicitacoes_bloqueio')) {
        table_solicitacoes.destroy();
    }

    table_solicitacoes = $('#tab_solicitacoes_bloqueio').DataTable({
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
        processing: true,
        serverSide: false,
        autoWidth: true,
        ajax: {
            url: 'pages/bloqueios_solicitados/listar_solicitacoes_bloqueio.php',
            method: 'POST',
            data: {
                'situacao': situacao,
                'divisao': divisao
            },
            dataType: 'json'
        },
        order: [[4, 'desc']],
        columns: [
            { data: "cod_verificacao" },
            { data: "nome_associado" },
            { data: "matricula" },
            { data: "empregador" },
            { data: "data_hora" },
            { data: "data_hora_resposta" },
            { data: "situacao" },
            { 
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    // Só mostra botões se estiver pendente (id_situacao = 1 ou null)
                    if (row.id_situacao == 1 || row.id_situacao == null || row.id_situacao == '') {
                        return '<button class="btn-aprovar" onclick="aprovarBloqueio(' + row.id + ')"><i class="fa fa-check"></i> Aprovar</button>' +
                               '<button class="btn-reprovar" onclick="reprovarBloqueio(' + row.id + ')"><i class="fa fa-times"></i> Reprovar</button>';
                    }
                    return '-';
                }
            }
        ],
        language: {
            decimal: ",",
            thousands: ".",
            processing: "Processando...",
            zeroRecords: "Nenhuma solicitação encontrada",
            emptyTable: "Nenhuma solicitação de bloqueio.",
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
            lengthMenu: "_MENU_ resultados por página"
        }
    });
}

// Função para aprovar bloqueio
function aprovarBloqueio(id) {
    Swal.fire({
        title: 'Aprovar Bloqueio?',
        text: 'O cartão do associado será bloqueado.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#5cb85c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, Aprovar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processando...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: 'pages/bloqueios_solicitados/processar_bloqueio.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    id: id,
                    acao: 'aprovar',
                    usuario_global: usuario_global
                },
                success: function(response) {
                    Swal.close();
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Bloqueio Aprovado!',
                            text: 'O cartão foi bloqueado com sucesso.',
                            confirmButtonText: 'OK'
                        });
                        carrega_solicitacoes_bloqueio($('#filtro_situacao_bloqueio').val());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: response.message || 'Erro ao processar.'
                        });
                    }
                },
                error: function() {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao processar a solicitação.'
                    });
                }
            });
        }
    });
}

// Função para reprovar bloqueio
function reprovarBloqueio(id) {
    Swal.fire({
        title: 'Reprovar Bloqueio?',
        text: 'A solicitação será reprovada e o cartão permanecerá ativo.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d9534f',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, Reprovar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processando...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: 'pages/bloqueios_solicitados/processar_bloqueio.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    id: id,
                    acao: 'reprovar'
                },
                success: function(response) {
                    Swal.close();
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Solicitação Reprovada',
                            text: 'A solicitação foi reprovada.',
                            confirmButtonText: 'OK'
                        });
                        carrega_solicitacoes_bloqueio($('#filtro_situacao_bloqueio').val());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: response.message || 'Erro ao processar.'
                        });
                    }
                },
                error: function() {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao processar a solicitação.'
                    });
                }
            });
        }
    });
}
