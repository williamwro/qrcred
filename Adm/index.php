<?PHP
include "php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST['Username'])){
    $usuario = $_POST['Username'];
}
if(isset($_POST['codigo'])){
    $cod_usuario = $_POST['codigo'];
}
if (isset($_POST['caminho'])){
    $caminho = $_POST['caminho'];
}else{
    $caminho = "";
}
if (isset($_POST['matricula'])){
    $matricula = $_POST['matricula'];
}else{
    $matricula = "";
}
if (isset($_POST['nome'])){
    $nome = $_POST['nome'];
}else{
    $nome = "";
}
if (isset($_POST['empregador'])){
    $empregador = $_POST['empregador'];
}else{
    $empregador = "";
}
if (isset($_POST['divisao_nome'])){
    $divisao_nome = $_POST['divisao_nome'];
}else{
    $divisao_nome = "";
}
if (isset($_POST['passuser'])){
    $passuser = $_POST['passuser'];
}else{
    $passuser = "";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title id="title_inicio"></title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport" />
    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css" />
    <!-- Ionicons sind/Adm/ -->
    <link rel="stylesheet" href="bower_components/Ionicons/css/ionicons.min.css" />
    <link rel="stylesheet" href="//cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.1.2/css/rowGroup.dataTables.min.css"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.1/css/select.dataTables.min.css"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.1.6/css/fixedHeader.dataTables.min.css"/>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap3-dialog/1.34.9/css/bootstrap-dialog.min.css"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.6.2/css/buttons.dataTables.min.css"/>
    <link rel="stylesheet" href="dist/css/AdminLTE.min.css" />
    <link rel="stylesheet" href="dist/css/skins/_all-skins.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap3-dialog/1.34.9/css/bootstrap-dialog.min.css" />
    <link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.12/css/dataTables.checkboxes.css" rel="stylesheet" />
    <!-- Theme style -->
    <link rel="stylesheet" href="dist/css/AdminLTE.min.css" />
    <link rel="stylesheet" href="dist/css/skins/_all-skins.min.css" />
    <style type="text/css">
        th {
            font-size: 12px;
            white-space: nowrap;
        }
        td {
            font-size: 11px;
        }

        .form-group {
            margin-bottom: 2px;
        }
        /*corrige o visual input select no mozila*/
        .i_bd {
            /* Disable vendor-specific appearance */
            -webkit-appearance: none;
            /* Use triangle background as arrow */
            background-image: url(data:image/svg+xml;base64,PHN2ZyBmaWxsPSIjMDAwMDAwIiBoZWlnaHQ9IjI0IiB2aWV3Qm94PSIwIDAgMjQgMjQiIHdpZHRoPSIyNCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4gICAgPHBhdGggZD0iTTcgMTBsNSA1IDUtNXoiLz48L3N2Zz4=);
            background-size: 24px 24px;
            background-repeat: no-repeat;
            background-position: center right;
            padding-top: 0;
        }
        #tabela_producao_length select {
            /* Disable vendor-specific appearance */
            -webkit-appearance: none;
            /* Use triangle background as arrow */
            background-image: url(data:image/svg+xml;base64,PHN2ZyBmaWxsPSIjMDAwMDAwIiBoZWlnaHQ9IjI0IiB2aWV3Qm94PSIwIDAgMjQgMjQiIHdpZHRoPSIyNCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4gICAgPHBhdGggZD0iTTcgMTBsNSA1IDUtNXoiLz48L3N2Zz4=);
            background-size: 24px 24px;
            background-repeat: no-repeat;
            background-position: center right;
            padding-top: 0;
        }
        td.details-control {
            background: url('../img/details_open.png') no-repeat center center;
            cursor: pointer;
            width: 40px;
        }
        tr.details td.details-control {
            background: url('../img/details_close.png') no-repeat center center;
            width: 40px;
        }
        .material-switch > input[type="checkbox"] {
            display: none;
        }
        .material-switch > label {
            cursor: pointer;
            height: 0px;
            position: relative;
            width: 40px;
        }
        .material-switch > label::before {
            background: rgb(0, 0, 0);
            box-shadow: inset 0px 0px 10px rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            content: '';
            height: 16px;
            margin-top: -8px;
            position:absolute;
            opacity: 0.3;
            transition: all 0.4s ease-in-out;
            width: 40px;
        }
        .material-switch > label::after {
            background: rgb(255, 255, 255);
            border-radius: 16px;
            box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.3);
            content: '';
            height: 24px;
            left: -4px;
            margin-top: -8px;
            position: absolute;
            top: -4px;
            transition: all 0.3s ease-in-out;
            width: 24px;
        }
        .material-switch > input[type="checkbox"]:checked + label::before {
            background: inherit;
            opacity: 0.5;
        }
        .material-switch > input[type="checkbox"]:checked + label::after {
            background: inherit;
            left: 20px;
        }
    </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<input type="hidden" value="<?PHP echo $caminho; ?>" id="caminho">
