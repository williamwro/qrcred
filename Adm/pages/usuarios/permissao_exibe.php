<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST["codigo_menu"])){
    $std = new stdClass();
    $codigo_menu     = $_POST["codigo_menu"];
    $status          = $_POST["status"];
    $codigo_usuario  = $_POST["codigo_ususario"];
    $menu            = $_POST["menu"];

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
               WHERE dynamic_menu.id = ".$codigo_menu." AND usuarios_menu.codigo_usuario = ".$codigo_usuario."ORDER BY dynamic_menu.id";

    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();
    $linha = array();

    foreach ($result as $row){

        $std->menu_item_id   = $row["menu_item_id"];
        $std->menu_item_name = htmlspecialchars($row["menu_item_name"]);
        $std->status_usuario = $row["status_usuario"];
        $std->codigo_usuario = $row["codigo_usuario"];

    }
    echo json_encode($std);
}