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

// Resto do seu código PHP...
$cod_convenio = 0;
$std = new stdClass();
include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_POST['cartaodigitado'])){

		$std->nome = "login fazio";
		$std->cod_cart = "";
		$std->matricula = "";
		$std->empregador = "";
		$std->parcelas_permitidas = "";
		$std->limite = "";
		$std->email = "";
		$std->cpf = "";
		$std->cel = "";
		$std->id = ""; // CAMPO ADICIONADO
		$std->endereco  = $_POST;
		
}else{			
	$cartaodigitado  = $_POST['cartaodigitado'];
	
    $sql_conv_senha = $pdo->prepare("SELECT associado.id,associado.codigo,associado.nome,
                                            associado.empregador,associado.limite,
                                            associado.salario,associado.parcelas_permitidas,
                                            c_cartaoassociado.cod_situacaocartao,
                                            c_cartaoassociado.cod_verificacao,associado.email,
                                            associado.cel,associado.cpf,associado.token_associado,
											associado.id_divisao,
											empregador.nome as nome_empregador,
											divisao.nome as nome_divisao
                                       FROM sind.associado 
                                 INNER JOIN sind.c_cartaoassociado 
                                         ON associado.codigo = c_cartaoassociado.cod_associado
                                        AND associado.empregador = c_cartaoassociado.empregador
                                 INNER JOIN sind.empregador
                                         ON associado.empregador = empregador.id
                                 INNER JOIN sind.divisao
                                         ON associado.id_divisao = divisao.id_divisao
                                      WHERE c_cartaoassociado.cod_verificacao = :cartaodigitado");
    $sql_conv_senha->bindParam(':cartaodigitado', $cartaodigitado, PDO::PARAM_STR);
    $sql_conv_senha->execute();
    while($row_senha = $sql_conv_senha->fetch()) {
        $cod_convenio = 1;
		$std->id                  = $row_senha['id']; // CAMPO ADICIONADO
		$std->nome                = $row_senha['nome'];
		$std->cod_cart            = $row_senha['cod_verificacao'];
		$std->matricula           = $row_senha['codigo'];
		$std->empregador          = $row_senha['empregador'];
		$std->parcelas_permitidas = $row_senha["parcelas_permitidas"];
		$std->limite              = number_format(($row_senha["limite"]), 2, '.', '');
		$std->email               = $row_senha["email"];
		$std->cpf                 = $row_senha["cpf"];
		$std->cel                 = $row_senha["cel"];
        $std->token_associado     = $row_senha["token_associado"];
		$std->id_divisao          = $row_senha["id_divisao"];
		$std->nome_empregador     = $row_senha["nome_empregador"];
		$std->nome_divisao        = $row_senha["nome_divisao"];
    }
    if( $cod_convenio == 0 ){


			$std->nome = "login incorreto";
			$std->cod_cart = "";
			$std->matricula = "";
			$std->empregador = "";
			$std->parcelas_permitidas = "";
			$std->limite = "";
			$std->email = "";
			$std->cpf = "";
			$std->cel = "";
			$std->id = ""; // CAMPO ADICIONADO
			$std->id_divisao = "";
		
	}
}
echo json_encode($std);