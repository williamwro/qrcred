<?PHP
include "../../php/banco.php";
include "../../php/funcoes.php";
ini_set('display_errors', true);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if(isset($_POST["cpf"])){
        $cpf = $_POST["cpf"];
        
        // Log para debug
        error_log("DEBUG - CPF recebido: " . $cpf);
        
        // Tentar diferentes formatos de CPF
        $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf); // Remove pontos e traços
        $cpf_formatado = substr($cpf_limpo, 0, 3) . '.' . substr($cpf_limpo, 3, 3) . '.' . substr($cpf_limpo, 6, 3) . '-' . substr($cpf_limpo, 9, 2);
        
        error_log("DEBUG - CPF limpo: " . $cpf_limpo);
        error_log("DEBUG - CPF formatado: " . $cpf_formatado);
        
        // Tentar buscar com CPF original, limpo e formatado
        $query = "SELECT codigo, limite, id as id_associado, id_divisao 
                  FROM sind.associado 
                  WHERE cpf = :cpf OR cpf = :cpf_limpo OR cpf = :cpf_formatado
                  LIMIT 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':cpf', $cpf, PDO::PARAM_STR);
        $stmt->bindParam(':cpf_limpo', $cpf_limpo, PDO::PARAM_STR);
        $stmt->bindParam(':cpf_formatado', $cpf_formatado, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("DEBUG - Resultado da consulta: " . json_encode($row));

        if ($row) {
            $response = array(
                'success' => true,
                'codigo' => $row["codigo"],
                'limite' => $row["limite"] ? number_format($row["limite"], 2, ',', '.') : "0,00",
                'id_associado' => $row["id_associado"],
                'id_divisao' => $row["id_divisao"],
                'debug_cpf_usado' => $cpf,
                'debug_cpf_limpo' => $cpf_limpo,
                'debug_cpf_formatado' => $cpf_formatado
            );
            error_log("DEBUG - Associado encontrado: " . json_encode($response));
        } else {
            // Verificar se existe algum registro na tabela
            $count_query = "SELECT COUNT(*) as total FROM sind.associado";
            $count_stmt = $pdo->prepare($count_query);
            $count_stmt->execute();
            $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
            
            $response = array(
                'success' => false,
                'message' => 'Associado não encontrado para este CPF',
                'debug_cpf_usado' => $cpf,
                'debug_cpf_limpo' => $cpf_limpo,
                'debug_cpf_formatado' => $cpf_formatado,
                'debug_total_associados' => $count_result['total']
            );
            error_log("DEBUG - Associado NÃO encontrado: " . json_encode($response));
        }
        
        echo json_encode($response);
    } else {
        echo json_encode(array(
            'success' => false,
            'message' => 'CPF não informado'
        ));
    }
    
} catch (PDOException $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Erro de banco de dados: ' . $e->getMessage()
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Erro geral: ' . $e->getMessage()
    ));
}
?> 