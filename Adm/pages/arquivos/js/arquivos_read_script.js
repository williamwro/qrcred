var table;
var mes_selecionado;
var $C_mes = $('#C_mes');
var $C_empregador = $('#C_empregador');
var $C_tipo = $('#C_tipo');

// Função para carregar empregadores com base no mês selecionado
function carregarEmpregadores() {
    var divisao = sessionStorage.getItem("divisao");
    $C_empregador.empty();
    $C_empregador.append('<option data-subtext="" value=""></option>');
    
    // Obter o mês selecionado para filtrar empregadores
    var mesSelecionado = $C_mes.val();
    var parametros = {"divisao": divisao};
    if (mesSelecionado) {
        parametros.mes = mesSelecionado;
    }
    
    // Debug para verificar parâmetros sendo enviados
    console.log("DEBUG carregarEmpregadores() - Parâmetros enviados:", parametros);
    console.log("DEBUG carregarEmpregadores() - Mês selecionado:", mesSelecionado);
    
    $.getJSON( "../Adm/pages/arquivos/producao_empregador.php", parametros, function( data ) {
        console.log("DEBUG carregarEmpregadores() - Dados recebidos:", data);
        console.log("DEBUG carregarEmpregadores() - Tipo de dados:", typeof data);
        console.log("DEBUG carregarEmpregadores() - Length:", data.length);
        
        if (data.error) {
            console.error("ERROR carregarEmpregadores() - Erro do servidor:", data.error);
            return;
        }
        
        $.each(data, function (index, value) {
            // Debug para verificar caracteres especiais
            if (value.nome && (value.nome.includes('ç') || value.nome.includes('á') || value.nome.includes('ã') || value.nome.includes('í'))) {
                console.log("DEBUG - Nome com caracteres especiais:", value.nome);
                console.log("DEBUG - Char codes:", value.nome.split('').map(c => c.charCodeAt(0)));
            }
            
            // Escapar caracteres HTML para evitar problemas na exibição
            var nomeEscapado = $('<div>').text(value.nome || '').html();
            var abreviacaoEscapada = $('<div>').text(value.abreviacao || '').html();
            
            $C_empregador.append('<option data-subtext="' + abreviacaoEscapada + '" value="' + value.id + '">' + nomeEscapado + '</option>');
        });
        // Refresh do selectpicker se estiver sendo usado
        if ($C_empregador.hasClass('selectpicker')) {
            $C_empregador.selectpicker('refresh');
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error("ERROR carregarEmpregadores() - Falha na requisição:", textStatus, errorThrown);
        console.error("ERROR carregarEmpregadores() - Response:", jqXHR.responseText);
    });
}
var mescorrente = "";
var $tabela_dados = $('#tabela_dados');
var total_farmacia = 0;
var total_compras = 0;
var total_unimed = 0;
var divisao = 0;
var card1;
var card2;
var card3;
var card4;
var card5;
var card6;
$(document).ready(function(){

    waitingDialog.show('Carregando, aguarde ...');
    divisao = sessionStorage.getItem("divisao");
    divisao_nome = sessionStorage.getItem("divisao_nome");
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");
    card1 = sessionStorage.getItem("card1");
    card2 = sessionStorage.getItem("card2");
    card3 = sessionStorage.getItem("card3");
    card4 = sessionStorage.getItem("card4");
    card5 = sessionStorage.getItem("card5");
    card6 = sessionStorage.getItem("card6");


    $.getJSON( "../Adm/pages/arquivos/meses_conta.php",{ "origem": "convenio", "divisao": divisao }, function( data ) {
        $.each(data, function (index, value) {
            if (value.mes_corrente !== undefined) {
                mescorrente = value.mes_corrente;
            }
            if (value.abreviacao !== undefined) {
                var textoOpcao = value.abreviacao;
                if (value.periodo !== undefined && value.periodo !== null && value.periodo !== '') {
                    textoOpcao = value.abreviacao + ' - ' + value.periodo;
                }
                if (mescorrente === value.abreviacao) {
                    $C_mes.append('<option selected value="' + value.abreviacao + '">' + textoOpcao + '</option>');
                } else {
                    $C_mes.append('<option value="' + value.abreviacao + '">' + textoOpcao + '</option>');
                }
            }
        });
        // Carregar empregadores após carregar os meses (para garantir que o mês esteja selecionado)
        carregarEmpregadores();
    });
    $C_tipo.attr({"title":"Escollha o tipo"});
    $C_tipo.append('<option value=""></option>');
    $.getJSON( "../Adm/pages/producao/producao_tipo.php", function( data ) {
        $.each(data, function (index, value) {
            $C_tipo.append('<option value="' + value.codigo + '">' + value.nome + '</option>');
        });
    });
    waitingDialog.hide();
});
$C_mes.change(function () {
    // Recarregar lista de empregadores quando o mês muda
    carregarEmpregadores();
    
    if ($C_mes.val() !== "" && $C_empregador.val() !== ""){
        waitingDialog.show('Carregando, aguarde ...');
        // constroi uma datatabe no primeiro carregamento da tela

        mes_selecionado = $(this).children("option:selected").val();
        carregar_grid();
        waitingDialog.hide();
    }
});
$C_empregador.change(function () {
    if ($C_mes.val() !== "" && $C_empregador.val() !== "") {
        waitingDialog.show('Carregando, aguarde ...',);
        // constroi uma datatabe no primeiro carregamento da tela

        carregar_grid();
        waitingDialog.hide();
    } else if ($C_empregador.val() === "") {
        if ( $.fn.dataTable.isDataTable( '#tabela_dados' ) ) {
            table.clear().draw();
        }
    }
});
$C_tipo.change(function () {
    if ($C_mes.val() !== "" && $C_empregador.val() !== "" ){
        waitingDialog.show('Carregando, aguarde ...');
        // constroi uma datatabe no primeiro carregamento da tela

        carregar_grid();
        waitingDialog.hide();
    }
});
$("#gerararquivo").click(function () {

    if (divisao_nome === "Casserv") {
        mes_selecionado = $('#C_mes').val();
        if( $('#C_empregador').val() === "1" ||  $('#C_empregador').val() === "8" ) { // 1 = PMV PREFEITURA MUNICIPAL
            var data = table.rows().data();
            var texto = '';
            var obj = {};
            obj.dados = [];
            var d = new Date();
            var dataHora = (d.toLocaleString());
            dataHora.substring(0, 10);
            var farmacia = 0;
            var compras = 0;
            var financeira = 0;
            var unimed = 0;
            var financeira2 = 0;
            var financeira3 = 0;
            var linha = '';
            if (table.rows().count() > 0) {
                data.each(function (value, index) {
                    if (value.nome_tipo === 'FARMACIA') {//farmacia
                        farmacia = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0439' + farmacia;
                    } else if (value.nome_tipo === 'COMPRAS') {//compras
                        compras = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0354' + compras;
                    } else if (value.nome_tipo === 'FINANCEIRA') {//financeira
                        financeira = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0313' + financeira;
                    } else if (value.nome_tipo === 'UNIMED') {//unimed
                        unimed = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0495' + unimed;
                    } else if (value.nome_tipo === 'FINANCEIRA2') {//financeira2
                        financeira2 = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0317' + financeira2;
                    } else if (value.nome_tipo === 'FINANCEIRA3') {//financeira3
                        financeira3 = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        if ($C_empregador.value === 'PREFEITURA MUNICIPAL') {
                            linha += value.associado + '0350' + financeira3;
                        } else if ($C_empregador.value === 'INPREV') {
                            linha += value.associado + '0350' + financeira3;
                        }
                    }
                    farmacia = 0;
                    compras = 0;
                    financeira = 0;
                    unimed = 0;
                    financeira2 = 0;
                    financeira3 = 0;
                });
                let blob = new Blob([linha], {type: "text/plain;charset=utf-8"});
                saveAs(blob, divisao_nome + "_" + $('#C_empregador').val() + "_" + mes_selecionado + "_VALORES_" + dataHora.substring(0, 10));
            }
        }else if( $('#C_empregador').val() === "3" ) { // 3 = FH - FUNDACAO HOSPITALAR
            var data = table.rows().data();
            var texto = '';
            var obj = {};
            obj.dados = [];
            var d = new Date();
            var dataHora = (d.toLocaleString());
            var data_short = dataHora.substring(0, 10);
            var data_vetor = data_short.split("/");
            var mes = data_vetor[1];
            var ano = data_vetor[2];
            var farmacia = 0;
            var compras = 0;
            var linha = '';
            mes_selecionado = $('#C_mes').val();
            if (table.rows().count() > 0) {
                data.each(function (value, index) {
                    if (value.nome_tipo === 'FARMACIA') {//farmacia
                        farmacia = ("        " + (parseFloat(value.total).toFixed(2).replace(',', ''))).slice(-8);
                        linha += '"' + ano + '","' + mes + '","' + value.associado + '","4293","' + farmacia + '","01"' + "\r\n";
                    } else if (value.nome_tipo === 'COMPRAS') {//compras
                        compras = ("        " + (parseFloat(value.total).toFixed(2).replace(',', ''))).slice(-8);
                        linha += '"' + ano + '","' + mes + '","' + value.associado + '","4292","' + compras + '","01"'+ "\r\n";
                    } else if (value.nome_tipo === 'UNIMED') {//compras
                        compras = ("        " + (parseFloat(value.total).toFixed(2).replace(',', ''))).slice(-8);
                        linha += '"' + ano + '","' + mes + '","' + value.associado + '","448","' + compras + '","01"'+ "\r\n";
                    }
                    farmacia = 0;
                    compras = 0;
                });

                let blob = new Blob([linha], {type: "text/plain;charset=utf-8"});
                saveAs(blob, divisao_nome + "_" + mes_selecionado + "_VALORES_" + dataHora.substring(0, 10)+".txt");
            }
        }
    }else if (divisao_nome === "Sindicato"){
        mes_selecionado = $('#C_mes').val();
        if( $('#C_empregador').val() === "10" ) { // 1 = PMV PREFEITURA MUNICIPAL
            var data = table.rows().data();
            var texto = '';
            var obj = {};
            obj.dados = [];
            var d = new Date();
            var dataHora = (d.toLocaleString());
            dataHora.substring(0, 10);
            var farmacia = 0;
            var compras = 0;
            var financeira = 0;
            var unimed = 0;
            var financeira2 = 0;
            var financeira3 = 0;
            var linha = '';
            if (table.rows().count() > 0) {
                data.each(function (value, index) {
                    if (value.nome_tipo === 'FARMACIA') {//farmacia
                        farmacia = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0350' + farmacia;
                    } else if (value.nome_tipo === 'COMPRAS') {//compras
                        compras = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0355' + compras;
                    } else if (value.nome_tipo === 'FINANCEIRA') {//financeira
                        financeira = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0313' + financeira;
                    } else if (value.nome_tipo === 'UNIMED') {//unimed
                        unimed = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0495' + unimed;
                    } else if (value.nome_tipo === 'FINANCEIRA2') {//financeira2
                        financeira2 = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                        linha += value.associado + '0317' + financeira2;
                    } else if (value.nome_tipo === 'FINANCEIRA3') {//financeira3
                        financeira3 = ("        " + (parseFloat(value.total).toFixed(2).replace('.', ''))).slice(-11) + "\r\n";
                    }
                    farmacia = 0;
                    compras = 0;
                    financeira = 0;
                    unimed = 0;
                    financeira2 = 0;
                    financeira3 = 0;
                });
                let blob = new Blob([linha], {type: "text/plain;charset=utf-8"});
                saveAs(blob, divisao_nome + "_" + $('#C_empregador').val() + "_" + mes_selecionado + "_VALORES_" + dataHora.substring(0, 10));
            }
        }else if( $('#C_empregador').val() === "12" ) { // 3 = FH - FUNDACAO HOSPITALAR
            var data = table.rows().data();
            var texto = '';
            var obj = {};
            obj.dados = [];
            var d = new Date();
            var dataHora = (d.toLocaleString());
            var data_short = dataHora.substring(0, 10);
            var data_vetor = data_short.split("/");
            var mes = data_vetor[1];
            var ano = data_vetor[2];
            var farmacia = 0;
            var compras = 0;
            var linha = '';
            mes_selecionado = $('#C_mes').val();
            if (table.rows().count() > 0) {
                data.each(function (value, index) {
                    if (value.nome_tipo === 'FARMACIA') {//farmacia
                        farmacia = ("        " + (parseFloat(value.total).toFixed(2).replace(',', ''))).slice(-8);
                        linha += '"' + ano + '","' + mes + '","' + value.associado + '","D350","' + farmacia + '","01"' + "\r\n";
                    } else if (value.nome_tipo === 'COMPRAS') {//compras
                        compras = ("        " + (parseFloat(value.total).toFixed(2).replace(',', ''))).slice(-8);
                        linha += '"' + ano + '","' + mes + '","' + value.associado + '","D448","' + compras + '","01"'+ "\r\n";
                    } else if (value.nome_tipo === 'UNIMED') {//compras
                        compras = ("        " + (parseFloat(value.total).toFixed(2).replace(',', ''))).slice(-8);
                        linha += '"' + ano + '","' + mes + '","' + value.associado + '","D448","' + compras + '","01"'+ "\r\n";
                    }
                    farmacia = 0;
                    compras = 0;
                });

                let blob = new Blob([linha], {type: "text/plain;charset=utf-8"});
                saveAs(blob, divisao_nome + "_" + mes_selecionado + "_VALORES_" + dataHora.substring(0, 10)+".txt");
            }
        }
    }
});
$('#relatoriofinal').click(function () {
    var mes_atual  = $('#C_mes').val();
    var empregador = $('#C_empregador').val();
    var tipo = $('#C_tipo').val();
    
    // Validações para relatório individual
    if (!mes_atual) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um mês.'
        });
        return;
    }
    
    if (!empregador) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um empregador.'
        });
        return;
    }
    
    var params = { mes_atual: mes_atual, empregador: empregador, divisao: divisao,divisao_nome: divisao_nome };
    if (tipo && tipo !== '') {
        params.tipo = tipo;
    }
    $.redirect('../Adm/pages/arquivos/relatorio_final.php', params, "POST", "_blank");
});

