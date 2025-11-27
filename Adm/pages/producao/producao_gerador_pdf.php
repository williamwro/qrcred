<?php
// Clear any output buffer and prevent output before PDF generation
ob_start();
ob_clean();

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
    
    private static $EMPRESA_NOME_FANTASIA = '';
    private static $EMPRESA_CNPJ = '';
    private static $EMPRESA_ENDERECO = '';
    private static $BANCO_NOME = '';
    private static $BANCO_AGENCIA = '';
    private static $BANCO_CONTA = '';
    private static $BANCO_TIPO_CONTA = '';
    private static $PIX_TIPO = '';
    private static $PIX_CHAVE = '';
    
    public static function setEmpresaInfo($nomeFantasia, $cnpj, $endereco, $bancoInfo) {
        self::$EMPRESA_NOME_FANTASIA = $nomeFantasia;
        self::$EMPRESA_CNPJ = $cnpj;
        self::$EMPRESA_ENDERECO = $endereco;
        
        // Set banking information if available
        if ($bancoInfo) {
            self::$BANCO_NOME = $bancoInfo['banco_nome'] ?? '';
            self::$BANCO_AGENCIA = $bancoInfo['banco_agencia'] ?? '';
            self::$BANCO_CONTA = $bancoInfo['banco_conta'] ?? '';
            self::$BANCO_TIPO_CONTA = $bancoInfo['banco_tipo_conta'] ?? '';
            self::$PIX_TIPO = $bancoInfo['pix_tipo'] ?? '';
            self::$PIX_CHAVE = $bancoInfo['pix_chave'] ?? '';
        }
    }
