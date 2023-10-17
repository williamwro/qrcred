<?php
date_default_timezone_set('America/Araguaina');

include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mes_atual    = $_POST['mes_atual'];
if (isset($_POST['cod_convenio'])){
    $cod_convenio = $_POST['cod_convenio'];
    $todos = 0;
}else{
    $cod_convenio = 0;
    $todos = 1;
}
$ordem = "associado.nome";
//$ordem        = $_POST['ordem'];
if(isset($_POST['parcela'])){
    $parcela = $_POST['parcela'];
}
if(isset($_POST['empregador'])) {
    $empregador = $_POST['empregador'];
}
$divisao = $_POST['divisao'];
//$mes_atual = $mes_atual."/".$_POST['ano'];

require("../components/fpdf/fpdf.php");

class PDF extends FPDF
{
    private static $RS;
    public static function setRS( $RSL ) {
        self::$RS = $RSL;
    }
    private static $MS;
    public static function setMS( $MES ) {
        self::$MS = $MES;
    }
    private static $PG;
    public static function setPG( $PAGINA ) {
        self::$PG = $PAGINA;
    }
    private static $DV;
    public static function getDV( $DIVISAOX ) {
        return self::$DV = $DIVISAOX;
    }
// Page header
    function Header()
    {
        // Logo
        if(self::$DV == 1){//AFFE
            $this->Image('../../../pictures_site-sind/logo4.png',10,8,18); // pictures_site-sind/logo4.png',7,9,25);
        }
        // Arial bold 15
        $this->SetFont('Arial','B',12   );

        $this->Cell(22);//move para direita 20 posiçoes
        if(self::$DV == 1){//AFFE
            $this->Write(0,utf8_decode('Relatório de produção do convenio QrCred'));
        }

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,date('d/m/Y')." - ".date('H:i:s'));

        $this->Ln();//pula linha
        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(12,"Estabelecimento: ".utf8_decode(self::$RS));// razao social

        $this->Ln();//pula linha
        $this->Cell(22);
        $this->Write(0,utf8_decode("Mês: ").self::$MS);

        $this->Cell(102);
        $this->Write(0,"Pagina: ".self::$PG);

        $this->Ln(8);//pula linha
        $this->SetFont('Arial','B',8);

        $this->Cell(15,-6,"Registro",0,0,'L');

        $this->Cell(15,-6,"Matricula",0,0,'L');

        $this->Cell(90,-6,"nome",0,0,'L');

        $this->Cell(26,-6,"data",0,0,'L');

        $this->Cell(17,-6,"Hora",0,0,'L');

        $this->Cell(12,-6,"valor",0,0,'R');

        $this->Cell(23,-6,"Parcela",0,0,'C');

        // Line break
        $this->Ln(0);
        //linha horizontal
        $this->SetLineWidth(0.2);
        $this->Line("7","29","201","29");
    }

// Page footer
    function Footer()
    {

        // Position at 1.5 cm from bottom
        $this->SetY(-15);


        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Page number
        //$this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
        $this->Cell(0,10,'QRCRED',0,0,'C');
        $this->SetLineWidth(0.2);
        $this->Line("7","280","201","280");
    }
}
PDF::setMS($mes_atual);
$pagina=1;
PDF::setPG($pagina);
PDF::getDV($divisao);

$item   = 0;
$item_pagina = 0;
$total  = 0;