// Opção 1: Somente Relatório (relatório original)
$('#relatorio_somente').click(function (e) {
    e.preventDefault();
    var mes_atual  = $('#C_mes').val();
    var empregador = $('#C_empregador').val();
    var tipo = $('#C_tipo').val();
    
    if (!mes_atual) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um mês.'
        });
        return;
    }
    
    if (!empregador) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um empregador.'
        });
        return;
    }
    
    var params = { mes_atual: mes_atual, empregador: empregador, divisao: divisao, divisao_nome: divisao_nome };
    if (tipo && tipo !== '') {
        params.tipo = tipo;
    }
    $.redirect('../Adm/pages/arquivos/relatorio_final.php', params, "POST", "_blank");
});

// Opção 2: Ofício (novo relatório com formato de ofício)
$('#relatorio_oficio').click(function (e) {
    e.preventDefault();
    var mes_atual  = $('#C_mes').val();
    var empregador = $('#C_empregador').val();
    var tipo = $('#C_tipo').val();
    var data_vencimento = $('#C_data_vencimento').val();
    
    if (!mes_atual) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um mês.'
        });
        return;
    }
    
    if (!empregador) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um empregador.'
        });
        return;
    }
    
    if (!data_vencimento) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, informe a data de vencimento para o ofício.'
        });
        return;
    }
    
    var params = { mes_atual: mes_atual, empregador: empregador, divisao: divisao, divisao_nome: divisao_nome, data_vencimento: data_vencimento };
    if (tipo && tipo !== '') {
        params.tipo = tipo;
    }
    $.redirect('../Adm/pages/arquivos/relatorio_oficio.php', params, "POST", "_blank");
});

