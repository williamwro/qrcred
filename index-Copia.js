var table;
var usuario;
var senha;
var windowsize;

/**
 * FUNÇÃO DE SEGURANÇA: Limpar dados sensíveis da memória
 * Evita que senhas e números de cartão fiquem salvos no navegador
 */
function limparDadosSensiveis() {
    // Limpar campos de cartão e senha
    $("#txtCartao, #cod_cart").val("");
    $("#txtSenhaCartao, #senhacartao").val("");
    
    // Limpar variáveis globais se existirem
    if (typeof cartao_ !== 'undefined') cartao_ = null;
    if (typeof senha_ !== 'undefined') senha_ = null;
    
    // Forçar garbage collection se suportado
    if (window.gc && typeof window.gc === 'function') {
        window.gc();
    }
    
    console.log("🔒 Dados sensíveis limpos da memória por segurança");
}
// Função para substituir redirectWithData - cria form POST e redireciona
function redirectWithData(url, data) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    
    // Adicionar dados como campos hidden
    if (data) {
        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = data[key];
                form.appendChild(input);
            }
        }
    }
    
    document.body.appendChild(form);
    form.submit();
}

$(document).ready(function(){
    var ie = /*@cc_on!@*/false || !!document.documentMode;
    var browser_name;
    if(ie) {
        alert("AVISO!, ATUALIZAMOS O SISTEMA! abre o 'GOOGLE CHROME' ou 'MOZILLA FIREFOX' e acesse 'www.makecard.com.br' para abrir o sistema do convenio, no INTERNET EXPLORER não funciona mais.");
        browser_name = "iexplorer";
        exit();
    }else{
        browser_name = "";
    }
    // Só executar passa_valores se os elementos existirem
    if (document.getElementById("userconv") || document.getElementById("userConvenio")) {
        passa_valores();
    }
    
    $('input[type="text"], select').val('');
    
    // Limpar campos do layout antigo se existirem
    if ($("#userconv").length) $("#userconv").val("");
    if ($("#passconv").length) $("#passconv").val("");
    if ($("#txtSenhaCartao").length) $("#txtSenhaCartao").val("");
    if ($("#txtCartao").length) $("#txtCartao").val("");
    if ($("#cod_carteira_login").length) $("#cod_carteira_login").val("");
    
    // SEGURANÇA EXTRA: Limpar campos sensíveis da memória
    // Forçar garbage collection do JavaScript (se suportado)
    if (window.gc && typeof window.gc === 'function') {
        window.gc();
    }
    
    // MEDIDAS DE SEGURANÇA EXTRAS
    // Limpar dados sensíveis quando a página for fechada/recarregada
    $(window).on('beforeunload unload', limparDadosSensiveis);
    
    // Limpar dados sensíveis a cada 60 segundos (medida extra de segurança)
    setInterval(function() {
        // Só limpar se não há formulário ativo sendo preenchido
        if (!$('#txtCartao').is(':focus') && !$('#txtSenhaCartao').is(':focus') && 
            !$('#cod_cart').is(':focus') && !$('#senhacartao').is(':focus')) {
            limparDadosSensiveis();
        }
    }, 60000);
    $("#divLoading").css("display", "none");
    $('.navbar-nav li a').on('click', function(){
        if(!$( this ).hasClass('dropdown-toggle')){
            $('.navbar-collapse').collapse('hide');
        }
    });
    
    // Inicializar DataTable apenas se não estivermos no layout moderno
    // O layout moderno tem sua própria inicialização
    if (!document.getElementById('mainNavbar')) {
        // Inicializar tabela após delay apenas no layout antigo
        setTimeout(initRedeConveniadaTable, 500);
    } else {
        // ===== INICIALIZAÇÃO DO LAYOUT MODERNO =====
        console.log('Layout moderno detectado - Inicializando funções modernas...');
        initializeModernApp();
        
        // Inicializar DataTable para o layout moderno após um delay
        setTimeout(function() {
            if (document.getElementById('rede-conveniada')) {
                console.log('Inicializando DataTable para layout moderno...');
                initRedeConveniadaTable();
            }
        }, 1000);
    }
    $("#recuparar_senha_admin").click(function (e) {
        e.preventDefault();
        var usuarioadmin = $("#login-username").val();
        if(usuarioadmin === "") {
            swal.fire({
                title: "Atuação!",
                text: "Favor o informar o usuário !",
                icon: "warning",
                dangerMode: true
            })
            exit();
        }else{
            redirectWithData('esqueci_a_senha.php', {usuario:usuarioadmin});
        }
    });
    $("#btnEntrar").click(function (e) {
        e.preventDefault();
        var tipo_loginx;
        var usuario = $("#userconv").val();
        var senha = $("#passconv").val();
        if (usuario === "" && senha === "") {
            if (browser_name === "iexplorer"){

                alert("AVISO!, ATUALIZAMOS O SISTEMA! abre o 'GOOGLE CHROME' ou 'MOZILLA FIREFOX' e acesse 'www.makecard.com.br' para abrir o sistema do convenio, no INTERNET EXPLORER não funciona mais.");
                redirectWithData('index.html');
                exit();
            }else{
                swal({
                    title: "Atenção!",
                    text: "Informe o usuário e a senha !",
                    icon: "warning",
                    dangerMode: true
                })
            }
        } else if (usuario === "" && senha !== "") {
            if (browser_name === "iexplorer"){
                $.fallr.show({icon: 'error', content: '<p>Informe o usuário !</p>', position: 'center'});
            }else{
                swal({
                    title: "Atenção!",
                    text: "Informe o usuário !",
                    icon: "warning",
                    dangerMode: true
                })
            }
        } else if (usuario !== "" && senha === "") {
            if (browser_name === "iexplorer"){
                $.fallr.show({icon: 'error', content: '<p>Informe a senha !</p>', position: 'center'});
            }else{
                swal({
                    title: "Atenção!",
                    text: "Informe a senha !",
                    icon: "warning",
                    dangerMode: true
                })
            }
        } else {
            $.ajax({
                url: "localiza_convenio.php",
                type: "POST",
                async: true,
                cache: false,
                data: {
                    userconv: usuario,
                    passconv: senha
                },
                dataType: 'json',
                beforeSend: function () {
                    $("#divLoading").css("display", "block");
                },
                done: function () {
                    $("#divLoading").css("display", "none");
                },
                success: function (data) {
                    tipo_loginx = data.tipo_login;
                    if (tipo_loginx === "login sucesso") {
                        if (browser_name === "iexplorer"){
                            $.fallr.show({icon: 'info', content: '<p>AVISO!, ATUALIZAMOS O SISTEMA! abre o \'GOOGLE CHROME\' ou \'MOZILLA FIREFOX\' e acesse \'www.makecard.com.br\' para abrir o sistema do convenio, no INTERNET EXPLORER não funciona mais.QUALQUER DÚVIDA LIGUE (35)99812-0032</p>', position: 'center'});
                            $("#divLoading").css("display", "none");
                            redirectWithData('index.html');
                        }else{
                            redirectWithData('pagina_principal.php', data);
                        }

                    } else if (tipo_loginx === "login cob") {
                        redirectWithData('msg_cob.php', data);
                    } else if (tipo_loginx === "login inativo") {
                        $("#divLoading").css("display", "none");
                        if (browser_name === "iexplorer"){
                            $.fallr.show({icon: 'info', content: '<p>Informe a senha !</p>', position: 'center'});
                        }else{
                            swal({
                                title: "Atenção!",
                                text: "Informe a senha !",
                                icon: "warning",
                                dangerMode: true
                            })
                        }
                    } else if (tipo_loginx === "login incorreto") {
                        $("#divLoading").css("display", "none");
                        if (browser_name === "iexplorer"){
                            $.fallr.show({icon: 'info', content: '<p>Login Incorreto !</p>', position: 'center'});
                        }else{
                            swal({
                                title: "Atenção!",
                                text: "Login Incorreto !",
                                icon: "warning",
                                dangerMode: true
                            })
                        }
                    }
                }
            });
        }
    });
    $("#btnEntrarAss").click(function (e) {
        waitingDialog.show('Carregando, aguarde ...');
        e.preventDefault();
        var tipo_loginx;
        var cartao = $("#txtCartao").val();
        var senha = $("#txtSenhaCartao").val();
        if (cartao === "" && senha === "") {
            if (browser_name === "iexplorer"){
                waitingDialog.hide();
                $.fallr.show({icon: 'error', content: '<p>Informe o cartão e a senha !</p>', position: 'center'});
            }else{
                waitingDialog.hide();
                swal({
                    title: "Atenção!",
                    text: "Informe o cartão e a senha !",
                    icon: "warning",
                    dangerMode: true
                })
            }
        } else if (cartao === "" && senha !== "") {
            if (browser_name === "iexplorer"){
                waitingDialog.hide();
                $.fallr.show({icon: 'error', content: '<p>Informe o cartão !</p>', position: 'center'});
            }else{
                waitingDialog.hide();
                swal({
                    title: "Atenção!",
                    text: "Informe o cartão ! !",
                    icon: "warning",
                    dangerMode: true
                })
            }
        } else if (cartao !== "" && senha === "") {
            if (browser_name === "iexplorer"){
                waitingDialog.hide();
                $.fallr.show({icon: 'error', content: '<p>Informe a senha !</p>', position: 'center'});
            }else{
                waitingDialog.hide();
                swal({
                    title: "Atenção!",
                    text: "Informe a senha !",
                    icon: "warning",
                    dangerMode: true
                })
            }
        } else {
            $.ajax({
                url: "localiza_associado_extrato.php",
                type: "POST",
                async: true,
                cache: false,
                data: $('#form_associado').serialize(),
                dataType: 'json',
                beforeSend: function () {
                    $("#divLoading").css("display", "block");
                    
                    // SEGURANÇA: Limpar campos sensíveis imediatamente após serialização
                    setTimeout(function() {
                        $("#txtCartao").val("");
                        $("#txtSenhaCartao").val("");
                        // Forçar limpeza da memória do formulário
                        document.getElementById('form_associado').reset();
                    }, 100);
                },
                done: function () {
                    $("#divLoading").css("display", "none");
                },
                success: function (data) {
                    tipo_loginx = data.situacao;
                    debugger;
                    if (tipo_loginx === 1) {
                        waitingDialog.hide();
                        $("#divLoading").css("display", "none");
                        redirectWithData('extratocartao/extrato.php', data);
                    } else if (tipo_loginx === 2) {
                        waitingDialog.hide();
                        $("#divLoading").css("display", "none");
                        if (browser_name === "iexplorer"){
                            $.fallr.show({icon: 'info', content: '<p>Login Incorreto !</p>', position: 'center'});
                        }else{
                            swal({
                                title: "Atenção!",
                                text: "Login Incorreto !",
                                icon: "warning",
                                dangerMode: true
                            })
                        }
                    } else if (tipo_loginx === 3) {
                        waitingDialog.hide();
                        $("#divLoading").css("display", "none");
                        if (browser_name === "iexplorer"){
                            $.fallr.show({icon: 'info', content: '<p>Senha Incorreta !</p>', position: 'center'});
                        }else{
                            swal({
                                title: "Atenção!",
                                text: "Senha Incorreta !",
                                icon: "warning",
                                dangerMode: true
                            })
                        }
                    } 
                }
            });
        }
    });
    
    // Login administrativo para layout moderno (formulário com submit)
    $(document).on('submit', '#loginAdminForm', function (e) {
        e.preventDefault();
        
        showModernLoading();
        var tipo_loginx;
      
        // Pegar valores dos campos do layout moderno
        usuario = $("#userAdmin").val();
        senha = $("#passwordAdmin").val();
        
        if (usuario === "" && senha === "") {
            Swal.fire({
                icon: 'error',
                title: 'Atenção!',
                text: 'Informe o usuário e a senha !'
            });
            hideModernLoading();
            return;
        } else if (usuario === "" && senha !== "") {
            Swal.fire({
                icon: 'error',
                title: "Atenção!",
                text: "Informe o usuário !"
            });
            hideModernLoading();
            return;
        } else if (usuario !== "" && senha === "") {
            Swal.fire({
                title: "Atenção!",
                text: "Informe a senha !",
                icon: "error"
            });
            hideModernLoading();
            return;
        }
        
        // Fazer login
        $.ajax({
            url: "login_adm_localiza-Copia.php",
            type: "POST",
            async: true,
            cache: false,
            timeout: 30000,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            data: {
                'login-username': usuario,
                'password': senha,
                'autorizado': 'sim'
            },
            dataType: 'json',
            beforeSend: function () {
                $("#divLoading").css("display", "block");
            },
            done: function () {
                $("#divLoading").css("display", "none");
            },
            success: function (data) {
                tipo_loginx = data.tipo_login;
                if (tipo_loginx === "login sucesso") {
                    sessionStorage.setItem('divisao', data.divisao);
                    sessionStorage.setItem('divisao_nome', data.divisao_nome);
                    sessionStorage.setItem('usuario_global', data.Username);
                    sessionStorage.setItem('passuser', data.senha);
                    sessionStorage.setItem('usuario_cod', data.codigo);
                    hideModernLoading();
                    
                    // Fechar modal se estiver aberto
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('loginAdminModal'));
                        if (modal) {
                            modal.hide();
                        }
                    }
                    
                    redirectWithData('Adm/index.php', data);
                } else if (tipo_loginx === "login inativo") {
                    $("#divLoading").css("display", "none");
                    Swal.fire({
                        icon: 'error',
                        title: 'Atenção!',
                        text: 'Login Inativo !'
                    });
                    hideModernLoading();
                } else if (tipo_loginx === "login bloqueado") {
                    $("#divLoading").css("display", "none");
                    Swal.fire({
                        title: "Atenção!",
                        text: "Login bloqueado !",
                        icon: "error"
                    });
                    hideModernLoading();
                } else if (tipo_loginx === "login incorreto") {
                    $("#divLoading").css("display", "none");
                    Swal.fire({
                        title: "Atenção!",
                        text: "Login Incorreto !",
                        icon: "error",
                    });
                    hideModernLoading();
                }
            },
            error: function(xhr, status, error) {
                $("#divLoading").css("display", "none");
                hideModernLoading();
                
                if (status === 'error' && (error.includes('ERR_NETWORK_CHANGED') || xhr.status === 0)) {
                    setTimeout(function() {
                        console.log('Tentando novamente devido a ERR_NETWORK_CHANGED...');
                        $('#loginAdminForm').submit();
                    }, 2000);
                    
                    Swal.fire({
                        title: "Reconectando...",
                        text: "Problema de rede detectado. Tentando novamente...",
                        icon: "info",
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    Swal.fire({
                        title: "Erro de Conexão!",
                        text: "Erro ao conectar com o servidor. Tente novamente.",
                        icon: "error",
                        confirmButtonText: "Tentar Novamente"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#loginAdminForm').submit();
                        }
                    });
                }
            }
        });
    });
    
    // Login administrativo - compatível com ambos os layouts
    $(document).on('click', '#btn-login', function (e) {
        waitingDialog.show('Carregando, aguarde ...');
        e.preventDefault();
        var tipo_loginx;
      
        // Verificar qual layout está sendo usado
        var userElement = $("#login-username").length ? $("#login-username") : $("#userAdmin");
        var passElement = $("#login-password").length ? $("#login-password") : $("#passwordAdmin");
        
        usuario = userElement.val();
        senha = passElement.val();
        var divisao = $("#divisao").val();
        var divisao_nome = $("#divisao_nome").val();
      
        if (usuario === "" && senha === "") {
            Swal.fire({
                icon: 'error',
                title: 'Atenção!',
                text: 'Informe o usuário e a senha !'
            });
            waitingDialog.hide();
        } else if (usuario === "" && senha !== "") {
            Swal.fire({
                icon: 'error',
                title: "Atenção!",
                text: "Informe o usuário !"
            });
            waitingDialog.hide();
        } else if (usuario !== "" && senha === "") {
            Swal.fire({
                title: "Atenção!",
                text: "Informe a senha !",
                icon: "error"
            });
            waitingDialog.hide();
        } else {
            $.ajax({
                url: "login_adm_localiza-Copia.php",
                type: "POST",
                async: true,
                cache: false,
                timeout: 30000,
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                },
                data: {
                    'login-username': usuario,
                    'password': senha,
                    'autorizado': 'sim'
                },
                dataType: 'json',
                beforeSend: function () {
                    $("#divLoading").css("display", "block");
                },
                done: function () {
                    $("#divLoading").css("display", "none");
                },
                success: function (data) {
                    tipo_loginx = data.tipo_login;
                    if (tipo_loginx === "login sucesso") {
                        sessionStorage.setItem('divisao', data.divisao);
                         sessionStorage.setItem('divisao_nome', data.divisao_nome);
                        sessionStorage.setItem('usuario_global', data.Username);
                        sessionStorage.setItem('passuser', data.senha);
                        sessionStorage.setItem('usuario_cod', data.codigo);
                        waitingDialog.hide();
                        redirectWithData('Adm/index.php', data);
                    } else if (tipo_loginx === "login inativo") {
                        $("#divLoading").css("display", "none");
                        Swal.fire({
                            icon: 'error',
                            title: 'Atenção!',
                            text: 'Login Inativo !'
                        });
                        waitingDialog.hide();
                    } else if (tipo_loginx === "login bloqueado") {
                        $("#divLoading").css("display", "none");

                        Swal.fire({
                            title: "Atenção!",
                            text: "Login bloqueado !",
                            icon: "error"
                        });
                        waitingDialog.hide();
                    } else if (tipo_loginx === "login incorreto") {
                        $("#divLoading").css("display", "none");
                        Swal.fire({
                            title: "Atenção!",
                            text: "Login Incorreto !",
                            icon: "error",
                        });
                        waitingDialog.hide();
                    }
                },
                error: function(xhr, status, error) {
                    $("#divLoading").css("display", "none");
                    waitingDialog.hide();
                    
                    // Tratamento específico para ERR_NETWORK_CHANGED
                    if (status === 'error' && (error.includes('ERR_NETWORK_CHANGED') || xhr.status === 0)) {
                        // Retry automático após 2 segundos
                        setTimeout(function() {
                            console.log('Tentando novamente devido a ERR_NETWORK_CHANGED...');
                            $('#btnEntrar').click();
                        }, 2000);
                        
                        Swal.fire({
                            title: "Reconectando...",
                            text: "Problema de rede detectado. Tentando novamente...",
                            icon: "info",
                            showConfirmButton: false,
                            timer: 2000
                        });
                    } else {
                        // Outros erros
                        Swal.fire({
                            title: "Erro de Conexão!",
                            text: "Erro ao conectar com o servidor. Tente novamente.",
                            icon: "error",
                            confirmButtonText: "Tentar Novamente"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $('#btnEntrar').click();
                            }
                        });
                    }
                }
            });
        }
    });
        // Função para carregar dados e inicializar DataTable
    function initRedeConveniadaTable() {
        console.log('Iniciando carregamento da rede conveniada...');
        
        // Verificar qual layout está sendo usado
        const isModernLayout = document.getElementById('redeTable') !== null;
        const tableId = isModernLayout ? '#redeTable' : '#tabela_rede_conveniada';
        const statusId = isModernLayout ? '#tableInfo' : '#status_tabela';
        
        $(statusId).html(isModernLayout ? 'Carregando dados...' : '<div class="alert alert-info">Carregando dados...</div>');
        
        // Destruir tabela existente se houver
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
            $(tableId + ' tbody').empty();
        }
        
        // Primeiro, carregar os dados via AJAX
        $.ajax({
            url: 'rede_conveniada_dados.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Resposta recebida:', response);
                
                if (response && response.data && Array.isArray(response.data)) {
                    console.log('Total de registros:', response.data.length);
                    
                    // Limpar tbody e popular com dados
                    var tbody = $(tableId + ' tbody');
                    tbody.empty();
                    
                    // Adicionar cada linha - ajustar colunas conforme layout
                    response.data.forEach(function(item) {
                        var row;
                        if (isModernLayout) {
                            // Layout moderno: Profissional, Especialidade, Endereço, Telefone, Ações
                            row = '<tr>' +
                                '<td>' + (item.profissional || item.convenio || '') + '</td>' +
                                '<td>' + (item.especialidade || '') + '</td>' +
                                '<td>' + (item.endereco || 'Não informado') + '</td>' +
                                '<td>' + (item.telefone || 'Não informado') + '</td>' +
                                '<td><button class="btn btn-sm btn-outline-primary" onclick="viewProfessional(\'' + (item.profissional || item.convenio || '') + '\')"><i class="bi bi-eye"></i></button></td>' +
                                '</tr>';
                        } else {
                            // Layout antigo: CONVÊNIO, ESPECIALIDADE, PROFISSIONAL, TIPO ESPECIALIDADE
                            row = '<tr>' +
                                '<td>' + (item.convenio || '') + '</td>' +
                                '<td>' + (item.especialidade || '') + '</td>' +
                                '<td>' + (item.profissional || '') + '</td>' +
                                '<td>' + (item.tipo_especialidade || '') + '</td>' +
                                '</tr>';
                        }
                        tbody.append(row);
                    });
                    
                    console.log('Linhas adicionadas à tabela');
                    
                    // Agora inicializar o DataTable
                    try {
                        table = $(tableId).DataTable({
                            "processing": false,
                            "serverSide": false,
                            "paging": true,
                            "searching": true,
                            "ordering": true,
                            "order": [[ 0, "asc" ]],
                            "pageLength": 25,
                            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                            "language": {
                                "search": "Pesquisar:",
                                "lengthMenu": "Mostrar _MENU_ registros por página",
                                "info": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                                "infoEmpty": "Mostrando 0 até 0 de 0 registros",
                                "infoFiltered": "(Filtrados de _MAX_ registros)",
                                "zeroRecords": "Nenhum registro encontrado",
                                "emptyTable": "Nenhum dado disponível na tabela",
                                "paginate": {
                                    "first": "Primeiro",
                                    "previous": "Anterior",
                                    "next": "Próximo",
                                    "last": "Último"
                                }
                            },
                            "responsive": true,
                            "autoWidth": false
                        });
                        
                        if (isModernLayout) {
                            $(statusId).html('Tabela carregada com ' + response.data.length + ' registros!');
                        } else {
                            $(statusId).html('<div class="alert alert-success">Tabela carregada com ' + response.data.length + ' registros!</div>');
                        }
                        console.log('DataTable inicializado com sucesso!');
                        
                        // Esconder status após 3 segundos
                        setTimeout(function() {
                            $(statusId).fadeOut();
                        }, 3000);
                        
                    } catch(e) {
                        console.error('Erro ao inicializar DataTable:', e);
                        if (isModernLayout) {
                            $(statusId).html('Erro ao inicializar tabela: ' + e.message);
                        } else {
                            $(statusId).html('<div class="alert alert-danger">Erro ao inicializar tabela: ' + e.message + '</div>');
                        }
                    }
                    
                } else {
                    console.error('Formato de dados inválido:', response);
                    if (isModernLayout) {
                        $(statusId).html('Nenhum dado encontrado');
                    } else {
                        $(statusId).html('<div class="alert alert-warning">Nenhum dado encontrado</div>');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro ao carregar dados:', xhr, status, error);
                if (isModernLayout) {
                    $(statusId).html('Erro ao carregar dados: ' + error + ' (Status: ' + xhr.status + ')');
                } else {
                    $(statusId).html('<div class="alert alert-danger">Erro ao carregar dados: ' + error + ' (Status: ' + xhr.status + ')</div>');
                }
            }
        });
    }
    
        // Esta linha foi movida para cima para evitar duplicação
});
function passa_valores()
{
    // Verificar se estamos no layout antigo ou moderno
    var userElement = document.getElementById("userconv") || document.getElementById("userConvenio");
    var passElement = document.getElementById("passconv") || document.getElementById("passwordConvenio");
    
    if (userElement) {
        var user_convenio = userElement.value;
        localStorage.setItem("texto_user_convenio", user_convenio);
    }

    if (passElement) {
        var pass_convenio = passElement.value;
        localStorage.setItem("texto_pass_convenio", pass_convenio);
    }

    return false;
}