if (isset($_POST["cod_convenio"]) and $_POST["cod_convenio"] != "") {
    if (isset($_POST["empregador"]) and $_POST["empregador"] != "") {
        if (isset($_POST["parcela"]) and $_POST["parcela"] != "") {
            $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, empregador.id AS codigo_empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
                      FROM sind.associado 
                      RIGHT JOIN (sind.empregador 
                      RIGHT JOIN (sind.convenio 
                      RIGHT JOIN sind.conta ON convenio.codigo = conta.convenio) 
                      ON empregador.id = conta.empregador) 
                      ON associado.codigo = conta.associado AND associado.empregador = conta.empregador
                      WHERE convenio.codigo = " . $_POST["cod_convenio"] . " 
                      AND conta.mes = '" . $_POST["mes_atual"] . "'
                      AND empregador.id =" . $_POST["empregador"] . " 
                      AND left(conta.parcela,2) ='" . $_POST["parcela"] . "'
                      AND empregador.divisao = " . $divisao . " 
                      AND convenio.desativado = false
                   
                      ORDER BY convenio.razaosocial, " . $ordem . ";";
        }else{
            $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, empregador.id AS codigo_empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
                      FROM sind.associado 
                      RIGHT JOIN (sind.empregador 
                      RIGHT JOIN (sind.convenio 
                      RIGHT JOIN sind.conta 
                      ON convenio.codigo = conta.convenio) 
                      ON empregador.id = conta.empregador) 
                      ON associado.codigo = conta.associado AND associado.empregador = conta.empregador
                      WHERE convenio.codigo = " . $_POST["cod_convenio"] . " AND conta.mes = '" . $_POST["mes_atual"] . "'
                      AND empregador.id =" . $_POST["empregador"] . " AND empregador.divisao = " . $divisao . " AND convenio.desativado = false ORDER BY convenio.razaosocial, " . $ordem . ";";
        }
    } else {
        if (isset($_POST["parcela"]) and $_POST["parcela"] != "") {
            $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, empregador.id AS codigo_empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
            FROM sind.associado 
            RIGHT JOIN (sind.empregador 
            RIGHT JOIN (sind.convenio 
            RIGHT JOIN sind.conta 
            ON convenio.codigo = conta.convenio) 
            ON empregador.id = conta.empregador) 
            ON associado.codigo = conta.associado AND associado.empregador = conta.empregador
            WHERE convenio.codigo = " . $_POST["cod_convenio"] . " 
            AND conta.mes = '" . $_POST["mes_atual"] . "' 
            AND left(conta.parcela,2) ='" . $_POST["parcela"] . "'
            AND empregador.divisao = " . $divisao . " 
            AND convenio.desativado = false
           
            ORDER BY convenio.razaosocial, " . $ordem . ";";
        }else{
            $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, empregador.id AS codigo_empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
            FROM sind.associado 
            RIGHT JOIN (sind.empregador 
            RIGHT JOIN (sind.convenio 
            RIGHT JOIN sind.conta 
            ON convenio.codigo = conta.convenio) 
            ON empregador.id = conta.empregador) 
            ON associado.codigo = conta.associado AND associado.empregador = conta.empregador
            WHERE convenio.codigo = " . $_POST["cod_convenio"] . " AND conta.mes = '" . $_POST["mes_atual"] . "' AND empregador.divisao = " . $divisao . " AND convenio.desativado = false
         
            ORDER BY convenio.razaosocial, " . $ordem . ";";
        }
    }

} else {

    if (isset($_POST["empregador"]) and $_POST["empregador"] != "") {
        $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, empregador.id AS codigo_empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
        FROM sind.associado 
        RIGHT JOIN (sind.empregador 
        RIGHT JOIN (sind.convenio 
        RIGHT JOIN sind.conta 
        ON convenio.codigo = conta.convenio) 
        ON empregador.id = conta.empregador) 
        ON associado.codigo = conta.associado AND associado.empregador = conta.empregador
        WHERE empregador.id =" . $_POST["empregador"] . " AND conta.mes = '" . $_POST["mes_atual"] . "' AND empregador.divisao = ".$divisao."  AND convenio.desativado = false
        
        ORDER BY convenio.razaosocial, ".$ordem.";";

    } else {
        $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, empregador.id AS codigo_empregador, convenio.razaosocial AS convenio, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao
        FROM sind.associado 
        RIGHT JOIN (sind.empregador 
        RIGHT JOIN (sind.convenio 
        RIGHT JOIN sind.conta 
        ON convenio.codigo = conta.convenio) 
        ON empregador.id = conta.empregador) 
        ON associado.codigo = conta.associado AND associado.empregador = conta.empregador
        WHERE conta.mes = '" . $_POST["mes_atual"] . "' AND empregador.divisao = ".$divisao." AND convenio.desativado = false
         
        ORDER BY convenio.razaosocial ASC, ".$ordem." ASC;";
    }
}
/*AND associado.codigo <> '".$card1."'
AND associado.codigo <> '".$card2."'
AND associado.codigo <> '".$card3."'*/
$grupo_todos_convenios = "SELECT empregador.nome, sum(conta.valor) as total
                            FROM sind.convenio 
                      RIGHT JOIN sind.conta 
                              ON convenio.codigo = conta.convenio 
                      RIGHT JOIN sind.empregador
                              ON empregador.id = conta.empregador 
                           WHERE (((conta.mes)='" . $mes_atual . "') 
                             AND empregador.divisao = ".$divisao." 
                             AND convenio.desativado = false)
                        GROUP BY empregador.id;";