// Opção 3: Ofício JPEG (gera imagem JPEG do ofício)
$('#relatorio_oficio_jpeg').click(function (e) {
    e.preventDefault();
    var mes_atual  = $('#C_mes').val();
    var empregador = $('#C_empregador').val();
    var tipo = $('#C_tipo').val();
    var data_vencimento = $('#C_data_vencimento').val();
    
    if (!mes_atual) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um mês.'
        });
        return;
    }
    
    if (!empregador) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um empregador.'
        });
        return;
    }
    
    if (!data_vencimento) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, informe a data de vencimento para o ofício.'
        });
        return;
    }
    
    var params = { mes_atual: mes_atual, empregador: empregador, divisao: divisao, divisao_nome: divisao_nome, data_vencimento: data_vencimento };
    if (tipo && tipo !== '') {
        params.tipo = tipo;
    }
    $.redirect('../Adm/pages/arquivos/relatorio_oficio_jpeg_v3.php', params, "POST", "_blank");
});

// Novo botão para gerar todos os relatórios em um único PDF
$('#relatorio_todos').click(function () {
    var mes_atual = $('#C_mes').val();
    var tipo = $('#C_tipo').val();
    
    // Validações
    if (!mes_atual) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um mês.'
        });
        return;
    }
    
    // Confirmação antes de gerar (pode ser um processo longo)
    Swal.fire({
        title: 'Confirmar Geração',
        text: 'Deseja gerar o relatório consolidado com todos os empregadores? Este processo pode levar alguns minutos.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Sim, gerar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Gerando Relatórios...',
                text: 'Aguarde enquanto todos os relatórios são compilados.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Fazer a requisição para o novo arquivo PHP
            var params = { mes_atual: mes_atual, divisao: divisao,divisao_nome: divisao_nome };
            if (tipo && tipo !== '') {
                params.tipo = tipo;
            }
            $.redirect('../Adm/pages/arquivos/relatorio_todos_empregadores.php', params, "POST", "_blank");
            
            // Fechar o loading após um tempo (o PDF abrirá em nova aba)
            setTimeout(() => {
                Swal.close();
            }, 3000);
        }
    });
});

