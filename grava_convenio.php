<?PHP
include "Adm/php/banco.php";
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$std = new stdClass();
$_RazaoSocial     = isset($_POST['C_razaosocial'])     ?      $_POST['C_razaosocial']          : "";
$_nomefantasia    = isset($_POST['C_nomefantasia'])    ?      $_POST['C_nomefantasia']         : "";
$_endereco        = isset($_POST['C_endereco'])        ?      $_POST['C_endereco']             : "";
$_numero          = isset($_POST['C_numero'])          ?      $_POST['C_numero']               : "";
$_bairro          = isset($_POST['C_bairro'])          ?      $_POST['C_bairro']               : "";
$_cidade          = isset($_POST['C_cidade'])          ?      $_POST['C_cidade']               : "";
$_uf              = isset($_POST['C_uf'])              ?      $_POST['C_uf']                   : "";
$_cep             = isset($_POST['C_cep'])             ?      $_POST['C_cep']                  : "";
$_tel1            = isset($_POST['C_tel1'])            ?      $_POST['C_tel1']                 : "";
$_tel2            = isset($_POST['C_tel2'])            ?      $_POST['C_tel2']                 : "";
$_cel             = isset($_POST['C_cel'])             ?      $_POST['C_cel']                  : "";
$_tipo            = 2; // COMPRAS
$_contato         = isset($_POST['C_contato'])         ?      $_POST['C_contato']              : "";
$_prolabore       = isset($_POST['C_prolabore'])       ?      $_POST['C_prolabore']            : null;
$_prolabore2      = isset($_POST['C_prolabore2'])      ?      $_POST['C_prolabore2']           : null;
$_cnpj            = isset($_POST['C_cnpj'])            ?      $_POST['C_cnpj']                 : "";
$_cpf             = isset($_POST['C_cpf'])             ?      $_POST['C_cpf']                  : "";
$_insc_est        = isset($_POST['C_Inscestadual'])    ?      $_POST['C_Inscestadual']         : "";
$_categoria       = isset($_POST['C_categoria'])       ? (int)$_POST['C_categoria']            :  0;
$_categoriarecibo = isset($_POST['C_categoriarecibo']) ? (int)$_POST['C_categoriarecibo']      :  0;
$_parcelamento    = isset($_POST['C_parcelamento'])    ? (int)$_POST['C_parcelamento']         :  0;
$_registro        = isset($_POST['C_registro'])        ?      $_POST['C_registro']             : "";
$_data_cadastro   = isset($_POST['C_datacadastro'])    ?      $_POST['C_datacadastro']         : date('d/M/Y');
$_email           = isset($_POST['C_email'])           ?      $_POST['C_email']                : "";
$_email2          = isset($_POST['C_email2'])          ?      $_POST['C_email2']               : "";
$_inscmunicipal   = isset($_POST['C_inscmunicipal'])   ?      $_POST['C_inscmunicipal']        : "";
$_tipoempresa     = isset($_POST['C_tipoempresa'])     ? (int)$_POST['C_tipoempresa']          :  1; //1 fisico, 2 juridico
$_cobranca        = isset($_POST['C_cobranca'])        ? 1                                     :  0;
$_desativado      = isset($_POST['C_desativado'])      ? 1                                     :  0;
$_app             = isset($_POST['C_app'])             ? 1                                     :  0;
$_lista_site      = 1; // APARECE NA RELAÇÃO DE CONVENIOS
$_divulga         = "S";
$_pede_senha      = 1;
$_desativado      = 0;
$_situacao        = "S";
$_existe_cpf      = false;
$_existe_cnpj     = false;

