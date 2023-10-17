<?PHP
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
		$std->endereco  = $_POST;
		
}else{			
	$cartaodigitado  = $_POST['cartaodigitado'];
	
    $sql_conv_senha = $pdo->query("SELECT associado.codigo,associado.nome,
                                            associado.empregador,associado.limite,
                                            associado.salario,associado.parcelas_permitidas,
                                            c_cartaoassociado.cod_situacaocartao,
                                            c_cartaoassociado.cod_verificacao,associado.email,
                                            associado.cel,associado.cpf,associado.token_associado
                                       FROM sind.associado 
                                 INNER JOIN sind.c_cartaoassociado 
                                         ON associado.codigo = c_cartaoassociado.cod_associado
                                        AND associado.empregador = c_cartaoassociado.empregador 
                                      WHERE c_cartaoassociado.cod_verificacao='".$cartaodigitado."'");
    while($row_senha = $sql_conv_senha->fetch()) {
        $cod_convenio = 1;
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
		
	}
}
echo json_encode($std);