// Novo botão para gerar PDFs individuais separados para cada empregador
$('#relatorio_individuais').click(function () {
    var mes_atual = $('#C_mes').val();
    var tipo = $('#C_tipo').val();
    var divisao = sessionStorage.getItem("divisao");
    
    // Validações
    if (!mes_atual) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Por favor, selecione um mês.'
        });
        return;
    }
    
    if (!divisao) {
        Swal.fire({
            icon: 'warning',
            title: 'Erro de Sessão!',
            text: 'Divisão não encontrada. Por favor, recarregue a página e tente novamente.'
        });
        return;
    }
    
    // Confirmação antes de gerar (pode ser um processo longo)
    Swal.fire({
        title: 'Confirmar Geração de PDFs Individuais',
        html: 'Deseja gerar um PDF separado para cada empregador?<br><br>' +
              '<strong>Cada arquivo terá o nome:</strong><br>' +
              '<em>Empregador_Mês_DataHora.pdf</em><br><br>' +
              '<small>Este processo pode levar alguns minutos e irá baixar múltiplos arquivos.</small>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Sim, gerar PDFs!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Primeira requisição: buscar lista de empregadores
            var ajaxData = {
                mes_atual: mes_atual,
                divisao: divisao
            };
            if (tipo && tipo !== '') {
                ajaxData.tipo = tipo;
            }
            
            $.ajax({
                url: '../Adm/pages/arquivos/buscar_empregadores_mes.php',
                method: 'POST',
                data: ajaxData,
                dataType: 'json',
                success: function(empregadores) {
                    if (!empregadores || empregadores.length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Nenhum Empregador',
                            text: 'Não foram encontrados empregadores com dados para o mês selecionado.'
                        });
                        return;
                    }
                    
                    // Mostrar progresso
                    let currentIndex = 0;
                    const totalEmpregadores = empregadores.length;
                    
                    Swal.fire({
                        title: 'Gerando PDFs Individuais...',
                        html: `Progresso: <strong>0 de ${totalEmpregadores}</strong> empregadores`,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Função para gerar PDF de cada empregador sequencialmente
                    function gerarProximoPDF() {
                        if (currentIndex >= totalEmpregadores) {
                            // Todos os PDFs foram gerados
                            Swal.fire({
                                icon: 'success',
                                title: 'Concluído!',
                                text: `${totalEmpregadores} PDFs foram gerados e baixados com sucesso.`,
                                timer: 3000
                            });
                            return;
                        }
                        
                        const empregador = empregadores[currentIndex];
                        console.log(`DEBUG: Gerando PDF ${currentIndex + 1}/${totalEmpregadores} para empregador:`, empregador);
                        
                        // Atualizar progresso
                        Swal.update({
                            html: `Progresso: <strong>${currentIndex + 1} de ${totalEmpregadores}</strong> empregadores<br>
                                   Gerando: <em>${empregador.nome}</em>`
                        });
                        
                        // Gerar PDF para este empregador
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': '../Adm/pages/arquivos/relatorio_individual_download.php',
                            'target': '_blank'
                        });
                        
                        form.append($('<input>', {'type': 'hidden', 'name': 'mes_atual', 'value': mes_atual}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'empregador', 'value': empregador.id}));
                        if (tipo && tipo !== '') {
                            form.append($('<input>', {'type': 'hidden', 'name': 'tipo', 'value': tipo}));
                        }
                        form.append($('<input>', {'type': 'hidden', 'name': 'divisao', 'value': divisao}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'divisao_nome', 'value': divisao_nome}));
                        
                        console.log(`DEBUG: Submetendo form para empregador ${empregador.nome} (${empregador.id})`);
                        console.log('DEBUG: Dados do form:', {
                            mes_atual: mes_atual,
                            empregador: empregador.id,
                            tipo: tipo,
                            divisao: divisao,
                            divisao_nome: divisao_nome
                        });
                        
                        $('body').append(form);
                        form.submit();
                        form.remove();
                        
                        // Incrementar apenas após submissão
                        currentIndex++;
                        
                        // Aguardar mais tempo para garantir que o download anterior foi iniciado
                        setTimeout(gerarProximoPDF, 2500);
                    }
                    
                    // Iniciar geração
                    gerarProximoPDF();
                },
                error: function(xhr, status, error) {
                    console.error('Erro ao buscar empregadores:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao buscar lista de empregadores. Tente novamente.'
                    });
                }
            });
        }
    });
});
// ── Todos em 1 PDF → Ofício pdf ──────────────────────────────────────────────
$('#relatorio_todos_oficio').click(function () {
    var mes_atual       = $('#C_mes').val();
    var tipo            = $('#C_tipo').val();
    var data_vencimento = $('#C_data_vencimento').val();

    if (!mes_atual) {
        Swal.fire({ icon: 'warning', title: 'Atenção!', text: 'Por favor, selecione um mês.' });
        return;
    }
    if (!data_vencimento) {
        Swal.fire({ icon: 'warning', title: 'Atenção!', text: 'Por favor, informe a data de vencimento para o ofício.' });
        return;
    }

    Swal.fire({
        title: 'Confirmar Geração',
        text: 'Deseja gerar o ofício consolidado (PDF) com todos os empregadores? Este processo pode levar alguns minutos.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f57c00',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Sim, gerar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Gerando Ofícios...', text: 'Aguarde enquanto todos os ofícios são compilados.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            var params = { mes_atual: mes_atual, divisao: divisao, divisao_nome: divisao_nome, data_vencimento: data_vencimento };
            if (tipo && tipo !== '') { params.tipo = tipo; }
            $.redirect('../Adm/pages/arquivos/relatorio_todos_oficio.php', params, "POST", "_blank");
            setTimeout(() => { Swal.close(); }, 3000);
        }
    });
});

