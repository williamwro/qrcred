<?PHP
header("Content-type: application/json");
ini_set('display_errors', true);
error_reporting(E_ALL);
include "Adm/php/banco.php";
include "Adm/php/funcoes.php";
include "uuid.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
date_default_timezone_set('America/Sao_Paulo');
$stmt = new stdClass();
$id_categoria  = "";
$uuid = "";
if ( isset($_POST['valor_pedido']) ) {
    // VARIAVEIS ------------------------------------
    $std              = array();
    //$std->parcelas    = new stdClass();
    $codigo_convenio  	  = $_POST['cod_convenio'];
    $matricula        	  = $_POST['matricula'];
    $senha             	  = $_POST['pass'];
    $nome             	  = $_POST['nome'];
    $cartao     	  	  = $_POST['cartao'];
    $empregador       	  = $_POST['empregador'];
    $valor_pedido     	  = $_POST['valor_pedido'];
    $valor_parcela_string = $_POST['valor_parcela'];
    $valor_parcela_float  = tofloat($valor_parcela_string);
    $aux              	  = $_POST['mes_corrente'];
    $m_p              	  = $_POST['mes_corrente'];
    $mes_inicial      	  = $_POST['mes_corrente'];
    $primeiro_mes      	  = $_POST['primeiro_mes'];
    $valor_pedido_float   = tofloat($valor_pedido);
    $mes_pedido       	  = explode("/", $_POST['mes_corrente']);
    $qtde_parcelas    	  = (int)$_POST['qtde_parcelas'];
    $evetivar         	  = false;
    $cont_senha_assoc 	  = 0;
    $pede_senha       	  = "";
    $registrolan      	  = "";
    $datay            	  = "";
    $hora             	  = date("H:i:s");
    $data            	  = date("Y-m-d");
    $uri_cupom        	  = $_POST['uri_cupom'];
    $datafatura = data_fatura($mes_pedido[0]);
    try {
        // -----------------------------------------------------------
        $sql_pede_senha = $pdo->query("SELECT * FROM sind.convenio WHERE codigo = " .  $codigo_convenio);
        while ($row_convenio = $sql_pede_senha->fetch()) {
            $nomefantasia   = $row_convenio["nomefantasia"];
            $razaosocial    = $row_convenio["razaosocial"];
            $endereco       = $row_convenio["endereco"];
            $bairro         = $row_convenio["bairro"];
            $parcela_conv   = $row_convenio['n_parcelas'];
            $pede_senha     = $row_convenio['pede_senha'];
            $id_categoria   = $row_convenio['id_categoria'];



            if ($pede_senha == 1) {
                $sql_pede_senha = $pdo->query("SELECT * FROM sind.c_senhaassociado WHERE cod_associado = '" . $matricula . "' AND id_empregador = ".$empregador." AND senha = '" . $senha . "'");
                while ($row_senha = $sql_pede_senha->fetch()) {
                    $cont_senha_assoc = 1;
                }
                if ($cont_senha_assoc == 0) {
                    $evetivar = false;
                }else{
                    $evetivar = true;
                }
            }else{
                $evetivar = true;
            }
            if ($evetivar == true) {
                $dataNull = null;
                if ($qtde_parcelas > 1) {


                    $std["situacao"]     = 1; /*1 - sucesso*/
                    $std["registrolan"]  = "";
                    $std["matricula"]    = $matricula;
                    $std["nome"]         = $nome;
                    $std["id_categoria"] = $id_categoria;
                    $std["parcelas"][]   = "";
                    $uuid = UUID::v4();
                    for ($as = 1; $as <= $qtde_parcelas; $as++) {

                        $sql = "INSERT INTO sind.conta (associado,convenio,valor,data,hora,mes,empregador,parcela,uri_cupom,data_fatura,uuid_conta) ";
                        $sql .= "VALUES (:associado,:convenio,:valor,:data,:hora,:mes,:empregador,:parcela,:uri_cupom,:data_fatura,:uuid_conta) RETURNING lastval()";
                        $parcela = "";
                        $parcela = str_pad($as, 2, "0", STR_PAD_LEFT) . "/" . str_pad($qtde_parcelas, 2, "0", STR_PAD_LEFT);

                        $stmt = $pdo->prepare($sql);
                        $stmt->bindParam(':associado', $matricula, PDO::PARAM_STR);
                        $stmt->bindParam(':convenio', $codigo_convenio, PDO::PARAM_INT);
                        $stmt->bindParam(':valor', $valor_parcela_float, PDO::PARAM_STR);
                        $stmt->bindParam(':data', $data, PDO::PARAM_STR);
                        $stmt->bindParam(':hora', $hora, PDO::PARAM_STR);
                        $stmt->bindParam(':mes', $m_p, PDO::PARAM_STR);
                        $stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
                        $stmt->bindParam(':parcela', $parcela, PDO::PARAM_STR);
                        $stmt->bindParam(':data_fatura', $datafatura, PDO::PARAM_STR);
                        $stmt->bindParam(':uuid_conta', $uuid, PDO::PARAM_STR);
                        if($as == 1){
                            $stmt->bindParam(':uri_cupom', $uri_cupom, PDO::PARAM_STR);
                        }else{
                            $stmt->bindParam(':uri_cupom', $dataNull, PDO::PARAM_STR);
                        }
                        $stmt->execute();

                        $registrolan = $stmt->fetchColumn();

                        $std["parcelas"][$as]["numero"]        = $as;
                        $std["parcelas"][$as]["valor_parcela"] = $valor_parcela_string;
                        $std["parcelas"][$as]["registrolan"]   = $registrolan;
                        $std["parcelas"][$as]["mes_seq"]       = $aux;
                        

                        $m_p          = somames_gravar($aux); // soma 1 mes
                        $mes_pedido   = explode("/", $m_p);
                        $aux          = $m_p;

                    }//fecha for
                    $std["parcelas"][]   = "";
                    $std["nparcelas"]    = $qtde_parcelas;
                    $std["mes_seq"]      = $m_p;
                    $std["razaosocial"]  = $razaosocial;
                    $std["nomefantasia"] = $nomefantasia;
                    $std["codcarteira"]  = $cartao;
                    $std["valorpedido"]  = $valor_pedido;
                    $std["endereco"]     = $endereco;
                    $std["bairro"]       = $bairro;
                    $std["parcela_conv"] = $parcela_conv;
                    $std["datacad"]      = $data;
                    $std["hora"]         = $hora;
                    $std["cod_convenio"] = $codigo_convenio;
                    $std["primeiro_mes"] = $primeiro_mes;
                    $std["pede_senha"]   = $pede_senha;

                } else {
                    $uuid = UUID::v4();
                    $count = 0;
                    $sql = "INSERT INTO sind.conta (associado,convenio,valor,data,hora,mes,empregador,uri_cupom,data_fatura,uuid_conta) ";
                    $sql .= "VALUES (:associado,:convenio,:valor,:data,:hora,:mes,:empregador,:uri_cupom,:data_fatura,:uuid_conta) RETURNING lastval()";

                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':associado', $matricula, PDO::PARAM_STR);
                    $stmt->bindParam(':convenio', $codigo_convenio, PDO::PARAM_INT);
                    $stmt->bindParam(':valor', $valor_pedido_float, PDO::PARAM_STR);
                    $stmt->bindParam(':data', $data, PDO::PARAM_STR);
                    $stmt->bindParam(':hora', $hora, PDO::PARAM_STR);
                    $stmt->bindParam(':mes', $m_p, PDO::PARAM_STR);
                    $stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
                    $stmt->bindParam(':uri_cupom', $uri_cupom, PDO::PARAM_STR);
                    $stmt->bindParam(':data_fatura', $datafatura, PDO::PARAM_STR);
                    $stmt->bindParam(':uuid_conta', $uuid, PDO::PARAM_STR);
                    $stmt->execute();

                    $registrolan = $stmt->fetchColumn();

                    $std["situacao"]     = 1; /*1 - sucesso*/
                    $std["registrolan"]  = $registrolan;
                    $std["matricula"]    = $matricula;
                    $std["nome"]         = $nome;
                    $std["nparcelas"]    = 1;
                    $std["valorpedido"]  = $valor_pedido;
                    $std["mes_seq"]      = $m_p;
                    $std["razaosocial"]  = $razaosocial;
                    $std["nomefantasia"] = $nomefantasia;
                    $std["endereco"]     = $endereco;
                    $std["bairro"]       = $bairro;
                    $std["parcela_conv"] = $parcela_conv;
                    $std["codcarteira"]  = $cartao;
                    $std["datacad"]      = $data;
                    $std["hora"]         = $hora;
                    $std["cod_convenio"] = $codigo_convenio;
                    $std["primeiro_mes"] = "";
                    $std["pede_senha"]   = $pede_senha;
                    $std["id_categoria"] = $id_categoria;

                }
            }else{
                $std["situacao"]     = 2; /*2- senha errada*/
                $std["matricula"]    = $matricula;
                $std["nome"]         = $nome;
                $std["nparcelas"]    = $qtde_parcelas;
                $std["valorpedido"]  = $valor_pedido;
                $std["mes_seq"]      = $m_p;
                $std["razaosocial"]  = $razaosocial;
                $std["nomefantasia"] = $nomefantasia;
                $std["endereco"]     = $endereco;
                $std["bairro"]       = $bairro;
                $std["parcela_conv"] = $parcela_conv;
                $std["codcarteira"]  = $cartao;
                $std["datacad"]      = $data;
                $std["hora"]         = $hora;
                $std["cod_convenio"] = $codigo_convenio;
                $std["primeiro_mes"] = "";
                $std["pede_senha"]   = $pede_senha;
                $std["id_categoria"] = $id_categoria;
            }
            $someArray = $std;
            //var_dump(json_encode($std));
            echo json_encode($someArray);
        }
    } catch (PDOException $erro) {

        echo $erro->getMessage()."Data : ".$data." parcela : ".$parcela." mes :".$m_p." valor : ".$valor_pedido_float;

    }

}