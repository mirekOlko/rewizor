<?php

/** 
 * @author aaadmin
 * 
 */
class FakturaGotowkaBezVat extends FakturaGotowka
{
   
    public function __construct($name,$pass,$database,$kontrachent)
    {
        require_once 'Connect.php';
        $this->connect=new Connect($name,$pass,$database); 
        $this->kontrachent=$kontrachent;
        //$this->kontrachent->listaKontrachentow();
    } 
  
    public function setDataFaktura($dataWystawienia, $dataSprzedazy, $terminPlatnosci, $nip, $rodzajDokumentu, $numerDokumentu, $wartoscDokumentuNetto, $wartoscDokumentuVAT, $formaPlatnosci, $rejestr):void
    {
        $format = 'Y-n-j H:i:s';
        if($dataWystawienia!='')
            $this->dataWystawienia=DateTime::createFromFormat($format, $dataWystawienia.' 00:00:00');
            
            if($dataSprzedazy!='')
                $this->dataSprzedazy=DateTime::createFromFormat($format, $dataSprzedazy.' 00:00:00');
                else $this->dataSprzedazy=new DateTime('NOW');
                
                if($terminPlatnosci!='')
                    $this->terminPlatnosci=DateTime::createFromFormat($format, $terminPlatnosci.' 00:00:00');
                    else $this->terminPlatnosci=new DateTime('NOW');
                    
                    $this->nip=$nip;
                    $this->rodzajDokumentu=$rodzajDokumentu;
                    $this->numerDokumentu=$numerDokumentu;
                    $this->wartoscDokumentuNetto=$wartoscDokumentuNetto;
                    $this->wartoscDokumentuVAT=0;
                    $this->wartoscDokumentuBrutto=$wartoscDokumentuNetto;
                    $this->formaPlatnosci=$formaPlatnosci;
                    $this->rejestr=$rejestr;
                    $this->dkr_IdRoku= $this->rokRozliczeniowy[$this->dataWystawienia->format('Y')];
                    
                    $this->wartosci=$this->obliczeniaDekret();
                    
                    
    }

  
    
