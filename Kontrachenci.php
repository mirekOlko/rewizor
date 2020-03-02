<?php

/** 
 * @author aaadmin
 * 
 */
class Kontrachenci
{
    public $listaKontrachentow;
    private $connect;
    private $resultApiVAT;
    //$wyniki['adrh_Nazwa']=$obj->result->subject->name;
    //$wyniki['adrh_NIP']=$obj->result->subject->nip;
    //$wyniki['kh_REGON']=$obj->result->subject->regon;
    //$wyniki['kh_KRS']=$obj->result->subject->krs;
    //$wyniki['adr_Ulica']=$ulica;
    //$wyniki['adr_NrDomu']=$numer;
    //$wyniki['adr_Kod']=substr(trim($temp[1]),0,6);
    //$wyniki['adr_Miejscowosc']=substr(trim($temp[1]),6,100);
    
    //plan kont zmienne
    private $ko_Id;
    private $ko_IdElementuKartoteki;
    
    private $IdRokuObrotowego;
    
    private $adr_Id;
    private $adr_Symbol;
    private $kh_NrAnalitykaD;
    private $time;
    
    
    public function __construct($name,$pass,$database)
    {
        require_once 'Connect.php';
        $this->connect=new Connect($name,$pass,$database); 
        $this->getKontrachenci();
        
    }
    
    public function getKontrachenci():array{
        $tabela='adr__Ewid';
        $q="select adr_NIP, adr_Id, adr_IdObiektu, adr_Symbol, adr_IdWersja from ".$tabela." where adr_NIP!=''";
        $r=sqlsrv_query($this->connect->getLink(), $q);
        $wyniki=array();
        if( $r === false)
            $this->erroorsDatabase();
            else
            {
                while($s=sqlsrv_fetch_array($r,SQLSRV_FETCH_NUMERIC))
                {
                    $temp=array();
                    $temp=$this->connect->query('SELECT adrh_Id FROM adr_Historia WHERE adrh_IdAdresu='.$s[1]);
                    
                    
                    $wyniki[$s[0]]=array(
                        'adr_Id'=>$s[1],            //10
                        'adr_IdHistoria'=>$temp[0], //6
                        'adr_IdObiektu'=>$s[2],     //2
                        'Symbol'=>$s[3],            //ORLEN
                        'adr_IdWersja'=>$s[4],      //1
                        );
                }
                
                
            }
            $this->listaKontrachentow=$wyniki;
            return $wyniki;
    }
    
    public function createNewkontrachent($nip,$rok):void
    {       
        require_once 'api.php';
        $this->resultApiVAT=VAT($nip);
        
        $temp=$this->connect->query('SELECT robr_Id FROM pd_RokObrotowy WHERE robr_Symbol='.$rok.';');
        $this->IdRokuObrotowego=$temp[0];
 
        
        $this->genPk_PlanKont();
        $this->genAdr__Ewidt();
        $this->genXkh_Ewid();
        $this->genXpk_Ewid();
        $this->genKh__Kontrahent();
        $this->genIns_Slad();
        $this->getKontrachenci();
        $this->connect->disconnect();
    }
    
    private function genSQLqueryFromTable(string $nazwaTabeli, array $zapytanieSQL):string
    {
        $temp=array();
        $temp[0]='';
        $temp[1]='';
        $test=true;
        foreach($zapytanieSQL As $k=>$v)
        {
            if(is_string($v)==true)
                $v="'".$v."'";
            
            if($v instanceof DateTime)         
                //$v='GETDATE ()';
                //$v="CONVERT(VARCHAR,'".$v->format('Y-m-d H:i:s')."',120)";
                $v="CONVERT(VARCHAR,'".$this->time."',120)";
                //$v="CONVERT(VARCHAR,'".$v->format('d/m/Y')."',103)";
                
                
            if($test==true){
                $temp[0].=$k;
                $temp[1].=$v;
                $test=false;
            }
            else {
                $temp[0].=','.$k;
                $temp[1].=','.$v;
            }
        }
        return 'INSERT INTO '.$nazwaTabeli.' ('.$temp[0].') VALUES ('.$temp[1].');';    
    }
    
