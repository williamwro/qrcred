<?PHP
header("Content-type: application/json");

include "Adm/php/banco.php";
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $someArray = array();
    $i=0;
    $sql = $pdo->query("SELECT convenio.codigo, convenio.razaosocial, 
                                       convenio.nomefantasia, convenio.endereco, convenio.numero, 
                                       convenio.bairro, convenio.cidade, 
                                       convenio.cep, convenio.telefone, convenio.cel,convenio.latitude,convenio.longitude,
                                       convenio.email, categoriaconvenio.nome AS nome_categoria, 
                                       categoriaconvenio.codigo AS codigo_categoria
                                  FROM sind.categoriaconvenio 
                            INNER JOIN sind.convenio 
                                    ON categoriaconvenio.codigo = convenio.id_categoria 
                                 WHERE lista_site = true
                              ORDER BY categoriaconvenio.nome ASC,convenio.nomefantasia ASC;");
    while($row = $sql->fetch()) {
        $someArray[$i] = array_map("utf8_encode",$row);
        $i++;
    }
	//$someArray2 = array_values( array_unique( $someArray, SORT_REGULAR ) );
	// Make a JSON string from the array.
	//$someArray3 = json_encode( $someArray2 );
    echo json_encode($someArray, JSON_PRETTY_PRINT);