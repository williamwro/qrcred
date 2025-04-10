<?PHP
// Permitir acesso de qualquer origem
header("Access-Control-Allow-Origin: *");

// Ou para permitir apenas de origens específicas:
// header("Access-Control-Allow-Origin: http://localhost:3000");

// Definir métodos HTTP permitidos
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

// Permitir headers específicos
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Definir por quanto tempo (em segundos) o navegador pode armazenar em cache os resultados da preflight request
header("Access-Control-Max-Age: 86400");
header("Content-type: application/json");
    include 'Adm/php/banco.php';
    include "Adm/php/funcoes.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if(isset($_GET['mes_atual'])){
        $mes_atual = $_GET['mes_atual'];
    }else if(isset($_POST['mes_atual'])){
        $mes_atual = $_POST['mes_atual'];
    }
    if(isset($_GET['cod_convenio'])){
        $cod_convenio = $_GET['cod_convenio'];
    }else if(isset($_POST['cod_convenio'])){
        $cod_convenio = $_POST['cod_convenio'];
    }
    //$mes_atual = 'OUT/2017';
    //$cod_convenio = 99;

    $item  = 0;
    $total = 0;
    $someArray = array();
    $query = "SELECT conta.lancamento, 
                     conta.associado AS matricula, 
                     conta.valor, 
                     conta.data, 
                     to_char(conta.hora, 'HH24:MI') as hora,
                     conta.mes, 
                     empregador.nome AS empregador, 
                     empregador.id AS codigo_empregador, 
                     convenio.razaosocial AS convenio, 
                     convenio.codigo AS cod_convenio, 
                     associado.nome AS associado, 
                     conta.funcionario, 
                     conta.parcela, 
                     conta.descricao,
                     conta.data_fatura,
                     convenio.senha_estorno
                FROM sind.associado 
          RIGHT JOIN (sind.empregador 
          RIGHT JOIN (sind.convenio 
          RIGHT JOIN sind.conta 
          ON convenio.codigo = conta.convenio) 
          ON empregador.id = conta.empregador) 
          ON associado.codigo = conta.associado AND associado.empregador = conta.empregador 
          WHERE convenio.codigo = " . $cod_convenio . " AND conta.mes = '" . $mes_atual . "' AND convenio.desativado = false ORDER BY conta.lancamento DESC";

    $sql_conv_vendas = $pdo->query($query);

    while($row_vendas = $sql_conv_vendas->fetch()) {

        $someArray['data'][] = array_map("utf8_encode",$row_vendas);

    }
    echo json_encode($someArray);