PDF::setMS($mes_atual);
$convenio_aux="";
$aux = 0;
$total_paginas=0;
$sql_conv_vendas = $pdo->query($query);
//$xxx = count($sql_conv_vendas->fetchAll()); //QUANTIDADE DE REGISTROS
$linhas_filtradas = $sql_conv_vendas->rowCount();
$count_ana = 0;
$count_marcia = 0;
$count_marcio = 0;
$count_william = 0;
$datax = "";
$horax = "";
//*******************     EXCLUIR TABELA TEMPORARIA INICIO     *******************/
$sql_limpa_temp = "DELETE FROM sind.temp_vendas_convenio";
$stmt = $pdo->prepare($sql_limpa_temp);
$stmt->execute();
//*******************      EXCLUIR TABELA TEMPORARIA FIM       ********************/
//*******************      LISTA OS VALORES E GRAVA TAB TEMP INICIO      ********************/
while($row = $sql_conv_vendas->fetch()) {
    if ($convenio_aux == "") {
        $convenio_aux = $row['convenio'];
    }
    if ($convenio_aux != $row['convenio']) {
        $grupo_por_convenio = "SELECT empregador.nome, sum(conta.valor) as total
                                    FROM sind.convenio 
                              RIGHT JOIN sind.conta 
                                      ON convenio.codigo = conta.convenio 
                              RIGHT JOIN sind.empregador
                                      ON empregador.id = conta.empregador 
                                   WHERE (((conta.mes)='" . $mes_atual . "') 
                                     AND empregador.divisao = " . $divisao . " 
                                     AND convenio.codigo = " . $cod_convenio . "
                                     AND convenio.desativado = false)
                                GROUP BY empregador.id;";
        $convenio_aux = $row['convenio'];
        $cod_convenio = $row['cod_convenio'];
        //$total = 0;
        $item = 0;
    }
    $item++;
    $valor = floatval($row['valor']);
    $total = $total + $valor;
    //$valor = number_format($valor, 2, ',', '.');
    $datax = date('d/m/Y', strtotime($row['data']));
    $horax = substr($row['hora'], 0, 5);
    $sql_inser = "INSERT INTO sind.temp_vendas_convenio(";
    $sql_inser .= "registro, matricula, nome, data, hora, valor, parcela) VALUES(";
    $sql_inser .= ":registro,:matricula,:nome,:data,:hora,:valor,:parcela)";
   
    $stmt = $pdo->prepare($sql_inser);

    //  AQUI NAO APARECE OS LANÇAMENTOS MAS SOMA TODOS
    $stmt->bindParam(':registro', $row['lancamento'], PDO::PARAM_STR);
    $stmt->bindParam(':matricula', $row['matricula'], PDO::PARAM_STR);
    $stmt->bindParam(':nome', $row['associado'], PDO::PARAM_STR);
    $stmt->bindParam(':data', $datax, PDO::PARAM_STR);
    $stmt->bindParam(':hora', $horax, PDO::PARAM_STR);
    $stmt->bindParam(':valor', $valor, PDO::PARAM_STR);
    $stmt->bindParam(':parcela', $row['parcela'], PDO::PARAM_STR);

    $stmt->execute();
 
    $convenio_aux = $row['convenio'];
    $cod_convenio = $row['cod_convenio'];
}

   
$sql_result = "SELECT registro, matricula, nome, data, hora, valor, parcela
                 FROM sind.temp_vendas_convenio          
             ORDER BY nome ASC";

$sql_tab_temp_vendas = $pdo->query($sql_result);
$linhas_filtradas_temp = $sql_tab_temp_vendas->rowCount();
$pagina = 1;
PDF::setPG($pagina);
PDF::setRS($convenio_aux);
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 8);
$item_pagina = 0;

while($row = $sql_tab_temp_vendas->fetch()) {

    $item++;
    $item_pagina++;
    if ($item_pagina === 60) {
        $pagina = $pagina + 1;
        $item_pagina = 0;
        PDF::setPG($pagina);
        $pdf->AddPage();
    }
    

    $valor = floatval($row['valor']);
    //$total = $total + $valor;
    $valor = number_format($valor, 2, ',', '.');
   
    $pdf->Cell(15, 4, $row['registro']);
    $pdf->Cell(15, 4, $row['matricula']);
    $pdf->Cell(90, 4, $row['nome']);
    $pdf->Cell(25, 4, $row['data']);
    $pdf->Cell(17, 4, $row['hora']);
    $pdf->Cell(13, 4, $valor, '', '', 'R');
    $pdf->Cell(23, 4, $row['parcela'], '', '', 'C');
    $pdf->Ln();

}
$pdf->Ln(8);
$pdf->Cell(40, 10, "TOTAL : ", 0, 0, 'R');
$pdf->Cell(18, 10, number_format($total, "2", ",", "."), 0, 0, 'R');
$total = 0;
$item = 0;
if($todos === 0){
    $pdf->Output('I',$convenio_aux."-".$mes_atual."-QRCRED.pdf");
}else{
    $pdf->Output('I',"TODOS_CONVENIOS-".$mes_atual."-QRCRED.pdf");
}