    private function genPk_PlanKont():void
    {
        $tabela='pk_PlanKont';
        $temp=array();
        $temp=$this->connect->query('SELECT max(ko_Id) FROM pk_PlanKont;');
        $this->ko_Id=$temp[0]+1;
        
        $temp=$this->connect->query('SELECT max(ko_RozrachObj_Id) FROM pk_PlanKont;');
        $this->ko_IdElementuKartoteki=$temp[0]+1;
        
        $ko_Nr='';
        switch($this->ko_IdElementuKartoteki)
        {
            case $this->ko_IdElementuKartoteki<10:
                $ko_Nr='0000'.$this->ko_IdElementuKartoteki;                
                break;
            case $this->ko_IdElementuKartoteki<100:
                $ko_Nr='000'.$this->ko_IdElementuKartoteki;
                break;
            case $this->ko_IdElementuKartoteki<1000:
                $ko_Nr='00'.$this->ko_IdElementuKartoteki;
                break;
        }
        $this->kh_NrAnalitykaD=$ko_Nr;
        
       
        
        $zapytanieSQL=array();
        $zapytanieSQL['ko_Id']=(int) $this->ko_Id;
        $zapytanieSQL['ko_IdRokuObrotowego']=(int) $this->IdRokuObrotowego;
        $zapytanieSQL['ko_Nr']= (string) '210-'.$ko_Nr;
        $zapytanieSQL['ko_Nazwa']=(string) $this->resultApiVAT['adrh_Nazwa'];
        $zapytanieSQL['ko_Opis']=(string) '';
        $zapytanieSQL['ko_Poziom']=(int) 1;
        $zapytanieSQL['ko_JestLisciem']=(int) 1;
        $zapytanieSQL['ko_Bilansowe']=(int) 1;
        $zapytanieSQL['ko_Wynikowe']=(int) 0;
        $zapytanieSQL['ko_Pozabilansowe']=(int) 0;
        $zapytanieSQL['ko_Rozrachunkowe']=(int) 1;
        $zapytanieSQL['ko_Powiazane']=(int) 0;
        $zapytanieSQL['ko_IdKartoteki']=(int) 1;
        $zapytanieSQL['ko_Kartotekowe']=(int) 1;
        $zapytanieSQL['ko_IdElementuKartoteki']=(int) $this->ko_IdElementuKartoteki;
        $zapytanieSQL['ko_Walutowe']=(int) 0;
        $zapytanieSQL['ko_PodlegaWycenie']=(int) 0;
        $zapytanieSQL['ko_IdKartoteki01']=(int) 1;
        $zapytanieSQL['ko_RozrachObj_Id']=(int) $this->ko_IdElementuKartoteki;
        $zapytanieSQL['ko_RozrachObj_Typ']=(int) 1;
        $zapytanieSQL['ko_IdBiezacejKartoteki']=(int) 1;
        
        
        $q=$this->genSQLqueryFromTable('pk_PlanKont', $zapytanieSQL);
        if($s=$this->connect->query($q))
            echo 'dodano pk_PlanKont:'.$zapytanieSQL['ko_Nr'].' SELECT * FROM '.$tabela.'; <br>';

        
        $zapytanieSQL['ko_Id']=(int) $this->ko_Id+1;
        $zapytanieSQL['ko_Nr']= (string) '200-'.$ko_Nr;
        $zapytanieSQL['ko_IdKartoteki']=(int) 2;
        $zapytanieSQL['ko_IdKartoteki01']=(int) 2;
        $zapytanieSQL['ko_RozrachObj_Typ']=(int) 2;
        $zapytanieSQL['ko_IdBiezacejKartoteki']=(int) 2;
        
        $q=$this->genSQLqueryFromTable('pk_PlanKont', $zapytanieSQL);
        if($this->connect->query($q)==true)
            echo 'dodano pk_PlanKont:'.$zapytanieSQL['ko_Nr'].' SELECT * FROM '.$tabela.'; <br>';
    }
    
