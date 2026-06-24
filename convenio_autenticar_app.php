<?PHP
//$userconv="";
//$passconv="";
$usuario_x="";
$password_x="";
$cod_convenio = 0;
$std = new stdClass();
include "Adm/php/banco.php";
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


if (isset($_POST['userconv']) && isset($_POST['passconv'])){
    
	$usuario_x  = $_POST['userconv'];
	$password_x = $_POST['passconv'];
	//$userconv = md5($_POST['userconv']);
	//$passconv = md5($_POST['passconv']);
   
	// VERIFICA SENHA ******************************************************************************************************************************************************
    $stmt_conv_senha = $pdo->prepare("SELECT usuario_texto, password, cod_convenio FROM sind.c_senhaconvenio WHERE usuario_texto = :usuario AND password = :password");
    $stmt_conv_senha->bindParam(':usuario', $usuario_x, PDO::PARAM_STR);
    $stmt_conv_senha->bindParam(':password', $password_x, PDO::PARAM_STR);
    $stmt_conv_senha->execute();
    while($row_senha = $stmt_conv_senha->fetch()) {
        $cod_convenio = $row_senha["cod_convenio"];
    }
    if( $cod_convenio != 0 ){
        // BUSCA MES CORRETE ********************************************************************************************************************************************
        $query = $pdo->query("SELECT * FROM sind.mes_corrente");
        while($row_mes = $query->fetch()) {
            $std->mes_corrente  = $row_mes["abreviacao"];
        }
		// VERIFICA SE TA ATIVO ********************************************************************************************************************************************
        $stmt_conv = $pdo->prepare("SELECT * FROM sind.convenio WHERE codigo = :codigo AND divulga = 'S'");
        $stmt_conv->bindParam(':codigo', $cod_convenio, PDO::PARAM_INT);
        $stmt_conv->execute();
        $sql_conv = $stmt_conv;
        while($row_conv = $sql_conv->fetch()) {

            $std->tipo_login    = "login sucesso";
            $std->cod_convenio  = $cod_convenio;
            $std->razaosocial   = htmlspecialchars($row_conv["razaosocial"]);
            $std->nomefantasia  = htmlspecialchars($row_conv["nomefantasia"]);
            $std->endereco      = htmlspecialchars($row_conv["endereco"]);
            $std->bairro        = htmlspecialchars($row_conv["bairro"]);
            $std->cidade        = htmlspecialchars($row_conv["cidade"]);
            $std->numero        = $row_conv["numero"];
            $std->email         = $row_conv["email"];
            $std->estado        = $row_conv["uf"];
            $std->cep           = $row_conv["cep"];
            $std->cel           = $row_conv["cel"];
            $std->tel           = $row_conv["telefone"];
            $std->cnpj          = $row_conv["cnpj"];
            $std->cpf           = $row_conv["cpf"];
            $std->userconv      = $usuario_x;
            $std->passconv      = $password_x;
            $std->parcela_conv  = $row_conv["n_parcelas"];
            $std->divulga       = "S";
            $std->pede_senha    = $row_conv["pede_senha"];
            $std->latitude      = $row_conv["latitude"];
            $std->longitude     = $row_conv["longitude"];
            $std->contato       = $row_conv["contato"];
            $std->id_categoria  = $row_conv["id_categoria"];
            $std->senha         = $_POST['passconv'];
            $std->aceita_termo  = $row_conv["aceita_termo"];
            $std->divisao       = $row_conv["divisao"];
        }
	}else{
		$std->tipo_login   = "login incorreto";
		$std->cod_convenio = 0;
        $std->razaosocial  = "";
        $std->nomefantasia = "";
        $std->endereco     = "";
        $std->bairro       = "";
		$std->userconv     = "";
		$std->passconv     = "";
		$std->parcela_conv = 0;
        $std->divulga      = "S";
        $std->pede_senha   = "";
        $std->latitude     = "";
        $std->longitude    = "";
        $std->divisao      = "";
	}
}else{
	$std->tipo_login   =  "login vazio";
	$std->cod_convenio = 0;
	$std->razaosocial  = "";
	$std->nomefantasia = "";
	$std->endereco     = "";
	$std->bairro       = "";
	$std->userconv     = $usuario_x;
	$std->passconv     = $password_x;
	$std->parcela_conv = 0;
	$std->divulga      = "S";
    $std->pede_senha   = "";
    $std->latitude     = "";
    $std->longitude    = "";
    $std->divisao      = "";
}
echo json_encode($std);