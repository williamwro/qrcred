class classe_arquivos {

    constructor() {
    }

    arquivo_sindserva(){
        var data = table.rows().data();
        var texto = '';
        var obj = {};
        obj.dados = [];
        var d = new Date();
        var dataHora = (d.toLocaleString());
        dataHora.substring(0,10);
        var farmacia=0;
        var compras=0;
        var financeira=0;
        var unimed=0;
        var financeira2=0;
        var financeira3=0;
        var linha = '';
        var mes_selecionado = $('#C_mes').val();
        if ( table.rows().count() > 0 ){
            data.each(function (value, index) {
                if(value.nome_tipo === 'FARMACIA'){//farmacia
                    farmacia = ("        " +(parseFloat(value.total).toFixed(2).replace('.',''))).slice(-11)+"\r\n";
                    linha += value.associado + '0350' + farmacia
                }else if(value.nome_tipo === 'COMPRAS'){//compras
                    compras = ("        " +(parseFloat(value.total).toFixed(2).replace('.',''))).slice(-11)+"\r\n";
                    linha += value.associado + '0355' + compras
                }else if(value.nome_tipo === 'FINANCEIRA'){//financeira
                    financeira = ("        " +(parseFloat(value.total).toFixed(2).replace('.',''))).slice(-11)+"\r\n";
                    linha += value.associado + '0313' + financeira
                }else if(value.nome_tipo === 'UNIMED'){//unimed
                    unimed = ("        " +(parseFloat(value.total).toFixed(2).replace('.',''))).slice(-11)+"\r\n";
                    linha += value.associado + '0495' + unimed
                }else if(value.nome_tipo === 'FINANCEIRA2'){//financeira2
                    financeira2 = ("        " +(parseFloat(value.total).toFixed(2).replace('.',''))).slice(-11)+"\r\n";
                    linha += value.associado + '0317' + financeira2
                }else if(value.nome_tipo === 'FINANCEIRA3'){//financeira3
                    financeira3 = ("        " +(parseFloat(value.total).toFixed(2).replace('.',''))).slice(-11)+"\r\n";
                    if($C_empregador.value === 'PREFEITURA MUNICIPAL') {
                        linha += value.associado + '0350' + financeira3
                    }else if($C_empregador.value === 'INPREV'){
                        linha += value.associado + '0350' + financeira3
                    }
                }
                farmacia=0;
                compras=0;
                financeira=0;
                unimed=0;
                financeira2=0;
                financeira3=0;
            });
            let blob = new Blob([linha], { type: "text/plain;charset=utf-8" });
            return saveAs(blob,divisao_nome+"_"+mes_selecionado+"_VALORES_"+dataHora.substring(0,10));
        }
    }
}

var divisao = new classe_arquivos();