<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 23/08/2018
 * Time: 14:02
 * 
 * VERSÃO CORRIGIDA COM HEADERS CORS
 */

// HEADERS CORS PARA PERMITIR REQUISIÇÕES DO FRONTEND
// Lista de domínios permitidos
$allowed_origins = [
    'https://sasapp.tec.br',
    'https://sasapp-one.vercel.app'
];

// Verificar se a origem da requisição está na lista permitida
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
} else {
    // Fallback para o domínio principal se a origem não for reconhecida
    header("Access-Control-Allow-Origin: https://sasapp.tec.br");
}

// Definir métodos HTTP permitidos
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

// Permitir headers específicos
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Definir por quanto tempo (em segundos) o navegador pode armazenar em cache os resultados da preflight request
header("Access-Control-Max-Age: 86400");

// Permitir cookies/credenciais (se necessário)
header("Access-Control-Allow-Credentials: true");

// Definir o tipo de conteúdo da resposta
header("Content-Type: application/json; charset=UTF-8");

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Responder apenas com os headers CORS para requisições OPTIONS
    http_response_code(200);
    exit();
}

// Resto do código original...
include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST['codigo'])) {
    $matricula = $_POST['codigo'];
}else{
    $matricula = "";
}

if(isset($_POST['empregador'])) {
    $empregador = $_POST['empregador'];
}else{
    $empregador = "";
}

if(isset($_POST['cel'])) {
    $cel = $_POST['cel'];
}else{
    $cel = "";
}

if(isset($_POST['cpf'])) {
    $cpf = $_POST['cpf'];
}else{
    $cpf = "";
}

if(isset($_POST['email'])) {
    $email = $_POST['email'];
}else{
    $email = "";
}

if(isset($_POST['cep'])) {
    $cep = $_POST['cep'];
}else{
    $cep = "";
}

if(isset($_POST['endereco'])) {
    $endereco = $_POST['endereco'];
}else{
    $endereco = "";
}

if(isset($_POST['numero'])) {
    $numero = $_POST['numero'];
}else{
    $numero = "";
}

if(isset($_POST['bairro'])) {
    $bairro = $_POST['bairro'];
}else{
    $bairro = "";
}

if(isset($_POST['cidade'])) {
    $cidade = $_POST['cidade'];
}else{
    $cidade = "";
}

if(isset($_POST['estado'])) {
    $estado = $_POST['estado'];
}else{
    $estado = "";
}

if(isset($_POST['celzap'])) {
    if ($_POST['celzap'] === "true") {
        $celzap = "true";
    }else{
        $celzap = "false";
    }
}else{
    $celzap = "";
}

$sql = "UPDATE sind.associado SET 
               email = :email,
               cel = :cel,
               cpf = :cpf,
               cep = :cep, 
               endereco = :endereco,
               numero = :numero,
               bairro = :bairro,
               cidade = :cidade,
               uf = :estado,
               celwatzap = :celzap
               WHERE codigo = :codigo AND empregador = :empregador";

$stmt = $pdo->prepare($sql);

$stmt->bindParam(':codigo', $matricula, PDO::PARAM_STR);
$stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->bindParam(':cel', $cel, PDO::PARAM_STR);
$stmt->bindParam(':cpf', $cpf, PDO::PARAM_STR);
$stmt->bindParam(':cep', $cep, PDO::PARAM_STR);
$stmt->bindParam(':endereco', $endereco, PDO::PARAM_STR);
$stmt->bindParam(':numero', $numero, PDO::PARAM_STR);
$stmt->bindParam(':bairro', $bairro, PDO::PARAM_STR);
$stmt->bindParam(':cidade', $cidade, PDO::PARAM_STR);
$stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
$stmt->bindParam(':celzap', $celzap, PDO::PARAM_STR);

try {
    $count = $stmt->execute();
    if ($count == 1) {
        echo "gravou";
    } else {
        echo "nao gravou";
    }
} catch (Exception $e) {
    // Log do erro para debugging
    error_log("Erro ao atualizar associado: " . $e->getMessage());
    echo "erro: " . $e->getMessage();
}
?>
