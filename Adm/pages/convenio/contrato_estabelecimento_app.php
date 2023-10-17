<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<HEAD>
	<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=utf-8">
	<TITLE>REGULAMENTO PARA UTILIZAÇÃO DO CONVENIO SINDSERVA</TITLE>
	<META NAME="GENERATOR" CONTENT="LibreOffice 4.1.6.2 (Linux)">
	<META NAME="AUTHOR" CONTENT="w">
	<META NAME="CREATED" CONTENT="20190206;230700000000000">
	<META NAME="CHANGEDBY" CONTENT="Usuário do Windows">
	<META NAME="CHANGED" CONTENT="20200929;183000000000000">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/paper-css/0.3.0/paper.css" rel="stylesheet" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/0.9.0rc1/jspdf.min.js"></script>
	<STYLE TYPE="text/css">
	<!--
		@page { margin-right: 0.69in; margin-top: 0.49in; margin-bottom: 0.49in; size: A4;  }

		A:link { color: #0000ff }
		.container {
			display: flex;
			flex-direction: row;
			justify-content: center;
			align-items: center;
	    }
	    body {
	        margin: 0px;
        }
	</STYLE>
</HEAD>
<?PHP
    if($_GET['cnpj'] ==! ""){
        $cnpjorcpf      = $_GET['cnpj'];
        $cnpjorcpftexto = "pessoa jurídica de direito privado inscrita no CNPJ sob o nº ";
    }else{
        $cnpjorcpf      = $_GET['cpf'];
        $cnpjorcpftexto = "pessoa fisica inscrita no CPF sob o nº ";
    }
?>
<BODY onload="carrega()" id="conteudo" LANG="en-US" TEXT="#000000" LINK="#0000ff" DIR="LTR" class="A4">
<div class="container">
    <div class="box">
		<section class="sheet padding-10mm">
		<DIV TYPE=HEADER>
			<P LANG="pt-BR" ALIGN=CENTER STYLE="text-indent: 0.98in; margin-bottom: 0in">
			 <IMG SRC="bb0ff3070d9b8b4dcde8e428ab15a247_html_d9c0254a.png" NAME="graphics1" ALIGN=LEFT HSPACE=12 WIDTH=70 HEIGHT=67 BORDER=0>
				 <FONT FACE="Century Gothic, sans-serif"><FONT SIZE=2>Sindicato
			dos Funcionários da Prefeitura Municipal de Varginha-MG, </FONT></FONT>
			</P>
			<P LANG="pt-BR" ALIGN=CENTER STYLE="text-indent: 0.98in; margin-bottom: 0in;margin-top: 0in;">
			<FONT FACE="Century Gothic, sans-serif"><FONT SIZE=2>das Autarquias
			e das Fundações Municipais</FONT></FONT></P>
			<P LANG="pt-BR" ALIGN=CENTER STYLE="text-indent: 0.98in; margin-bottom: 0in;margin-top: 0in;">
			<FONT FACE="Century Gothic, sans-serif"><FONT SIZE=2>CNPJ :
			17.680.975/0001-00</FONT></FONT></P>
			<P LANG="pt-BR" ALIGN=CENTER STYLE="text-indent: 1.00in; margin-bottom: 1.30in;margin-top: 0in;">
			<FONT FACE="Century Gothic, sans-serif"><FONT SIZE=2>Rua Argentina,
			245 – Vila Pinto Cep: 37.010-640 Varginha-MG</FONT></FONT></P>
		</DIV>
		<P LANG="pt-BR" CLASS="western" ALIGN=CENTER STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><FONT SIZE=4 STYLE="font-size: 16pt"><U>CONTRATO
		DE CREDENCIAMENTO AO CONVÊNIO SINDSERVA</U></FONT></FONT></P>

		<P LANG="pt-BR" CLASS="western" ALIGN=CENTER STYLE="margin-bottom: 0in">
		<BR>
		</P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<font FACE="Century Gothic, sans-serif"><B>Sindicato dos Funcionários
		da Prefeitura Municipal de Varginha-MG</B>, das autarquias e das
		Fundações Municipais, inscrita no CNPJ/MF sob o nº
		17.680.975/0001-00, com sede administrativa na cidade de Varginha/MG
		na Rua Argentina, 245 Vila Pinto, doravante denominada <B>CONVENIO
    	SINDSERVA</B> e <B><?PHP echo $_GET['razaosocial']; ?></B>, <?PHP echo $cnpjorcpftexto; ?>, <?PHP echo $cnpjorcpf; ?>
		, com sede administrativa na cidade de
        <?PHP echo $_GET['cidade']; ?> - <?PHP echo $_GET['estado']; ?>, na <?PHP echo $_GET['endereco']; ?>, <?PHP echo $_GET['bairro']; ?> -
        <?PHP echo $_GET['numero']; ?>, doravante denominada <B>CONVENIADO</B></font></P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<BR>
		</P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>OBJETO DO CONTRATO</B></FONT><FONT FACE="Century Gothic, sans-serif">.
		O presente contrato tem por objeto o credenciamento do
		ESTABELECIMENTO ao SISTEMA de cartão CONVENIO SINDSERVA, através do
		recebimento, aceitação e/ou utilização pelo ESTABELECIMENTO, do
		cartão SINDSERVA e APLICATIVO denominados CARTÃO DO CONVENIO
		SINDSERVA que venham a ser utilizados por trabalhadores associados ao
		CONVENIO SINDSERVA.</FONT></P>

		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>1º</B></FONT><FONT FACE="Century Gothic, sans-serif">
		O CONVENIADO está autorizado a fornecer produtos e prestação de
		serviços aos titulares do cartão, CONVENIO SINDSERVA; </FONT>
		</P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>2º</B></FONT><FONT FACE="Century Gothic, sans-serif">
		Os valores lançados no sistema web ou aplicativo serão descontados
		na folha de pagamento do titular do cartão;</FONT></P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>3º</B></FONT><FONT FACE="Century Gothic, sans-serif">
		O CONVENIADO deverá exigir assinatura do portador do cartão
		CONVENIO SINDSERVA no comprovante de compra emitido pelo sistema web
		ou no cupom fiscal ou qualquer documento que comprove o fornecimento
		de produtos e serviços;</FONT></P>
		</P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>4º</B></FONT><FONT FACE="Century Gothic, sans-serif">
		O CONVENIO SINDSERVA compromete se a Implantar, organizar, manter e
		gerenciar o SISTEMA e o APLICATIVO junto à empresa conveniada;</FONT></P>
		</P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>5º</B></FONT><FONT FACE="Century Gothic, sans-serif">
		O CONVENIADO compromete-se a guardar os comprovantes devidamente
		assinados durante cinco anos;</FONT></P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>6º</B></FONT><FONT FACE="Century Gothic, sans-serif">
		O CONVENIADO receberá o pagamento das vendas ou prestação de
		serviços através de cheque nominal impresso em duas vias com
		recibo, após 30 dias da data de fechamento do convenio. O pagamento
		estará disponível no endereço Av Rio branco, 417, sala 305 –
		centro de segunda a sexta-feira de 13:00 as 17:00 hrs;</FONT></P>
			<DIV TYPE=FOOTER>
				<P LANG="pt-BR" ALIGN=CENTER STYLE="margin-top: 0.2in; margin-bottom: 0in">
					<FONT FACE="Century Gothic, sans-serif"><FONT SIZE=1 STYLE="font-size: 8pt">Rua
						Argentina, 245 – Vila Pinto – Varginha-MG</FONT></FONT></P>
				<P LANG="pt-BR" ALIGN=CENTER STYLE="margin-bottom: 0in"><FONT FACE="Century Gothic, sans-serif"><FONT SIZE=1 STYLE="font-size: 8pt">3221-2516
					* 3221-1096</FONT></FONT></P>
			</DIV>
		</section>
		<section class="sheet padding-10mm">
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>7º </B></FONT><FONT FACE="Century Gothic, sans-serif">O
		CONVENIADO </FONT><FONT FACE="Century Gothic, sans-serif"><U>poderá</U></FONT><FONT FACE="Century Gothic, sans-serif">
		definir o preço praticado na venda de produtos ou prestação de
		serviços;</FONT></P>

		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-right: 0.01in; margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>8º </B></FONT><FONT FACE="Century Gothic, sans-serif">O
		período de fornecimento produtos e serviços inicia se no dia 4
		(quatro) de cada mês e encerrará no dia 3 (três) do mês
		subsequente;</FONT></P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>9º </B></FONT><FONT FACE="Century Gothic, sans-serif">O
		CONVENIO SINDSERVA não irá fazer o repasse dos valores dos produtos
		ou serviços que não tiverem devidamente cadastrados no sistema ou
		não possuir o comprovante assinado pelo portador do cartão;</FONT></P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT COLOR="#000000"><FONT FACE="Courier New Negrito, Times New Roman, serif"><FONT SIZE=3><SPAN STYLE="font-style: normal"><SPAN STYLE="font-weight: normal"><FONT FACE="Century Gothic, sans-serif"><B>10º
		</B></FONT></SPAN></SPAN></FONT></FONT></FONT><FONT COLOR="#000000"><FONT FACE="Courier New, monospace"><FONT SIZE=3><SPAN STYLE="font-style: normal"><SPAN STYLE="font-weight: normal"><FONT FACE="Century Gothic, sans-serif">A
		Taxa administrativa do cartão </FONT></SPAN></SPAN></FONT></FONT></FONT><FONT FACE="Century Gothic, sans-serif">CONVENIO
		SINDSERVA </FONT><FONT COLOR="#000000"><FONT FACE="Courier New, monospace"><FONT SIZE=3><SPAN STYLE="font-style: normal"><SPAN STYLE="font-weight: normal"><FONT FACE="Century Gothic, sans-serif">é
		de (4,0%), sendo que 1% vem descontado no cheque e 3% enviamos um
		boleto bancário para pagamento;</FONT></SPAN></SPAN></FONT></FONT></FONT></P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>11º</B></FONT><FONT FACE="Century Gothic, sans-serif">
		O CONVENIADO tem duas opções para lançar os valores no sistema,
		através do site www.makecard.com.br, ou através do Aplicativo
		disponível para androide no GooglePlay;</FONT></P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>12º </B></FONT><FONT FACE="Century Gothic, sans-serif">O
		CONVENIADO se compromete a fornecer produtos ou prestação de
		serviços somente para os associados que possuírem o cartão de
		identificação ou o Aplicativo no celular;</FONT></P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>13º </B></FONT><FONT FACE="Century Gothic, sans-serif">O
		CONVENIADO não pode fornecer produtos ou serviços em outro endereço
		diferente ao deste contrato. Para utilizar outro endereço é
		necessário fazer um novo cadastro;</FONT></P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif"><B>14ª</B></FONT><FONT FACE="Century Gothic, sans-serif">
		O CONVENIADO </FONT><FONT COLOR="#000000"><FONT FACE="Century Gothic, sans-serif"><SPAN LANG="pt-PT">autoriza
		a divulgação e a publicação da logomarca e informações
		cadastrais no site do convenio e no Aplicativo e em outros meios de
		divulgação, renunciando a qualquer pagamento referente a direito de
		imagem;</SPAN></FONT></FONT></P>
		<P LANG="pt-BR" ALIGN=JUSTIFY STYLE="margin-bottom: 0in"><FONT FACE="Century Gothic, sans-serif"><B>15º
		</B></FONT><FONT FACE="Century Gothic, sans-serif">As partes elegem o
		foro da Comarca de Varginha-MG, para dirimir qualquer controvérsia
		em razão do presente instrumento.</FONT></P>
		<P LANG="pt-BR" CLASS="western" STYLE="margin-bottom: 0in"><BR>
		</P>
		<P LANG="pt-BR" CLASS="western" STYLE="margin-bottom: 0in"><FONT FACE="Century Gothic, sans-serif">	</FONT></P>
		<P LANG="pt-BR" CLASS="western" STYLE="margin-bottom: 0in"><BR>
		</P>
		<P LANG="pt-BR" CLASS="western" STYLE="margin-bottom: 0in"><FONT FACE="Century Gothic, sans-serif">Varginha,
		25 de Agosto 2020.</FONT></P>
		<P LANG="pt-BR" CLASS="western" STYLE="margin-bottom: 0in"><BR>
		</P>
		<P LANG="pt-BR" CLASS="western" STYLE="margin-bottom: 0in"><BR>
		</P>
		<P LANG="pt-BR" CLASS="western" STYLE="margin-bottom: 0in"><BR>
		</P>
		<P LANG="pt-BR" CLASS="western" STYLE="margin-bottom: 0in"><FONT FACE="Century Gothic, sans-serif">	</FONT></P>
		<P LANG="pt-BR" CLASS="western" ALIGN=CENTER STYLE="margin-bottom: 0in">
		<FONT FACE="Century Gothic, sans-serif">
										CONVENIO SINDSERVA</FONT></P>
		<P LANG="pt-BR" CLASS="western" STYLE="margin-bottom: 0in"><BR>
		</P>
		<P LANG="pt-BR" CLASS="western" ALIGN=JUSTIFY STYLE="margin-bottom: 0in">
		<BR>
		</P>
		<P LANG="pt-BR" CLASS="western" STYLE="margin-bottom: 0in"><BR>
		</P>
		<P LANG="pt-BR" CLASS="western" STYLE="margin-bottom: 0in"><BR>
		</P>
		<DIV TYPE=FOOTER>
			<P LANG="pt-BR" ALIGN=CENTER STYLE="margin-top: 0.47in; margin-bottom: 0in">
			<FONT FACE="Century Gothic, sans-serif"><FONT SIZE=1 STYLE="font-size: 8pt">Rua
			Argentina, 245 – Vila Pinto – Varginha-MG</FONT></FONT></P>
			<P LANG="pt-BR" ALIGN=CENTER STYLE="margin-bottom: 0in"><FONT FACE="Century Gothic, sans-serif"><FONT SIZE=1 STYLE="font-size: 8pt">3221-2516
			* 3221-1096</FONT></FONT></P>
		</DIV>
		</section>
	</div>
</div>
<div id="editor"></div>
<script>
	var doc = new jsPDF();
	var specialElementHandlers = {
		'#editor': function (element, renderer) {
			return true;
		}
	};
	$('#btGerarPDF').click(function () {
		doc.fromHTML($('#conteudo').html(), 15, 15, {
			'width': 170,
			'elementHandlers': specialElementHandlers
		});
		debugger;
		doc.save('exemplo-pdf.pdf');
	});
</script>
</BODY>
</html>