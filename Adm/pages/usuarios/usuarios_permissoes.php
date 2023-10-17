<?PHP
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
    $std = new stdClass();
    if($_POST["operation"]  ==  'Add'){
        $cod_usuario = 0;
    }else{
        $cod_usuario = $_POST["cod_usuario"];
    }
    $query = "SELECT dynamic_menu.id as menu_item_id,
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
               WHERE usuarios_menu.codigo_usuario = ".$cod_usuario." ORDER BY dynamic_menu.id";
    $statment = $pdo->prepare($query);

    $statment->execute();
    $result = $statment->fetchAll();
    $salario='';
    $linha = array();

    foreach ($result as $row){
        $sub_array = array();
        $sub_array["menu_item_id"]     = $row["menu_item_id"];
        $sub_array["menu_parent_id"]   = $row["menu_parent_id"];
        $sub_array["url"]              = $row["url"];
        $sub_array["menu_parent_id"]   = $row["menu_parent_id"];
        $sub_array["menu_item_name"]   = htmlspecialchars($row["menu_item_name"]);
        $sub_array["menu_order"]       = $row["menu_order"];
        $sub_array["description"]      = htmlspecialchars($row["description"]);
        if($row["status_usuario"] ==  1){
            $sub_array["status_usuario"] = "1";
            $sub_array["badges"]       = '<span></span>';
        }else{
            $sub_array["status_usuario"] = "0";
            $sub_array["badges"]       = '<span class="badge badge-pill badge-danger" style="background-color: red">Bloqueado</span>';
        }
        $sub_array["botao"]            = '<button type="button" name="update2" id="'.$row["menu_item_id"].'" class="btn btn-warning btn-xs update2">Alterar</button>';
        $someArray["data"][]           = array_map("utf8_encode",$sub_array);
    }
echo json_encode($someArray);