<?PHP
ini_set('display_errors', true);
error_reporting(E_ALL);
require '../../php/banco.php';
include "../../php/funcoes.php";
$_codigo = isset($_POST['C_codigo']) ? (int)$_POST['C_codigo'] : 0;
$_RazaoSocial = isset($_POST['C_razaosocial']) ?  strtoupper(htmlspecialchars($_POST['C_razaosocial'])) : "";
$_nomefantasia = isset($_POST['C_nomefantasia']) ?  strtoupper(htmlspecialchars($_POST['C_nomefantasia'])) : "";
$_endereco = isset($_POST['C_endereco']) ?  strtoupper(htmlspecialchars($_POST['C_endereco'])) : "";
$_bairro = isset($_POST['C_bairro']) ?  strtoupper(htmlspecialchars($_POST['C_bairro'])) : "";
$_cidade = isset($_POST['C_cidade']) ?  htmlspecialchars($_POST['C_cidade']) : "";
$_uf = isset($_POST['C_uf']) ?  $_POST['C_uf'] : "";
$_numero = isset($_POST['C_numero']) ?  $_POST['C_numero'] : "";
$_cep = isset($_POST['C_cep']) ? str_replace('.','',$_POST['C_cep']) : "";
$_tel1 = isset($_POST['C_tel1']) ? $_POST['C_tel1'] : "";
$_tel2 = isset($_POST['C_tel2']) ? $_POST['C_tel2'] : "";
$_cel = isset($_POST['C_cel']) ? $_POST['C_cel'] : "";
$_tipo = isset($_POST['C_tipo']) ? (int)$_POST['C_tipo'] : 0;
$_contato = isset($_POST['C_contato']) ?  strtoupper(htmlspecialchars($_POST['C_contato'])) : "";
$_prolabore = isset($_POST['C_prolabore']) ? $_POST['C_prolabore'] : "";
$_prolabore2 = isset($_POST['C_prolabore2']) ? $_POST['C_prolabore2'] : "";
$_prolabore = str_replace(",", ".", $_prolabore);
$_prolabore2 = str_replace(",", ".", $_prolabore2);
if($_prolabore == ""){$_prolabore = null;}
if($_prolabore2 == ""){$_prolabore2 = null;}
$_cnpj = isset($_POST['C_cnpj']) ? $_POST['C_cnpj'] : "";
$_cpf = isset($_POST['C_cpf']) ? $_POST['C_cpf'] : "";
$_insc_est = isset($_POST['C_Inscestadual']) ? $_POST['C_Inscestadual'] : "";
$_categoria = isset($_POST['C_categoria']) ? (int)$_POST['C_categoria'] : (int)0;
$_categoriarecibo = isset($_POST['C_categoriarecibo']) ? (int)$_POST['C_categoriarecibo'] : (int)0;
$_parcelamento = isset($_POST['C_parcelamento']) ? (int)$_POST['C_parcelamento'] : (int)0;
$_registro = isset($_POST['C_registro']) ? $_POST['C_registro'] : "";
$_situacao = isset($_POST['C_ativo']) == "S" ? "S" : "N";
$_divulga = isset($_POST['C_divulga']) == "S" ? "S" : "N";
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
$_cobranca = isset($_POST['C_cobranca']) ? true : false;
$_desativado = isset($_POST['C_desativado']) ? true : false;
$_parc_ind = isset($_POST['C_parc_ind']) ? true : false;
$_pede_senha = 1;
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
        $sql .= "aceita_parce_individ = :aceita_parce_individ ";
        $sql .= "WHERE Codigo = " . $_codigo;

        $msg_grava_cad = "atualizado";

    }elseif($_POST["operation"] == "Add") {

        $sql = "INSERT INTO sind.convenio( ";
        $sql .= "codigo, razaosocial, nomefantasia, endereco, numero, bairro, cidade, uf, cep, telefone, fax, cel, tipo, contato, prolabore, prolabore2, cnpj, cpf, insc, id_categoria, ";
        $sql .= "id_categoria_recibo, n_parcelas, registro, situacao, divulga, data_cadastro, email, email2, insc_mun, tipo2, cobranca, desativado, pede_senha,aceita_parce_individ) VALUES( ";
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
        $sql .= ":pede_senha)";

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
        $stmt->bindParam(':id_categoria', $_categoria, PDO::PARAM_INT);
        $stmt->bindParam(':id_categoria_recibo', $_categoriarecibo, PDO::PARAM_INT);
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
        $stmt->bindParam(':pede_senha', $_pede_senha, PDO::PARAM_INT);

        $stmt->execute();

        echo $msg_grava_cad;

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();

    }
}