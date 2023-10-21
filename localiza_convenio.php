<?PHP
ini_set('display_errors', true);
error_reporting(E_ALL);
$userconv="";
$passconv="";
$cod_divisao=1;
if (isset($_POST['userconv']) && isset($_POST['passconv'])){
    require 'Adm/php/banco.php';
    include "Adm/php/funcoes.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$userconv = md5($_POST['userconv']);
	$passconv = md5($_POST['passconv']);
    $cod_convenio = 0;
	$std = new stdClass();

	// VERIFICA SENHA ******************************************************************************************************************************************************
    $sql_conv_senha = $pdo->query("SELECT usuario, senha, cod_convenio FROM sind.c_senhaconvenio WHERE usuario='".$userconv."' AND senha='".$passconv."'");
    while($row_senha = $sql_conv_senha->fetch()) {
        $cod_convenio = $row_senha["cod_convenio"];
    }
    if( $cod_convenio != 0 ){

		// VERIFICA SE TA ATIVO ********************************************************************************************************************************************
        $ativo = false;
        $sql_conv = $pdo->query("SELECT * FROM sind.convenio WHERE codigo=".$cod_convenio." AND divulga = 'S'");
        while($row_conv = $sql_conv->fetch()) {
            $ativo = true;
            $std->tipo_login    = "login sucesso";
            $std->cod_convenio  = $cod_convenio;
            $std->razaosocial   = $row_conv["razaosocial"];
            $std->nomefantasia  = $row_conv["nomefantasia"];
            $std->endereco      = $row_conv["endereco"];
            $std->numero        = $row_conv["numero"];
            $std->bairro        = $row_conv["bairro"];
            $std->cidade        = $row_conv["cidade"];
            $std->cnpj          = $row_conv["cnpj"];
            $std->cpf           = $row_conv["cpf"];
            $std->userconv      = $userconv;
            $std->passconv      = $passconv;
            $std->parcela_conv  = $row_conv["n_parcelas"];
            $std->divulga       = "S";
            $std->pede_senha    = $row_conv["pede_senha"];
            $std->aceita_parce_individ = $row_conv["aceita_parce_individ"];
        }
        if( $ativo === false) {

            $std->tipo_login   = "login inativo";
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
            $std->aceita_parce_individ = false;
        }else{
            $sql_divisao = $pdo->query("SELECT nome, cidade, id_divisao, descricao FROM sind.divisao WHERE id_divisao = ".$cod_divisao);
            while($row_senha = $sql_divisao->fetch()) {
                $std->divisao = $row_senha["nome"];
            }
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
        $std->aceita_parce_individ = false;
	}
}else{
	$std->tipo_login   = "login vazio";
	$std->cod_convenio = 0;
	$std->razaosocial  = "";
	$std->nomefantasia = "";
	$std->endereco     = "";
	$std->bairro       = "";
	$std->userconv     = $userconv;
	$std->passconv     = $passconv;
	$std->parcela_conv = 0;
	$std->divulga      = "S";
    $std->pede_senha   = "";
    $std->aceita_parce_individ = false;
}

    function getBrowser()
    {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version = "";
        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }
        // Next get the name of the useragent yes seperately and for good reason
        if (preg_match('/WOW64/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        } elseif (preg_match('/Firefox/i', $u_agent)) {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        } elseif (preg_match('/Chrome/i', $u_agent)) {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        } elseif (preg_match('/Safari/i', $u_agent)) {
            $bname = 'Apple Safari';
            $ub = "Safari";
        } elseif (preg_match('/Opera/i', $u_agent)) {
            $bname = 'Opera';
            $ub = "Opera";
        } elseif (preg_match('/Netscape/i', $u_agent)) {
            $bname = 'Netscape';
            $ub = "Netscape";
        }
        $known = array('Version', $ub, 'other');
        // check if we have a number
        if ($version == null || $version == "") {
            $version = "?";
        }
        return array(
            'userAgent' => $u_agent,
            'name' => $bname,
            'version' => $version,
            'platform' => $platform,
        );
    }
    $dataagora = date("d/m/Y");
    $horaagora = date("H:i:s");
    // now try it
    $ua=getBrowser();
    $sql_log = "UPDATE sind.convenio SET browser='".$ua['name']."', data_ultimo_acesso='".$dataagora."', hora_ultimo_acesso='".$horaagora."' WHERE codigo=".$cod_convenio;
    $sql_acrescenta = $pdo->exec($sql_log);

    echo json_encode($std);