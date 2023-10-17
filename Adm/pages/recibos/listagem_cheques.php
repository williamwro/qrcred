<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require "../../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$divisao = $_POST['divisao'];
$mes_atual = $_POST['mes_atual'];
$today = date("d-m-Y");
$nome_arquivo = 'cheques dos convenios '.$today.' '.$mes_atual.'.xlsx';
$ordem  = $_POST['ordem'];
// Redirect output to a client’s web browser (Xlsx)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename='.$nome_arquivo);
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header('Pragma: public'); // HTTP/1.0

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$writer = new Xlsx($spreadsheet);
$sheet = $spreadsheet->getActiveSheet();

//Define os cabeçalhos
$sheet->setCellValue('A1', "Código");
$sheet->setCellValue('B1', "Razão Social");
$sheet->setCellValue('C1', "Total Líquido");


$total = 0;
$prolabore = 0;
$valor_prolabore = 0;
$total_liquido = 0;
if (isset($_POST["categoria"])) {
    $query = "SELECT nome_convenio as descri,sum(valor) as total,prolabore,divisao,cod_convenio,id_categoria_recibo, categoria_recibo 
              FROM sind.qextrato 
              WHERE mes = '" . $mes_atual ."' 
              AND id_categoria_recibo = " . $_POST["categoria"] . " 
              AND divisao = ".$divisao." 
              AND cobranca = true 
              GROUP BY nome_convenio,divisao,prolabore,cod_convenio,id_categoria_recibo, categoria_recibo 
              ORDER BY nome_convenio";
} else {
    if($ordem == "todos"){
        $query = "SELECT nome_convenio as descri,sum(valor) as total,prolabore,divisao,cod_convenio,id_categoria_recibo, categoria_recibo 
                FROM sind.qextrato 
                WHERE mes = '" . $mes_atual ."' 
                AND divisao = ".$divisao."
                AND cobranca = true 
                GROUP BY nome_convenio,divisao,prolabore,cod_convenio,id_categoria_recibo, categoria_recibo  
                ORDER BY nome_convenio";
    }else{
        $query = "SELECT nome_convenio as descri,sum(valor) as total,prolabore,divisao,cod_convenio,id_categoria_recibo, categoria_recibo 
        FROM sind.qextrato 
        WHERE mes = '" . $mes_atual ."' 
        AND divisao = ".$divisao."
        AND cobranca = true 
        GROUP BY nome_convenio,divisao,prolabore,cod_convenio,id_categoria_recibo, categoria_recibo  
        ORDER BY categoria_recibo, nome_convenio";
    }
}
$someArray = array();
$statment  = $pdo->query($query);
$i = 0;
$soma_total = 0.0;
$horizontal = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT;
while($row = $statment->fetch()) {

    $i++;
    $total                            = $row["total"];
    $prolabore                        = $row["prolabore"];
    $valor_prolabore                  = ($total * $prolabore)/100;
    $total_liquido                    = $total - $valor_prolabore;
    $total_liquido2                   = number_format((float)$total_liquido,2, ',', '');
    $sheet->setCellValue('A'.($i + 2), $row["cod_convenio"]);
    $sheet->setCellValue('B'.($i + 2), $row["descri"]);
    $sheet->setCellValue('C'.($i + 2), $total_liquido2);

    $soma_total = $soma_total + $total_liquido;
    //$soma_total = tofloat($soma_total);

}
$sheet->setCellValue('B'.($i + 4), 'Total:');
$sheet->setCellValue('C'.($i + 4), number_format($soma_total,2, ',', ''));
$sheet->getStyle('C')->getAlignment()->setHorizontal($horizontal);
$sheet->getColumnDimension('C')->setAutoSize(true);
$sheet->getColumnDimension('B')->setAutoSize(true);

$writer = new Xlsx($spreadsheet);
$mes_atual = substr_replace($mes_atual,"-",3,1);
//O arquivo será salvo na mesma pasta desse script
//$writer->save('cheques dos convenios '.$today.' '.$mes_atual.'.xlsx');
ob_end_clean();
$writer->save('php://output');