// ===== FUNÇÕES ADICIONADAS PARA O NOVO LAYOUT MODERNO =====

// ===== INICIALIZAÇÃO DA APLICAÇÃO MODERNA =====
function initializeModernApp() {
    initializeModernNavigation();
    initializeScrollEffects();
    initializeModernForms();
    initializeModernModals();
    initializeModernAnimations();
}

// ===== NAVEGAÇÃO MODERNA =====
function initializeModernNavigation() {
    const navbar = document.getElementById('mainNavbar');
    const navLinks = document.querySelectorAll('.nav-link[data-section]');
    
    if (!navLinks.length) return; // Se não há links com data-section, não executa
    
    // Smooth scrolling para links de navegação
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-section');
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                // Atualizar link ativo
                navLinks.forEach(nl => nl.classList.remove('active'));
                this.classList.add('active');
                
                // Scroll suave para a seção
                const offsetTop = targetSection.offsetTop - (navbar ? navbar.offsetHeight : 0) - 20;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
                
                // Fechar menu mobile se estiver aberto
                const navbarCollapse = document.getElementById('navbarNav');
                if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                        const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                        bsCollapse.hide();
                    }
                }
            }
        });
    });
    
    // Atualizar link ativo no scroll
    window.addEventListener('scroll', updateActiveNavLink);
}

