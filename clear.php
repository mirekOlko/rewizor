<html><head>
<style>
.red{
color:red;
}
.green{
color:green;
}
</style>
</head><body>
<?php
require_once 'Connect.php';

$test=2;
switch($test){
    case 1: // kontrachent
        $con=new Connect('test1','test1','test_kontrahent');
        $SQL=array(
            'delete FROM pk_PlanKont WHERE ko_Id>612;',
            'delete FROM adr__Ewid WHERE adr_Id>27;',
            'delete FROM adr_Historia WHERE adrh_Id>29;',
            'delete FROM xkh_Ewid WHERE khx_IdSource>7;',
            'delete FROM xpk_Ewid WHERE pkx_IdSource>612;',
            'delete FROM kh__Kontrahent WHERE kh_Id>7;',
            'SET IDENTITY_INSERT ins_Slad ON;',
            'delete FROM ins_Slad WHERE slad_Id>15;',
            'SET IDENTITY_INSERT ins_Slad OFF;'
        );
        break;
    case 2: // faktura
        $test='test_kontrahent';
        $con=new Connect('test1','test1',$test);
        echo $test.'<br>';
        $SQL=array(
            'delete FROM nz_RozDekret WHERE nzd_IdRozrachunku>4;',
            'delete FROM nz__Finanse WHERE nzf_Id>4;',
            'delete FROM vat__EwidVAT WHERE ev_Id>1;',
            'delete FROM dkr_Pozycja WHERE dko_Id>6;',
            'delete FROM vat_DaneVAT WHERE dv_Id>35;',
            'delete FROM dkr_SladRewizyjny WHERE srw_Id>1;',
            'delete FROM dkr__Dokument WHERE dkr_Id>1;',
            'delete FROM ins_Slad WHERE slad_Id>18;',
            );
        break;
}
for($i=0;$i<sizeof($SQL);$i++)
{
    
    if($con->query($SQL[$i])==true)
        echo '<p class="green">'.$SQL[$i].'</p>'; 
    else 
        echo '<p class="red">'.$SQL[$i].'</p>';
    
    echo "\n";
    
}
$con->disconnect();
?>
</body></html>