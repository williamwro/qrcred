var abreviacao;
var mes_anterior  = '';
var mes_anterior2 = '';
$(document).ready(function(){
    divisao = sessionStorage.getItem("divisao");
    divisao_nome = sessionStorage.getItem("divisao_nome");
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");

    $.ajax({
        url: "pages/manutencao/manutencao.php",
        method: "POST",
        data: {divisao : divisao},
        dataType: "json",
        success:function (data) {

            $("#mes_atual").val(data.abreviacao_anterior);
            mes_anterior = data.abreviacao_anterior;
            mes_anterior2 = data.abreviacao_anterior2;
            if(data.status_admin === "0"){
                $("#btnBloquear").text("Liberar");
                $("#status").text("Bloqueado").addClass('badge badge-danger').css("background-color", "red");
            }else{
                $("#btnBloquear").text("Bloquear");
                $("#status").text("Liberado").addClass('badge badge-success').css("background-color", "green");
            }
        }
    })
});
$('#btnBloquear').click(function () {

    abreviacao = $("#mes_atual").val();
    var status = $("#status").text();
    if($("#btnBloquear").text() === "Bloquear"){
        $("#btnBloquear").text("Liberar");
        $("#status").text("Bloqueado").addClass('badge badge-danger').css("background-color", "red");
    }else{
        $("#btnBloquear").text("Bloquear");
        $("#status").text("Liberado").addClass('badge badge-success').css("background-color", "green");
    }

    $.ajax({
        url: "pages/manutencao/atualizar.php",
        method: "POST",
        data: {divisao : divisao, abreviacao: abreviacao, status: status, mes_anterior: mes_anterior, mes_anterior2: mes_anterior2},
        dataType: "json",
        success:function (data) {
            if(data.resultado === "atualizado"){

            }
            //$("#mes_atual").val(data.abreviacao_anterior);
        }
    })
});