// ── Todos em 1 PDF → Ofício jpeg ─────────────────────────────────────────────
$('#relatorio_todos_oficio_jpeg').click(function () {
    var mes_atual       = $('#C_mes').val();
    var tipo            = $('#C_tipo').val();
    var data_vencimento = $('#C_data_vencimento').val();

    if (!mes_atual) {
        Swal.fire({ icon: 'warning', title: 'Atenção!', text: 'Por favor, selecione um mês.' });
        return;
    }
    if (!data_vencimento) {
        Swal.fire({ icon: 'warning', title: 'Atenção!', text: 'Por favor, informe a data de vencimento para o ofício.' });
        return;
    }

    Swal.fire({
        title: 'Confirmar Geração',
        text: 'Deseja gerar o ofício consolidado (JPEG) com todos os empregadores? Este processo pode levar alguns minutos.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#388e3c',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Sim, gerar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Gerando Ofícios JPEG...', text: 'Aguarde enquanto todos os ofícios são compilados.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            var params = { mes_atual: mes_atual, divisao: divisao, divisao_nome: divisao_nome, data_vencimento: data_vencimento };
            if (tipo && tipo !== '') { params.tipo = tipo; }
            $.redirect('../Adm/pages/arquivos/relatorio_todos_oficio_jpeg.php', params, "POST", "_blank");
            setTimeout(() => { Swal.close(); }, 3000);
        }
    });
});

