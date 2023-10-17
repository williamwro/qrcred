var table;
var usuario;
var senha;
var windowsize;
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
    passa_valores()
    $('input[type="text"], select').val('');
    $("#userconv").val("");
    $("#passconv").val("");
    $("#txtSenhaCartao").val("");
    $("#cod_carteira_login").val("");
    $("#divLoading").css("display", "none");
    $('.navbar-nav li a').on('click', function(){
        if(!$( this ).hasClass('dropdown-toggle')){
            $('.navbar-collapse').collapse('hide');
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
                $.redirect('index.html');
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
                            $.redirect('index.html');
                        }else{
                            $.redirect('pagina_principal.php', data);
                        }

                    } else if (tipo_loginx === "login cob") {
                        $.redirect('msg_cob.php', data);
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
                        $.redirect('extratocartao/extrato.php', data);
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
    $("#btn-login").click(function (e) {
        waitingDialog.show('Carregando, aguarde ...');
        e.preventDefault();
        var tipo_loginx;
      
        usuario = $("#login-username").val();
        senha = $("#login-password").val();
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
                data: $('#form_administrativo').serialize(),
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
                        $.redirect('Adm/index.php', data);
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
                }
            });
        }
    });
    // econstroi uma datatabe no primeiro carregamento da tela
    table = $('#tabela_rede_conveniada').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": false,
        "serverSide": false,
        "responsive": true,
        "autoWidth": true,
        autoFill: true,
        "ajax": {
            "url": 'Adm/pages/convenio/convenio_categorias_app.php',
            "method": 'POST',
            "data": "",
            "dataType": 'json'
        },
        "order": [[ 1, "asc" ]],
        "columns": [
            { "data": "nome_categoria" },
            { "data": "razaosocial" },
            { "data": "endereco" },
            { "data": "bairro" },
            { "data": "telefone" },
        ],
        "pagingType": "full_numbers",
        language: {
            //url: "pages/conta/Portuguese-Brasil.json"
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
        }
    });
});
function passa_valores()
{
    var user_convenio=document.getElementById("userconv").value;
    localStorage.setItem("texto_user_convenio",user_convenio);

    var pass_convenio=document.getElementById("passconv").value;
    localStorage.setItem("texto_pass_convenio",pass_convenio);

    return false;
}
