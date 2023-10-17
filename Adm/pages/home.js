$(document).ready(function() {


    var divisao = sessionStorage.getItem("divisao");
    var divisao_nome = sessionStorage.getItem("divisao_nome");
    var descricao = sessionStorage.getItem("descricao");
    if(divisao === "1"){//QrCred
        $("#img_makecard").attr("src", "");
        $("#img_empresa").attr("src", "../pictures_site-sind/logo4.png").width('500px').height('390px'); 
        $('#rotulo_divisao_makecard').html("Sistema administrativo do cartão convenio QrCred");

    }else if(divisao === "2"){//
        $("#img_makecard").attr("src", "");
        $("#img_empresa").hide() //.attr("src", "../Adm/pages/logo_sind.png").width('128px').height('128px');
        $('#rotulo_divisao_makecard').html("");
    }
    $('#rotulo_divisao').html(divisao_nome)
    $('#rotulo_descricao').html(descricao)

})