// ===== EFEITOS DE SCROLL =====
function initializeScrollEffects() {
    const navbar = document.getElementById('mainNavbar');
    
    if (!navbar) return; // Se não há navbar, não executa
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
}

// ===== ATUALIZAR LINK ATIVO NA NAVEGAÇÃO =====
function updateActiveNavLink() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link[data-section]');
    const navbar = document.getElementById('mainNavbar');
    
    if (!sections.length || !navLinks.length) return;
    
    const scrollPos = window.scrollY + (navbar ? navbar.offsetHeight : 0) + 50;
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.offsetHeight;
        const sectionId = section.getAttribute('id');
        
        if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('data-section') === sectionId) {
                    link.classList.add('active');
                }
            });
        }
    });
}

// ===== FORMULÁRIOS MODERNOS =====
function initializeModernForms() {
    // Formulário de contato
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactForm);
    }
}

// ===== MANIPULADOR DO FORMULÁRIO DE CONTATO =====
function handleContactForm(e) {
    e.preventDefault();
    showModernLoading();
    
    // Simular envio do formulário
    setTimeout(() => {
        hideModernLoading();
        showModernAlert('Mensagem enviada com sucesso! Entraremos em contato em breve.', 'success');
        e.target.reset();
    }, 2000);
}

// ===== MODAIS MODERNOS =====
function initializeModernModals() {
    // Resetar formulários quando modais são fechados
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            const forms = this.querySelectorAll('form');
            forms.forEach(form => form.reset());
        });
    });
}