// Helper: gera ofícios individuais para cada empregador usando o PHP informado
function gerarOficiosIndividuais(phpFile, labelTipo) {
    var mes_atual       = $('#C_mes').val();
    var tipo            = $('#C_tipo').val();
    var data_vencimento = $('#C_data_vencimento').val();
    var divisao         = sessionStorage.getItem("divisao");

    if (!mes_atual) {
        Swal.fire({ icon: 'warning', title: 'Atenção!', text: 'Por favor, selecione um mês.' });
        return;
    }
    if (!data_vencimento) {
        Swal.fire({ icon: 'warning', title: 'Atenção!', text: 'Por favor, informe a data de vencimento para o ofício.' });
        return;
    }
    if (!divisao) {
        Swal.fire({ icon: 'warning', title: 'Erro de Sessão!', text: 'Divisão não encontrada. Por favor, recarregue a página.' });
        return;
    }

    Swal.fire({
        title: 'Confirmar Geração de Ofícios Individuais',
        html: 'Deseja gerar um ofício <strong>' + labelTipo + '</strong> separado para cada empregador?<br><br>' +
              '<small>Este processo pode levar alguns minutos e irá baixar múltiplos arquivos.</small>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Sim, gerar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) { return; }

        var ajaxData = { mes_atual: mes_atual, divisao: divisao };
        if (tipo && tipo !== '') { ajaxData.tipo = tipo; }

        $.ajax({
            url: '../Adm/pages/arquivos/buscar_empregadores_mes.php',
            method: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: function(empregadores) {
                if (!empregadores || empregadores.length === 0) {
                    Swal.fire({ icon: 'info', title: 'Nenhum Empregador', text: 'Não foram encontrados empregadores com dados para o mês selecionado.' });
                    return;
                }

                let currentIndex = 0;
                const totalEmpregadores = empregadores.length;

                Swal.fire({
                    title: 'Gerando Ofícios Individuais...',
                    html: 'Progresso: <strong>0 de ' + totalEmpregadores + '</strong> empregadores',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                function gerarProximo() {
                    if (currentIndex >= totalEmpregadores) {
                        Swal.fire({ icon: 'success', title: 'Concluído!', text: totalEmpregadores + ' ofícios foram gerados e baixados com sucesso.', timer: 3000 });
                        return;
                    }

                    const emp = empregadores[currentIndex];
                    Swal.update({ html: 'Progresso: <strong>' + (currentIndex + 1) + ' de ' + totalEmpregadores + '</strong> empregadores<br>Gerando: <em>' + emp.nome + '</em>' });

                    const form = $('<form>', { method: 'POST', action: '../Adm/pages/arquivos/' + phpFile, target: '_blank' });
                    form.append($('<input>', { type: 'hidden', name: 'mes_atual',        value: mes_atual }));
                    form.append($('<input>', { type: 'hidden', name: 'empregador',       value: emp.id }));
                    form.append($('<input>', { type: 'hidden', name: 'divisao',          value: divisao }));
                    form.append($('<input>', { type: 'hidden', name: 'divisao_nome',     value: divisao_nome }));
                    form.append($('<input>', { type: 'hidden', name: 'data_vencimento',  value: data_vencimento }));
                    if (tipo && tipo !== '') { form.append($('<input>', { type: 'hidden', name: 'tipo', value: tipo })); }

                    $('body').append(form);
                    form.submit();
                    form.remove();
                    currentIndex++;
                    setTimeout(gerarProximo, 2500);
                }

                gerarProximo();
            },
            error: function(xhr, status, error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro ao buscar lista de empregadores. Tente novamente.' });
            }
        });
    });
}

// ── PDFs Separados → Ofício pdf ───────────────────────────────────────────────
$('#relatorio_individuais_oficio').click(function () {
    gerarOficiosIndividuais('relatorio_oficio.php', 'PDF');
});

// ── PDFs Separados → Ofício jpeg ──────────────────────────────────────────────
$('#relatorio_individuais_oficio_jpeg').click(function () {
    gerarOficiosIndividuais('relatorio_oficio_jpeg_v3.php', 'JPEG');
});

$('#btnImprimirTodosExtratos').click(function () {
    var mes_atual  = $('#C_mes').val();
    var empregador = $('#C_empregador').val();
    if($('#C_empregador').val() != "") {
        debugger;
        $.redirect('../Adm/pages/arquivos/conta_imprimir_todos_pdf.php',{ mes_atual: mes_atual, empregador: empregador, card1: card1, card2: card2, card3: card3, card4: card4, card5: card5, card6: card6}, "POST", "_blank");
    }else{
        BootstrapDialog.show({
            closable: false,
            title: 'Atenção',
            message: 'Escolha o empregador PMV',
            buttons: [{
                cssClass: 'btn-danger',
                label: 'Ok',
                action: function (dialogItself) {
                    dialogItself.close();
                    //$("#C_Senha").focus();
                }
            }]
        });
     }

});
function carregar_grid() {
    total_compras = 0;
    total_farmacia = 0;
    total_unimed = 0;
    if ( $.fn.dataTable.isDataTable( '#tabela_dados' ) ) {
        table.destroy();
        table = $tabela_dados.DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            serverSide: false,
            responsive: true,
            autoWidth: true,
            JQueryUI: true,
            searching: true,
            order: [[1, "asc"]],
            ajax: {
                url: '../Adm/pages/arquivos/selecionar_dados.php',
                method: 'POST',
                data: function (data) {
                    data.mes = $("#C_mes").val();
                    data.empregador = $("#C_empregador").val();
                    data.tipo = $("#C_tipo").val();
                    data.divisao = divisao;
                    data.card1 = card1;
                    data.card2 = card2;
                    data.card3 = card3;
                    data.card4 = card4;
                    data.card5 = card5;
                    data.card6 = card6;
                },
                dataType: 'json'
            },
            columns: [
                {data: "associado"},
                {data: "nome"},
                {data: "nome_tipo"},
                {
                    data: "total",
                    render: $.fn.dataTable.render.number('.', ',', 2, 'R$ '),
                    className: "text-right"
                }
            ],
            pagingType: "full_numbers",
            language: {
                //url: "pages/conta/Portuguese-Brasil.json",
                decimal: ",",
                thousands: ".",
                zeroRecords: "Não ha dados",
                emptyTable: "Não ha dados.",
                infoEmpty: 'Zero registros',
                paginate: {
                    next: "Próximo",
                    previous: "Anterior",
                    first: "Primeiro",
                    last: "Último"
                },
                search: "Pesquisar",
                info: "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                infoFiltered: "(Filtrados de _MAX_ registros)",
                infoPostFix: "",
                lengthMenu: "_MENU_ resultados por página"
            },
            createdRow: function (row, data, index){
                // Now that we have the data and not the HTML it is cleaner

                if (data["nome_tipo"] === "COMPRAS") {

                    $('td', row).css('background-color', '#a9f5b4');
                    total_compras += parseFloat(data["total"].replace(",", "."));

                }else if (data["nome_tipo"] === "FARMACIA") {

                    $('td', row).css('background-color', '#a3a9f5');
                    total_farmacia += parseFloat(data["total"].replace(",", "."));

                }else if (data["nome_tipo"] === "UNIMED") {

                    $('td', row).css('background-color', '#f5eea0');
                    total_unimed += parseFloat(data["total"].replace(",", "."));

                }
                $('.somacompras').html(total_compras.toFixed(2).toLocaleString());
                $('.somafarmacia').html((total_farmacia).toFixed(2).toLocaleString());
                $('.somaunimed').html((total_unimed).toFixed(2).toLocaleString());
            },
            "footerCallback": function ( row, data, start, end, display ) {
                var api = this.api(), data;
                // Remove the formatting to get integer data for summation
                var intVal = function ( i ) {
                    return typeof i === 'string' ?
                        i.replace(/[\$,]/g, '')*1 :
                        typeof i === 'number' ?
                            i : 0;
                };
                // Total geral
                total = api
                    .column( 3 )
                    .data()
                    .reduce( function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0 );
                // Update footer
                $(  api.column( 3 ).footer() ).html(
                    'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                );
            }
        });
    }else{
        table = $tabela_dados.DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            serverSide: false,
            responsive: true,
            autoWidth: true,
            JQueryUI: true,
            searching: true,
            order: [[1, "asc"]],
            ajax: {
                url: '../Adm/pages/arquivos/selecionar_dados.php',
                method: 'POST',
                data: function (data) {
                    data.mes = $("#C_mes").val();
                    data.empregador = $("#C_empregador").val();
                    data.tipo = $("#C_tipo").val();
                    data.divisao = divisao;
                    data.card1 = card1;
                    data.card2 = card2;
                    data.card3 = card3;
                    data.card4 = card4;
                    data.card5 = card5;
                    data.card6 = card6;
                },
                dataType: 'json'
            },
            columns: [
                {data: "associado"},
                {data: "nome"},
                {data: "nome_tipo"},
                {
                    data: "total",
                    render: $.fn.dataTable.render.number('.', ',', 2, 'R$ '),
                    className: "text-right"
                },
            ],
            pagingType: "full_numbers",
            language: {
                //url: "pages/conta/Portuguese-Brasil.json",
                decimal: ",",
                thousands: ".",
                zeroRecords: "Não ha dados",
                emptyTable: "Não ha dados.",
                infoEmpty: 'Zero registros',
                paginate: {
                    next: "Próximo",
                    previous: "Anterior",
                    first: "Primeiro",
                    last: "Último"
                },
                search: "Pesquisar",
                info: "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                infoFiltered: "(Filtrados de _MAX_ registros)",
                infoPostFix: "",
                lengthMenu: "_MENU_ resultados por página"
            },
            createdRow: function (row, data, index){
                // Now that we have the data and not the HTML it is cleaner
      
                if (data["nome_tipo"] === "COMPRAS") {

                    $('td', row).css('background-color', '#a9f5b4');
                    total_compras += parseFloat(data["total"].replace(",", "."));

                }else if (data["nome_tipo"] === "FARMACIA") {

                    $('td', row).css('background-color', '#f59a9a');
                    total_farmacia += parseFloat(data["total"].replace(",", "."));

                }else if (data["nome_tipo"] === "UNIMED") {

                    $('td', row).css('background-color', '#f5eea0');
                    total_unimed += parseFloat(data["total"].replace(",", "."));

                }
                $('.somacompras').html(total_compras.toLocaleString());
                $('.somafarmacia').html((total_farmacia).toLocaleString());
                $('.somaunimed').html((total_unimed).toLocaleString());
            },
            "footerCallback": function ( row, data, start, end, display ) {
                var api = this.api(), data;
                // Remove the formatting to get integer data for summation
                var intVal = function ( i ) {
                    return typeof i === 'string' ?
                        i.replace(/[\$,]/g, '')*1 :
                        typeof i === 'number' ?
                            i : 0;
                };
                // Total geral
                total = api
                    .column( 3 )
                    .data()
                    .reduce( function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0 );
                // Update footer
                $(  api.column( 3 ).footer() ).html(
                    'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                );
            }
        });
    }
}