    protected function genDkr__Dokument():void
    {
        $tabela='dkr__Dokument';
        $zapytanieSQL=$this->Dkr__Dokument($tabela);
   
        $zapytanieSQL['dkr_Opis']=(string) 'bez vat'; // varchar
       
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        //echo $q;
        //$this->showProperty($zapytanieSQL);
       // echo $this->toString();
        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['dkr_Id'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL); 

    }
    protected function obliczeniaDekret():array{

        //obliczenia dla VAT
        $wartosci=array();
        //$wartosci['23']['netto']=$this->wartoscDokumentuNetto;
        $wartosci['npo']['netto']=$this->wartoscDokumentuNetto;
        //$wartosci['23']['VAT']=$this->wartoscDokumentuVAT;
        $wartosci['npo']['VAT']=0;
        //$wartosci['23']['brutto']=$wartosci['23']['netto']+$wartosci['23']['VAT'];
        $wartosci['npo']['brutto']=$wartosci['npo']['netto'];
        //$wartosci['221-23']=$wartosci['23']['VAT'];
        $wartosci['401']=$this->wartoscDokumentuNetto;
        //$wartosci['401']=round(($this->wartoscDokumentuNetto+$wartosci['23']['VAT'])*0.2,2);
        //$wartosci['409']=$this->wartoscDokumentuBrutto-$wartosci['401']-$wartosci['221-23'];
        
        return $wartosci;
 
    }
    
    
    protected function genDkr_Pozycja():void
    {
        $tabela='dkr_Pozycja';
        //echo ' <font color="green">'.$tabela.'</font> ';
        $temp=array();
        $temp=$this->connect->query('SELECT max(dko_Id) FROM '.$tabela.';');
        $this->dko_Id=$temp[0]+1;
        
        $temp=$this->connect->query('SELECT max(dko_IdDokumentu) FROM '.$tabela.';');
        $this->dko_IdDokumentu=$temp[0]+1;
 
        $zapytanieSQL['dko_Id']=(int) $this->dko_Id;// Lp. 1
        $zapytanieSQL['dko_IdRoku']=(int) $this->dkr_IdRoku;// Lp. 2
        $zapytanieSQL['dko_DataDekretacji']= $this->dataWystawienia;// Lp. 3
        $zapytanieSQL['dko_Status']=(int) 1;// Lp. 4
        $zapytanieSQL['dko_IdDokumentu']=(int) $this->dko_IdDokumentu;// Lp. 5
        $zapytanieSQL['dko_Konto']=(string) '101'; // varchar// Lp. 6
        $zapytanieSQL['dko_KwotaMa']=(float) $this->wartoscDokumentuBrutto; // money// Lp. 9
        $zapytanieSQL['dko_KwotaMaWaluta']=(float) $this->wartoscDokumentuBrutto; // money// Lp. 10
        $zapytanieSQL['dko_Waluta']=(string) 'PLN'; // char// Lp. 11
        $zapytanieSQL['dko_Kurs']=(float) 1.0000; // money// Lp. 12
        $zapytanieSQL['dko_LiczbaJednostek']=(int) 1;// Lp. 13
        $zapytanieSQL['dko_RodzajKursu']=(int) 1;// Lp. 14
        $zapytanieSQL['dko_DataKursu']=$this->getDataKursu();// Lp. 15
        $zapytanieSQL['dko_IdBanku']=(int) 1;// Lp. 16
        $zapytanieSQL['dko_Opis']=(string) ''; // varchar// Lp. 17
        $zapytanieSQL['dko_Grupa']=(int) 1;// Lp. 18
        $zapytanieSQL['dko_LpWiersza']=(int) 1;// Lp. 19
        $zapytanieSQL['dko_TypWiersza']=(int) 1;// Lp. 20
        $zapytanieSQL['dko_KorektaZaokraglen']=(boolean) false; //(bit) 0; // Lp. 21
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        //echo $q.'<br>';
        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['dko_Id'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL);
            
        
        
        $zapytanieSQL['dko_Id']=(int) $this->dko_Id+1;// Lp. 1
        $zapytanieSQL['dko_Konto']=(string) '401'; // varchar// Lp. 6
        $zapytanieSQL['dko_KwotaWn']=(float) $this->wartosci['401'];
        $zapytanieSQL['dko_KwotaWnWaluta']=(float) $this->wartosci['401'];
        $zapytanieSQL['dko_Grupa']=(int) 1;// Lp. 18
        $zapytanieSQL['dko_LpWiersza']=(int) 2;// Lp. 19
        $zapytanieSQL['dko_TypWiersza']=(int) 0;// Lp. 20
        unset($zapytanieSQL['dko_IdStawkiVAT']);
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        //echo $q.'<br>';
        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['dko_Id'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL);
        
            
        $ko_Nr='';
        switch($this->kontrachent->listaKontrachentow[$this->nip]['adr_IdObiektu'])
        {
            case $this->kontrachent->listaKontrachentow[$this->nip]['adr_IdObiektu']<10:
                $ko_Nr='0000'.$this->kontrachent->listaKontrachentow[$this->nip]['adr_IdObiektu'];
                break;
            case $this->kontrachent->listaKontrachentow[$this->nip]['adr_IdObiektu']<100:
                $ko_Nr='000'.$this->kontrachent->listaKontrachentow[$this->nip]['adr_IdObiektu'];
                break;
            case $this->kontrachent->listaKontrachentow[$this->nip]['adr_IdObiektu']<1000:
                $ko_Nr='00'.$this->kontrachent->listaKontrachentow[$this->nip]['adr_IdObiektu'];
                break;
        }
        
        $this->nzd_IdDekretu=$this->dko_Id+2;
        $zapytanieSQL['dko_Id']=(int) $this->dko_Id+2;// Lp. 1
        $zapytanieSQL['dko_Konto']=(string) '210-'.$ko_Nr; // varchar// Lp. 6
        unset($zapytanieSQL['dko_KwotaWn']);
        unset($zapytanieSQL['dko_KwotaWnWaluta']);
        $zapytanieSQL['dko_KwotaMa']=(float) $this->wartoscDokumentuBrutto; // money// Lp. 9
        $zapytanieSQL['dko_KwotaMaWaluta']=(float) $this->wartoscDokumentuBrutto; // money// Lp. 10
        $zapytanieSQL['dko_Grupa']=(int) 2;// Lp. 18
        $zapytanieSQL['dko_LpWiersza']=(int) 3;// Lp. 19
        $zapytanieSQL['dko_TypWiersza']=(int) 1;// Lp. 20
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        //echo $q.'<br>';
        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['dko_Id'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL);
        
        $zapytanieSQL['dko_Id']=(int) $this->dko_Id+3;// Lp. 1
        $zapytanieSQL['dko_KwotaWn']=$this->wartoscDokumentuBrutto;;
        $zapytanieSQL['dko_KwotaWnWaluta']=$this->wartoscDokumentuBrutto;;
        unset($zapytanieSQL['dko_KwotaMa']);  // money// Lp. 9
        unset($zapytanieSQL['dko_KwotaMaWaluta']); // money// Lp. 10
        $zapytanieSQL['dko_Grupa']=(int) 2;// Lp. 18
        $zapytanieSQL['dko_LpWiersza']=(int) 4;// Lp. 19
        $zapytanieSQL['dko_TypWiersza']=(int) 0;// Lp. 20
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        //echo $q.'<br>';
        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['dko_Id'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL);
    }
    
    
    protected function genVat_DaneVAT():void
    {
        $tabela='vat_DaneVAT';
        //echo ' <font color="green">'.$tabela.'</font> ';
        $temp=array();
        $temp=$this->connect->queryLong('SELECT TOP 2 dv_Id,dv_IdEwidVAT  FROM vat_DaneVAT order by  dv_Id DESC;');
        

        $dv_Id=$temp[0][0]+42;
        
        $zapytanieSQL['dv_Id']=(int) $dv_Id;// Lp. 1
        $zapytanieSQL['dv_IdEwidVAT']=(int) $this->ev_Id;// Lp. 2
        $zapytanieSQL['dv_Typ']=(int) 3;// Lp. 3
        $zapytanieSQL['dv_IdStawkiVAT']=(int) 9;// Lp. 4
        
        $zapytanieSQL['dv_KwotaVAT']= 0; // money// Lp. 6
        $zapytanieSQL['dv_Netto']=$this->wartosci['npo']['netto']; // money// Lp. 7
        $zapytanieSQL['dv_Brutto']=$this->wartosci['npo']['brutto']; // money// Lp. 8
            //echo $q.'<br>';
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);

        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['dv_Id'].' SELECT * FROM '.$tabela.'; <br>';
            else 
                echo '<br>'.$q.'<br>';
        }
        else
            $this->showProperty($zapytanieSQL);
                 
                
    }
    


}
?>


