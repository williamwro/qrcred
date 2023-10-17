<?PHP
header("Cache-Control: no-cache, no-store, must-revalidate"); // limpa o cache
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
clearstatcache(); // limpa o cache

include "Adm/php/banco.php";
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = new stdClass();
$std = new stdClass();

$std->existe_cartao = false;

if(isset($_POST['cartao']))
{
    $cartao = $_POST['cartao'];
    $stmt = $pdo->query("SELECT c.id, s.senha, a.email, c.cod_situacaocartao, c.cod_associado, c.cod_verificacao, 
                                c.motivo_cancela, c.empregador, c.id_divisao, c.cod_situacao2, a.nome
                           FROM sind.c_cartaoassociado c
                           JOIN sind.c_senhaassociado s 
                             ON c.cod_associado = s.cod_associado 
                            AND c.empregador = s.id_empregador 
                           JOIN sind.associado a
                             ON a.codigo = c.cod_associado 
                            AND a.empregador = c.empregador 
                          WHERE c.cod_verificacao = '".$cartao."'");
                        
    while ($row = $stmt->fetch()) {

      $std->existe_cartao      = true;
      $std->id                 = $row["id"];
      $std->senha              = $row["senha"];
      $std->email              = $row["email"];
      $std->cod_situacaocartao = $row["cod_situacaocartao"];
      $std->cod_associado      = $row["cod_associado"];
      $std->cod_verificacao    = $row["cod_verificacao"];
      $std->motivo_cancela     = $row["motivo_cancela"];
      $std->empregador         = $row["empregador"];
      $std->id_divisao         = $row["id_divisao"];
      $std->cod_situacao2      = $row["cod_situacao2"];
      $std->nome               = $row["nome"];

    }

    echo json_encode($std);

  }