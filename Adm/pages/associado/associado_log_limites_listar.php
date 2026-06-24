<?PHP
if (ob_get_level()) { ob_end_clean(); }
ini_set('display_errors', false);
error_reporting(0);
ob_start();

try {
    include "../../php/banco.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id_associado = isset($_POST['id_associado']) ? (int)$_POST['id_associado'] : 0;

    if ($id_associado <= 0) {
        throw new Exception("id_associado inválido");
    }

    $sql = "SELECT 
                log.id,
                log.associado,
                log.limite_old,
                log.limite_new,
                log.usuario,
                log.datahora,
                COALESCE(u.nome, CAST(log.usuario AS VARCHAR)) AS nome_usuario
            FROM sind.associado_log_limites log
            LEFT JOIN sind.usuarios u ON u.codigo = log.usuario
            WHERE log.id_associado = :id_associado
            ORDER BY log.datahora DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = array();
    foreach ($rows as $row) {
        $data[] = array(
            'datahora'     => $row['datahora'] ? date('d/m/Y H:i:s', strtotime($row['datahora'])) : '',
            'limite_old'   => $row['limite_old'],
            'limite_new'   => $row['limite_new'],
            'nome_usuario' => $row['nome_usuario'],
            'associado'    => $row['associado']
        );
    }

    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('success' => true, 'data' => $data), JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (ob_get_level()) { ob_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('success' => false, 'error' => $e->getMessage()), JSON_UNESCAPED_UNICODE);
}

if (ob_get_level()) { ob_end_flush(); }
?>
