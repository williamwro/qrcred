$(document).ready(function() {
    var divisao = sessionStorage.getItem("divisao");
    var divisao_nome = sessionStorage.getItem("divisao_nome");
    var descricao = sessionStorage.getItem("descricao");
    
    if(divisao === "1"){//QrCred
        $('#rotulo_divisao_makecard').html("Sistema administrativo do cartão convenio QrCred");
    }else if(divisao === "2"){//
        $('#rotulo_divisao_makecard').html("");
    }
    
    $('#rotulo_divisao').html(divisao_nome)
    $('#rotulo_descricao').html(descricao)
})