    private function genAdr__Ewidt():void
    {
        $tabela='adr__Ewid';
        $temp=array();
        $temp=$this->connect->query('SELECT max(adr_Id) FROM '.$tabela.';');
        $this->adr_Id=$temp[0]+1;

        $dateNow = new DateTime(null, new DateTimeZone('Europe/Warsaw'));
        $this->time=$dateNow->format('Y-m-d H:i:s');       

        
        $zapytanieSQL=array();
        $zapytanieSQL['adr_Id']=(int)$this->adr_Id;
        $zapytanieSQL['adr_IdObiektu']=(int)$this->ko_IdElementuKartoteki;
        $zapytanieSQL['adr_TypAdresu']=(int)1;
        $zapytanieSQL['adr_Nazwa']=(string)substr($this->resultApiVAT['adrh_Nazwa'],0,45);
        $zapytanieSQL['adr_NazwaPelna']=(string)$this->resultApiVAT['adrh_Nazwa'];
        $zapytanieSQL['adr_Telefon']=(string)'';
        $zapytanieSQL['adr_Faks']=(string)'';
        $zapytanieSQL['adr_Ulica']=(string)$this->resultApiVAT['adr_Ulica'];
        $zapytanieSQL['adr_NrDomu']=(string)$this->resultApiVAT['adr_NrDomu'];
        $zapytanieSQL['adr_NrLokalu']=(string)'';
        $zapytanieSQL['adr_Kod']=(string)$this->resultApiVAT['adr_Kod'];
        $zapytanieSQL['adr_Miejscowosc']=(string)$this->resultApiVAT['adr_Miejscowosc'];
        $zapytanieSQL['adr_IdPanstwo']=(int)1;
        $zapytanieSQL['adr_NIP']=(string)$this->resultApiVAT['adrh_NIP'];
        $zapytanieSQL['adr_Symbol']=(string)$this->resultApiVAT['adrh_NIP'];
        $zapytanieSQL['adr_IdWersja']=(int)1;
        $zapytanieSQL['adr_IdZmienil']=(int)1;
        $zapytanieSQL['adr_DataZmiany']=$dateNow;

        $this->adr_Symbol=$zapytanieSQL['adr_Symbol'];
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        if($this->connect->query($q))
            echo 'dodano '.$tabela.':'.$zapytanieSQL['adr_Id'].' SELECT * FROM '.$tabela.'; <br>';
        $this->genAdr_Historia($zapytanieSQL);
        
        $zapytanieSQL['adr_Id']=(int)$this->adr_Id+1;        
        $zapytanieSQL['adr_TypAdresu']=(int)2;
        $zapytanieSQL['adr_Nazwa']=(string)'';
        $zapytanieSQL['adr_NazwaPelna']=(string)'';
        $zapytanieSQL['adr_Telefon']=(string)'';
        $zapytanieSQL['adr_Faks']=(string)'';
        $zapytanieSQL['adr_Ulica']=(string)'';
        $zapytanieSQL['adr_NrDomu']=(string)'';
        $zapytanieSQL['adr_NrLokalu']=(string)'';
        $zapytanieSQL['adr_Kod']=(string)'';
        $zapytanieSQL['adr_Miejscowosc']=(string)'';
        $zapytanieSQL['adr_IdPanstwo']=(int)1;
        $zapytanieSQL['adr_NIP']=(string)'';
        $zapytanieSQL['adr_Symbol']=(string)'';
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        if($this->connect->query($q))
            echo 'dodano '.$tabela.':'.$zapytanieSQL['adr_Id'].' SELECT * FROM '.$tabela.'; <br>';
        
        $this->genAdr_Historia($zapytanieSQL);
        
        $zapytanieSQL['adr_Id']=(int)$this->adr_Id+2;
        $zapytanieSQL['adr_TypAdresu']=(int)11;
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        if($this->connect->query($q))
            echo 'dodano '.$tabela.':'.$zapytanieSQL['adr_Id'].' SELECT * FROM '.$tabela.'; <br>';
       
         $this->genAdr_Historia($zapytanieSQL);
        //if($s=$this->connect->query($q))
        //    echo 'dodano '.$tabela.':'.$zapytanieSQL['ko_Nr'].'<br>';
            

    }
    
    private function genAdr_Historia($adresy):void
    {
        $tabela='adr_Historia';
        $temp=array();
        $temp=$this->connect->query('SELECT max(adrh_Id) FROM '.$tabela.';');
        
        $zapytanieSQL=array();
        $zapytanieSQL['adrh_Id']=$temp[0]+1;
        foreach ($adresy As $k=>$v)
        {
            if($k=='adr_Id')
                $zapytanieSQL['adrh_IdAdresu']=$v;
            
            if($k!='adr_Id' && $k!='adr_IdObiektu' && $k!='adr_TypAdresu')
            {
                $zapytanieSQL['adrh_'.substr($k, 4,100)]=$v;
            }

        }
        
        if(isset($zapytanieSQL['adrh_Ulica']) && isset($zapytanieSQL['adrh_NrDomu']))
            $zapytanieSQL['adrh_Adres']=$zapytanieSQL['adrh_Ulica'].' '.$zapytanieSQL['adrh_NrDomu'];
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        if($this->connect->query($q))
            echo 'dodano '.$tabela.':'.$zapytanieSQL['adrh_Id'].' SELECT * FROM '.$tabela.'; <br>';
            
    }
    
