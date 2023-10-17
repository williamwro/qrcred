<?PHP
error_reporting(E_ALL ^ E_NOTICE);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
$date = strftime('%A, %d de %B de %Y', strtotime('today'));

require "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$_limite = 0;
$_limite_hidden = 0;

$_usuario_cod       = $_POST['usuario_cod'];
$_divisao           = isset($_POST['divisao']) ? $_POST['divisao'] : 0;
$_matricula         = isset($_POST['C_matricula_assoc']) ? $_POST['C_matricula_assoc'] : "";
$_matriculax        = isset($_POST['C_matricula_original']) ? $_POST['C_matricula_original'] : "";
$_nome              = isset($_POST['C_nome_assoc']) ? strtoupper(htmlspecialchars($_POST['C_nome_assoc'])) : "";
$_endereco          = isset($_POST['C_endereco_assoc']) ? strtoupper(htmlspecialchars($_POST['C_endereco_assoc'])) : "";
$_numero            = isset($_POST['C_numero_assoc']) ? $_POST['C_numero_assoc'] : "";
$_nascimento        = isset($_POST['C_nascimento']) ? converte_data($_POST['C_nascimento']) : null;

if(isset($_POST['C_salario'])){
    if($_POST['C_salario'] != ''){
        $_salario = $_POST['C_salario'];
        $_salario = str_replace(".","",$_salario);
        $_salario = str_replace(",",".",$_salario);
    }else{
        $_salario = 0;
    }
}else{
    $_salario = 0;
}
if(isset($_POST['C_limite_assoc'])){
    if($_POST['C_limite_assoc'] != ''){
        $_limite = $_POST['C_limite_assoc'];
        $_limite = str_replace(".","",$_limite);
        $_limite = str_replace(",",".",$_limite);
    }else{
        $_limite = 0;
    }
}else{
    $_limite = 0;
}
$_limite_hidden = $_POST['C_limite_hidden'];
$_limite_hidden = str_replace(".","",$_limite_hidden);
$_limite_hidden = str_replace(",",".",$_limite_hidden);

