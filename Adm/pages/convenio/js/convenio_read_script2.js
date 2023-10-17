$(document).ready(function(){
    var table;
    // econstroi uma datatabe no primeiro carregamento da tela
    table = $('#tabela_producao').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": false,
        "serverSide": false,
        "responsive": true,
        "autoWidth": true,
        "ajax": {
            "url": 'convenio_categorias_app.php',
            "method": 'POST',
            "data": "",
            "dataType": 'json'
        },
        "order": [[ 0, "asc" ]],
        "columns": [
            { "data": "nomefantasia" },
            { "data": "nome_categoria" },
            { "data": "endereco" },
            { "data": "numero" },
			{ "data": "bairro" },
            { "data": "telefone" },
        ],
        "pagingType": "full_numbers",
        "language": {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Portuguese-Brasil.json",
            "decimal": ",",
            "thousands": "."
        }
    });
    $('#gerarpdf').click(function () {
        var cod_convenio = $('#cod_convenio').val();
        var mes_atual = $('#mes_atual').val();
        var ano = $('#ano').val();
        $.redirect('gerador_pdf_convenios.php',{ cod_convenio: cod_convenio, mes_atual: mes_atual, ano: ano});
    });
    // Array to track the ids of the details displayed rows
    var detailRows = [];

});