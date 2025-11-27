<?PHP
    header("Content-type: application/json");
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Request-ID, Cache-Control, Pragma, Expires');
    
    include "Adm/php/banco.php";
    include "Adm/php/funcoes.php";
    
    try {
        $pdo = Banco::conectar_postgres();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $origem = $_GET['origem'] ?? 'convenio';
        $divisao = $_GET['divisao'] ?? '';
        
        if (empty($divisao)) {
            echo json_encode(['error' => 'Parâmetro divisao é obrigatório']);
            exit;
        }
        
        $someArray = array();
        $i = 0;
        
        // Buscar mês corrente
        $row = $pdo->query("SELECT abreviacao FROM sind.mes_corrente")->fetch();
        $someArray[$i]["mes_corrente"] = $row["abreviacao"];
        
        // Definir SQL baseado na origem
        if($origem === "admin"){
            $sql = "SELECT abreviacao, data, completo, periodo, status_admin, status_convenio, status_relatorio, id, status_cadastro, status_ultimo_mes, status_cheque, divisao FROM sind.meses_conta WHERE status_admin = 1 AND divisao = ? ORDER BY data";
        }elseif($origem === "convenio"){
            $sql = "SELECT abreviacao, data, completo, periodo, status_admin, status_convenio, status_relatorio, id, status_cadastro, status_ultimo_mes, status_cheque, divisao FROM sind.meses_conta WHERE status_convenio = 1 AND divisao = ? ORDER BY data";
        }elseif($origem === "relatorio"){
            $sql = "SELECT abreviacao, data, completo, periodo, status_admin, status_convenio, status_relatorio, id, status_cadastro, status_ultimo_mes, status_cheque, divisao FROM sind.meses_conta WHERE status_relatorio = 1 AND divisao = ? ORDER BY data";
        }elseif($origem === "cadastro"){
            $sql = "SELECT abreviacao, data, completo, periodo, status_admin, status_convenio, status_relatorio, id, status_cadastro, status_ultimo_mes, status_cheque, divisao FROM sind.meses_conta WHERE status_cadastro = 1 AND divisao = ? ORDER BY data";
        }elseif($origem === "ultimo_mes"){
            $sql = "SELECT abreviacao, data, completo, periodo, status_admin, status_convenio, status_relatorio, id, status_cadastro, status_ultimo_mes, status_cheque, divisao FROM sind.meses_conta WHERE status_ultimo_mes = 1 AND divisao = ? ORDER BY data";
        } else {
            // Padrão para convenio se origem não reconhecida
            $sql = "SELECT abreviacao, data, completo, periodo, status_admin, status_convenio, status_relatorio, id, status_cadastro, status_ultimo_mes, status_cheque, divisao FROM sind.meses_conta WHERE status_convenio = 1 AND divisao = ? ORDER BY data";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($divisao));
        $i++;
        
        while($row = $stmt->fetch()) {
            $someArray[$i] = array_map(function($value) {
                return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
            }, $row);
            $i++;
        }

        echo json_encode($someArray);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
    }
?>
