<?php
include "../../php/banco.php";
include "../../php/funcoes.php";

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
require("../components/fpdf/fpdf.php");
$divisao = $_POST['divisao'];

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
    private static $DV;
    public static function getDV( $DIVISAOX ) {
        return self::$DV = $DIVISAOX;
    }
// Page header
    function Header()
    {

        // Logo
        if(self::$DV == 1){//CASSERV
            $this->Image('../../../pictures_site-sind/logo4.png',10,8,18);
        }
        // Arial bold 15
        $this->SetFont('Arial','B',12);

        $this->Cell(80);//move para direita 20 posiçoes
        if(self::$DV == 1){//CASSERV
            $this->Write(4,utf8_decode('RELAÇÃO DOS CONVENIOS QRCRED'));
        }

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(4,date('d/m/Y')." - ".date('H:i:s'));

        $this->Ln();//pula linha
        //$this->Cell(20);//move para direita 20 posiçoes
        //$this->Write(12,"Estabelecimento: ".utf8_decode(self::$RS));// razao social

        $this->Ln();//pula linha
        //$this->Cell(20);
        //$this->Write(0,utf8_decode("Mês: ").self::$MS);

        $this->Ln(12);//pula linha
        $this->SetFont('Arial','B',8);

        $this->Cell(60,-6,"CATEGORIA",0,0,'L');

        $this->Cell(90,-6,"NOME convenio",0,0,'L');

        $this->Cell(69,-6,utf8_decode("ENDEREÇO"),0,0,'L');

        $this->Cell(35,-6,"BAIRRO",0,0,'L');

        $this->Cell(8,-6,"TELEFONE",0,0,'L');

        

        // Line break
        $this->Ln(0);
        //linha horizontal
        $this->SetLineWidth(0.2);
        $this->Line("7","29","290","29");
    }

// Page footer
    function Footer()
    {

        // Position at 1.5 cm from bottom
        $this->SetY(-15);


        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Page number
        $this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
        $this->SetLineWidth(0.2);
        $this->Line("7","280","201","280");
    }
}

PDF::getDV($divisao);

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage('Landscape');

$pdf->SetFont('Arial','B',8);
$item  = 0;
$total = 0;

$sql_conv_vendas = $pdo->query("SELECT convenio.codigo, 
                                               convenio.razaosocial, 
                                               convenio.nomefantasia, 
                                               convenio.endereco, 
                                               convenio.bairro, 
                                               convenio.cidade, 
                                               convenio.cep, 
                                               convenio.telefone, 
                                               convenio.email, 
                                               categoriaconvenio.nome AS nome_categoria, 
                                               categoriaconvenio.codigo AS codigo_categoria
                                          FROM sind.categoriaconvenio 
                                    INNER JOIN sind.convenio 
                                            ON categoriaconvenio.codigo = convenio.id_categoria 
                                         WHERE situacao = 'S' 
                                           AND desativado = false 
                                           AND convenio.lista_site = true 
                                      ORDER BY categoriaconvenio.nome, convenio.nomefantasia ASC;");
while($row_vendas = $sql_conv_vendas->fetch()) {
    $item++;

    $pdf->Cell(60,4,$row_vendas['nome_categoria']);
    $pdf->Cell(90,4,$row_vendas['nomefantasia']);
    $pdf->Cell(69,4,$row_vendas['endereco']);
    $pdf->Cell(35,4,$row_vendas['bairro']);
    $pdf->Cell(19,4,$row_vendas['telefone']);

    $pdf->Ln();
}
$pdf->Ln();
$pdf->Cell(142, 11,"Registros : ".$item,0,0,'R');

$pdf->Output('I','CONVENIOS-SINDSERVA-MAKECARD.pdf');