    private function genXkh_Ewid():void
    {
        //kod wygenerowany automatycznieecho 
        echo 'tabela Xkh_Ewid: wygenerowana automatycznie<br>';
    }
    private function genXpk_Ewid():void
    {
        //kod wygenerowany automatycznieecho
        echo 'tabela xpk_Ewid: wygenerowana automatycznie<br>';
    }
    
    private function genKh__Kontrahent():void
    {
        $tabela='kh__Kontrahent';
        $dateNow = new DateTime(null, new DateTimeZone('Europe/Warsaw'));
        $this->time=$dateNow->format('Y-m-d H:i:s');        
        
        $zapytanieSQL=array();
        $zapytanieSQL['kh_Id']=(int)$this->ko_IdElementuKartoteki;
        $zapytanieSQL['kh_Symbol']=(string)$this->adr_Symbol;
        $zapytanieSQL['kh_Rodzaj']=(int)0;
        $zapytanieSQL['kh_REGON']=(string)$this->resultApiVAT['kh_REGON'];
        $zapytanieSQL['kh_CentrumAut']=(int)0;
        $zapytanieSQL['kh_InstKredytowa']=(int)0;
        $zapytanieSQL['kh_IdGrupa']=(int)1;
        $zapytanieSQL['kh_PlatOdroczone']=(int)1;
        $zapytanieSQL['kh_OdbDet']=(int)0;
        $zapytanieSQL['kh_MaxDokKred']=(int)0;
        $zapytanieSQL['kh_MaxWartDokKred']=(string)'.0000';
        $zapytanieSQL['kh_MaxWartKred']=(string)'.0000';
        $zapytanieSQL['kh_MaxDniSp']=(int)0;
        $zapytanieSQL['kh_NrAnalitykaD']=(string)$this->kh_NrAnalitykaD;
        $zapytanieSQL['kh_NrAnalitykaO']=(string)$this->kh_NrAnalitykaD;
        $zapytanieSQL['kh_ZgodaDO']=(int)0;
        $zapytanieSQL['kh_ZgodaMark']=(int)0;
        $zapytanieSQL['kh_ZgodaEMail']=(int)0;
        $zapytanieSQL['kh_CzyKomunikat']=(int)0;
        $zapytanieSQL['kh_KomunikatZawsze']=(int)1;
        $zapytanieSQL['kh_Jednorazowy']=(int)0;
        $zapytanieSQL['kh_Zablokowany']=(int)0;
        $zapytanieSQL['kh_AdresKoresp']=(int)0;
        $zapytanieSQL['kh_UpowaznienieVAT']=(int)0;
        $zapytanieSQL['kh_ProcKarta']=(string)'.0000';
        $zapytanieSQL['kh_ProcKredyt']=(string)'.0000';
        $zapytanieSQL['kh_ProcGotowka']=(string)'100.0000';
        $zapytanieSQL['kh_ProcPozostalo']=(string)'.0000';
        $zapytanieSQL['kh_EwVATSpMcOdliczenia']=(int)0;
        $zapytanieSQL['kh_EwVATZakRodzaj']=(int)2;
        $zapytanieSQL['kh_EwVATZakSposobOdliczenia']=(int)0;
        $zapytanieSQL['kh_EwVATZakMcOdliczenia']=(int)0;
        $zapytanieSQL['kh_TransakcjaVATSp']=(int)0;
        $zapytanieSQL['kh_TransakcjaVATZak']=(int)0;
        $zapytanieSQL['kh_PodVATZarejestrowanyWUE']=(int)0;
        $zapytanieSQL['kh_PlatPrzelew']=(int)0;
        $zapytanieSQL['kh_AdresDostawy']=(int)0;
        $zapytanieSQL['kh_CRM']=(int)0;
        $zapytanieSQL['kh_Potencjalny']=(int)0;
        $zapytanieSQL['kh_IdDodal']=(int)1;
        $zapytanieSQL['kh_IdZmienil']=(int)1;
        $zapytanieSQL['kh_DataDodania']=$dateNow;
        $zapytanieSQL['kh_DataZmiany']=$dateNow;
        $zapytanieSQL['kh_ProcPrzelew']=(string)'.0000';
        $zapytanieSQL['kh_Osoba']=(int)0;
        $zapytanieSQL['kh_Akcyza']=(int)1;
        $zapytanieSQL['kh_EFakturyZgoda']=(int)0;
        $zapytanieSQL['kh_MetodaKasowa']=(int)0;
        $zapytanieSQL['kh_StatusAkcyza']=(int)0;
        $zapytanieSQL['kh_CzynnyPodatnikVAT']=(int)0;
        $zapytanieSQL['kh_WzwIdFS']=(int)0;
        $zapytanieSQL['kh_WzwIdWZ']=(int)0;
        $zapytanieSQL['kh_WzwIdWZVAT']=(int)0;
        $zapytanieSQL['kh_WzwIdZK']=(int)0;
        $zapytanieSQL['kh_WzwIdZKZAL']=(int)0;
        $zapytanieSQL['kh_ZgodaNewsletterVendero']=(int)0;
        $zapytanieSQL['kh_KlientSklepuInternetowego']=(int)0;
        $zapytanieSQL['kh_WzwIdZD']=(int)0;
        $zapytanieSQL['kh_WzwIdCrmTransakcja']=(int)0;
        $zapytanieSQL['kh_StosujRabatWMultistore']=(int)0;
        $zapytanieSQL['kh_CelZakupu']=(int)0;
        $zapytanieSQL['kh_StosujIndywidualnyCennikWSklepieInternetowym']=(int)0;
        $zapytanieSQL['kh_OdbiorcaCesjaPlatnosci']=(int)0;
        $zapytanieSQL['kh_BrakPPDlaRozrachunkowAuto']=(int)0;
        $zapytanieSQL['kh_DomyslnyTypCeny']=(int)-2;
        $zapytanieSQL['kh_DomyslnaWalutaMode']=(int)0;
        $zapytanieSQL['kh_DomyslnyRachBankowyIdMode']=(int)0;
        $zapytanieSQL['kh_StosujSzybkaPlatnosc']=(int)1;

        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        //echo $q;
        if($this->connect->query($q))
            echo 'dodano '.$tabela.':'.$zapytanieSQL['kh_Id'].' SELECT * FROM '.$tabela.'; <br>';
        
    }

