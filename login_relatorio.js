var ie = /*@cc_on!@*/false || !!document.documentMode;
if(ie) {
    alert("AVISO!, ATUALIZAMOS O SISTEMA! abre o 'GOOGLE CHROME' ou 'MOZILLA FIREFOX' e acesse 'www.makecard.com.br' para abrir o sistema do convenio, no INTERNET EXPLORER não funciona mais.");
}
var usuario;
var senha;
$(document).ready(function() {
    $("#btn-login").click(function (e) {
        waitingDialog.show('Carregando, aguarde ...');
        e.preventDefault();
        var tipo_loginx;
        debugger;
        usuario = $("#login-username").val();
        senha = $("#login-password").val();
        var divisao = $("#divisao").val();
        var divisao_nome = $("#divisao_nome").val();
        debugger;
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
                url: "localiza_convenio.php",
                type: "POST",
                async: true,
                cache: false,
                data: $('#loginform').serialize(),
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
                        sessionStorage.setItem('razaosocial', data.razaosocial);
                        sessionStorage.setItem('endereco', data.endereco);
                        sessionStorage.setItem('bairro', data.bairro);
                        sessionStorage.setItem('cidade', data.cidade);
                        waitingDialog.hide();
                        $.redirect('relatorio_vendas2.php', data);
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
    $("#recuperar_senha").click(function () {
        debugger;
        $.redirect('esqueci_a_senha.php', {usuario:$("#login-username").val()});
    })
    $("#btnEntrarAss").click(function (e) {
        e.preventDefault();
        var tipo_loginx;
        var cartao = $("#cod_carteira_login").val();
        var senha = $("#passasso").val();
        if (cartao === "" && senha === "") {
            if (browser_name === "iexplorer"){
                $.fallr.show({icon: 'error', content: '<p>Informe o cartão e a senha !</p>', position: 'center'});
            }else{
                swal({
                    title: "Atenção!",
                    text: "Informe o cartão e a senha !",
                    icon: "warning",
                    dangerMode: true
                })
            }
        } else if (cartao === "" && senha !== "") {
            if (browser_name === "iexplorer"){
                $.fallr.show({icon: 'error', content: '<p>Informe o cartão !</p>', position: 'center'});
            }else{
                swal({
                    title: "Atenção!",
                    text: "Informe o cartão ! !",
                    icon: "warning",
                    dangerMode: true
                })
            }
        } else if (cartao !== "" && senha === "") {
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
                url: "localiza_associado.php",
                type: "POST",
                async: true,
                cache: false,
                data: $('#form_index').serialize(),
                dataType: 'json',
                beforeSend: function () {
                    $("#divLoading").css("display", "block");
                },
                done: function () {
                    $("#divLoading").css("display", "none");
                },
                success: function (data) {
                    tipo_loginx = data.situacao;
                    if (tipo_loginx === "1") {
                        $.redirect('extratocartao/extrato.php', data);
                    } else if (tipo_loginx === "login cob") {
                        $.redirect('msg_cob.php', data);
                    } else if (tipo_loginx === "login inativo") {
                        $("#divLoading").css("display", "none");
                        if (browser_name === "iexplorer"){
                            $.fallr.show({icon: 'info', content: '<p>Convênio inativo !</p>', position: 'center'});
                        }else{
                            swal({
                                title: "Atenção!",
                                text: "Convênio inativo !",
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
    $('#cod_carteira').on('keyup', function(e){
        if (e.keyCode === 13) {
            e.preventDefault();
            $("#btn-login").click();
        }
    });
});