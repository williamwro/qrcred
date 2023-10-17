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

$std->existe_convenio = false;

if(isset($_POST['usuario']))
{
    $usuario = $_POST['usuario'];
    $stmt = $pdo->query("SELECT s.id, s.cod_convenio, s.nome_funcionario, s.perfil,
                                s.usuario, s.senha, s.usuario_texto, s.password,
                                c.razaosocial, c.cnpj, c.cpf, c.email	
                           FROM sind.c_senhaconvenio s
                           JOIN sind.convenio c 
                             ON c.codigo = s.cod_convenio 
                          WHERE s.usuario_texto = '".$usuario."'");
                        
    while ($row = $stmt->fetch()) {

      $std->existe_convenio  = true;
      $std->id               = $row["id"];
      $std->cod_convenio     = $row["cod_convenio"];
      $std->nome_funcionario = $row["nome_funcionario"];
      $std->perfil           = $row["perfil"];
      $std->usuario          = $row["usuario"];
      $std->senha            = $row["senha"];
      $std->usuario_texto    = $row["usuario_texto"];
      $std->password         = $row["password"];
      $std->razaosocial      = $row["razaosocial"];
      $std->cnpj             = $row["cnpj"];
      $std->cpf              = $row["cpf"];
      $std->email            = $row["email"];

    }

    echo json_encode($std);

  }