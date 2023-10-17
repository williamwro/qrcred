    $(document).ready(function(){
        var table;
        table = $('#tabela_producao').DataTable({
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],

            "ajax": {
                "url": 'pages/cartoes/cartoes_read2.php',
                "method": 'POST',
                "data":"",
                "dataType": 'json'
            },
            "order": [[ 2, "asc" ]],
            "columns": [
                { "data": "nome" },
                { "data": "cod_verificacao" },
                { "data": "descri_situacao" },
                { "data": "motivo_cancela" },
                { "data": "botao" },
                { "data": "botaosenha" }
            ],

            "pagingType": "full_numbers",
            "language": {
                url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Portuguese-Brasil.json",
                "decimal": ",",
                "thousands": "."
            }
        });

    });
    $(function () {
        var progressbar = $("#progressbar"),
            progressLabel = $(".progress-label");

        progressbar.progressbar({
            value: false,
            change: function () {
                progressLabel.text(progressbar.progressbar("value") + "%");
            },
            complete: function () {
                progressLabel.text("Complete!");
            }
        });

        function progress() {
            var val = progressbar.progressbar("value") || 0;

            progressbar.progressbar("value", val + 1);

            if (val < 99) {
                setTimeout(progress, 100);
            }
        }

        setTimeout(progress, 3000);

    });