if($_tipoempresa === 1) {
    $row_conv = $pdo->query("SELECT codigo, cpf
                                       FROM sind.convenio 
                                      WHERE cpf='" . $_cpf . "'")->fetch();
    if($row_conv){
        $_existe_cpf = true;
    }else{
        $_existe_cpf = false;
    }
}else if($_tipoempresa === 2) {
    $row_conv = $pdo->query("SELECT codigo, cnpj
                                       FROM sind.convenio 
                                      WHERE cnpj='" . $_cnpj . "'")->fetch();
    if($row_conv) {
        $_existe_cnpj = true;
    }else{
        $_existe_cnpj = false;
    }
}
if($_existe_cpf == false && $_existe_cnpj == false) {
    $_senhaconvenio = mt_rand(111111, 999999);
    if ($_tipoempresa === 1) {// fisica
        $_ususarioconvenio = $_cpf;
    } else {                   // juridica
        $_ususarioconvenio = $_cnpj;
    }
    $_ususarioconvenio = str_replace('.', '', $_ususarioconvenio);
    $_ususarioconvenio = str_replace('-', '', $_ususarioconvenio);
    $_ususarioconvenio = str_replace('/', '', $_ususarioconvenio);

    $_senhaconvenio_crypto = md5($_senhaconvenio);
    $_ususarioconvenio_crypto = md5($_ususarioconvenio);

    if ($_RazaoSocial != "") {

        $std = new stdClass();

        $count = 0;

        $sql = "INSERT INTO sind.convenio (";
        $sql .= "razaosocial, ";
        $sql .= "nomefantasia, ";
        $sql .= "endereco, ";
        $sql .= "numero, ";
        $sql .= "bairro, ";
        $sql .= "cidade, ";
        $sql .= "uf, ";
        $sql .= "cep, ";
        $sql .= "telefone, ";
        $sql .= "fax, ";
        $sql .= "cel, ";
        $sql .= "tipo, ";
        $sql .= "contato, ";
        $sql .= "prolabore, ";
        $sql .= "prolabore2, ";
        $sql .= "cnpj, ";
        $sql .= "cpf, ";
        $sql .= "insc, ";
        $sql .= "id_categoria, ";
        $sql .= "id_categoria_recibo, ";
        $sql .= "n_parcelas, ";
        $sql .= "registro, ";
        $sql .= "data_cadastro, ";
        $sql .= "email, ";
        $sql .= "email2, ";
        $sql .= "insc_mun, ";
        $sql .= "tipo2, ";
        $sql .= "cobranca, ";
        $sql .= "desativado, ";
        $sql .= "divulga, ";
        $sql .= "situacao, ";
        $sql .= "app, ";
        $sql .= "lista_site) ";

        $sql .= "VALUES (";
        $sql .= ":RazaoSocial, ";
        $sql .= ":NomeFantasia, ";
        $sql .= ":Endereco, ";
        $sql .= ":Numero, ";
        $sql .= ":Bairro, ";
        $sql .= ":Cidade, ";
        $sql .= ":uf, ";
        $sql .= ":CEP, ";
        $sql .= ":Telefone, ";
        $sql .= ":Fax, ";
        $sql .= ":Cel, ";
        $sql .= ":Tipo, ";
        $sql .= ":Contato, ";
        $sql .= ":Prolabore, ";
        $sql .= ":prolabore2, ";
        $sql .= ":CNPJ, ";
        $sql .= ":cpf, ";
        $sql .= ":Insc, ";
        $sql .= ":id_categoria, ";
        $sql .= ":id_categoria_recibo, ";
        $sql .= ":n_parcelas, ";
        $sql .= ":registro, ";
        $sql .= ":data_cadastro, ";
        $sql .= ":EMail, ";
        $sql .= ":EMail2, ";
        $sql .= ":insc_mun, ";
        $sql .= ":tipo2, ";
        $sql .= ":cobranca, ";
        $sql .= ":desativado, ";
        $sql .= ":divulga, ";
        $sql .= ":situacao, ";
        $sql .= ":app, ";
        $sql .= ":lista_site) RETURNING lastval()";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':RazaoSocial', $_RazaoSocial, PDO::PARAM_STR);
            $stmt->bindParam(':NomeFantasia', $_nomefantasia, PDO::PARAM_STR);
            $stmt->bindParam(':Endereco', $_endereco, PDO::PARAM_STR);
            $stmt->bindParam(':Numero', $_numero, PDO::PARAM_STR);
            $stmt->bindParam(':Bairro', $_bairro, PDO::PARAM_STR);
            $stmt->bindParam(':Cidade', $_cidade, PDO::PARAM_STR);
            $stmt->bindParam(':uf', $_uf, PDO::PARAM_STR);
            $stmt->bindParam(':CEP', $_cep, PDO::PARAM_STR);
            $stmt->bindParam(':Telefone', $_tel1, PDO::PARAM_STR);
            $stmt->bindParam(':Fax', $_tel2, PDO::PARAM_STR);
            $stmt->bindParam(':Cel', $_cel, PDO::PARAM_STR);
            $stmt->bindParam(':Tipo', $_tipo, PDO::PARAM_INT);
            $stmt->bindParam(':Contato', $_contato, PDO::PARAM_STR);
            $stmt->bindParam(':Prolabore', $_prolabore, PDO::PARAM_STR);
            $stmt->bindParam(':prolabore2', $_prolabore2, PDO::PARAM_STR);
            $stmt->bindParam(':CNPJ', $_cnpj, PDO::PARAM_STR);
            $stmt->bindParam(':cpf', $_cpf, PDO::PARAM_STR);
            $stmt->bindParam(':Insc', $_insc_est, PDO::PARAM_STR);
            $stmt->bindParam(':id_categoria', $_categoria, PDO::PARAM_INT);
            $stmt->bindParam(':id_categoria_recibo', $_categoriarecibo, PDO::PARAM_INT);
            $stmt->bindParam(':n_parcelas', $_parcelamento, PDO::PARAM_INT);
            $stmt->bindParam(':registro', $_registro, PDO::PARAM_STR);
            $stmt->bindParam(':data_cadastro', $_data_cadastro, PDO::PARAM_STR);
            $stmt->bindParam(':EMail', $_email, PDO::PARAM_STR);
            $stmt->bindParam(':EMail2', $_email2, PDO::PARAM_STR);
            $stmt->bindParam(':insc_mun', $_inscmunicipal, PDO::PARAM_STR);
            $stmt->bindParam(':tipo2', $_tipoempresa, PDO::PARAM_INT);
            $stmt->bindParam(':cobranca', $_cobranca, PDO::PARAM_INT);
            $stmt->bindParam(':desativado', $_desativado, PDO::PARAM_INT);
            $stmt->bindParam(':divulga', $_divulga, PDO::PARAM_STR);
            $stmt->bindParam(':situacao', $_divulga, PDO::PARAM_STR);
            $stmt->bindParam(':app', $_app, PDO::PARAM_INT);
            $stmt->bindParam(':lista_site', $_lista_site, PDO::PARAM_INT);

            $stmt->execute();
            $codigo_convenio = $stmt->fetchColumn();
            $std->situacao = 1;/*2- gravado com sucesso*/
            $std->cel = $_cel;

            // grava usuario e senha convenio
            $sql = "INSERT INTO sind.c_senhaconvenio(";
            $sql .= "cod_convenio,usuario,senha,usuario_texto,password) ";
            $sql .= "VALUES(";
            $sql .= ":cod_convenio, ";
            $sql .= ":usuario, ";
            $sql .= ":senha, ";
            $sql .= ":usuario_texto, ";
            $sql .= ":password)";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':cod_convenio', $codigo_convenio, PDO::PARAM_INT);
            $stmt->bindParam(':usuario', $_ususarioconvenio_crypto, PDO::PARAM_STR);
            $stmt->bindParam(':senha', $_senhaconvenio_crypto, PDO::PARAM_STR);
            $stmt->bindParam(':usuario_texto', $_ususarioconvenio, PDO::PARAM_STR);
            $stmt->bindParam(':password', $_senhaconvenio, PDO::PARAM_STR);
            $stmt->execute();

            $std->cod_convenio = $codigo_convenio;
            $std->usuario = $_ususarioconvenio;
            $std->senha = $_senhaconvenio;
            $std->tipoempresa = $_tipoempresa;
            $std->cpf = $_cpf;
            $std->cnpj = $_cnpj;
            $std->email = $_email;
        } catch (PDOException $erro) {
            if ($erro->getCode() === '42501') {
                $msg_grava_cad = "Seu usuario não tem permissão!";
            } else {
                $msg_grava_cad = "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
            }
            $std->situacao = $erro->getMessage(); /*2- erro*/
        }
    }
}else if($_existe_cpf == true && $_existe_cnpj == false) {
    $std->situacao = 2; // existe cpf
    $std->cpf = $_cpf;
}else if($_existe_cpf == false && $_existe_cnpj == true) {
    $std->situacao = 3; // existe cnpf
    $std->cnpj = $_cnpj;
}
echo json_encode($std);