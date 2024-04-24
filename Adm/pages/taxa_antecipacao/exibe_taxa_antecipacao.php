<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $std = new stdClass();
   
    $query = "SELECT id, porcentagem FROM sind.taxa_antecipacao;";
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();

    foreach ($result as $row){
        $std->id           = $row["id"];
        $std->porcentagem  = $row["porcentagem"];
    }
    echo json_encode($std);