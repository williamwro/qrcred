$(document).ready(function(){
    // Cache DOM elements for better performance
    var $caminho = $("#caminho");
    var $matricula = $("#matricula");
    var $nome = $("#nome");
    var $empregador = $("#empregador");
    var $paginaConteudo = $('#pagina_conteudo');
    
    var caminho = $caminho.val();
    var matricula = $matricula.val();
    var nome = $nome.val();
    var empregador = $empregador.val();
    var divisao = sessionStorage.getItem("divisao");
    var divisao_nome = sessionStorage.getItem("divisao_nome");
    var usuario_global = sessionStorage.getItem("usuario_global");
    var usuario_cod = sessionStorage.getItem("usuario_cod");
    
    // Inicializar UserManager globalmente se o usuário estiver logado
    // Aguardar 2 segundos para garantir que o login seja completamente processado
    if (usuario_global && usuario_cod && typeof UserManager !== 'undefined') {
        setTimeout(function() {
            console.log('Inicializando UserManager após login...');
            window.userManager = new UserManager();
        }, 2000);
    }
   
    $('#title_inicio').html("SasCred");
    $('#texto_footer').html("Makecard 2025.");

    $('#pagina_conteudo').load('pages/home.html');
    if (usuario_global){
        $('#link_dashboard').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/home.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_extrato_associado').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/extrato/extrato.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_convenio_manutencao').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/convenio/convenio_read.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_convenio_relatorio').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $.redirect('pages/convenio/gerador_pdf_convenios.php',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod}, "POST", "_blank");
        });
        $('#link_associado').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/associado/associado_read.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_producao').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/producao/producao_read.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_transferencia').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/conta/transferencia.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_manutencao').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/conta/manutencao.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_cadastrocartoes').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/cartoes/cartoes_cadastro.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_manutencaocartao').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/cartoes/cartoes_read.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_conv_totais').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/producao/producao_read_totais.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_total_meses').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/producao/producao_read_totalmes.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_arqdesc').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/arquivos/gerar_arquivos.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_usuario_manutencao').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/usuarios/usuarios.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_retorno').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/arquivos/retorno.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_divisao').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/divisao/divisao.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_empregador').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/empregador/empregador.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_categoria').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/categoria_convenio/categoria.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_funcao').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/funcao/funcao.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_cadcartoes').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/cartoes/gerar_arquivo_cartoes.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_bloquear_mes').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/manutencao/bloquear_mes.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_recibos').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/recibos/recibos.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_cartoesbloqueados').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/cartoes/cartoes_bloqueados.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_cobranca').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/cobranca/cobranca.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_cobranca').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/producao/soma_mes.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_soma_mes').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/producao/producao_soma_mes.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_estorno_conta').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/conta/estorno.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_cheques').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/cheques/cheques.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_antecipacao').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/antecipacao/antecipacao_read.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_email_app').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/email_app/email_app.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_taxa_antecipacao').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/taxa_antecipacao/taxa_antecipacao_app.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_pendentes').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/associados_pendentes/associados_pendentes.html', {divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_especialidades').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/especialidades/especialidades.html', {divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_tipo_especialidade').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/tipo_especialidade/tipo_especialidade.html', {divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        $('#link_profissionais').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/profissionais/profissionais.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        //link_assinaturas_digitais
        $('#link_assinaturas_digitais').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/assinaturas_digitais/assinaturas_digitais_read.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
         //link_agendamentos
         $('#link_agendamentos').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/agendamento/agendamento_read.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        //link_assoc_data
        $('#link_assoc_data').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/producao/associados_data.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        //link_taxa_cartao
        $('#link_taxa_cartao').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/txcartao/valor_taxa_gerenciar.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
        //link_bloqueios_solicitados  
        $('#link_bloqueios_solicitados').click(function(){
            $.ajaxSetup({
                cache:true
            });
            $('#pagina_conteudo').load('pages/bloqueios_solicitados/bloqueios_solicitados.html',{divisao: divisao,divisao_nome:divisao_nome,usuario_global:usuario_global,usuario_cod:usuario_cod},function () {
            });
        });
    }else{

        $.redirect('../index.html');

    }
    $('#botao_sair').click(function (){
        sessionStorage.clear();
        $.redirect('../index.html');
    })

    // Função de logout que finaliza a sessão corretamente
    window.logout = function() {
        var usuario_cod = sessionStorage.getItem('usuario_cod');
        var session_id = sessionStorage.getItem('session_id');
        
        if (usuario_cod && session_id && typeof userManager !== 'undefined') {
            // Finalizar TODAS as sessões do usuário no servidor
            $.ajax({
                url: 'pages/usuarios/user_heartbeat.php',
                method: 'POST',
                data: {
                    action: 'close_all_user_sessions',
                    usuario_cod: usuario_cod,
                    session_id: session_id
                },
                dataType: 'json',
                async: false, // Importante: síncrono para garantir execução
                success: function(response) {
                    console.log('Sessão finalizada com sucesso');
                },
                error: function() {
                    console.warn('Erro ao finalizar sessão no servidor');
                },
                complete: function() {
                    // Limpar dados locais e redirecionar
                    sessionStorage.clear();
                    location.href = '../index.html';
                }
            });
        } else {
            // Se não há dados de sessão, apenas redirecionar
            sessionStorage.clear();
            location.href = '../index.html';
        }
    };

});