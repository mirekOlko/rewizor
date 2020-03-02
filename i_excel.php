<html>
<head>
<meta charset="UTF-8">
</head>
<body>
	<div id="czas"></div>
	<div id="pamiec"></div>
<?php
function getmicrotime()
{
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
}
$time=getmicrotime();

/** 
 * @author aaadmin
 * 
 */
require_once 'FakturaGotowkaPaliwo.php';
require_once 'FakturaGotowka.php';
require_once 'Kontrachenci.php';


$dataWystawienia='2015-2-20';
$dataSprzedazy='';
$terminPlatnosci='';
$nip='7740001454';
//$nip='9510065428';
$rodzajDokumentu='FV';
$numerDokumentu='A';
$wartoscDokumentuNetto=162.55;
$wartoscDokumentuVAT=37.39;
$formaPlatnosci='G'; // G dla faktura paliwo, faktura gotówka inna
$rejestr=1;


$name='test1'; $pass='test1'; $database='test';
echo '<font color="red"> baza:'.$database.'</font><br>';
$kontrachent= new Kontrachenci($name,$pass,$database);
$kontrachent->getKontrachenci();
/*
foreach($kontrachent->listaKontrachentow As $k=>$v){
    echo '<br>'.$k.'<br>';
    foreach ($v As $k=>$s){
     echo $k.' '.$s.' ';
    }
}
*/
require_once 'FakturaGotowka.php';
require_once 'FakturaGotowkaPaliwo.php';
require_once 'FakturaGotowkaBezVat.php';
$numerDokumentu.='gotowka';
$faktura=new FakturaGotowka($name,$pass,$database,$kontrachent);
//$faktura=new FakturaPaliwo($name,$pass,$database,$kontrachent);
//$faktura=new FakturaPaliwo($name,$pass,$database,$kontrachent);
/*
 *                         'adr_Id'=>$s[1],            //10
                        'adr_IdHistoria'=>$temp[0], //6
                        'adr_IdObiektu'=>$s[2],     //2
                        'Symbol'=>$s[3],            //ORLEN
                        'adr_IdWersja'=>$s[4],      //1
 */
$faktura->configure();

if(!isset($kontrachent->listaKontrachentow[$nip])){
    $rok=explode("-",$dataWystawienia);
    $kontrachent->createNewkontrachent($nip,$rok[0]);
    echo '<p>tworze kontrachenta o NIP:'.$nip.'</p>';
    }
    else {
        echo 'kontrachent: '.$kontrachent->listaKontrachentow[$nip]['Symbol'].'<br>';
        }
        

$faktura->setDataFaktura($dataWystawienia, $dataSprzedazy, $terminPlatnosci, $nip, $rodzajDokumentu, $numerDokumentu, $wartoscDokumentuNetto, $wartoscDokumentuVAT, $formaPlatnosci, $rejestr);
$faktura->createNewFaktura();

$numerDokumentu.='paliwo';
$faktura1=new FakturaGotowkaPaliwo($name,$pass,$database,$kontrachent);
$faktura1->configure();
$faktura1->setDataFaktura($dataWystawienia, $dataSprzedazy, $terminPlatnosci, $nip, $rodzajDokumentu, $numerDokumentu, $wartoscDokumentuNetto, $wartoscDokumentuVAT, $formaPlatnosci, $rejestr);
$faktura1->createNewFaktura();

$numerDokumentu.='gotowka bez vat';
$faktura2=new FakturaGotowkaBezVat($name,$pass,$database,$kontrachent);
$faktura2->configure();
$faktura2->setDataFaktura($dataWystawienia, $dataSprzedazy, $terminPlatnosci, $nip, $rodzajDokumentu, $numerDokumentu, $wartoscDokumentuNetto, $wartoscDokumentuVAT, $formaPlatnosci, $rejestr);
$faktura2->createNewFaktura();
//echo $faktura->toString();
/*
echo '<br><br><br>';
$s='slad_Id, slad_TypObiektu, slad_Zdarzenie, slad_CzasZdarzenia, slad_IdUzytkownika';
$x=explode(',',$s);
echo '<p>';
for($i=0;$i<sizeof($x);$i++)
    echo '$zapytanieSQL['."'".trim($x[$i])."'".']=(string)'."''".';<br>';
echo '</p>';
*/

$end=getmicrotime()-$time;
echo '
    <script type="text/javascript">
		document.getElementById("czas").innerHTML = "'.$end.' s <br> '.(memory_get_usage()/1000000).' MB";
		    
     </script>
';
?>
</body></html>