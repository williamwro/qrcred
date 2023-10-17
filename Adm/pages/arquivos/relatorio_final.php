<?php
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('America/Araguaina');
include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$mes_atual = $_POST['mes_atual'];
$empregador = $_POST['empregador'];
$divisao    = $_POST['divisao'];


if (isset($_POST['tipo'])) {
    $tipo = $_POST['tipo'];
    $sql = $pdo->query("SELECT nome FROM sind.tipoconvenio WHERE codigo = ".$tipo);
    while($row = $sql->fetch()) {
        $nome_tipo = $row['nome'];
    }
}else{
    $nome_tipo = "Todos";
    $tipo = null;
}

//require("../components/fpdf/fpdf.php");
require('rotation.php');

class PDF extends PDF_Rotate
{
    private static $RS;
    public static function setRS( $RSL ) {
        self::$RS = $RSL;
    }
    private static $MS;
    public static function setMS( $MES ) {
        self::$MS = $MES;
    }
    private static $TP;
    public static function setTIPO( $TIPO ) {
        self::$TP = $TIPO;
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
        if(self::$DV == 1){//CASSERV
            $this->Image('../../../pictures_site-sind/logo4.png',10,8,18);
        }
        // Arial bold 15
        $this->SetFont('Arial','B',12   );

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,utf8_decode('Relatório final para desconto em folha'));

        $this->Cell(38);//move para direita 20 posiçoes
        $this->Write(0,date('d/m/Y')." - ".date('H:i:s'));

        $this->Ln();//pula linha
        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(12,"Empregador: ".utf8_decode(self::$RS));// razao social

        $this->Ln();//pula linha
        $this->Cell(22);
        $this->Write(0,utf8_decode("Mês: ").self::$MS);

        $this->Ln();//pula linha
        $this->Cell(100);
        $this->Write(0,utf8_decode("Tipo: ").self::$TP);

        $this->Cell(35);
        $this->Write(0,"Pagina: ".self::$PG);

        $this->Ln(8);//pula linha
        $this->SetFont('Arial','B',8);

        $this->Cell(15,-6,"matricula",0,0,'L');

        $this->Cell(96,-6,"nome",0,0,'L');

        $this->Cell(20,-6,"compras",0,0,'R');

        $this->Cell(20,-6,"farmacia",0,0,'R');

        $this->Cell(20,-6,"unimed",0,0,'R');

        $this->Cell(18,-6,"total",0,0,'R');