// Page header
    function Header()
    {
        // Logo
       
        $this->Image('../../../pictures_site-sind/logo_saspng.png',10,8,18); 
       
        // Arial bold 15
        $this->SetFont('Arial','B',12);

        $this->Cell(22);//move para direita 20 posiçoes
        
        $this->Write(0,mb_convert_encoding('Relatório de produção do convenio', 'ISO-8859-1', 'UTF-8'));
       
        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,date('d/m/Y')." - ".date('H:i:s'));

        $this->Ln(6);//pula linha
        
        // Razão Social
        $this->SetFont('Arial','B',10);
        $this->Cell(22);
        $this->Write(0, mb_convert_encoding('Razão Social: ', 'ISO-8859-1', 'UTF-8') . mb_convert_encoding(self::$RS, 'ISO-8859-1', 'UTF-8'));
        
        $this->Ln(5);
        
        // Nome Fantasia
        if (!empty(self::$EMPRESA_NOME_FANTASIA)) {
            $this->Cell(22);
            $this->Write(0, mb_convert_encoding('Nome Fantasia: ', 'ISO-8859-1', 'UTF-8') . mb_convert_encoding(self::$EMPRESA_NOME_FANTASIA, 'ISO-8859-1', 'UTF-8'));
            $this->Ln(5);
        }
        
        // CNPJ
        if (!empty(self::$EMPRESA_CNPJ)) {
            $this->Cell(22);
            $this->Write(0, mb_convert_encoding('CNPJ: ', 'ISO-8859-1', 'UTF-8') . mb_convert_encoding(self::$EMPRESA_CNPJ, 'ISO-8859-1', 'UTF-8'));
            $this->Ln(5);
        }
        
        // Endereço
        if (!empty(self::$EMPRESA_ENDERECO)) {
            $this->Cell(22);
            $this->Write(0, mb_convert_encoding('Endereço: ', 'ISO-8859-1', 'UTF-8') . mb_convert_encoding(self::$EMPRESA_ENDERECO, 'ISO-8859-1', 'UTF-8'));
            $this->Ln(5);
        }
        $this->Cell(22);
        $this->Write(0,mb_convert_encoding("Mês: ", 'ISO-8859-1', 'UTF-8').self::$MS);

        $this->Cell(102);
        $this->Write(0,"Pagina: ".self::$PG);
        
        // Linha horizontal após Mês e Página
        $this->Ln(7);
        $this->SetLineWidth(0.2);
        $this->Line("11","39","200","39");
        
        // Banking Information Section - Exibe cada campo individualmente se estiver preenchido
        $hasBankingInfo = !empty(self::$BANCO_NOME) || !empty(self::$BANCO_AGENCIA) || 
                         !empty(self::$BANCO_CONTA) || !empty(self::$BANCO_TIPO_CONTA) ||
                         !empty(self::$PIX_TIPO) || !empty(self::$PIX_CHAVE);
        
        if ($hasBankingInfo) {
            //$this->Ln(1); // Espaço antes dos dados bancários
            $this->SetFont('Arial','B',9);
            $this->Cell(22);
            $this->Cell(0, 3, mb_convert_encoding('DADOS BANCÁRIOS', 'ISO-8859-1', 'UTF-8'), 0, 1);
            $this->Ln(3); // Espaço maior após o título
            $this->SetFont('Arial','',8);
            
            // Banco
            if (!empty(self::$BANCO_NOME)) {
                $this->Cell(22);
                $this->Write(0, mb_convert_encoding('Banco: ' . self::$BANCO_NOME, 'ISO-8859-1', 'UTF-8'));
                $this->Ln(4);
            }
            
            // Agência
            if (!empty(self::$BANCO_AGENCIA)) {
                $this->Cell(22);
                $this->Write(0, mb_convert_encoding('Agência: ' . self::$BANCO_AGENCIA, 'ISO-8859-1', 'UTF-8'));
                $this->Ln(4);
            }
            
            // Conta
            if (!empty(self::$BANCO_CONTA)) {
                $this->Cell(22);
                $this->Write(0, mb_convert_encoding('Conta: ' . self::$BANCO_CONTA, 'ISO-8859-1', 'UTF-8'));
                $this->Ln(4);
            }
            
            // Tipo de Conta
            if (!empty(self::$BANCO_TIPO_CONTA)) {
                $this->Cell(22);
                $this->Write(0, mb_convert_encoding('Tipo Conta: ' . self::$BANCO_TIPO_CONTA, 'ISO-8859-1', 'UTF-8'));
                $this->Ln(4);
            }
            
            // Tipo PIX
            if (!empty(self::$PIX_TIPO)) {
                $this->Cell(22);
                $this->Write(0, mb_convert_encoding('Tipo PIX: ' . self::$PIX_TIPO, 'ISO-8859-1', 'UTF-8'));
                $this->Ln(4);
            }
            
            // Chave PIX
            error_log("=== DEBUG HEADER PIX ===");
            error_log("PIX_CHAVE value: [" . self::$PIX_CHAVE . "]");
            error_log("PIX_CHAVE length: " . strlen(self::$PIX_CHAVE));
            error_log("PIX_CHAVE empty?: " . (empty(self::$PIX_CHAVE) ? 'SIM' : 'NAO'));
            
            if (!empty(self::$PIX_CHAVE)) {
                error_log("ENTRANDO NO IF - VAI EXIBIR CHAVE PIX");
                $this->Cell(22);
                $this->Write(0, mb_convert_encoding('Chave PIX: ' . self::$PIX_CHAVE, 'ISO-8859-1', 'UTF-8'));
                $this->Ln(4);
            } else {
                error_log("NAO ENTROU NO IF - PIX_CHAVE VAZIO");
            }
            
            // Linha separadora
            $this->Cell(0, 3, '', 'T');
            $this->Ln(3);
        }

        
        $this->Ln(4);//pula linha
        $this->SetFont('Arial','B',8);

        $this->Cell(15,-6,"Registro",0,0,'L');

        $this->Cell(20,-6,"Matricula",0,0,'L');

        $this->Cell(90,-6,"nome",0,0,'L');

        $this->Cell(26,-6,"data",0,0,'L');

        $this->Cell(17,-6,"Hora",0,0,'L');

        $this->Cell(10,-6,"valor",0,0,'R');

        $this->Cell(23,-6,"Parcela",0,0,'C');

        // Line break
        $this->Ln(0);
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
// Fetch company information from the database
$sql_empresa = "SELECT 
    c.razaosocial as nome_fantasia,
    c.nomefantasia,
    c.cnpj,
    c.insc as inscricao_estadual,
    c.endereco,
    c.numero,
    c.bairro,
    c.cidade,
    c.uf,
    c.cep,
    c.telefone,
    c.email,
    b.agencia as banco_agencia,
    b.conta as banco_conta,
    CASE 
        WHEN b.cod_tipo = 1 THEN 'Conta Corrente'
        WHEN b.cod_tipo = 2 THEN 'Conta Poupança'
        ELSE ''
    END as banco_tipo_conta,
    bn.banco as banco_nome,
    cp.nome_chave as pix_tipo,
    b.chave_pix
FROM sind.convenio c
LEFT JOIN sind.banco_convenio b ON c.codigo = b.cod_convenio
LEFT JOIN sind.bancos bn ON b.cod_banco::bigint = bn.id
LEFT JOIN sind.chaves_pix cp ON b.id_chave_pix = cp.id_chave_pix
WHERE c.codigo = :codigo_convenio";