// ===== ANIMAÇÕES MODERNAS =====
function initializeModernAnimations() {
    // Intersection Observer para animações de scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
            }
        });
    }, observerOptions);
    
    // Observar elementos para animação
    const animateElements = document.querySelectorAll('.card, .contact-item, .feature-item');
    animateElements.forEach(el => observer.observe(el));
}

// ===== CONTROLE DE LOADING MODERNO =====
function showModernLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.add('show');
    }
}

function hideModernLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.remove('show');
    }
}

// ===== SISTEMA DE ALERTAS MODERNO =====
function showModernAlert(message, type = 'info') {
    // Criar elemento de alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Remover automaticamente após 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// ===== FUNÇÕES UTILITÁRIAS MODERNAS =====
// Função debounce para eventos de scroll
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Utilitários responsivos
function isMobile() {
    return window.innerWidth <= 768;
}

function isTablet() {
    return window.innerWidth > 768 && window.innerWidth <= 1024;
}

function isDesktop() {
    return window.innerWidth > 1024;
}

// ===== AÇÕES PARA PROFISSIONAIS (REDE CONVENIADA) =====
function viewProfessional(name) {
    showModernAlert(`Visualizando detalhes de: ${name}`, 'info');
}

function contactProfessional(phone) {
    if (confirm(`Deseja ligar para ${phone}?`)) {
        window.location.href = `tel:${phone.replace(/\D/g, '')}`;
    }
}

// ===== TRATAMENTO DE ERROS MODERNO =====
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    hideModernLoading();
});

