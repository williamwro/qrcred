<?PHP
date_default_timezone_set('America/Sao_Paulo');
include "Adm/php/funcoes.php";
$cod_convenio       = "";
$userconv           = "";
$passconv           = "";
if(isset( $_POST['userconv'] )){ $userconv=$_POST['userconv']; }else{ $userconv=""; }
if(isset( $_POST['passconv'] )){ $passconv=$_POST['passconv']; }else{ $passconv=""; }
if (isset($_POST['cod_convenio'])) {
$cod_convenio = $_POST['cod_convenio'];
}else{
$cod_convenio = "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Bootstrap Sider Menu Example</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/4.3.1/flatly/bootstrap.min.css">
    <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css"/>
</head>
<body>
<div class="container">
    <!-- Main content -->
    <div class="well well-sm rounded" style="background: #ecf0f1;padding: 4px;margin-left: -20px;margin-right: -20px;">
        <div class="well well-sm rounded" style="background: #cecece;padding: 4px;">
            <h4 class="display-6 text-center"><span id="titulo_convenio">SUPERMERCADOS BH</span></h4>
            <h6 class="text-center font-weight-light"><span id="titulo_endereco">RUA HUMBERTO PIZZO, 999 - JARDIM CANAA, CNPJ :04.641.376/0001-36, VARGINHA-MG</span></h6>
        </div>
        <section class="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="navbar-header">

                    <a class="navbar-brand" href="#">Período</a>
                </div>
                <form id="listagem" name="listagem" class="form-inline" role="search">
                    <div class="form-group" style="padding-right: 15px;">
                        <label for="C_datainicial" class="sr-only i_bd">Data inicial:</label>
                        <input type="text" name="C_datainicial" id="C_datainicial" placeholder="Data inicial" class="form-control"/>
                        <input type="hidden" name="cod_convenio" id="cod_convenio" value="<?PHP echo $cod_convenio;?>"/>
                    </div>
                    <div class="form-group" style="padding-right: 15px;">
                        <label for="C_datafinal" class="sr-only i_bd">Data inicial:</label>
                        <input type="text" name="C_datafinal" id="C_datafinal"  placeholder="Data final" class="form-control"/>
                    </div>
                    <div class="form-group">
                        <input type="button" style="margin-right: 17px;" name="btnAtualizar" id="btnAtualizar" class="form-control btn btn-primary" value="Exibir"/>
                    </div>
                    <div class="form-group">
                        <input type="button" name="gerarplanilha" id="gerarplanilha" class="form-control btn btn-primary" value="Exportar em Excel"/>
                    </div>
                </form>
            </nav>
            <div class="row">
                <div class="col-md-12">
                    <table id="tabela_producao" class="display table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>REGISTRO</th>
                            <th>NOME</th>
                            <th>DIA</th>
                            <th>HORA</th>
                            <th>VALOR TOTAL</th>
                            <th>VALOR PARCELA</th>
                            <th>PARCELA</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="js/jquery-fallr-2.0.1.js"></script>
<script type="text/javascript" src="js/maskmoney.js"></script>
<script type="text/javascript" src="js/jquery.redirect.js"></script>
<script type="text/javascript" src="js/sweetalert2.all.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
<script src="js/printThis.js"></script>
<script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js"></script>
<script type="text/javascript" src="js/relatorio_vendas2.js"></script>
</body>
</html>