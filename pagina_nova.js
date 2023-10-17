var table;
var usuario;
var senha;
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
    $('input[type="text"], select').val('');
    $("#userconv").val("");
    $("#passconv").val("");
    $("#txtSenhaCartao").val("");
    $("#cod_carteira_login").val("");
    $("#divLoading").css("display", "none");

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
        e.preventDefault();
        var tipo_loginx;
        var cartao = $("#cod_carteira_login").val();
        var senha = $("#txtSenhaCartao").val();
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
        } else if (cartao != "" && senha === "") {
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
                    if (tipo_loginx === 1) {
                        $.redirect('extratocartao/extrato.php', data);
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
    // econstroi uma datatabe no primeiro carregamento da tela
    table = $('#tabela_producao').DataTable({
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
        "language": {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Portuguese-Brasil.json",
            "decimal": ",",
            "thousands": "."
        }
    });
});