<?php
require_once '../../../functions.php';
include "../../php/banco.php";
include "../../php/funcoes.php";

// Verificar se a extensão mbstring está disponível
if (!extension_loaded('mbstring')) {
    die('Erro: A extensão mbstring do PHP é necessária para este relatório.');
}

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
require("../components/fpdf/fpdf.php");
$divisao = $_POST['divisao'];
$divisao_nome = $_POST['divisao_nome'];

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
    private static $DN;
    public static function setDN( $DIVISAON ) {
        self::$DN = $DIVISAON;
    }
// Page header
    function Header()
    {

        // Logo
        if(self::$DV == 1){//CASSERV
            $this->Image('../../../pictures_site-sind/logo_saspng.png',10,8,18);
        }
        // Arial bold 15
        $this->SetFont('Arial','B',12);

        $this->Cell(80);//move para direita 20 posiçoes
        if(self::$DV == 1){//CASSERV
            $this->Write(4,mb_convert_encoding('RELATÓRIO DE PROFISSIONAIS E ESPECIALIDADES', 'ISO-8859-1', 'UTF-8'));
        }

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(4,date('d/m/Y')." - ".date('H:i:s'));

        $this->Ln();//pula linha
        //$this->Cell(20);//move para direita 20 posiçoes
        //$this->Write(12,"Estabelecimento: ".mb_convert_encoding(self::$RS, 'ISO-8859-1', 'UTF-8'));// razao social

        $this->Ln();//pula linha
        //$this->Cell(20);
        //$this->Write(0,mb_convert_encoding("Mês: ", 'ISO-8859-1', 'UTF-8').self::$MS);

        $this->Ln(15);//pula linha
        $this->SetFont('Arial','B',8);

        $this->Cell(50,-6,mb_convert_encoding("CONVÊNIO", 'ISO-8859-1', 'UTF-8'),0,0,'L');

        $this->Cell(50,-6,"ESPECIALIDADE",0,0,'L');

        $this->Cell(50,-6,"PROFISSIONAL",0,0,'L');

        $this->Cell(35,-6,"TIPO ESPECIALIDADE",0,0,'L');

        $this->Cell(25,-6,"CONTATO 1",0,0,'L');

        $this->Cell(25,-6,"TELEFONE 1",0,0,'L');

        $this->Cell(25,-6,"CONTATO 2",0,0,'L');

        $this->Cell(25,-6,"TELEFONE 2",0,0,'L');

        

        // Line break
        $this->Ln(0);
        //linha horizontal
        $this->SetLineWidth(0.2);
        $this->Line("7","32","292","32");
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
        $this->Line("7","280","380","280");
    }
}

PDF::getDV($divisao);
PDF::setDN($divisao_nome);
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage('Landscape');

$pdf->SetFont('Arial','B',8);
$item  = 0;
$total = 0;

$sql_profissionais = $pdo->query("SELECT distinct
    c.razaosocial AS convenio_nome,
    e.nome_especialidade AS especialidade,
    p.nome_profissional AS profissional,
    COALESCE(te.nome_tipo, 'Não informado') AS tipo_estabelecimento,
    p.contato_nome1,
    p.cel_telefone1,
    p.contato_nome2,
    p.cel_telefone2
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
    c.razaosocial, e.nome_especialidade, p.nome_profissional;");

while($row_prof = $sql_profissionais->fetch()) {
    $item++;

    // Truncar textos longos para evitar quebra de linha
    $convenio_texto = $row_prof['convenio_nome'] ? mb_convert_encoding($row_prof['convenio_nome'], 'ISO-8859-1', 'UTF-8') : '';
    $especialidade_texto = $row_prof['especialidade'] ? mb_convert_encoding($row_prof['especialidade'], 'ISO-8859-1', 'UTF-8') : '';
    $profissional_texto = $row_prof['profissional'] ? mb_convert_encoding($row_prof['profissional'], 'ISO-8859-1', 'UTF-8') : '';
    
    $convenio = substr($convenio_texto, 0, 30);
    $especialidade = substr($especialidade_texto, 0, 30);
    $profissional = substr($profissional_texto, 0, 30);
    
    // Adicionar "..." se o texto foi truncado
    if (strlen($convenio_texto) > 30) {
        $convenio = substr($convenio, 0, 27) . '...';
    }
    if (strlen($especialidade_texto) > 30) {
        $especialidade = substr($especialidade, 0, 27) . '...';
    }
    if (strlen($profissional_texto) > 30) {
        $profissional = substr($profissional, 0, 27) . '...';
    }

    $pdf->Cell(50,4,$convenio);
    $pdf->Cell(50,4,$especialidade);
    $pdf->Cell(50,4,$profissional);
    $pdf->Cell(35,4,$row_prof['tipo_estabelecimento'] ? mb_convert_encoding($row_prof['tipo_estabelecimento'], 'ISO-8859-1', 'UTF-8') : '');
    $pdf->Cell(25,4,$row_prof['contato_nome1'] ? mb_convert_encoding($row_prof['contato_nome1'], 'ISO-8859-1', 'UTF-8') : '');
    $pdf->Cell(25,4,$row_prof['cel_telefone1'] ?: '');
    $pdf->Cell(25,4,$row_prof['contato_nome2'] ? mb_convert_encoding($row_prof['contato_nome2'], 'ISO-8859-1', 'UTF-8') : '');
    $pdf->Cell(25,4,$row_prof['cel_telefone2'] ?: '');

    $pdf->Ln();
}
$pdf->Ln();
$pdf->Cell(142, 11,"Registros : ".$item,0,0,'R');

$pdf->Output('I',"PROFISSIONAIS-ESPECIALIDADES-".$divisao_nome.".pdf");