<input type="hidden" value="<?PHP echo $matricula; ?>" id="matricula">
<input type="hidden" value="<?PHP echo $nome; ?>" id="nome">
<input type="hidden" value="<?PHP echo $empregador; ?>" id="empregador">
<input type="hidden" value="<?PHP echo $usuario; ?>" id="usuario">
<input type="hidden" value="<?PHP echo $passuser; ?>" id="passuser">
<div class="wrapper">
    <header class="main-header">
        <!-- Logo -->
        <a href="#" class="logo">
            <!-- mini logo for sidebar mini 50x50 pixels -->
            <span class="logo-mini"><b><span id="logo1"></span></b><span id="logo2"></span></span>
            <!-- logo for regular state and mobile devices -->
            <span class="logo-lg"><b><span id="logo3"></span></b><span id="logo4"></span></span>
        </a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top">
            <!-- Sidebar toggle button-->
            <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button"> <span class="sr-only">Toggle navigation</span> </a>
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">
                    <li class="dropdown user user-menu">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <img src="dist/img/person-icon.png" class="user-image" alt="User Image">
                            <span class="hidden-xs"><?PHP echo $nome . " [ " .  $divisao_nome. " ] "; ?></span>
                        </a>
                    </li>
                    <li class="dropdown user user-menu">
                        <a href="#" id="botao_sair" class="dropdown-toggle" data-toggle="dropdown">Sair</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <!-- Left side column. contains the logo and sidebar -->
    <aside class="main-sidebar">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">
            <?php
            //require_once('pdo_db.php');
            //$pdo= new core_db;
            $hasil=$pdo->query("SELECT dynamic_menu.id as menu_item_id,
                                                             dynamic_menu.parent_id as menu_parent_id,
                                                             dynamic_menu.title as menu_item_name,
                                                             dynamic_menu.url,
                                                             dynamic_menu.menu_order,
                                                             dynamic_menu.status,
                                                             dynamic_menu.level,
                                                             dynamic_menu.icon,
                                                             dynamic_menu.description,
                                                             usuarios_menu.codigo_usuario,
                                                             usuarios_menu.status as status_usuario
                                                        FROM sind.usuarios_menu
                                                  RIGHT JOIN sind.dynamic_menu 
                                                          ON dynamic_menu.id = usuarios_menu.id_menu
                                                       WHERE usuarios_menu.codigo_usuario = ".$cod_usuario." ORDER BY dynamic_menu.id");
            $refs = array();
            $list = array();
            while($data = $hasil->fetch(PDO::FETCH_ASSOC))
            {
                if($data['status_usuario'] == 1){
                    $thisref = &$refs[ $data['menu_item_id'] ];
                    $thisref['menu_parent_id'] = $data['menu_parent_id'];
                    $thisref['menu_item_name'] = $data['menu_item_name'];
                    $thisref['url'] = $data['url'];
                    $thisref['icon'] = $data['icon'];
                    if ($data['menu_parent_id'] == 0)
                    {
                        $list[ $data['menu_item_id'] ] = &$thisref;
                    }
                    else
                    {
                        $refs[ $data['menu_parent_id'] ]['children'][ $data['menu_item_id'] ] = &$thisref;
                    }
                }
            }
            function create_list( $arr ,$urutan)
            {
                if($urutan==0){
                    $html = "\n<ul class='sidebar-menu tree' data-widget='tree'><li class='header'>MENU PRINCIPAL</li>\n";
                }else
                {
                    $html = "\n<ul class='treeview-menu' style='display:none'>\n";
                }
                foreach ($arr as $key=>$v)
                {
                    if (array_key_exists('children', $v))
                    {
                        $html .= "<li class='treeview'>\n";
                        $html .= '<a href="#">
                                                <i class="'.$v['icon'].'"></i>
                                                <span>'.$v['menu_item_name'].'</span>
                                                <span class="pull-right-container">
                                                    <i class="fa fa-angle-left pull-right"></i>
                                                </span>
                                          </a>';

                        $html .= create_list($v['children'],1);
                        $html .= "</li>\n";
                    }
                    else{
                        $html .= '<li><a href="#" id="'.$v['url'].'">';
                        if($urutan==0)
                        {
                            $html .=	'<i class="'.$v['icon'].'"></i>';
                        }
                        if($urutan==1)
                        {
                            $html .=	'<i class="fa fa-angle-double-right"></i>';
                        }
                        $html .= "<span>".$v['menu_item_name']."</span></a></li>\n";}
                }
                $html .= "</ul>\n";
                return $html;
            }
            echo create_list( $list,0 );
            ?>
        </section>
        <!-- sidebar -->
    </aside>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <!-- Main content -->
        <section class="content" style="padding-top: 0; margin: 0;">
            <div id="pagina_conteudo"></div>
        </section>
        <!-- content -->
    </div>
    <!-- content-wrapper -->
    <footer class="main-footer">
        <div class="pull-right hidden-xs">
            <b>Version</b> 2.0.0
        </div>
        <strong><span id="texto_footer"></span></strong> All rights
        reserved.
    </footer>
    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <div class="tab-content">
        </div>
    </aside>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" type="text/javascript" ></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="//cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/rowgroup/1.1.2/js/dataTables.rowGroup.min.js"></script>

<script src="https://cdn.datatables.net/fixedheader/3.1.6/js/dataTables.fixedHeader.min.js"></script>
<script src="https://cdn.rawgit.com/mgalante/jquery.redirect/master/jquery.redirect.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-notify/0.2.0/js/bootstrap-notify.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-waitingfor/1.2.8/bootstrap-waitingfor.min.js"></script>
<script src="https://cdn.datatables.net/select/1.3.1/js/dataTables.select.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap3-dialog/1.34.9/js/bootstrap-dialog.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
<script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.2/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@9" type="text/javascript" ></script>
<script src="../Adm/pages/cartoes/js/FileSaver.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.2/js/buttons.print.min.js"></script>
<script src="../js/validator.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.12/js/dataTables.checkboxes.min.js"></script>
<script src="index.js"></script>

</body>
</html>