<?php
include "../../php/funcoes.php";
include "../../php/banco.php";

$divisao        = isset($_POST['divisao'])        ? (int)$_POST['divisao']        : 0;
$empregador     = isset($_POST['empregador'])     ? (int)$_POST['empregador']     : 0;
$nome_divisao   = isset($_POST['nome_divisao'])   ? $_POST['nome_divisao']        : '';
$nome_empregador = isset($_POST['nome_empregador']) ? $_POST['nome_empregador']   : '';

if (!$divisao || !$empregador) {
    die('Parâmetros inválidos.');
}

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require("../components/fpdf/fpdf.php");

class PDF extends FPDF
{
    private static $empregador;
    private static $divisao;
    private static $data;

    public static function setEmpregador($v) { self::$empregador = $v; }
    public static function setDivisao($v)    { self::$divisao    = $v; }
    public static function setData($v)       { self::$data       = $v; }

    function Header()
    {
        $this->Image('../../../pictures_site-sind/logo_saspng.png', 15, 11, 15);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(22);
        $this->Write(0, mb_convert_encoding('Cartões e Senhas', 'ISO-8859-1', 'UTF-8'));
        $this->Ln(5);
        $this->Cell(22);
        $this->Write(0, mb_convert_encoding(self::$divisao, 'ISO-8859-1', 'UTF-8'));
        $this->Ln(5);
        $this->Cell(22);
        $this->Write(0, mb_convert_encoding('Empregador: ' . self::$empregador, 'ISO-8859-1', 'UTF-8'));
        $this->Ln(5);
        $this->SetFont('Arial', '', 8);
        $this->Cell(22);
        $this->Write(0, date('d/m/Y') . ' - ' . date('H:i:s'));
        $this->Ln(5);

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(70, 5, mb_convert_encoding('Nome',      'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $this->Cell(25, 5, mb_convert_encoding('Matrícula', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $this->Cell(35, 5, mb_convert_encoding('Nº Cartão', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $this->Cell(20, 5, 'Senha',                                                  0, 0, 'L');
        $this->Ln(5);
        $this->SetLineWidth(0.2);
        $this->Line(7, $this->GetY(), 201, $this->GetY());
        $this->Ln(2);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $this->SetLineWidth(0.2);
        $this->Line(7, 280, 201, 280);
    }
}

PDF::setEmpregador($nome_empregador);
PDF::setDivisao($nome_divisao);
PDF::setData(date('d/m/Y'));

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);

$sql = "SELECT a.nome, a.codigo, cc.cod_verificacao, cs.senha
        FROM sind.associado a
        INNER JOIN sind.c_cartaoassociado cc
               ON cc.cod_associado = a.codigo
              AND cc.empregador    = a.empregador
        LEFT JOIN sind.c_senhaassociado cs
               ON cs.cod_associado = a.codigo
              AND cs.id_empregador  = a.empregador
        WHERE a.empregador   = :empregador
          AND a.id_divisao   = :divisao
        ORDER BY a.nome";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
$stmt->bindParam(':divisao',    $divisao,    PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$item = 0;

foreach ($rows as $row) {
    $item++;
    $pdf->Cell(70, 5, mb_convert_encoding($row['nome'] ?? '', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
    $pdf->Cell(25, 5, $row['codigo'],          0, 0, 'L');
    $pdf->Cell(35, 5, $row['cod_verificacao'], 0, 0, 'L');
    $pdf->Cell(20, 5, $row['senha'] ?? '',           0, 0, 'L');
    $pdf->Ln();
}

$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(40, 6, mb_convert_encoding('Total de registros: ' . $item, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');

$pdf->Output('I', 'cartoes_senhas-' . date('d-m-Y') . '.pdf');
