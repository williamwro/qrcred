$(document).ready(function(){
    $("#btnCob2").click(function(e){
        e.preventDefault();
        $("#divLoading").css("display", "block");
        var campos = $('#formcob2').serialize();
        var campos2 = getUrlVars(campos);
        console.log(campos2);
        $.redirect('introduction.php',campos2);

        $("#divLoading").css("display", "none");
    });
});
function getUrlVars(url) {
    var hash;
    var myJson = {};
    var aux;
    var hashes = url.slice(url.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        aux = hash[1];
        myJson[hash[0]] = aux.replace(/\%20/g," ");
    }
    return myJson;
}
//aux.replace(/\%20/g," "); SUBSTITUI %20 POR ESPAÇO 1 " " NA URL