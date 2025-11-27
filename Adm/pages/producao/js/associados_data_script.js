var table;
var divisao;
var divisao_nome;
var usuario_global;
var usuario_cod;

$C_mes = $('#C_mes');
$C_data_inicial = $('#C_data_inicial');
$C_data_final = $('#C_data_final');

$(document).ready(function() {
    divisao = sessionStorage.getItem("divisao");
    divisao_nome = sessionStorage.getItem("divisao_nome");
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");

    // Carrega os meses disponíveis
    $.getJSON("../Adm/pages/producao/meses_conta.php", { "origem": "convenio", "divisao": divisao, "divisao_nome": divisao_nome }, function(data) {
        $.each(data, function(index, value) {
            if (value.abreviacao !== undefined) {
                $('#C_mes').append('<option value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
            }
        });
    });
    
    // Define "Todos os meses" como selecionado
    $('#C_mes').val("");

    waitingDialog.hide();
});

// Evento do botão Exibir
$('#btnExibir').click(function() {
    waitingDialog.show('Carregando, aguarde...');
    $("#tabela_associados_data").show();
    carrega_dados();
    waitingDialog.hide();
});

// Evento do botão Gerar PDF
$('#gerarpdf').click(function() {
    var mes = $('#C_mes').val();
    var data_inicial = $('#C_data_inicial').val();
    var data_final = $('#C_data_final').val();

    $.redirect('../Adm/pages/producao/associados_data_pdf.php', {
        mes: mes,
        data_inicial: data_inicial,
        data_final: data_final,
        divisao: divisao,
        divisao_nome: divisao_nome
    }, "POST", "_blank");
});

// Função para carregar os dados na DataTable
function carrega_dados() {
    if ($.fn.dataTable.isDataTable('#tabela_associados_data')) {
        table.destroy();
    }
    
    table = $('#tabela_associados_data').DataTable({
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        processing: false,
        serverSide: false,
        responsive: true,
        autoWidth: true,
        JQueryUI: true,
        searching: true,
        info: true,
        ajax: {
            url: '../Adm/pages/producao/associados_data_read.php',
            method: 'POST',
            data: function(data) {
                data.mes = $("#C_mes").val();
                data.data_inicial = $("#C_data_inicial").val();
                data.data_final = $("#C_data_final").val();
                data.divisao = divisao;
            },
            dataType: 'json'
        },
        order: [[0, "asc"]],
        columns: [
          
            { data: "nome" },
            { data: "abreviacao" },
            { data: "razaosocial" },
            { data: "mes" },
            { data: "data" },
            { 
                data: "total",
                render: $.fn.dataTable.render.number('.', ',', 2, ''),
                className: "text-right"
            }
        ],
        pagingType: "full_numbers",
        footerCallback: function(row, data, start, end, display) {
            var api = this.api();
            
            // Calcula o total da coluna TOTAL
            var total = api
                .column(5, { page: 'current' })
                .data()
                .reduce(function(a, b) {
                    return parseFloat(a) + parseFloat(b);
                }, 0);
            
            // Formata o total
            var totalFormatado = total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            // Atualiza o rodapé
            $(api.column(5).footer()).html('R$ ' + totalFormatado);
        },
        language: {
            decimal: ",",
            thousands: ".",
            zeroRecords: "Não há dados",
            emptyTable: "Não há dados.",
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
}
