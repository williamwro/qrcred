<?PHP
header('Content-Type: application/json; charset=utf-8');
require_once '../../../functions.php';
ini_set('display_errors', true);
error_reporting(E_ALL);
require '../../php/banco.php';
include "../../php/funcoes.php";
$_codigo = isset($_POST['C_codigo']) ? (int)$_POST['C_codigo'] : 0;
$_RazaoSocial = isset($_POST['C_razaosocial']) ?  strtoupper($_POST['C_razaosocial']) : "";
$_nomefantasia = isset($_POST['C_nomefantasia']) ?  strtoupper($_POST['C_nomefantasia']) : "";
$_endereco = isset($_POST['C_endereco']) ?  strtoupper($_POST['C_endereco']) : "";
$_bairro = isset($_POST['C_bairro']) ?  strtoupper($_POST['C_bairro']) : "";
$_cidade = isset($_POST['C_cidade']) ?  $_POST['C_cidade'] : "";
$_uf = isset($_POST['C_uf']) ?  $_POST['C_uf'] : "";
$_numero = isset($_POST['C_numero']) ?  $_POST['C_numero'] : "";
$_cep = isset($_POST['C_cep']) ? str_replace('.','',$_POST['C_cep']) : "";
$_tel1 = isset($_POST['C_tel1']) ? $_POST['C_tel1'] : "";
$_tel2 = isset($_POST['C_tel2']) ? $_POST['C_tel2'] : "";
$_cel = isset($_POST['C_cel']) ? $_POST['C_cel'] : "";
$_tipo = isset($_POST['C_tipo']) ? (int)$_POST['C_tipo'] : 0;
$_contato = isset($_POST['C_contato']) ?  strtoupper($_POST['C_contato']) : "";
$_prolabore = isset($_POST['C_prolabore']) ? $_POST['C_prolabore'] : "";
$_prolabore2 = isset($_POST['C_prolabore2']) ? $_POST['C_prolabore2'] : "";
$_prolabore = str_replace(",", ".", $_prolabore);
$_prolabore2 = str_replace(",", ".", $_prolabore2);
if($_prolabore == ""){$_prolabore = null;}
if($_prolabore2 == ""){$_prolabore2 = null;}
$_cnpj = isset($_POST['C_cnpj']) ? $_POST['C_cnpj'] : "";
$_cpf = isset($_POST['C_cpf']) ? $_POST['C_cpf'] : "";
$_insc_est = isset($_POST['C_Inscestadual']) ? $_POST['C_Inscestadual'] : "";
$_categoria = isset($_POST['C_categoria']) && $_POST['C_categoria'] != 0 ? (int)$_POST['C_categoria'] : null;
$_categoriarecibo = isset($_POST['C_categoriarecibo']) && $_POST['C_categoriarecibo'] != 0 ? (int)$_POST['C_categoriarecibo'] : null;
$_parcelamento = isset($_POST['C_parcelamento']) ? (int)$_POST['C_parcelamento'] : (int)0;
$_registro = isset($_POST['C_registro']) ? $_POST['C_registro'] : "";
$_situacao =  "S";
$_divulga = "S";
if($_POST["operation"] == "Add") {
    $_data_cadastro = $_POST['C_datacadastro'] !== "" ? $_POST['C_datacadastro'] : null;
}else{
    $_data_cadastro = $_POST['C_datacadastro'] !== "" ? $_POST['C_datacadastro'] : null;
}
$datex = str_replace('/', '-', $_data_cadastro);
$_data_cadastro = date('Y-m-d', strtotime($datex));
$_email = isset($_POST['C_email']) ? $_POST['C_email'] : "";
$_email2 = isset($_POST['C_email2']) ? $_POST['C_email2'] : "";
$_inscmunicipal = isset($_POST['C_inscmunicipal']) ? $_POST['C_inscmunicipal'] : "";
$_tipoempresa = isset($_POST['C_tipoempresa']) ? (int)$_POST['C_tipoempresa'] : (int)1; //1 fisico, 2 juridico
$_cobranca =  true;
$_desativado = isset($_POST['C_desativado']) ? true : false;
$_parc_ind = isset($_POST['C_parc_ind']) ? true : false;
$_aprova_convenio = isset($_POST['C_aprova_convenio']) ? true : false;
$_divisao = isset($_POST['divisao']) ? (int)$_POST['divisao'] : null;
$_pede_senha = true;
function converte_data($date) {
    return substr($date,6,4).'-'.substr($date,3,2).'-'.substr($date,0,2).' 00:00:00';
}
$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad="";
if(isset($_POST["operation"])) {
    if($_POST["operation"] == "Update") {

        $sql = "UPDATE sind.convenio SET ";
        $sql .= "razaosocial = :razaosocial, ";
        $sql .= "nomefantasia = :nomefantasia, ";
        $sql .= "endereco = :endereco, ";
        $sql .= "numero = :numero, ";
        $sql .= "bairro = :bairro, ";
        $sql .= "cidade = :cidade, ";
        $sql .= "uf = :uf, ";
        $sql .= "cep = :cep, ";
        $sql .= "telefone = :telefone, ";
        $sql .= "fax = :fax, ";
        $sql .= "cel = :cel, ";
        $sql .= "tipo = :tipo, ";
        $sql .= "contato = :contato, ";
        $sql .= "prolabore = :prolabore, ";
        $sql .= "prolabore2 = :prolabore2, ";
        $sql .= "cnpj = :cnpj, ";
        $sql .= "cpf = :cpf, ";
        $sql .= "insc = :insc, ";
        $sql .= "id_categoria = :id_categoria, ";
        $sql .= "id_categoria_recibo = :id_categoria_recibo, ";
        $sql .= "n_parcelas = :n_parcelas, ";
        $sql .= "registro = :registro, ";
        $sql .= "situacao = :situacao, ";
        $sql .= "divulga = :divulga, ";
        $sql .= "data_cadastro = :data_cadastro, ";
        $sql .= "email = :email, ";
        $sql .= "email2 = :email2, ";
        $sql .= "insc_mun = :insc_mun, ";
        $sql .= "tipo2 = :tipo2, ";
        $sql .= "cobranca = :cobranca, ";
        $sql .= "desativado = :desativado, ";
        $sql .= "pede_senha = :pede_senha, ";
        $sql .= "aceita_parce_individ = :aceita_parce_individ , ";
        $sql .= "lista_site = :lista_site ";
        $sql .= "WHERE Codigo = " . $_codigo;

        $msg_grava_cad = "atualizado";

    }elseif($_POST["operation"] == "Add") {
        
        // Verificar se CNPJ ou CPF já existe
        $check_sql = "SELECT codigo, razaosocial FROM sind.convenio WHERE ";
        $check_params = array();
        
        if (!empty($_cnpj)) {
            $check_sql .= "cnpj = :cnpj";
            $check_params[':cnpj'] = $_cnpj;
        } elseif (!empty($_cpf)) {
            $check_sql .= "cpf = :cpf";
            $check_params[':cpf'] = $_cpf;
        }
        
        if (!empty($check_params)) {
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute($check_params);
            $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                $documento = !empty($_cnpj) ? "CNPJ" : "CPF";
                $numero = !empty($_cnpj) ? $_cnpj : $_cpf;
                echo json_encode(array(
                    'status' => 'error', 
                    'message' => "Este $documento ($numero) já está cadastrado para o convênio: " . $existing['razaosocial'],
                    'documento_existente' => true
                ));
                exit;
            }
        }

        $sql = "INSERT INTO sind.convenio( ";
        $sql .= "codigo, razaosocial, nomefantasia, endereco, numero, bairro, cidade, uf, cep, telefone, fax, cel, tipo, contato, prolabore, prolabore2, cnpj, cpf, insc, id_categoria, ";
        $sql .= "id_categoria_recibo, n_parcelas, registro, situacao, divulga, data_cadastro, email, email2, insc_mun, tipo2, cobranca, desativado, pede_senha,aceita_parce_individ,lista_site,divisao) VALUES( ";
        $sql .= ":codigo, ";
        $sql .= ":razaosocial, ";
        $sql .= ":nomefantasia, ";
        $sql .= ":endereco, ";
        $sql .= ":numero, ";
        $sql .= ":bairro, ";
        $sql .= ":cidade, ";
        $sql .= ":uf, ";
        $sql .= ":cep, ";
        $sql .= ":telefone, ";
        $sql .= ":fax, ";
        $sql .= ":cel, ";
        $sql .= ":tipo, ";
        $sql .= ":contato, ";
        $sql .= ":prolabore, ";
        $sql .= ":prolabore2, ";
        $sql .= ":cnpj, ";
        $sql .= ":cpf, ";
        $sql .= ":insc, ";
        $sql .= ":id_categoria, ";
        $sql .= ":id_categoria_recibo, ";
        $sql .= ":n_parcelas, ";
        $sql .= ":registro, ";
        $sql .= ":situacao, ";
        $sql .= ":divulga, ";
        $sql .= ":data_cadastro, ";
        $sql .= ":email, ";
        $sql .= ":email2, ";
        $sql .= ":insc_mun, ";
        $sql .= ":tipo2, ";
        $sql .= ":cobranca, ";
        $sql .= ":desativado, ";
        $sql .= ":aceita_parce_individ, ";
        $sql .= ":pede_senha, ";
        $sql .= ":lista_site, ";
        $sql .= ":divisao)";

        $msg_grava_cad = "cadastrado";

    }
    try {

        $stmt = $pdo->prepare($sql);

        if($_POST["operation"] == "Add") {
            $stmt->bindParam(':codigo', $_codigo, PDO::PARAM_INT);
        }
        $stmt->bindParam(':razaosocial', $_RazaoSocial, PDO::PARAM_STR);
        $stmt->bindParam(':nomefantasia', $_nomefantasia, PDO::PARAM_STR);
        $stmt->bindParam(':endereco', $_endereco, PDO::PARAM_STR);
        $stmt->bindParam(':numero', $_numero, PDO::PARAM_STR);
        $stmt->bindParam(':bairro', $_bairro, PDO::PARAM_STR);
        $stmt->bindParam(':cidade', $_cidade, PDO::PARAM_STR);
        $stmt->bindParam(':uf', $_uf, PDO::PARAM_STR);
        $stmt->bindParam(':cep', $_cep, PDO::PARAM_STR);
        $stmt->bindParam(':telefone', $_tel1, PDO::PARAM_STR);
        $stmt->bindParam(':fax', $_tel2, PDO::PARAM_STR);
        $stmt->bindParam(':cel', $_cel, PDO::PARAM_STR);
        $stmt->bindParam(':tipo', $_tipo, PDO::PARAM_INT);
        $stmt->bindParam(':contato', $_contato, PDO::PARAM_STR);
        $stmt->bindParam(':prolabore', $_prolabore, PDO::PARAM_STR);
        $stmt->bindParam(':prolabore2', $_prolabore2, PDO::PARAM_STR);
        $stmt->bindParam(':cnpj', $_cnpj, PDO::PARAM_STR);
        $stmt->bindParam(':cpf', $_cpf, PDO::PARAM_STR);
        $stmt->bindParam(':insc', $_insc_est, PDO::PARAM_STR);
        if($_categoria !== null) {
            $stmt->bindParam(':id_categoria', $_categoria, PDO::PARAM_INT);
        } else {
            $stmt->bindParam(':id_categoria', $_categoria, PDO::PARAM_NULL);
        }
        if($_categoriarecibo !== null) {
            $stmt->bindParam(':id_categoria_recibo', $_categoriarecibo, PDO::PARAM_INT);
        } else {
            $stmt->bindParam(':id_categoria_recibo', $_categoriarecibo, PDO::PARAM_NULL);
        }
        $stmt->bindParam(':n_parcelas', $_parcelamento, PDO::PARAM_INT);
        $stmt->bindParam(':registro', $_registro, PDO::PARAM_STR);
        $stmt->bindParam(':situacao', $_situacao, PDO::PARAM_STR);
        $stmt->bindParam(':divulga', $_divulga, PDO::PARAM_STR);
        $stmt->bindParam(':data_cadastro', $_data_cadastro, PDO::PARAM_STR);
        $stmt->bindParam(':email', $_email, PDO::PARAM_STR);
        $stmt->bindParam(':email2', $_email2, PDO::PARAM_STR);
        $stmt->bindParam(':insc_mun', $_inscmunicipal, PDO::PARAM_STR);
        $stmt->bindParam(':tipo2', $_tipoempresa, PDO::PARAM_INT);
        $stmt->bindParam(':cobranca', $_cobranca, PDO::PARAM_BOOL);
        $stmt->bindParam(':desativado', $_desativado, PDO::PARAM_BOOL);
        $stmt->bindParam(':aceita_parce_individ', $_parc_ind, PDO::PARAM_BOOL);
        $stmt->bindParam(':pede_senha', $_pede_senha, PDO::PARAM_BOOL);
        $stmt->bindParam(':lista_site', $_aprova_convenio, PDO::PARAM_BOOL);
        if($_POST["operation"] == "Add") {
            if($_divisao !== null) {
                $stmt->bindParam(':divisao', $_divisao, PDO::PARAM_INT);
            } else {
                $stmt->bindParam(':divisao', $_divisao, PDO::PARAM_NULL);
            }
        }

        $stmt->execute();

        // Obter o código do convênio para usar nas especialidades
        $codigo_convenio = $_codigo;
        if($_POST["operation"] == "Add" && $codigo_convenio == 0) {
            // Se código não foi definido, obter o último inserido
            $sql_last = "SELECT COALESCE(MAX(codigo), 0) as ultimo_codigo FROM sind.convenio";
            $stmt_last = $pdo->prepare($sql_last);
            $stmt_last->execute();
            $result = $stmt_last->fetch(PDO::FETCH_ASSOC);
            $codigo_convenio = $result['ultimo_codigo'];
        }

        // Se for um novo convênio e há especialidades para adicionar
        if($_POST["operation"] == "Add" && isset($_POST['especialidades'])) {
            $especialidades = json_decode($_POST['especialidades'], true);
            
            // Log de debug
            error_log("DEBUG: Operação Add - Especialidades recebidas: " . $_POST['especialidades']);
            error_log("DEBUG: Código do convênio: " . $codigo_convenio);
            error_log("DEBUG: Especialidades decodificadas: " . print_r($especialidades, true));
            
            if(!empty($especialidades)) {
                // Inserir profissionais
                $sql_esp = "INSERT INTO sind.convenio_especialidades (cod_convenio, cod_profissional) VALUES (?, ?)";
                $stmt_esp = $pdo->prepare($sql_esp);
                
                foreach($especialidades as $profissional_id) {
                    error_log("DEBUG: Inserindo profissional ID: " . $profissional_id . " para convênio: " . $codigo_convenio);
                    $result = $stmt_esp->execute([$codigo_convenio, $profissional_id]);
                    error_log("DEBUG: Resultado da inserção: " . ($result ? "sucesso" : "falha"));
                }
                error_log("DEBUG: Todas as especialidades foram processadas");
            } else {
                error_log("DEBUG: Array de especialidades está vazio");
            }
        } else {
            if($_POST["operation"] == "Add") {
                error_log("DEBUG: Operação Add mas sem especialidades no POST");
            }
        }

        echo json_encode(array('status' => 'success', 'message' => $msg_grava_cad, 'reload_filtro' => true), JSON_UNESCAPED_UNICODE);

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();

    }
}