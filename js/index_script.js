$(document).ready(function() {
    var browser_name;
    if ($.browser.chrome) {
        //alert("Chrome , "+$.browser.version);
        browser_name = "chrome";
    }else if (!!navigator.userAgent.match(/Trident\/7\./)) {
        //alert("Explorer, "+$.browser.version);
        browser_name = "iexplorer";
    }else if ($.browser.mozilla) {
        //alert("Firefox, "+$.browser.version);
        browser_name = "firefox";
    }else if ($.browser.opera) {
        //alert("Opera, "+$.browser.version);
        browser_name = "opera";
    }else if ($.browser.safari) {
        //alert("safari, "+$.browser.version);
        browser_name = "safari";
    }
    $("#btnEntrar").click(function (e) {
        e.preventDefault();
        var tipo_loginx;
        var usuario = $("#userconv").val();
        var senha = $("#passconv").val();
        if (usuario == "" && senha == "") {
            if (browser_name == "iexplorer"){
                $.fallr.show({icon: 'error', content: '<p>Informe o usuário e a senha !</p>', position: 'center'});
            }else{
                swal({
                    title: "Atenção!",
                    text: "Informe o usuário e a senha !",
                    icon: "warning",
                    dangerMode: true
                })
            }
        } else if (usuario == "" && senha != "") {
            if (browser_name == "iexplorer"){
                $.fallr.show({icon: 'error', content: '<p>Informe o usuário !</p>', position: 'center'});
            }else{
                swal({
                    title: "Atenção!",
                    text: "Informe o usuário !",
                    icon: "warning",
                    dangerMode: true
                })
            }
        } else if (usuario != "" && senha == "") {
            if (browser_name == "iexplorer"){
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
                data: $('#form_index').serialize(),
                dataType: 'json',
                beforeSend: function () {
                    $("#divLoading").css("display", "block");
                },
                done: function () {
                    $("#divLoading").css("display", "none");
                },
                success: function (data) {
                    tipo_loginx = data.tipo_login;
                    if (tipo_loginx == "login sucesso") {
                        $.redirect('introduction.php', data);
                    } else if (tipo_loginx == "login cob") {
                        $.redirect('msg_cob.php', data);
                    } else if (tipo_loginx == "login inativo") {
                        $("#divLoading").css("display", "none");
                        if (browser_name == "iexplorer"){
                            $.fallr.show({icon: 'info', content: '<p>Informe a senha !</p>', position: 'center'});
                        }else{
                            swal({
                                title: "Atenção!",
                                text: "Informe a senha !",
                                icon: "warning",
                                dangerMode: true
                            })
                        }
                    } else if (tipo_loginx == "login incorreto") {
                        $("#divLoading").css("display", "none");
                        if (browser_name == "iexplorer"){
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
        e.preventDefault();
        var tipo_loginx;
        var cartao = $("#cod_carteira_login").val();
        var senha = $("#passasso").val();
        if (cartao == "" && senha == "") {
            if (browser_name == "iexplorer"){
                $.fallr.show({icon: 'error', content: '<p>Informe o cartão e a senha !</p>', position: 'center'});
            }else{
                swal({
                    title: "Atenção!",
                    text: "Informe o cartão e a senha !",
                    icon: "warning",
                    dangerMode: true
                })
            }
        } else if (cartao == "" && senha != "") {
            if (browser_name == "iexplorer"){
                $.fallr.show({icon: 'error', content: '<p>Informe o cartão !</p>', position: 'center'});
            }else{
                swal({
                    title: "Atenção!",
                    text: "Informe o cartão ! !",
                    icon: "warning",
                    dangerMode: true
                })
            }
        } else if (cartao != "" && senha == "") {
            if (browser_name == "iexplorer"){
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
                    if (tipo_loginx == "1") {
                        $.redirect('extratocartao/extrato.php', data);
                    } else if (tipo_loginx == "login cob") {
                        $.redirect('msg_cob.php', data);
                    } else if (tipo_loginx == "login inativo") {
                        $("#divLoading").css("display", "none");
                        if (browser_name == "iexplorer"){
                            $.fallr.show({icon: 'info', content: '<p>Convênio inativo !</p>', position: 'center'});
                        }else{
                            swal({
                                title: "Atenção!",
                                text: "Convênio inativo !",
                                icon: "warning",
                                dangerMode: true
                            })
                        }
                    } else if (tipo_loginx == "login incorreto") {
                        $("#divLoading").css("display", "none");
                        if (browser_name == "iexplorer"){
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
});