<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $std = new stdClass();
   
    $query = "SELECT id, email FROM sind.email_antecipacao;";
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();

    foreach ($result as $row){
        $std->id     = $row["id"];
        $std->email  = $row["email"];
    }
    echo json_encode($std);
