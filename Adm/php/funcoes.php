<?PHP
error_reporting(E_ALL);
function organiza_dt($dt1){
	$dt_atual = '';
	$dt_atual = substr($dt1,'8','2');
	$dt_atual = $dt_atual."/".substr($dt1,'5','2');
	$dt_atual = $dt_atual."/".substr($dt1,'0','4');
	return($dt_atual);
}
function semana( $data ){
 $aVet=explode( "/",$data );
 $nDia = date("w", mktime(0,0,0,$aVet[1],$aVet[0],$aVet[2] ));
 $semana = substr("domsegterquaquisexsab", ($nDia+1)*3-3,3 );
 return $semana;
}
function somadata( $data, $nDias ){
	if( !isset( $nDias ) ){
	 $nDias = 1;
	}
	$aVet = explode( "/",$data );
	if(($aVet[1] == "01") or ($aVet[1] == "03") or ($aVet[1] == "05") or ($aVet[1] == "07") or ($aVet[1] == "08") or ($aVet[1] == "10") or ($aVet[1] == "12")){
	 $nDias = $nDias+1;
	}
	return date( "d/m/Y",mktime(0,0,0,$aVet[1],$aVet[0]+$nDias,$aVet[2]));
}
function somames( $data, $nMes )
{
    $aVet = explode( "/",$data );
    $aux = date("m/Y", mktime(0, 0, 0, $aVet[0] + $nMes, 0, $aVet[1]));
    $aux2 = explode( "/",$aux );
    $arr2 = array("JAN"=>1,"FEV"=>2,"MAR"=>3,"ABR"=>4,"MAI"=>5,"JUN"=>6,"JUL"=>7,"AGO"=>8,"SET"=>9,"OUT"=>10,"NOV"=>11,"DEZ"=>12);
    $smes = array_search($aux2[0],$arr2);
    $ano = strval($aux2[1]);
    return $smes."/".$ano;
}
function data_fatura( $nMes )
{
    //***** ano *************************
    $data2 = new DateTime();
    $ano   = $data2->format('Y');
    //***********************************
    $arr2 = array(1=>"JAN",2=>"FEV",3=>"MAR",4=>"ABR",5=>"MAI",6=>"JUN",7=>"JUL",8=>"AGO",9=>"SET",10=>"OUT",11=>"NOV",12=>"DEZ");

    $smes = array_search($nMes,$arr2);
    if($smes === 12){
        $smes = "01";
        $ano = $ano + 1;
    }else if($smes === 1){
        $smes = $smes + 1;
        $ano = $ano + 1;
    }else{
        $smes = $smes + 1;
    }
    $data_fatura = strval($ano)."-".str_pad($smes,2,"0",STR_PAD_LEFT)."-"."05";
    //$ano = strval($aux2[1]);
    return $data_fatura;
}
function somames_gravar( $data )
{
    $aVet = explode( "/",$data );
    $arr = array(1=>"JAN",2=>"FEV",3=>"MAR",4=>"ABR",5=>"MAI",6=>"JUN",7=>"JUL",8=>"AGO",9=>"SET",10=>"OUT",11=>"NOV",12=>"DEZ");
    $nmes = array_search($aVet[0],$arr);
    $ano = $aVet[1];
    if ($nmes==12){
        $nmes = 1;
        $ano = $ano + 1;
    }else{
        $nmes = $nmes + 1;
    }
    $arr2 = array("JAN"=>1,"FEV"=>2,"MAR"=>3,"ABR"=>4,"MAI"=>5,"JUN"=>6,"JUL"=>7,"AGO"=>8,"SET"=>9,"OUT"=>10,"NOV"=>11,"DEZ"=>12);
    $smes = array_search($nmes,$arr2);
    $ano = strval($ano);
    return $smes."/".$ano;
}
function busca_mes($data){
    $aVet = explode( "/",$data );
    $arr = array("JANEIRO"=>"JAN","FEVEREIRO"=>"FEV","MARÇO"=>"MAR","ABRIL"=>"ABR","MAIO"=>"MAI","JUNHO"=>"JUN","JULHO"=>"JUL","AGOSTO"=>"AGO","SETEMBRO"=>"SET","OUTUBRO"=>"OUT","NOVEMBRO"=>"NOV","DEZEMBRO"=>"DEZ");
    $nmes = array_search($aVet[0],$arr);
    return $nmes;
}
function busca_mes2($data){
    $aVet = explode( "/",$data );
    $arr = array("JAN"=>"JANEIRO","FEV"=>"FEVEREIRO","MAR"=>"MARÇO","ABR"=>"ABRIL","MAI"=>"MAIO","JUN"=>"JUNHO","JUL"=>"JULHO","AGO"=>"AGOSTO","SET"=>"SETEMBRO","OUT"=>"OUTUBRO","NOV"=>"NOVEMBRO","DEZ"=>"DEZEMBRO");
    $nmes = array_search($aVet[0],$arr);
    return $nmes;
}
function EntreDatas( $inicio, $fim )
{
  $aInicio = explode( "/",$inicio );
  $aFim    = explode( "/",$fim    );
  $nTempo = mktime(0,0,0,$aFim[1],$aFim[0],$aFim[2]);
  $nTempo1= mktime(0,0,0,$aInicio[1],$aInicio[0],$aInicio[2]);
  return round(($nTempo-$nTempo1)/86400)+1;
}
function subtraidata( $data, $nDias )
{
    if( !isset( $nDias ) )
    {
        $nDias = 1;
    }
    $aVet = explode( "/",$data );
    return date( "d/m/Y",mktime(0,0,0,$aVet[1],$aVet[0]-$nDias,$aVet[2]));
}
function convertdata( $data )
{
    return date( "d/m/Y",strtotime($data));
}
function mes_atual_relatorio()
{
$mesx=date("d/m");
$a=date("Y");
$mesx=explode("/",$mesx);
if ($mesx[0] > 10){
	$mesx[1]++;
}
if ($mesx[1] > 12){
	$mesx[1]=1;
	$a++;
}
if($mesx[1]<10){ $mesx[1] = "0$mesx[1]"; }
$mes_atual = "$mesx[1]/$a";							
return $mes_atual;		
}
function convdata($dataentra,$tipo){
  if ($tipo == "mtn") {
    $datasentra = explode("-",$dataentra);
    $indice=2;
    while($indice != -1){
      $datass[$indice] = $datasentra[$indice];
      $indice--;
    }
    $datasaida=implode("/",$datass);
  } elseif ($tipo == "ntm") {
    $datasentra = explode("/",$dataentra);
    $indice=2;
    while($indice != -1){
      $datass[$indice] = $datasentra[$indice];
      $indice--;
    }
    $datasaida = implode("-",$datass);
  } else {
    $datasaida = "erro";
  }
  return $datasaida;
}
function tofloat($num) {
    $dotPos = strrpos($num, '.');
    $commaPos = strrpos($num, ',');
    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
        ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

    if (!$sep) {
        return floatval(preg_replace("/[^0-9]/", "", $num));
    }

    return floatval(
        preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
        preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
    );
}

