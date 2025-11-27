<?PHP
header("Content-type: application/json; charset=utf-8");

include "Adm/php/banco.php";
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $someArray = array();
    $i=0;
    
    $query = "SELECT distinct
                    c.razaosocial AS convenio_nome,
                    e.nome_especialidade AS especialidade,
                    p.nome_profissional AS profissional,
                    COALESCE(te.nome_tipo, 'Não informado') AS tipo_estabelecimento
                
                FROM 
                    sind.convenio_especialidades ce
                JOIN 
                    sind.convenio c ON ce.cod_convenio = c.codigo
                JOIN 
                    sind.profissionais p ON ce.cod_profissional = p.id_profissional
                JOIN 
                    sind.profissionais_especialidade pe ON p.id_profissional = pe.id_profissional
                JOIN 
                    sind.especialidade e ON pe.id_especialidade = e.id_especialidade
                LEFT JOIN 
                    sind.tipo_especialidade te ON e.id_tipo_especialidade = te.id_tipo_especialidade
                ORDER BY 
                    c.razaosocial, e.nome_especialidade, p.nome_profissional;";
    $sql = $pdo->query($query);   

    while($row_conv = $sql->fetch()) {

        $sub_array = array();

        $sub_array["convenio_nome"]        = $row_conv["convenio_nome"];
        $sub_array["especialidade"]        = $row_conv["especialidade"];
        $sub_array["profissional"]         = $row_conv["profissional"];
        $sub_array["tipo_estabelecimento"] = $row_conv["tipo_estabelecimento"];

        $someArray[] = $sub_array;
   
    }
    echo json_encode($someArray);