// Get codigo_convenio from POST or use a default value
$codigo_convenio = isset($_POST['cod_convenio']) ? $_POST['cod_convenio'] : 1;

$stmt_empresa = $pdo->prepare($sql_empresa);
$stmt_empresa->execute([':codigo_convenio' => $codigo_convenio]);
$empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC);

// Build full address
$endereco_completo = '';
if (!empty($empresa['endereco'])) {
    $endereco_completo = $empresa['endereco'];
    if (!empty($empresa['numero'])) $endereco_completo .= ', ' . $empresa['numero'];
    if (!empty($empresa['bairro'])) $endereco_completo .= ' - ' . $empresa['bairro'];
    if (!empty($empresa['cidade'])) $endereco_completo .= ' - ' . $empresa['cidade'];
    if (!empty($empresa['uf'])) $endereco_completo .= '/' . $empresa['uf'];
    if (!empty($empresa['cep'])) $endereco_completo .= ' - CEP: ' . $empresa['cep'];
}

// Prepare banking info - Aplicar trim para remover espaços extras
$bancoInfo = [];

// Debug detalhado da chave PIX
error_log("=== DEBUG CHAVE PIX ===");
error_log("chave_pix BRUTO: [" . $empresa['chave_pix'] . "]");
error_log("chave_pix length: " . strlen($empresa['chave_pix']));
error_log("chave_pix TRIM: [" . trim($empresa['chave_pix']) . "]");
error_log("chave_pix TRIM length: " . strlen(trim($empresa['chave_pix'])));
error_log("empty check: " . (empty(trim($empresa['chave_pix'])) ? 'VAZIO' : 'TEM VALOR'));

if (!empty(trim($empresa['banco_nome']))) $bancoInfo['banco_nome'] = trim($empresa['banco_nome']);
if (!empty(trim($empresa['banco_agencia']))) $bancoInfo['banco_agencia'] = trim($empresa['banco_agencia']);
if (!empty(trim($empresa['banco_conta']))) $bancoInfo['banco_conta'] = trim($empresa['banco_conta']);
if (!empty(trim($empresa['banco_tipo_conta']))) $bancoInfo['banco_tipo_conta'] = trim($empresa['banco_tipo_conta']);
if (!empty(trim($empresa['pix_tipo']))) $bancoInfo['pix_tipo'] = trim($empresa['pix_tipo']);
if (!empty(trim($empresa['chave_pix']))) $bancoInfo['pix_chave'] = trim($empresa['chave_pix']);

error_log("bancoInfo final: " . print_r($bancoInfo, true));

// Debug: verificar se os dados foram recuperados (desativado)
// error_log("Dados bancários recuperados: " . print_r($bancoInfo, true));
// error_log("Empresa completa: " . print_r($empresa, true));
// error_log("Codigo convenio usado: " . $codigo_convenio);

// Set PDF properties
PDF::setMS($mes_atual);
$pagina = 1;
PDF::setPG($pagina);
PDF::getDV($divisao);
PDF::setDN($divisao_nome);

// Set company information to PDF
PDF::setEmpresaInfo(
    $empresa['nome_fantasia'] ?? '',
    $empresa['cnpj'] ?? '',
    $endereco_completo,
    $bancoInfo
);

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
                      AND empregador.id =" . $_POST["empregador"] . " AND empregador.divisao = " . $divisao . " ORDER BY convenio.razaosocial, " . $ordem . ";";
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
            WHERE convenio.codigo = " . $_POST["cod_convenio"] . " AND conta.mes = '" . $_POST["mes_atual"] . "' AND empregador.divisao = " . $divisao . " 
         
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
        WHERE empregador.id =" . $_POST["empregador"] . " AND conta.mes = '" . $_POST["mes_atual"] . "' AND empregador.divisao = ".$divisao."  
        
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
        WHERE conta.mes = '" . $_POST["mes_atual"] . "' AND empregador.divisao = ".$divisao."
         
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
                             AND empregador.divisao = ".$divisao.")
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
                                     AND convenio.codigo = " . $cod_convenio . ")
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
    $pdf->Cell(20, 4, $row['matricula']);
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
// Clear any remaining output buffer before PDF output
if (ob_get_length()) {
    ob_end_clean();
}

// Ensure no whitespace or other output
if($todos === 0){
    $pdf->Output('I',$convenio_aux."-".$mes_atual."-".$divisao_nome.".pdf");
}else{
    $pdf->Output('I',"TODOS_CONVENIOS-".$mes_atual."-".$divisao_nome.".pdf");
}