// ===== ACESSIBILIDADE MODERNA =====
// Navegação por teclado para modais
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
        });
    }
});

// Gerenciamento de foco para melhor acessibilidade
document.addEventListener('shown.bs.modal', function(e) {
    const firstInput = e.target.querySelector('input, textarea, select');
    if (firstInput) {
        firstInput.focus();
    }
});

// ===== LOGIN EMPREGADOR =====
$("#btn-login-empregador").click(function() {
    var usuario = $("#userEmpregador").val().trim();
    var senha = $("#passwordEmpregador").val().trim();
    
    if (usuario === "" || senha === "") {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção',
            text: 'Informe o usuário e a senha!'
        });
        return;
    }
    
    waitingDialog.show('Autenticando...');
    
    $.ajax({
        url: "login_empregador.php",
        type: "POST",
        data: {
            'usuario': usuario,
            'senha': senha
        },
        dataType: 'json',
        success: function(data) {
            waitingDialog.hide();
            
            if (data.status === "sucesso") {
                // Salvar dados do empregador no sessionStorage
                sessionStorage.setItem('empregador_id', data.id);
                sessionStorage.setItem('empregador_nome', data.nome);
                sessionStorage.setItem('empregador_divisao', data.divisao);
                sessionStorage.setItem('empregador_usuario', data.usuario);
                sessionStorage.setItem('tipo_login', 'empregador');
                
                // Redirecionar para a página de consulta do empregador
                window.location.href = 'empregador/consultar_colaborador.html';
                
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: data.message || 'Usuário ou senha incorretos!'
                });
            }
        },
        error: function(xhr, status, error) {
            waitingDialog.hide();
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro ao conectar com o servidor. Tente novamente.'
            });
            console.error("Erro no login empregador:", error);
        }
    });
});

// ===== INICIALIZAÇÃO AUTOMÁTICA PARA O NOVO LAYOUT =====
// Esta seção foi consolidada no $(document).ready() principal acima
