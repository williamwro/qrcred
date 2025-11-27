<?php
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('America/Araguaina');

// Verificar se a extensão mbstring está disponível (necessária para mb_convert_encoding)
if (!extension_loaded('mbstring')) {
    die('Erro: A extensão mbstring do PHP é necessária para este relatório.');
}
include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$mes_atual = $_POST['mes_atual'];
$empregador = $_POST['empregador'];
$divisao    = $_POST['divisao'];
$divisao_nome = $_POST['divisao_nome'];

if (isset($_POST['tipo']) && !empty($_POST['tipo'])) {
    $tipo = $_POST['tipo'];
    $sql = $pdo->prepare("SELECT nome FROM sind.tipoconvenio WHERE codigo = :tipo");
    $sql->bindParam(':tipo', $tipo, PDO::PARAM_INT);
    $sql->execute();
    $row = $sql->fetch();
    if ($row) {
        $nome_tipo = $row['nome'];
    } else {
        $nome_tipo = "Todos";
    }
} else {
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
    private static $DN;
    public static function setDN( $DIVISAON ) {
        self::$DN = $DIVISAON;
    }
    public static function getDN( $DIVISAON ) {
        return self::$DN = $DIVISAON;
    }
    // Page header
    function Header()
    {

        // Logo
        $this->Image('../../../pictures_site-sind/logo_saspng.png',10,8,18);
        // Arial bold 15
        $this->SetFont('Arial','B',12   );

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,mb_convert_encoding('Relatório final para desconto em folha', 'ISO-8859-1', 'UTF-8'));

        $this->Cell(38);//move para direita 20 posiçoes
        $this->Write(0,date('d/m/Y')." - ".date('H:i:s'));

        $this->Ln();//pula linha
        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(12,"Empregador: ".mb_convert_encoding(self::$RS, 'ISO-8859-1', 'UTF-8'));// razao social

        $this->Ln();//pula linha
        $this->Cell(22);
        $this->Write(0,mb_convert_encoding("Mês: ", 'ISO-8859-1', 'UTF-8').self::$MS);

        $this->Ln();//pula linha
        $this->Cell(100);
        $this->Write(0,mb_convert_encoding("Tipo: ", 'ISO-8859-1', 'UTF-8').self::$TP);

        $this->Cell(35);
        $this->Write(0,"Pagina: ".self::$PG);

        $this->Ln(12);//pula linha - espaço maior para o cabeçalho
        //cabaçalho
        $this->SetFont('Arial','B',8);

        $this->Cell(21,-6,"CPF",0,0,'L');

        $this->Cell(70,-6,"Nome",0,0,'L');

        $this->Cell(25,-6,"Adiantamento",0,0,'R');

        $this->Cell(25,-6,mb_convert_encoding("Cartão", 'ISO-8859-1', 'UTF-8'),0,0,'R');

        $this->Cell(25,-6,mb_convert_encoding("Taxa de adm /", 'ISO-8859-1', 'UTF-8'),0,0,'R');
        $this->Cell(18,-6,"Total",0,0,'R');
        $this->Ln(4);
        $this->Cell(140);
        
        $this->Cell(25,-6,mb_convert_encoding("manutenção", 'ISO-8859-1', 'UTF-8'),0,0,'R');

        

        // Line break
        $this->Ln(0);
        //linha horizontal
        $this->SetLineWidth(0.2);
        $this->Line("11","37","205","37");
        //Insere marca dagua
        $this->SetFont('Arial','B',50);
        $this->SetTextColor(255,192,203);
       
        $this->RotatedText(55,200,'S  A  S  C  R  E  D',45);
       
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
        $this->Cell(0,10,self::$DN,0,0,'C');
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
PDF::setDN($divisao_nome);

$item_pagina  = 0;
$registros    = 0;
$total        = 0;
$emprestimo      = 0;
$emprestimo_tot  = 0;
$cartao     = 0;
$cartao_tot = 0;
$taxacartao     = 0;
$taxacartao_tot = 0;
$unimed       = 0;
$unimed_tot   = 0;
$total_assoc  = 0;

if (isset($_POST["empregador"]) and $_POST["mes_atual"] != "" and $tipo === null ) {
    $query = "SELECT codigo,nome,sum(valor) as valor,empregador,tipoconvenio,cpf
                FROM sind.qrelatoriofinal 
               WHERE empregador = " . $_POST["empregador"] . " 
                 AND mes = '" . $_POST["mes_atual"] . "'
                 AND (aprovado = true OR aprovado IS NULL)
            GROUP BY codigo, nome, tipoconvenio,empregador,cpf
            ORDER BY nome";
}else{
    $query = "SELECT codigo,nome,sum(valor) as valor,empregador,tipoconvenio,cpf
                FROM sind.qrelatoriofinal 
               WHERE empregador = " . $_POST["empregador"] . " 
                 AND mes = '" . $_POST["mes_atual"] . "'
                 AND tipoconvenio = " . $tipo . "
                 AND (aprovado = true OR aprovado IS NULL)
            GROUP BY codigo, nome, tipoconvenio,empregador,cpf
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
$aux_cpf = "";
$aux_nome = "";
$sql_conv_vendas = $pdo->query($query);//$xxx = count($sql_conv_vendas->fetchAll()); //QUANTIDADE DE REGISTROS
while($row = $sql_conv_vendas->fetch()) {

    if ($registros == 0){
        $registros = 1;
        $aux_cpf = $row['cpf'];
        $aux_nome = $row['nome'];
        if ($row['tipoconvenio'] == 1) {
            $cartao += $row['valor'];
            $cartao_tot += $row['valor'];
        }
        if ($row['tipoconvenio'] == 2) {
            $emprestimo += $row['valor'];
            $emprestimo_tot += $row['valor'];
        }
        if ($row['tipoconvenio'] == 3) {
            $taxacartao += $row['valor'];
            $taxacartao_tot += $row['valor'];
        }
        $total_assoc = $cartao + $emprestimo + $taxacartao;
        $valor = floatval($row['valor']);
        $total += $valor;
        $valor = number_format($valor, 2, ',', '.');
    }else{
        if($aux_cpf === $row['cpf']) {
            if ($row['tipoconvenio'] == 1) {
                $cartao += $row['valor'];
                $cartao_tot += $row['valor'];
            }
            if ($row['tipoconvenio'] == 2) {
                $emprestimo += $row['valor'];
                $emprestimo_tot += $row['valor'];
            }
            if ($row['tipoconvenio'] == 3) {
                $taxacartao += $row['valor'];
                $taxacartao_tot += $row['valor'];
            }
            $total_assoc = $cartao + $emprestimo + $taxacartao;
            $valor = floatval($row['valor']);
            $total += $valor;
            $valor = number_format($valor, 2, ',', '.');
        }else{
            $registros++;
            $item_pagina++;

            $pdf->Cell(21, 4, $aux_cpf,0,0,'C');
            $pdf->Cell(70, 4, mb_convert_encoding($aux_nome, 'ISO-8859-1', 'UTF-8'),0,0,'L');
            $pdf->Cell(25, 4, number_format($emprestimo, 2, ',', '.'),0,0,'R');
            $pdf->Cell(25, 4, number_format($cartao, 2, ',', '.'),0,0,'R');
            $pdf->Cell(25, 4, number_format($taxacartao, 2, ',', '.'),0,0,'R');
            $pdf->Cell(18, 4, number_format($total_assoc, 2, ',', '.'),0,0,'R');
            $pdf->Ln();

            $cartao    = 0;
            $emprestimo     = 0;
            $taxacartao    = 0;
            $total_assoc = 0;

            $aux_cpf = $row['cpf'];
            $aux_nome = $row['nome'];
            if ($row['tipoconvenio'] == 1) {
                $cartao += $row['valor'];
                $cartao_tot += $row['valor'];
            }
            if ($row['tipoconvenio'] == 2) {
                $emprestimo += $row['valor'];
                $emprestimo_tot += $row['valor'];
            }
            if ($row['tipoconvenio'] == 3) {
                $taxacartao += $row['valor'];
                $taxacartao_tot += $row['valor'];
            }
            $total_assoc = $cartao + $emprestimo + $taxacartao;
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
$pdf->Cell(21, 4, $aux_cpf,0,0,'C');
$pdf->Cell(70, 4, mb_convert_encoding($aux_nome, 'ISO-8859-1', 'UTF-8'),0,0,'L');
$pdf->Cell(25, 4, number_format($emprestimo, 2, ',', '.'),0,0,'R');
$pdf->Cell(25, 4, number_format($cartao, 2, ',', '.'),0,0,'R');
$pdf->Cell(25, 4, number_format($taxacartao, 2, ',', '.'),0,0,'R');
$pdf->Cell(18, 4, number_format($total_assoc, 2, ',', '.'),0,0,'R');
$pdf->Ln();

$cartao      = 0;
$emprestimo  = 0;
$taxacartao  = 0;
$total_assoc = 0;

PDF::setPG($pagina);
// SOMAS DA ULTIMA PAGINA **********************************************
$pdf->Cell(15, 14, "Registros: ".$registros, 0, 0, 'L');
$pdf->Cell(102, 10, number_format($emprestimo_tot, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(25, 10, number_format($cartao_tot, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(25, 10, number_format($taxacartao_tot, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(18, 10, number_format($total, "2", ",", "."), 0, 0, 'R');
$pdf->Ln(3);

$total        = 0;
$cartao     = 0;
$cartao_tot = 0;
$taxacartao     = 0;
$taxacartao_tot = 0;
$emprestimo      = 0;
$emprestimo_tot  = 0;
$total_assoc  = 0;
$registros    = 0;

$pdf->Output('I',"relatorio_final-".$mes_atual."-".$divisao_nome.".pdf");