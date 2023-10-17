<!DOCTYPE html>
<html lang="pt-br">
<head>
    <title>SINDSERVA - Relatório de Produção do Convenio<?PHP echo $_POST['cod_convenio'];?></title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>

    <style type="text/css">
        @import "datatables/DataTable/css/jquery.dataTables.min.css";
        @import "datatables/Buttons-1.5.1/css/buttons.dataTables.min.css";
        @import "bootstrap/css/bootstrap.css";
    </style>

    <script src="js/jquery-3.4.1.js"></script>
    <script src="bootstrap/js/bootstrap.js"></script>

     <script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/buttons/1.5.1/js/dataTables.buttons.min.js"></script>
     <script src="//cdn.datatables.net/buttons/1.5.1/js/buttons.flash.min.js"></script>
     <script src="//cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
     <script src="//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/pdfmake.min.js"></script>
     <script src="//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/vfs_fonts.js"></script>
     <script src="//cdn.datatables.net/buttons/1.5.1/js/buttons.html5.min.js"></script>
     <script src="//cdn.datatables.net/buttons/1.5.1/js/buttons.print.min.js"></script>
     <script src="//cdn.datatables.net/tabletools/2.2.4/js/dataTables.tableTools.min.js"></script>
     <script type="text/javascript" src="js/jquery.redirect.js"></script>
     <!-- Inclui todos os plugins compilados (abaixo), ou inclua arquivos separadados se necessário -->

    <script type="text/javascript">
        $(document).ready(function(){
             $('#ImprimirRelatorio').click(function() {
                $.ajax({
                    url: 'list_vendas_conv2.php',
                    type: 'post',
                    data: $("#listagem").serialize(),
                    dataType: 'json',
                    success: function(data) {
                        $.redirect('list_vendas_conv1.php',data);
                    }
                });
            });
         });
    </script>
    <style type="text/css">.printableFull {
            display: none;
        }

        /* print styles*/
        @media print {
            .printableFull {
                display: block;
            }

            .printableLeft {
                font-stretch: condensed!important;
            }

            .screen {
                display: none;
            }
        }

        #infosucesso {
            top: 200px;
            width: 775px;
            height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        #infosucesso p {
            padding-top: 40px;
        }

        img {
            max-width: 20pt;
            max-height: 20pt;
        }</style>
</head>
<?PHP
    include "Adm/php/funcoes.php";

    if(!isset($_POST['mes_atual'])){
        $cod_convenio = $_GET['cod_convenio'];
        $mes_atual    = $_GET['mes_atual'];
        $vetor        = explode("/",$mes_atual);
        $ano          = $vetor[1];
        $_POST['mes_atual'] = $mes_atual;
        //$mes          = busca_mes($mes_atual);
    }else{
        $cod_convenio = $_POST['cod_convenio'];
        $ano          = $_POST['ano'];
        $mes          = $_POST['mes_atual'];
        $mes_atual    = $mes."/".$ano;
        $mes_atual    = busca_mes2($mes_atual);
        $mes_atual    = $mes_atual."/".$ano;
    }

?>
<style type="text/css">

    .row{
        padding: 20px;

    }
    .centraliza {
        width: 300px;
        margin: 0 auto; !important;
        float: none; !important;
        background: snow;
        padding: 20px;
    }

</style>
<body style="background: silver">
<div style="position: absolute;height: 116px;top: 50%;width: 100%;left: 0;">
    <div style="position: relative; height: 116px;top: -160px;">
        <div class="container">
            <div class="row">
                <h4 align="center">Relatório produção do convenio</h4>
                <div class="col-md-2 centraliza">
                    <form id="listagem" class="form">
                        <div class="form-group">
                            <label for="mes_atual">MÊS:</label>
                            <select name="mes_atual" id="mes_atual" class="form-control">
                                <option value='JANEIRO'>JANEIRO</option>
                                <option value='FEVEREIRO'>FEVEREIRO</option>
                                <option value='MARÇO'>MARÇO</option>
                                <option value='ABRIL'>ABRIL</option>
                                <option value='MAIO'>MAIO</option>
                                <option value='JUNHO'>JUNHO</option>
                                <option value='JULHO'>JULHO</option>
                                <option value='AGOSTO'>AGOSTO</option>
                                <option value='SETEMBRO'>SETEMBRO</option>
                                <option value='OUTUBRO'>OUTUBRO</option>
                                <option value='NOVEMBRO'>NOVEMBRO</option>
                                <option value='DEZEMBRO'>DEZEMBRO</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ano">ANO</label>
                            <input type="text" name="ano" id="ano" class="form-control" value="<?PHP echo $ano; ?>"/>
                        </div>
                        <input type="button" name="ImprimirRelatorio" id="ImprimirRelatorio" class="btn btn-primary" value="Relatório"/>
                        <input type="button" name="retornar" id="retornar" class="btn btn-default" onClick="javascript:history.go(-1)" value="Voltar" />
                        <input type="hidden" name="mes_escolhido" id="mes_escolhido" value="<?PHP echo $mes_atual;?>"/>
                        <input type="hidden" name="cod_convenio" id="cod_convenio" value="<?PHP echo $cod_convenio; ?>"/>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>