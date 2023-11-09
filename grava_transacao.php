<?PHP
ini_set('display_errors', true);
error_reporting(E_ALL);
include "Adm/php/banco.php";
include "Adm/php/funcoes.php";
include "uuid.php";

$contador_senha = 0;
$contador_senha_associado = 0;
$pede_senha = "";
$codigo_convenio = "";
$parcela_recalculada = 0;
$soma_parcela = 0;
$diferenca_parcela = 0;
$parcela_recalculada_formatada = "";
$cidade = "";
$uuid = "";
$stmt = new stdClass();
$std = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
date_default_timezone_set('America/Sao_Paulo');
if (isset($_POST['valor_pedido']) && isset($_POST['txtSaldoCard'])) {
    $registrolan = "";
    $valor_pedido = str_replace('.','',$_POST['valor_pedido']);
    $valor_pedido = str_replace(',','.',$valor_pedido);
    $saldo_cartao = str_replace('.','',$_POST['txtSaldoCard']);
    $saldo_cartao = str_replace(',','.',$saldo_cartao);
    $codigo_convenio = $_POST['cod_convenio'];
    $pede_senha = $_POST['pede_senha'];
    if (isset($_POST['nparcelas'])) {
        $nparcelas = $_POST['nparcelas'];
    } else {
        $nparcelas = 0;
    }
    $sql_pede_senha = $pdo->query("SELECT * FROM sind.convenio WHERE codigo = " . $_POST['cod_convenio']);
    while ($row_senha = $sql_pede_senha->fetch()) {
        $nomefantasia = $row_senha["nomefantasia"];
        $pede_senha = $row_senha["pede_senha"];
        $cidade = $row_senha["cidade"];
        $contador_senha = 1;
    }
    if ($contador_senha == 1) {
        $std = new stdClass();
        $aux        = $_POST['m_p'];
        $mes_pedido = explode("/", $_POST['m_p']);
        $m_p        = $_POST['m_p'];
        $dia        = date("d");
        $data2      = new DateTime();
        $data       = $data2->format('Y-m-d');
        $hora       = date('H:i:s');
        $nparcelas  = (int)$_POST['nparcelas'];
        $evetivar   = false;
        $datafatura = data_fatura($mes_pedido[0]);
        if ($pede_senha == 1) {
            $sql_pede_senha = $pdo->query("SELECT * FROM sind.c_senhaassociado WHERE cod_associado = '" . $_POST['matricula'] . "' AND id_empregador = " . $_POST['e_p'] . " AND senha = '" . $_POST['pass'] . "'");
            while ($row_senha = $sql_pede_senha->fetch()) {
                $contador_senha_associado = 1;
            }
            if ($contador_senha_associado == 0) {
                $evetivar = false;
            }else{
                $evetivar = true;
            }
        }else{
            $evetivar = true;
        }

        if ($evetivar == true) {
            $id_situacao = 1;
            if ($nparcelas > 1) {

                
                $valor_parcela = str_replace('.','',$_POST['val_parcela']);
                $valor_parcela = str_replace(',','.',$valor_parcela);
                $std->situacao = 1; /*1 - sucesso*/
                $std->registrolan = "";
                $std->matricula = $_POST['matricula'];
                $std->nome = $_POST['nome'];
                $parcela_recalculada = round((float)$valor_pedido/$nparcelas,2);
                $parcela_recalculada_formatada = strval($parcela_recalculada);
               
                $soma_parcela = $parcela_recalculada * $nparcelas;

                $diferenca_parcela = round($valor_pedido - $soma_parcela,2);

                $uuid = UUID::v4();
                
            
                for ($as = 1; $as <= $nparcelas; $as++) {
                    $sql = "INSERT INTO sind.conta (associado,convenio,valor,data,hora,mes";
                    $sql .= ",empregador,parcela,id_situacao,data_fatura,uuid_conta) VALUES (:associado,:convenio,:valor,";
                    $sql .= ":data,:hora,:mes,:empregador,:parcela,:id_situacao,:data_fatura,:uuid_conta) RETURNING lastval()";
                    $parcela_formatada = str_pad($as, 2, "0", STR_PAD_LEFT) . "/" . str_pad($nparcelas, 2, "0", STR_PAD_LEFT);

                    if ($as == $nparcelas) {
                        $parcela_recalculada = round((float)$parcela_recalculada + $diferenca_parcela,2);
                        $parcela_recalculada_formatada = strval($parcela_recalculada);
                    }

                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':associado', $_POST['matricula'], PDO::PARAM_STR);
                    $stmt->bindParam(':convenio', $_POST['cod_convenio'], PDO::PARAM_INT);
                    $stmt->bindParam(':valor', $parcela_recalculada_formatada, PDO::PARAM_STR);
                    $stmt->bindParam(':data', $data, PDO::PARAM_STR);
                    $stmt->bindParam(':hora', $hora, PDO::PARAM_STR);
                    $stmt->bindParam(':mes',   $m_p , PDO::PARAM_STR);
                    $stmt->bindParam(':empregador', $_POST['e_p'], PDO::PARAM_INT);
                    $stmt->bindParam(':parcela', $parcela_formatada, PDO::PARAM_STR);
                    $stmt->bindParam(':id_situacao', $id_situacao, PDO::PARAM_INT);
                    $stmt->bindParam(':data_fatura', $datafatura, PDO::PARAM_STR);
                    $stmt->bindParam(':uuid_conta', $uuid, PDO::PARAM_STR);
                    $stmt->execute();

                    $ultimo_codigo =  $stmt->fetchColumn();

                    $std->$as = new stdClass();
                    $std->$as->numero = $as;
                    $std->$as->valor_parcela = $parcela_recalculada_formatada;
                    $std->$as->registrolan = $ultimo_codigo;
                    $std->$as->mes_seq = $aux;
                    $m_p = somames_gravar($aux); // soma 1 mes
                    $mes_pedido = explode("/", $m_p);
                    $_POST['m_p'] = $m_p;
                    $aux = $m_p;
                }//fecha for
            } else {
                $uuid = UUID::v4();

                $sql = "INSERT INTO sind.conta (associado,convenio,valor,data,hora,mes";
                $sql .= ",empregador,id_situacao,data_fatura,uuid_conta) VALUES (:associado,:convenio,:valor,";
                $sql .= ":data,:hora,:mes,:empregador,:id_situacao,:data_fatura,:uuid_conta) RETURNING lastval()";

                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':associado', $_POST['matricula'], PDO::PARAM_STR);
                $stmt->bindParam(':convenio', $_POST['cod_convenio'], PDO::PARAM_INT);
                $stmt->bindParam(':valor', $valor_pedido, PDO::PARAM_STR);
                $stmt->bindParam(':data', $data, PDO::PARAM_STR);
                $stmt->bindParam(':hora', $hora, PDO::PARAM_STR);
                $stmt->bindParam(':mes',   $m_p , PDO::PARAM_STR);
                $stmt->bindParam(':empregador', $_POST['e_p'], PDO::PARAM_INT);
                $stmt->bindParam(':id_situacao', $id_situacao, PDO::PARAM_INT);
                $stmt->bindParam(':data_fatura', $datafatura, PDO::PARAM_STR);
                $stmt->bindParam(':uuid_conta', $uuid, PDO::PARAM_STR);
                $stmt->execute();

                $ultimo_codigo =  $stmt->fetchColumn();
            }

            $std->situacao      = 1; /*1 - sucesso*/
            $std->registrolan   = $ultimo_codigo;
            $std->matricula     = $_POST['matricula'];
            $std->nome          = $_POST['nome'];
            $std->nparcelas     = $nparcelas;
            $std->valorpedido   = $valor_pedido;
            $std->mes_seq       = $m_p;
            $std->userconv      = $_POST['userconv'];
            $std->passconv      = $_POST['passconv'];
            $std->razaosocial   = $_POST['razaosocial'];
            $std->nomefantasia  = $_POST['nomefantasia'];
            $std->endereco      = $_POST['endereco'];
            $std->cnpj          = $_POST['cnpj'];
            $std->bairro        = $_POST['bairro'];
            $std->cidade        = $cidade;
            $std->parcela_conv  = $_POST['parcelas_permitidas'];
            $std->codcarteira   = $_POST['cod_carteira'];
            $std->datacad       = $data;
            $std->hora          = $hora;
            $std->cod_convenio  = $_POST['cod_convenio'];
            $std->primeiro_mes  = "";
            $std->pede_senha    = $pede_senha;

        }else{
            $std->situacao      = 2; /*2- senha errada*/
            $std->matricula     = $_POST['matricula'];
            $std->nome          = $_POST['nome'];
            $std->nparcelas     = $nparcelas;
            $std->valorpedido   = $_POST['valor_pedido'];
            $std->mes_seq       = $m_p;
            $std->userconv      = $_POST['userconv'];
            $std->passconv      = $_POST['passconv'];
            $std->razaosocial   = $_POST['razaosocial'];
            $std->nomefantasia  = $_POST['nomefantasia'];
            $std->endereco      = $_POST['endereco'];
            $std->bairro        = $_POST['bairro'];
            $std->cidade        = $cidade;
            $std->cnpj          = $_POST['cnpj'];
            $std->parcela_conv  = $_POST['nparcelas'];
            $std->codcarteira   = $_POST['cod_carteira'];
            $std->datacad       = $data;
            $std->hora          = $hora;
            $std->cod_convenio  = $_POST['cod_convenio'];
            $std->primeiro_mes  = "";
            $std->pede_senha    = $pede_senha;
        }

        echo json_encode($std);
    }
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
// now try it
$ua = getBrowser();
$sql_acrescenta = $pdo->exec("UPDATE sind.convenio SET browser='".$ua['name']."' WHERE codigo=".$codigo_convenio);