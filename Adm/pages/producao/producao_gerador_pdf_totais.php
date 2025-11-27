<?php
// Clear any output buffer and prevent output before PDF generation
ob_start();
ob_clean();

date_default_timezone_set('America/Araguaina');
ini_set('max_execution_time', 360);
include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$mes_atual    = $_POST['mes_atual'];
if (isset($_POST['cod_tipo'])){
    $cod_tipo = $_POST['cod_tipo'];
}else{
    $cod_tipo = 0;
}
if (isset($_POST['subtipo'])){
    $subtipo = $_POST['subtipo'];
}else{
    $subtipo = "";
}
if(isset($_POST['empregador']) && $_POST['empregador'] != '') {
    $empregador_id = $_POST['empregador'];
    $sql_empregador = $pdo->query("SELECT nome FROM sind.empregador WHERE id = ".$empregador_id);
    $empregador_nome = "";
    while($row = $sql_empregador->fetch()) {
        $empregador_nome = $row["nome"];
    }
}else{
    $empregador_id = 0;
    $empregador_nome = "";
}
$divisao = $_POST['divisao'];
$divisao_nome = $_POST['divisao_nome'];
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
    private static $DN;
    public static function setDN( $DIVISAON ) {
        self::$DN = $DIVISAON;
    }
    private static $DV;
    public static function getDV( $DIVISAOX ) {
        return self::$DV = $DIVISAOX;
    }
    function Header()
    {
        // Logo
        $this->Image('../../../pictures_site-sind/logo_saspng.png',10,8,18);
        // Arial bold 15
        $this->SetFont('Arial','B',12   );

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,mb_convert_encoding('Relatório de somas', 'ISO-8859-1', 'UTF-8'));

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,date('d/m/Y')." - ".date('H:i:s'));

        $this->Ln();//pula linha
        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(12,"Empregador: ".mb_convert_encoding(self::$RS ? self::$RS : '', 'ISO-8859-1', 'UTF-8'));// razao social

        $this->Cell(10);
        $this->Write(12,mb_convert_encoding("Mês: ", 'ISO-8859-1', 'UTF-8').self::$MS);

        $this->Cell(22);
        $this->Write(12,"Pagina: ".self::$PG);

        $this->Ln(18);//pula linha
        $this->SetFont('Arial','B',8);

        $this->Cell(105,3,mb_convert_encoding('Descrição', 'ISO-8859-1', 'UTF-8'),0,0,'L');

        $this->Cell(15,3,"Valor",0,0,'L');

        // Line break
        $this->Ln(0);
        //linha horizontal
        $this->SetLineWidth(0.2);
        $this->Line("11","32","205","32");
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
        $this->Cell(0,10,self::$DN,0,0,'C');
        $this->SetLineWidth(0.2);
        $this->Line("7","280","201","280");
    }
}
PDF::setMS($mes_atual);
$pagina=1;
PDF::setPG($pagina);
PDF::getDV($divisao);
PDF::setDN($divisao_nome);

$item   = 0;
$item_pagina = 0;
$total  = 0;


if ($cod_tipo != 0 and $empregador_id != 0 and $empregador_id != "" ) {
    PDF::setRS($empregador_nome);
    $query = "Select nome_convenio as descri, sum(valor) as total From sind.qextrato Where mes = '" . $mes_atual ."' and id_empregador = " . $empregador_id . " and cod_tipo_convenio = " . $cod_tipo . " and divisao = ".$divisao." and cobranca = true Group by nome_convenio, divisao, cod_convenio order by nome_convenio";

}else if ($cod_tipo != 0 and ($empregador_id == 0 or $empregador_id == "") ) {
    PDF::setRS("TODOS");
    if ($subtipo == "" || $subtipo == "EMPREGADOR") {
        $query = "Select nome_empregador as descri, sum(valor) as total From sind.qextrato Where mes = '" . $mes_atual . "' and cod_tipo_convenio = " . $cod_tipo . " and divisao = ".$divisao." and cobranca = true Group by nome_empregador, divisao, cod_convenio order by nome_empregador";
    }else{
        $query = "Select nome_convenio as descri, sum(valor) as total From sind.qextrato Where mes = '" . $mes_atual . "' and cod_tipo_convenio = " . $cod_tipo . " and divisao = ".$divisao." and cobranca = true Group by nome_convenio, divisao, cod_convenio order by nome_convenio";
    }

}else if ($cod_tipo == 0 and $empregador_id != 0 and $empregador_id != "" ) {
    PDF::setRS($empregador_nome);
    $query = "Select nome_convenio as descri, sum(valor) as total From sind.qextrato Where mes = '" . $mes_atual ."' and id_empregador = " . $empregador_id. " and divisao = ".$divisao." and cobranca = true Group by nome_convenio, divisao, cod_convenio order by nome_convenio";
}else if ($cod_tipo == 0 and ($empregador_id == 0 or $empregador_id == "") and $subtipo == "CONVENIO") {
    PDF::setRS("CONVENIOS");
    $query = "Select nome_convenio as descri, sum(valor) as total From sind.qextrato Where mes = '" . $mes_atual . "' and divisao = ".$divisao." and cobranca = true Group by nome_convenio, divisao, cod_convenio order by nome_convenio";
}else if ($cod_tipo == 0 and ($empregador_id == 0 or $empregador_id == "") and $subtipo == "EMPREGADOR") {
    PDF::setRS("EMPREGADORES");
    $query = "Select nome_empregador as descri, sum(valor) as total From sind.qextrato Where mes = '" . $mes_atual . "' and divisao = ".$divisao." and cobranca = true Group by nome_empregador, divisao, cod_convenio order by nome_empregador";
}else {
    // Default case - all records
    PDF::setRS("TODOS");
    $query = "Select nome_convenio as descri, sum(valor) as total From sind.qextrato Where mes = '" . $mes_atual . "' and divisao = ".$divisao." and cobranca = true Group by nome_convenio, divisao, cod_convenio order by nome_convenio";
}
PDF::setMS($mes_atual);
$convenio_aux="";
$aux = 0;
$total_paginas=0;
PDF::setPG($pagina);

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','B',8);
$pdf->Ln(5);
    $sql_conv_vendas = $pdo->query($query);
    //$xxx = count($sql_conv_vendas->fetchAll());
    while($row = $sql_conv_vendas->fetch()) {

        $item++;
        $item_pagina++;

        if ($item_pagina  ==  61){
            $pagina = $pagina + 1;
            $item_pagina = 0;
            $pdf->AliasNbPages();
            $pdf->AddPage();
            $pdf->Ln(5);
        }
        PDF::setPG($pagina);
        $Valor = floatval($row['total']);
        $total = $total + $Valor;
        $Valor = number_format($Valor, 2, ',', '.');

        $pdf->Cell(100, 4, $row['descri']);
        $pdf->Cell(15, 4, number_format($row['total'], 2, ',', '.'), '', '', 'R');

        $pdf->Ln();


}

PDF::setPG($pagina);

$pdf->Ln(1);
$pdf->Cell(50);
$pdf->Cell(40, 20, "Total : ", 0, 0, 'R');
$pdf->Cell(25, 20, number_format($total, "2", ",", "."), 0, 0, 'R');
$total = 0;

$item=0;

// Clear any remaining output buffer before PDF output
if (ob_get_length()) {
    ob_end_clean();
}

$pdf->Output('I','Totais'."-".$mes_atual."-".PDF::getDV($divisao).".pdf");