    private function genIns_Slad():void
    {
        $tabela='ins_Slad';
        $temp=array();
        $temp=$this->connect->query('SELECT max(slad_Id) FROM '.$tabela.';');
        
        
        $dateNow = new DateTime(null, new DateTimeZone('Europe/Warsaw'));
        $this->time=$dateNow->format('Y-m-d H:i:s');
        
        $unlock='SET IDENTITY_INSERT '.$tabela.' ON;';
        if($this->connect->query($unlock))
            echo 'dodblokowano '.$tabela.'; <br>';
        

        $zapytanieSQL=array();     
        $zapytanieSQL['slad_Id']=(int)($temp[0]+1);
        $zapytanieSQL['slad_TypObiektu']=(int)-12;
        $zapytanieSQL['slad_IdObiektu']=(int) $this->ko_IdElementuKartoteki;
        $zapytanieSQL['slad_Zdarzenie']=(int) 1;
        $zapytanieSQL['slad_CzasZdarzenia']=$dateNow;
        $zapytanieSQL['slad_IdUzytkownika']=(int)1;
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        if($this->connect->query($q))
            echo 'dodano '.$tabela.':'.$zapytanieSQL['slad_Id'].' SELECT * FROM '.$tabela.'; <br>';
        
        $lock='SET IDENTITY_INSERT '.$tabela.' OFF;';
        if($this->connect->query($unlock))
            echo 'zablokowano '.$tabela.'; <br>';
    }
    
}

/*
$name='test1'; $pass='test1'; $database='trial';
$kontrachent=new Kontrachenci($name, $pass, $database);
$kontrachent->getKontrachenci();
echo 'test<br>';
foreach($kontrachent->listaKontrachentow As $k=>$v)
{
    echo '<p>'.$k.' <br>';
    foreach($v As $k=>$va){
        echo $k.' '.$va.'<br>';
    }
    echo '</p>';
}
echo $kontrachent->listaKontrachentow['8821903368']['adr_Id'];
*/
?>