<?PHP
header("Content-type: application/json; charset=utf-8");

include "Adm/php/banco.php";
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $someArray = array();
    $i=0;
    
    $query = "SELECT convenio.codigo, convenio.razaosocial, 
                                       convenio.nomefantasia, convenio.endereco, convenio.numero, 
                                       convenio.bairro, convenio.cidade, 
                                       convenio.cep, convenio.telefone, convenio.cel,convenio.latitude,convenio.longitude,
                                       convenio.email, categoriaconvenio.nome AS nome_categoria, 
                                       categoriaconvenio.codigo AS codigo_categoria
                                  FROM sind.categoriaconvenio 
                            INNER JOIN sind.convenio 
                                    ON categoriaconvenio.codigo = convenio.id_categoria 
                                 WHERE lista_site = true
                              ORDER BY categoriaconvenio.nome ASC,convenio.nomefantasia ASC;";
    $sql = $pdo->query($query);   

    while($row_conv = $sql->fetch()) {

        $sub_array = array();

        $sub_array["codigo"]           = $row_conv["codigo"];
        $sub_array["razaosocial"]      = htmlspecialchars($row_conv["razaosocial"]);
        $sub_array["nomefantasia"]     = htmlspecialchars($row_conv["nomefantasia"]);
        $sub_array["endereco"]         = htmlspecialchars($row_conv["endereco"]);
        $sub_array["bairro"]           = htmlspecialchars($row_conv["bairro"]);
        $sub_array["cidade"]           = htmlspecialchars($row_conv["cidade"]);
        $sub_array["numero"]           = $row_conv["numero"];
        $sub_array["cep"]              = $row_conv["cep"];
        $sub_array["telefone"]         = $row_conv["telefone"];
        $sub_array["cel"]              = $row_conv["cel"];
        $sub_array["latitude"]         = $row_conv["latitude"];
        $sub_array["longitude"]        = $row_conv["longitude"];
        $sub_array["email"]            = $row_conv["email"];
        $sub_array["nome_categoria"]   = $row_conv["nome_categoria"];
        $sub_array["codigo_categoria"] = $row_conv["codigo_categoria"];
      
        $someArray[]        = $sub_array;
   
    }
    echo json_encode($someArray);