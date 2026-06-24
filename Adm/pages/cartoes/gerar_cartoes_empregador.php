<?PHP
header("Content-type: application/json");
require '../../php/banco.php';
ini_set('display_errors', false);
error_reporting(0);

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
date_default_timezone_set('America/Sao_Paulo');

$divisao    = isset($_POST['divisao'])    ? (int)$_POST['divisao']    : 0;
$empregador = isset($_POST['empregador']) ? (int)$_POST['empregador'] : 0;
$preview    = isset($_POST['preview'])    ? (int)$_POST['preview']    : 0;

if (!$divisao || !$empregador) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Parâmetros inválidos.']);
    exit;
}

// Gera número de cartão único (10 dígitos, não repete na tabela c_cartaoassociado)
function gerarNumeroCartao($pdo) {
    $tentativas = 0;
    while ($tentativas < 100) {
        $cartao = mt_rand(1111111111, 9999999999);
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM sind.c_cartaoassociado WHERE cod_verificacao = :cartao");
        $stmt->bindParam(':cartao', $cartao, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['total'] == 0) {
            return strval($cartao);
        }
        $tentativas++;
    }
    return false;
}

// Gera senha aleatória de 6 dígitos única na tabela c_senhaassociado
function gerarSenhaAssociado($pdo) {
    $tentativas = 0;
    while ($tentativas < 200) {
        $senha = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM sind.c_senhaassociado WHERE senha = :senha");
        $stmt->bindParam(':senha', $senha, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['total'] == 0) {
            return $senha;
        }
        $tentativas++;
    }
    return false;
}

// Query de associados sem cartão filtrada por empregador
$sql_sem_cartao = "SELECT a.codigo, a.nome, a.empregador, a.id_divisao, a.id
                   FROM sind.associado a
                   WHERE a.id_divisao  = :divisao
                     AND a.empregador  = :empregador
                     AND NOT EXISTS (
                         SELECT 1 FROM sind.c_cartaoassociado c
                         WHERE c.cod_associado = a.codigo
                           AND c.empregador    = a.empregador
                     )";

$stmt = $pdo->prepare($sql_sem_cartao);
$stmt->bindParam(':divisao',    $divisao,    PDO::PARAM_INT);
$stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
$stmt->execute();
$associados = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($associados);

// Modo preview: retorna apenas a contagem
if ($preview) {
    echo json_encode([
        'status' => 'preview',
        'total_sem_cartao' => $total
    ]);
    exit;
}

// Modo geração
if ($total === 0) {
    echo json_encode([
        'status'    => 'success',
        'mensagem'  => 'Nenhum associado sem cartão encontrado para este empregador.',
        'total_associados' => 0,
        'cartoes_gerados'  => 0,
        'erros'            => 0
    ]);
    exit;
}

$data2 = new DateTime();
$data3 = $data2->format('d-m-Y');
$data4 = new DateTime($data3);
$data  = $data4->format('d/m/Y');
$data  = substr($data, 6, 4) . '-' . substr($data, 3, 2) . '-' . substr($data, 0, 2) . ' 00:00:00';

$_codsituacao = 4;

$sql_insert = "INSERT INTO sind.c_cartaoassociado(
                   cod_situacaocartao,
                   cod_associado,
                   cod_verificacao,
                   empregador,
                   data_pedido,
                   cod_situacao2,
                   id_divisao
               ) VALUES (
                   :cod_situacaocartao,
                   :cod_associado,
                   :cod_verificacao,
                   :empregador,
                   :data_pedido,
                   :cod_situacao2,
                   :id_divisao
               )";

$sql_insert_senha = "INSERT INTO sind.c_senhaassociado(cod_associado, senha, id_empregador, id_associado, id_divisao)
                     VALUES(:cod_associado, :senha, :id_empregador, :id_associado, :id_divisao)";

try {
    $pdo->beginTransaction();

    $stmt_insert       = $pdo->prepare($sql_insert);
    $stmt_insert_senha = $pdo->prepare($sql_insert_senha);
    $cartoes_gerados   = 0;
    $erros             = 0;

    foreach ($associados as $assoc) {
        $cartao = gerarNumeroCartao($pdo);
        $senha  = gerarSenhaAssociado($pdo);

        if ($cartao && $senha) {
            $stmt_insert->bindParam(':cod_situacaocartao', $_codsituacao,        PDO::PARAM_INT);
            $stmt_insert->bindParam(':cod_associado',      $assoc['codigo'],     PDO::PARAM_STR);
            $stmt_insert->bindParam(':cod_verificacao',    $cartao,              PDO::PARAM_STR);
            $stmt_insert->bindParam(':empregador',         $assoc['empregador'], PDO::PARAM_INT);
            $stmt_insert->bindParam(':data_pedido',        $data,                PDO::PARAM_STR);
            $stmt_insert->bindParam(':cod_situacao2',      $_codsituacao,        PDO::PARAM_INT);
            $stmt_insert->bindParam(':id_divisao',         $assoc['id_divisao'], PDO::PARAM_INT);
            $stmt_insert->execute();

            $stmt_insert_senha->bindParam(':cod_associado', $assoc['codigo'],     PDO::PARAM_STR);
            $stmt_insert_senha->bindParam(':senha',         $senha,               PDO::PARAM_STR);
            $stmt_insert_senha->bindParam(':id_empregador', $assoc['empregador'], PDO::PARAM_INT);
            $stmt_insert_senha->bindParam(':id_associado',  $assoc['id'],         PDO::PARAM_INT);
            $stmt_insert_senha->bindParam(':id_divisao',    $assoc['id_divisao'], PDO::PARAM_INT);
            $stmt_insert_senha->execute();

            $cartoes_gerados++;
        } else {
            $erros++;
        }
    }

    $pdo->commit();

    echo json_encode([
        'status'           => 'success',
        'mensagem'         => 'Cartões gerados com sucesso!',
        'total_associados' => $total,
        'cartoes_gerados'  => $cartoes_gerados,
        'erros'            => $erros
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'status'   => 'error',
        'mensagem' => 'Erro ao gerar cartões: ' . $e->getMessage()
    ]);
}