        // Line break
        $this->Ln(0);
        //linha horizontal
        $this->SetLineWidth(0.2);
        $this->Line("7","29","201","29");
        //Insere marca dagua
        $this->SetFont('Arial','B',50);
        $this->SetTextColor(255,192,203);
        if(self::$DV == 1){//CASSERV
            $this->RotatedText(75,180,'Q  R  C  R  E  D',45);
        }
    }
    function RotatedText($x, $y, $txt, $angle)
    {
        //Text rotated around its origin
        $this->Rotate($angle,$x,$y);
        $this->Text($x,$y,$txt);
        $this->Rotate(0);
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

$sql_emp = $pdo->query("SELECT * FROM sind.empregador WHERE id = ".$empregador);
$result = $sql_emp->fetch(PDO::FETCH_ASSOC);

$pagina=1;
PDF::setMS($mes_atual);
PDF::setRS($result['nome']);
PDF::setPG($pagina);
PDF::setTIPO($nome_tipo);
PDF::getDV($divisao);

$item_pagina  = 0;
$registros    = 0;
$total        = 0;
$compras      = 0;
$compras_tot  = 0;
$farmacia     = 0;
$farmacia_tot = 0;
$unimed       = 0;
$unimed_tot   = 0;
$total_assoc  = 0;

if (isset($_POST["empregador"]) and $_POST["mes_atual"] != "" and $tipo === null ) {
    $query = "SELECT codigo,nome,sum(valor) as valor,empregador,tipoconvenio
                FROM sind.qrelatoriofinal 
               WHERE empregador = " . $_POST["empregador"] . " 
                 AND mes = '" . $_POST["mes_atual"] . "'
                 AND codigo <> '".$card1."' 
                 AND codigo <> '".$card2."' 
                 AND codigo <> '".$card3."'
                 AND codigo <> '".$card4."' 
                 AND codigo <> '".$card5."'
                 AND codigo <> '".$card6."'
            GROUP BY codigo, nome, tipoconvenio,empregador
            ORDER BY nome";
}else{
    $query = "SELECT codigo,nome,sum(valor) as valor,empregador,tipoconvenio
                FROM sind.qrelatoriofinal 
               WHERE empregador = " . $_POST["empregador"] . " 
                 AND mes = '" . $_POST["mes_atual"] . "'
                 AND tipoconvenio = " . $tipo . "
                 AND codigo <> '".$card1."' 
                 AND codigo <> '".$card2."' 
                 AND codigo <> '".$card3."'
                 AND codigo <> '".$card4."' 
                 AND codigo <> '".$card5."'
                 AND codigo <> '".$card6."'
            GROUP BY codigo, nome, tipoconvenio,empregador
            ORDER BY nome";
}
PDF::setMS($mes_atual);
$convenio_aux="";
$aux = 0;
$total_paginas=0;
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage('P');
$pdf->SetFont('Arial','B',8);
$aux_matricula = "";
$aux_nome = "";
$sql_conv_vendas = $pdo->query($query);//$xxx = count($sql_conv_vendas->fetchAll()); //QUANTIDADE DE REGISTROS
while($row = $sql_conv_vendas->fetch()) {

    if ($registros == 0){
        $registros = 1;
        $aux_matricula = $row['codigo'];
        $aux_nome = $row['nome'];
        if ($row['tipoconvenio'] == 1) {
            $farmacia += $row['valor'];
            $farmacia_tot += $row['valor'];
        }
        if ($row['tipoconvenio'] == 2) {
            $compras += $row['valor'];
            $compras_tot += $row['valor'];
        }
        if ($row['tipoconvenio'] == 4) {
            $unimed += $row['valor'];
            $unimed_tot += $row['valor'];
        }
        $total_assoc = $farmacia + $compras + $unimed;
        $valor = floatval($row['valor']);
        $total += $valor;
        $valor = number_format($valor, 2, ',', '.');
    }else{
        if($aux_matricula === $row['codigo']) {
            if ($row['tipoconvenio'] == 1) {
                $farmacia += $row['valor'];
                $farmacia_tot += $row['valor'];
            }
            if ($row['tipoconvenio'] == 2) {
                $compras += $row['valor'];
                $compras_tot += $row['valor'];
            }
            if ($row['tipoconvenio'] == 4) {
                $unimed += $row['valor'];
                $unimed_tot += $row['valor'];
            }
            $total_assoc = $farmacia + $compras + $unimed;
            $valor = floatval($row['valor']);
            $total += $valor;
            $valor = number_format($valor, 2, ',', '.');
        }else{
            $registros++;
            $item_pagina++;

            $pdf->Cell(15, 4, $aux_matricula,0,0,'C');
            $pdf->Cell(93, 4, $aux_nome,0,0,'L');
            $pdf->Cell(21, 4, number_format($compras, 2, ',', '.'),0,0,'R');
            $pdf->Cell(21, 4, number_format($farmacia, 2, ',', '.'),0,0,'R');
            $pdf->Cell(21, 4, number_format($unimed, 2, ',', '.'),0,0,'R');
            $pdf->Cell(18, 4, number_format($total_assoc, 2, ',', '.'),0,0,'R');
            $pdf->Ln();

            $farmacia    = 0;
            $compras     = 0;
            $unimed      = 0;
            $total_assoc = 0;

            $aux_matricula = $row['codigo'];
            $aux_nome = $row['nome'];
            if ($row['tipoconvenio'] == 1) {
                $farmacia += $row['valor'];
                $farmacia_tot += $row['valor'];
            }
            if ($row['tipoconvenio'] == 2) {
                $compras += $row['valor'];
                $compras_tot += $row['valor'];
            }
            if ($row['tipoconvenio'] == 4) {
                $unimed += $row['valor'];
                $unimed_tot += $row['valor'];
            }
            $total_assoc = $farmacia + $compras + $unimed;
            $total += $row['valor'];

        }
    }
    if ($item_pagina  ==  61){
        $pagina = $pagina + 1;
        $item_pagina = 0;
        $pdf->Ln(4);
        PDF::setPG($pagina);
        $pdf->AddPage('P');
        $pdf->SetFont('Arial','B',8);
    }
}
$pdf->Cell(15, 4, $aux_matricula,0,0,'C');
$pdf->Cell(93, 4, $aux_nome,0,0,'L');
$pdf->Cell(21, 4, number_format($compras, 2, ',', '.'),0,0,'R');
$pdf->Cell(21, 4, number_format($farmacia, 2, ',', '.'),0,0,'R');
$pdf->Cell(21, 4, number_format($unimed, 2, ',', '.'),0,0,'R');
$pdf->Cell(18, 4, number_format($total_assoc, 2, ',', '.'),0,0,'R');
$pdf->Ln();

$farmacia    = 0;
$compras     = 0;
$unimed      = 0;
$total_assoc = 0;

PDF::setPG($pagina);
// SOMAS DA ULTIMA PAGINA **********************************************
$pdf->Cell(15, 14, "Registros: ".$registros, 0, 0, 'L');
$pdf->Cell(114, 10, number_format($compras_tot, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(21, 10, number_format($farmacia_tot, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(21, 10, number_format($unimed_tot, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(18, 10, number_format($total, "2", ",", "."), 0, 0, 'R');
$pdf->Ln(3);

$total        = 0;
$farmacia     = 0;
$farmacia_tot = 0;
$compras      = 0;
$compras_tot  = 0;
$unimed       = 0;
$unimed_tot   = 0;
$total_assoc  = 0;
$registros    = 0;

$pdf->Output('I',"relatorio_final-".$mes_atual."-QRCRED.pdf");