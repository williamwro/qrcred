<?PHP
    // Permitir acesso de qualquer origem
    header("Access-Control-Allow-Origin: *");
    
    // Definir métodos HTTP permitidos
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    
    // Permitir headers específicos
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    
    // Definir por quanto tempo (em segundos) o navegador pode armazenar em cache os resultados da preflight request
    header("Access-Control-Max-Age: 86400");
    
    // Definir content-type como JSON
    header("Content-Type: application/json; charset=UTF-8");
    
    include "Adm/php/banco.php";
    include "Adm/php/funcoes.php";
    
    try {
        $pdo = Banco::conectar_postgres();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Validar e capturar parâmetros obrigatórios
        $matricula = isset($_POST['matricula']) && !empty($_POST['matricula']) ? $_POST['matricula'] : null;
        $empregador = isset($_POST['empregador']) && !empty($_POST['empregador']) ? (int)$_POST['empregador'] : null;
        $mes = isset($_POST['mes']) && !empty($_POST['mes']) ? $_POST['mes'] : null;
        $divisao = isset($_POST['divisao']) && !empty($_POST['divisao']) ? (int)$_POST['divisao'] : null;
        $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;
        
        // Log dos parâmetros recebidos para debug
        error_log("conta_app.php - Parâmetros recebidos: matricula=$matricula, empregador=$empregador, mes=$mes, divisao=$divisao, id=$id");
        
        // Validar parâmetros obrigatórios
        $erros = [];
        if ($matricula === null) $erros[] = "Parâmetro 'matricula' é obrigatório";
        if ($empregador === null) $erros[] = "Parâmetro 'empregador' é obrigatório";
        if ($mes === null) $erros[] = "Parâmetro 'mes' é obrigatório";
        if ($divisao === null) $erros[] = "Parâmetro 'divisao' é obrigatório";
        if ($id === null) $erros[] = "Parâmetro 'id' é obrigatório";
        
        if (!empty($erros)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Parâmetros obrigatórios ausentes',
                'details' => $erros,
                'received_params' => [
                    'matricula' => $matricula,
                    'empregador' => $empregador,
                    'mes' => $mes,
                    'divisao' => $divisao,
                    'id' => $id
                ]
            ]);
            exit;
        }
        
        // Preparar e executar a query com prepared statements para segurança
        $sql = "SELECT associado.codigo AS associado, associado.nome, 
                       convenio.razaosocial, convenio.nomefantasia, conta.lancamento, conta.valor, conta.mes, 
                       conta.parcela, conta.data as dia, conta.hora, convenio.cnpj,
                       empregador.id AS id_empregador, empregador.nome AS nome_empregador, 
                       divisao.id_divisao, divisao.nome AS nome_divisao, conta.uri_cupom
                FROM sind.divisao 
                INNER JOIN (sind.empregador 
                INNER JOIN ((sind.tipoconvenio 
                INNER JOIN sind.convenio 
                        ON tipoconvenio.codigo = convenio.tipo) 
                INNER JOIN (sind.associado 
                INNER JOIN sind.conta 
                        ON associado.codigo = conta.associado AND associado.empregador = conta.empregador) 
                        ON convenio.codigo = conta.convenio) 
                        ON (conta.empregador = empregador.id) 
                       AND (empregador.id = associado.empregador)) 
                        ON divisao.id_divisao = empregador.id_divisao
                WHERE associado.codigo = :matricula 
                  AND associado.empregador = :empregador 
                  AND conta.mes = :mes 
                  AND conta.divisao = :divisao 
                  AND associado.id = :id 
                ORDER BY conta.lancamento ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':matricula', $matricula, PDO::PARAM_STR);
        $stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
        $stmt->bindParam(':mes', $mes, PDO::PARAM_STR);
        $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        $stmt->execute();
        
        $someArray = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $someArray[] = array_map(function($value) {
                return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
            }, $row);
        }
        
        // Log do resultado para debug
        error_log("conta_app.php - Registros encontrados: " . count($someArray));
        
        echo json_encode($someArray);
        
    } catch (PDOException $e) {
        error_log("conta_app.php - Erro de banco de dados: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Erro interno do servidor',
            'details' => 'Erro de banco de dados',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("conta_app.php - Erro geral: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Erro interno do servidor',
            'details' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
?>
