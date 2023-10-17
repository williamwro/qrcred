<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Alterar senha</title>
    <link rel="stylesheet" href="Adm/bower_components/bootstrap/dist/css/bootstrap.min.css">
    <!-- <script src="Adm/bower_components/bootstrap/dist/js/bootstrap.js"></script> -->
    <script type="application/javascript" src="js/jquery-3.4.1.js"></script>
    <script type="text/javascript" src="js/jquery.redirect.js"></script>
    <script type="text/javascript" src="js/sweetalert2.all.js"></script>

    <!------ Include the above in your HEAD tag ---------->
</head>
<body>
<?PHP
$chave = "";
    if($_GET['chave']){
        $chave = preg_replace('/[^[:alnum:]]/','',$_GET['chave']);

    $user = $_GET['user'];
?>
<div class="container">
    <div id="loginbox" style="margin-top:50px;" class="mainbox col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
        <div class="panel panel-info" >
            <div class="panel-heading">
                <div class="panel-title">Makecard - Alterando</div>
            </div>
            <div style="padding-top:30px" class="panel-body" >

                <div style="display:none" id="login-alert" class="alert alert-danger col-sm-12"></div>

                <form action="set_nova_senha.php" method="POST" id="formrecupararsenha" class="form-horizontal" role="form">
                    <div style="margin-bottom: 25px" class="input-group">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-envelope"></i></span>
                        <input id="user" type="text" readonly class="form-control" name="user" value="<?PHP echo $user; ?>">
                    </div>
                    <div style="margin-bottom: 25px" class="input-group">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-envelope"></i></span>
                        <input id="email" type="email" class="form-control" name="email" value="" placeholder="E-mail">
                        <input type="hidden" name="chave" value="<?PHP echo $chave; ?>">
                    </div>
                    <div style="margin-bottom: 25px" class="input-group">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                        <input id="novasenha" type="password" class="form-control" name="novasenha" value="" placeholder="Nova senha">
                    </div>
                    <div style="margin-bottom: 25px" class="input-group">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                        <input id="confirmanovasenha" type="password" class="form-control" name="confirmanovasenha" value="" placeholder="Confirma nova senha">
                    </div>
                    <div style="margin-top:10px" class="form-group">
                        <!-- Button -->
                        <div class="col-sm-12 controls">
                            <button id="btn-login" class="btn btn-success">Enviar  </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?PHP
   } else{
        echo '<h1>pagina não encontrata';
    }
    ?>
</body>
</html>