$_empregador_novo     = isset($_POST['C_empregador_assoc']) ? (int)$_POST['C_empregador_assoc'] : 0;
$_empregador_original = isset($_POST['C_empregador_original']) ? (int)$_POST['C_empregador_original'] : 0;
if($_empregador_novo <> $_empregador_original){
    $_empregador = $_empregador_novo;
}else{
    $_empregador = $_empregador_original;
}
$_cep               = isset($_POST['C_cep_assoc']) ? str_replace(".", "", $_POST['C_cep_assoc']) : "";
$_telres            = isset($_POST['C_telres']) ? $_POST['C_telres'] : "";
$_telcom            = isset($_POST['C_telcom']) ? $_POST['C_telcom'] : "";
$_cel               = isset($_POST['C_cel_assoc']) ? $_POST['C_cel_assoc'] : "";
$_bairro            = isset($_POST['C_bairro_assoc']) ? strtoupper(htmlspecialchars($_POST['C_bairro_assoc'])) : "";
$_complemento       = isset($_POST['C_complemento_assoc']) ? strtoupper(htmlspecialchars($_POST['C_complemento_assoc'])) : "";
$_cidade            = isset($_POST['C_cidade_assoc']) ? htmlspecialchars($_POST['C_cidade_assoc']) : "";
$_uf                = isset($_POST['C_uf_assoc']) ? $_POST['C_uf_assoc'] : "";
$_tipo_novo         = isset($_POST['C_tipo_assoc']) ? (int)$_POST['C_tipo_assoc'] : (int)1; //1-EFETIVO,2-CONTRATADO
$_tipo_original     = isset($_POST['C_tipo_original']) ? (int)$_POST['C_tipo_original'] : (int)1; //1-EFETIVO,2-CONTRATADO
if($_tipo_novo <> $_tipo_original){
    $_tipo = $_tipo_novo;
}else{
    $_tipo = $_tipo_original;
}
$_rg                = isset($_POST['C_rg_assoc']) ? $_POST['C_rg_assoc'] : "";
$_cpf               = isset($_POST['C_cpf_assoc']) ? str_replace(".", "", $_POST['C_cpf_assoc']) : "";
$_cpf               = str_replace("-", "", $_cpf);
$_funcao_novo       = isset($_POST['C_funcao']) ? (int)$_POST['C_funcao'] : (int)0;
$_funcao_original   = isset($_POST['C_funcao_original']) ? (int)$_POST['C_funcao_original'] : (int)0;
if($_funcao_novo <> $_funcao_original){
    $_funcao = $_funcao_novo;
}else{
    $_funcao = $_funcao_original;
}
$_obs                  = isset($_POST['C_obs']) ? strtoupper($_POST['C_obs']) : "";
$_id_situacao_novo     = isset($_POST['C_situacao_assoc']) ? (int)$_POST['C_situacao_assoc'] : (int)1;//1-ATIVO,2-EXONERADO,3-FALECIDO
$_id_situacao_original = isset($_POST['C_situacao_original']) ? (int)$_POST['C_situacao_original'] : (int)1;//1-ATIVO,2-EXONERADO,3-FALECIDO
if($_id_situacao_novo <> $_id_situacao_original){
    $_id_situacao = $_id_situacao_novo;
}else{
    $_id_situacao = $_id_situacao_original;
}
$_email = isset($_POST['C_Email_assoc']) ? strtolower($_POST['C_Email_assoc']) : "";
if ($_POST['C_datacadastro_assoc'] != ""){
    $_data_filiacao = converte_data($_POST['C_datacadastro_assoc']);
}else{
    $_data_filiacao = null;
}
if (isset($_POST['C_datadesfiliacao'])){
    if ($_POST['C_datadesfiliacao'] != "") {
        $_data_desfiliacao = converte_data($_POST['C_datadesfiliacao']);
    }else{
        $_data_desfiliacao = null;
    }
}else{
    $_data_desfiliacao = null;
}
$_filiado = isset($_POST['C_filiado']) ? true : false;
$_celwatzap = isset($_POST['SwitchCelular']) ? true : false;
if(isset($_POST['C_parcelas_permitidas'])){
    if($_POST['C_parcelas_permitidas'] == ""){
        $_parcelas_permitidas = 0;
    }else{
        $_parcelas_permitidas = (int)$_POST['C_parcelas_permitidas'];
    }
}else{
    $_parcelas_permitidas = 0;
}
if (isset($_POST['C_ultimo_mes'])){
    $_ultimo_mes = $_POST['C_ultimo_mes'];
}else{
    $_ultimo_mes = null;
}
$_secretaria  = isset($_POST['C_secretaria']) ? (int)$_POST['C_secretaria'] : 0;
$_local       = $_POST['C_local'];
function converte_data($date) {
    return substr($date,6,4).'-'.substr($date,3,2).'-'.substr($date,0,2).' 00:00:00';
}
$stmt = new stdClass();
$stmt4 = new stdClass();
$stmt5 = new stdClass();
$stmt6 = new stdClass();
$msg_grava_cad="";
if(isset($_POST["operation"])) {
    if($_POST["operation"] == "Update") {

        $sql = "UPDATE sind.associado SET ";
        $sql .= "nome = :nome, ";
        $sql .= "endereco = :endereco, ";
        $sql .= "numero = :numero, ";
        $sql .= "nascimento = :nascimento, ";
        $sql .= "salario = :salario, ";
        $sql .= "limite = :limite, ";
        $sql .= "empregador = :empregador, ";
        $sql .= "cep = :cep, ";
        $sql .= "telres = :telres, ";
        $sql .= "telcom = :telcom, ";
        $sql .= "cel = :cel, ";
        $sql .= "bairro = :bairro, ";
        $sql .= "complemento = :complemento, ";
        $sql .= "cidade = :cidade, ";
        $sql .= "uf = :uf, ";
        $sql .= "rg = :rg, ";
        $sql .= "cpf = :cpf, ";
        $sql .= "funcao = :funcao, ";
        $sql .= "filiado = :filiado, ";
        $sql .= "celwatzap = :celwatzap, ";
        $sql .= "obs = :obs, ";
        $sql .= "id_situacao = :id_situacao, ";
        $sql .= "data_filiacao = :data_filiacao, ";
        $sql .= "data_desfiliacao = :data_desfiliacao, ";
        $sql .= "email = :email, ";
        $sql .= "tipo = :tipo, ";
        $sql .= "parcelas_permitidas = :parcelas_permitidas, ";
        $sql .= "ultimo_mes = :ultimo_mes, ";
        $sql .= "id_divisao = :divisao, ";
        $sql .= "id_secretaria = :id_secretaria, ";
        $sql .= "localizacao = :localizacao ";
        $sql .= "WHERE codigo = '" . $_matriculax ."' ";
        $sql .= "AND empregador = " . $_empregador_original ." ";
        $sql .= "AND id_divisao = " . $_divisao ."";

        $msg_grava_cad = "atualizado";

    }elseif($_POST["operation"] == "Add") {

        $sql = "INSERT INTO sind.associado( ";
        $sql .= "codigo,nome,endereco,numero,nascimento,salario,limite,empregador,bairro,cidade,uf,cep,telres,telcom,cel,complemento, ";
        $sql .= "rg,cpf,funcao,filiado,obs,id_situacao,data_filiacao,data_desfiliacao,email,tipo,parcelas_permitidas,celwatzap,ultimo_mes,id_divisao,id_secretaria,localizacao) VALUES( ";
        $sql .= ":codigo, ";
        $sql .= ":nome, ";
        $sql .= ":endereco, ";
        $sql .= ":numero, ";
        $sql .= ":nascimento, ";
        $sql .= ":salario, ";
        $sql .= ":limite, ";
        $sql .= ":empregador, ";
        $sql .= ":bairro, ";
        $sql .= ":cidade, ";
        $sql .= ":uf, ";
        $sql .= ":cep, ";
        $sql .= ":telres, ";
        $sql .= ":telcom, ";
        $sql .= ":cel, ";
        $sql .= ":complemento, ";
        $sql .= ":rg, ";
        $sql .= ":cpf, ";
        $sql .= ":funcao, ";
        $sql .= ":filiado, ";
        $sql .= ":obs, ";
        $sql .= ":id_situacao, ";
        $sql .= ":data_filiacao, ";
        $sql .= ":data_desfiliacao, ";
        $sql .= ":email, ";
        $sql .= ":tipo, ";
        $sql .= ":parcelas_permitidas, ";
        $sql .= ":celwatzap, ";
        $sql .= ":ultimo_mes, ";
        $sql .= ":divisao, ";
        $sql .= ":id_secretaria, ";
        $sql .= ":localizacao)";

        $msg_grava_cad = "cadastrado";

    }
    try {

        $stmt = $pdo->prepare($sql);

        if($_POST['operation'] == 'Add') {
            $stmt->bindParam(':codigo', $_matricula, PDO::PARAM_STR);   //1
        }
        $stmt->bindParam(':nome', $_nome, PDO::PARAM_STR);              //2
        $stmt->bindParam(':endereco', $_endereco, PDO::PARAM_STR);      //3
        $stmt->bindParam(':bairro', $_bairro, PDO::PARAM_STR);          //4
        $stmt->bindParam(':numero', $_numero, PDO::PARAM_STR);          //5
        $stmt->bindParam(':nascimento', $_nascimento, PDO::PARAM_STR);  //6
        $stmt->bindParam(':salario', $_salario, PDO::PARAM_STR);        //7
        $stmt->bindParam(':limite', $_limite, PDO::PARAM_STR);          //8
        $stmt->bindParam(':empregador', $_empregador, PDO::PARAM_INT);  //9
        $stmt->bindParam(':cidade', $_cidade, PDO::PARAM_STR);          //10
        $stmt->bindParam(':uf', $_uf, PDO::PARAM_STR);                  //11
        $stmt->bindParam(':cep', $_cep, PDO::PARAM_STR);                //12
        $stmt->bindParam(':telres', $_telres, PDO::PARAM_STR);          //13
        $stmt->bindParam(':telcom', $_telcom, PDO::PARAM_STR);          //14
        $stmt->bindParam(':cel', $_cel, PDO::PARAM_STR);                //15
        $stmt->bindParam(':bairro', $_bairro, PDO::PARAM_STR);          //16
        $stmt->bindParam(':complemento', $_complemento, PDO::PARAM_STR);//17
        $stmt->bindParam(':rg', $_rg, PDO::PARAM_STR);                  //18
        $stmt->bindParam(':cpf', $_cpf, PDO::PARAM_STR);                //19
        $stmt->bindParam(':funcao', $_funcao, PDO::PARAM_INT);          //20
        $stmt->bindParam(':filiado', $_filiado, PDO::PARAM_BOOL);       //21
        $stmt->bindParam(':obs', $_obs, PDO::PARAM_STR);                //22
        $stmt->bindParam(':id_situacao', $_id_situacao, PDO::PARAM_INT); //23
        $stmt->bindParam(':data_filiacao', $_data_filiacao, PDO::PARAM_STR);//24
        $stmt->bindParam(':data_desfiliacao', $_data_desfiliacao, PDO::PARAM_STR);//25
        $stmt->bindParam(':email', $_email, PDO::PARAM_STR);            //26
        $stmt->bindParam(':tipo', $_tipo, PDO::PARAM_INT);              //27
        $stmt->bindParam(':parcelas_permitidas', $_parcelas_permitidas, PDO::PARAM_INT);//28
        $stmt->bindParam(':celwatzap', $_celwatzap, PDO::PARAM_BOOL);    //29
        $stmt->bindParam(':ultimo_mes', $_ultimo_mes, PDO::PARAM_STR);  //31
        $stmt->bindParam(':divisao', $_divisao, PDO::PARAM_INT);        //32
        $stmt->bindParam(':id_secretaria', $_secretaria, PDO::PARAM_INT);  //33
        $stmt->bindParam(':localizacao', $_local, PDO::PARAM_STR);        //34

        $stmt->execute();

        $data2      = new DateTime();
        $data       = $data2->format('Y-m-d h:i:s');
        // REGISTRA LOG ALTERAÇOES DE LIMITE INICIO ******************************
        if($_limite != $_limite_hidden || $_limite_hidden === "") {
            if ( $_limite_hidden === ""){ $_limite_hidden = 0 ; }
            $sql6 = "INSERT INTO sind.associado_log_limites(";
            $sql6 .= "associado,limite_old,limite_new,usuario,datahora,id_divisao,empregador) VALUES (";
            $sql6 .= ":associado, ";
            $sql6 .= ":limite_old, ";
            $sql6 .= ":limite_new, ";
            $sql6 .= ":usuario, ";
            $sql6 .= ":datahora, ";
            $sql6 .= ":id_divisao, ";
            $sql6 .= ":empregador) ";

            $stmt6 = $pdo->prepare($sql6);
            $stmt6->bindParam(':associado', $_matricula, PDO::PARAM_STR);
            $stmt6->bindParam(':limite_old', $_limite_hidden, PDO::PARAM_STR);
            $stmt6->bindParam(':limite_new', $_limite, PDO::PARAM_STR);
            $stmt6->bindParam(':usuario', $_usuario_cod, PDO::PARAM_INT);
            $stmt6->bindParam(':datahora', $data, PDO::PARAM_STR);
            $stmt6->bindParam(':id_divisao', $_divisao, PDO::PARAM_INT);
            $stmt6->bindParam(':empregador', $_empregador, PDO::PARAM_INT);

            $stmt6->execute();

        }
        echo $msg_grava_cad;

    } catch (PDOException $erro) {
        if($erro->getCode() === '42501'){
            $msg_grava_cad = "Seu usuario não tem permissão!";
        }else{
            $msg_grava_cad = "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
        }
        echo $msg_grava_cad;
    }
}