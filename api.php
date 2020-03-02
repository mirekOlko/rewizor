<?php
function VAT($nipsz):array{
    $link=curl_init();
    
    $data=new DateTime();
    $getUrl='https://wl-api.mf.gov.pl/api/search/nip/'.$nipsz.'?date='.$data->format('Y-m-d');
    
    curl_setopt($link, CURLOPT_URL, $getUrl);
    curl_setopt($link, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($link, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($link, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
    
    //Transfer-Encoding=chunked
    $result=curl_exec($link);
    if(curl_error($link)) {
        echo curl_error($link);
    }
    curl_close($link);
    //echo  $result;
     
    //echo '<br><br>'.gettype($result).'<br><br>'; 
    //$result='{"result":{"subject":{"name":"ZAKŁAD REMONTOWY SERVICE SPÓŁKA Z OGRANICZONĄ ODPOWIEDZIALNOŚCIĄ","nip":"7792422251","statusVat":"Czynny","regon":"302737072","pesel":null,"krs":"0000512257","residenceAddress":null,"workingAddress":"KRAKOWSKA 29, 50-424 WROCŁAW","representatives":[],"authorizedClerks":[],"partners":[],"registrationLegalDate":"2014-06-24","registrationDenialBasis":null,"registrationDenialDate":null,"restorationBasis":null,"restorationDate":null,"removalBasis":null,"removalDate":null,"accountNumbers":["19102052420000260203597267"],"hasVirtualAccounts":false},"requestId":"jnima-866d64e"}}';
    $obj=json_decode($result);
    //foreach($obj As $k=>$v)
    //    echo $k.":".$v.'<br>';
    //print $obj;
    
    /*
    $name=$obj->result->subject->name;
    $nip=$obj->result->subject->nip;
    $statusVat=$obj->result->subject->statusVat;
    $regon=$obj->result->subject->regon;
    $pesel=$obj->result->subject->pesel;
    $krs=$obj->result->subject->krs;
    $residenceAddress=$obj->result->subject->residenceAddress;
    $workingAddress=$obj->result->subject->workingAddress;
    $representatives=$obj->result->subject->representatives;
    $authorizedClerks=$obj->result->subject->authorizedClerks;
    $partners=$obj->result->subject->partners;
    $registrationLegalDate=$obj->result->subject->registrationLegalDate;
    $registrationDenialBasis=$obj->result->subject->registrationDenialBasis;
    $registrationDenialDate=$obj->result->subject->registrationDenialDate;
    $restorationBasis=$obj->result->subject->restorationBasis;
    $restorationDate=$obj->result->subject->restorationDate;
    $removalBasis=$obj->result->subject->removalBasis;
    $removalDate=$obj->result->subject->removalDate;
    $accountNumbers=$obj->result->subject->accountNumbers;
    $hasVirtualAccounts=$obj->result->subject->hasVirtualAccounts;
    
    echo '<p>';
    echo $name.'<br>';
    echo $nip.'<br>';
    echo $regon.'<br>';
    echo $krs.'<br>';
    echo $workingAddress.'<br>';
    for($i=0;$i<sizeof($accountNumbers);$i++)
        echo $accountNumbers[$i];
    
        echo '</p>';
    */ 
    
    $wyniki=array();
    
    $wyniki['adrh_Nazwa']=$obj->result->subject->name;
    $wyniki['adrh_NIP']=$obj->result->subject->nip;
    
    if($obj->result->subject->regon!='')
        $wyniki['kh_REGON']=$obj->result->subject->regon;
        else
            $wyniki['kh_REGON']=null;
            
    if($obj->result->subject->krs!='')
        $wyniki['kh_KRS']=$obj->result->subject->krs;
        else
            $wyniki['kh_KRS']=null;
                
    if($obj->result->subject->workingAddress!='')
    {
        $temp=explode(',',$obj->result->subject->workingAddress);
        $temp1=explode(' ',trim($temp[0]));
        $ulica='';
        $numer='';
        $sizeof=sizeof($temp1);
        if(is_int($temp1[$sizeof-2])){
            $numer=$temp1[$sizeof-2].' '.$temp1[$sizeof-1];
            for($i=0;$i<$sizeof-2;$i++)
                $ulica.=$temp1[$i];
        }
        else{
                $numer=$temp1[$sizeof-1];
                for($i=0;$i<$sizeof-1;$i++)
                    $ulica.=$temp1[$i];
        }
        $wyniki['adr_Ulica']=$ulica;
        $wyniki['adr_NrDomu']=$numer;
        $wyniki['adr_Kod']=substr(trim($temp[1]),0,6);
        $wyniki['adr_Miejscowosc']=substr(trim($temp[1]),6,100);
    }
    else{
        //echo 'brak adresu';
        $wyniki['adr_Ulica']=null;
        $wyniki['adr_NrDomu']=null;
        $wyniki['adr_Kod']=null;
        $wyniki['adr_Miejscowosc']=null;
    }
        
    
    
    return $wyniki;
    //$wyniki['
    //$wyniki['
}

function wypisz($resylt):void
{
    foreach($resylt As $k=>$v)
        echo $k.' => '.$v.'<br>';    
}

//wypisz(VAT('8992429726'));
wypisz(VAT('8821903368'));
//wypisz(VAT('7792422251'));


?>
