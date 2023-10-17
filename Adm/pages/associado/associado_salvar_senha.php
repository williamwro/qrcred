<?PHP
require '../../php/banco.php';
include "../../php/funcoes.php";

if(isset($_POST['cod_associado_senha'])){
    $_codigo = $_POST['cod_associado_senha'];
    $_senha = $_POST['senha_associado'];
    $_Csenhaconfirma = $_POST['C_Confirma_Senha_assoc'];
    $_Csenha = $_POST['C_Senha_assoc'];
    $_existe_senha = $_POST['existe_senha'];
    $id_empregador = $_POST['id_empregador_senha'];
    $stmt = new stdClass();
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $msg_grava_cad="";

    try {
        if ($_existe_senha == "nao") {
            if( $_Csenha != "" && $_Csenhaconfirma != "" ) {
                if ($_Csenha == $_Csenhaconfirma) {

                        $sql = "INSERT INTO sind.c_senhaassociado(cod_associado, senha, id_empregador) ";
                        $sql .= "VALUES(:codigo_associado, :senha, :id_empregador)";
                        $msg_grava_cad = "cadastrado";

                        $stmt = $pdo->prepare($sql);
                        $stmt->bindParam(':codigo_associado', $_codigo, PDO::PARAM_STR);
                        $stmt->bindParam(':senha', $_Csenha, PDO::PARAM_STR);
                        $stmt->bindParam(':id_empregador', $id_empregador, PDO::PARAM_INT);

                        $stmt->execute();
                        echo $msg_grava_cad;


                } else {
                    echo "senha_divergente";
                }
            }else{
                echo "senha_vazia";
            }
        }else{
            if( $_Csenha != "" && $_Csenhaconfirma != "" ) {
                if ($_Csenha = $_Csenhaconfirma) {
                    $sql = "UPDATE sind.c_senhaassociado SET ";
                    $sql .= "senha = :senha ";
                    $sql .= "WHERE cod_associado = :cod_associado ";
                    $sql .= "AND id_empregador = :id_empregador";
                    $msg_grava_cad = "atualizado";

                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':cod_associado', $_codigo, PDO::PARAM_STR);
                    $stmt->bindParam(':senha', $_Csenhaconfirma, PDO::PARAM_STR);
                    $stmt->bindParam(':id_empregador', $id_empregador, PDO::PARAM_INT);

                    $stmt->execute();
                    echo $msg_grava_cad;

                } else {
                    echo "senha_divergente";
                }
            }else{
                echo "senha_vazia";
            }
        }
    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    }
}