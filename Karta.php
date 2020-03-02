<?php

/** 
 * @author aaadmin
 * 
 */
class FakturaPaliwo
{
    private $czyWypisac=false;
    private $connect;
    private $kontrachent;
    private $rokRozliczeniowy;
    
    private $uz_Id=1;
    private $uz_Identyfika = 'Szef';
    private $uz_imie_i_nazwisko;
      
    private $dataWystawienia; //'2016-8-1';
    private $dataSprzedazy; //'';
    private $terminPlatnosci; //'';
    private $nip; //'8821903368';
    private $rodzajDokumentu; //'FV';
    private $numerDokumentu; //'16146/2016';
    private $wartoscDokumentuNetto; //56.96;
    private $wartoscDokumentuVAT; //13.10;
    private $wartoscDokumentuBrutto; //70.60
    private $formaPlatnosci; //'G';
    private $rejestr; //1;
    private $wartosci;
    
    // nz_finanse
    private $nzf_Id;
    //dkr__Dokument
    private $dkr_Id;
    private $dkr_IdRoku;
    private $dkr_Numer;
    //dkr_pozycja
    private $dko_Id;
    private $dko_IdDokumentu;
    
    private $ev_Id;
    private $nzd_IdDekretu;

    
    public function __construct($name,$pass,$database,$kontrachent)
    {
        require_once 'Connect.php';
        $this->connect=new Connect($name,$pass,$database); 
        $this->kontrachent=$kontrachent;
        //$this->kontrachent->listaKontrachentow();
    }
    public function configure()
    {
        $temp=array();
        $temp=$this->connect->query('SELECT uz_Id, uz_Identyfikator, uz_Imie, uz_Nazwisko FROM pd_Uzytkownik;');
        $this->uz_Id=$temp[0];
        $this->uz_Identyfika=$temp[1];
        $this->uz_imie_i_nazwisko=$temp[2].' '.$temp[3];
        
        $temp=$this->connect->queryLong('SELECT robr_Symbol, robr_Id FROM pd_RokObrotowy;');
        $wyn=array();
        for($i=0,$max=sizeof($temp);$i<$max;$i++)
        {
            $wyn[$temp[$i][0]]=$temp[$i][1];
        }
        $this->rokRozliczeniowy=$wyn;
        
        
        //echo $this->uz_Id.' '.$this->uz_Identyfika.' '.$this->uz_imie_i_nazwisko.'<br><br>';
        
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
        $this->wartoscDokumentuVAT=$wartoscDokumentuVAT;
        $this->wartoscDokumentuBrutto=$wartoscDokumentuNetto+$wartoscDokumentuVAT;
        $this->formaPlatnosci=$formaPlatnosci;
        $this->rejestr=$rejestr;
        $this->dkr_IdRoku= $this->rokRozliczeniowy[$this->dataWystawienia->format('Y')];
        
        $this->wartosci=$this->obliczeniaDekret();
        
        
    }
    public function createNewFaktura():void
    {   
        //$this->connect->query('BEGIN TRANSACTION');
        
        $this->genNz__Finanse();
        $this->genVat__EwidVAT();
        $this->genDkr__Dokument();
        $this->genDkr_Pozycja();
        $this->genNz_RozDekret();     
        $this->genVat_DaneVAT();
        $this->genDkr_SladRewizyjny();
        //$this->genIns_Slad();
        
        //echo 'createNewFaktura';
       // $this->genDkr__Dokument();
       // $this->fakturaVatPaliwo();
        //$this->connect->query('COMMIT TRANSACTION ELSE ROLLBACK TRANSACTION');
        $this->connect->disconnect();
       
    }
    

   
    private function genNz__Finanse():void
    {
        
        
        $tabela='nz__Finanse';
        //echo ' <font color="green">'.$tabela.'</font> ';
        $temp=array();
        $temp=$this->connect->query('SELECT max(nzf_Id) FROM '.$tabela.';');
        $this->nzf_Id=$temp[0]+1;

        
        //$this->dkr_IdRoku=(int)$this->dataWystawienia->format('Y') - $this->rokRozliczeniowy;        
        
        $zapytanieSQL['nzf_Id']=(int)  $this->nzf_Id;
        $zapytanieSQL['nzf_Data']=(string) $this->dataWystawienia->format('Y-m-d');
        $zapytanieSQL['nzf_TerminPlatnosci']=(string) $this->terminPlatnosci->format('Y-m-d');
        $zapytanieSQL['nzf_Typ']=(int) 40;
        $zapytanieSQL['nzf_WartoscPierwotna']=(float) $this->wartoscDokumentuBrutto;
        $zapytanieSQL['nzf_WartoscPierwotnaWaluta']=(float) $this->wartoscDokumentuBrutto;
        $zapytanieSQL['nzf_Wartosc']=(float) $this->wartoscDokumentuBrutto;
        $zapytanieSQL['nzf_WartoscWaluta']=(float) $this->wartoscDokumentuBrutto;
        $zapytanieSQL['nzf_Splata']=(float) 0.0000;
        $zapytanieSQL['nzf_SplataWaluta']=(float) 0.0000;
        $zapytanieSQL['nzf_IdWaluty']=(string) 'PLN';
        $zapytanieSQL['nzf_Kurs']=(float) 1.0000;
        $zapytanieSQL['nzf_RodzajKursu']=(int) 1;
        //$zapytanieSQL['nzf_Tytulem']=(varchar) ;
        $zapytanieSQL['nzf_TypOdsetek']=(int) 1;
        $zapytanieSQL['nzf_StopaOdsetek']=(float) 0.0000;
   /*     
        'adr_Id'=>$s[1],            //10
        'adr_IdHistoria'=>$temp[0], //6
        'adr_IdObiektu'=>$s[2],     //2
        'Symbol'=>$s[3],            //ORLEN
        'adr_IdWersja'=>$s[4],      //1
   */     
        $zapytanieSQL['nzf_IdAdresu']=(int) $this->kontrachent->listaKontrachentow[$this->nip]['adr_Id'];
        $zapytanieSQL['nzf_IdHistoriiAdresu']=(int) $this->kontrachent->listaKontrachentow[$this->nip]['adr_IdHistoria'];
        $zapytanieSQL['nzf_IdObiektu']=(int) $this->kontrachent->listaKontrachentow[$this->nip]['adr_IdObiektu'];
        $zapytanieSQL['nzf_TypObiektu']=(int) 1;
        $zapytanieSQL['nzf_Status']=(int) 1;
        $zapytanieSQL['nzf_IdWystawil']=(int) $this->uz_Id;
        $zapytanieSQL['nzf_Wystawil']=(string) $this->uz_imie_i_nazwisko;
        $zapytanieSQL['nzf_Przeniesiony']=(int) 0;
        $zapytanieSQL['nzf_Nota']=(int) 0;
        $zapytanieSQL['nzf_Podtyp']=(int) 1;
        $zapytanieSQL['nzf_Zrodlo']=(int) 2;
        $zapytanieSQL['nzf_Program']=(int) 19;
        $zapytanieSQL['nzf_Powiazanie']=(int) 1;
        $zapytanieSQL['nzf_Korekta']=(int) 0;
        $zapytanieSQL['nzf_Transakcja']=(string) $this->numerDokumentu;
        $zapytanieSQL['nzf_NumerPelny']=(string) $this->numerDokumentu;
        $zapytanieSQL['nzf_GenerujTytulem']=(int) 0;
        $zapytanieSQL['nzf_Zaliczka']=(int) 0;
        $zapytanieSQL['nzf_DlaNieznany']=(int) 0;
        $zapytanieSQL['nzf_WyslanaHB']=(int) 0;
        $zapytanieSQL['nzf_Wydrukowana']=(boolean) false;
        $zapytanieSQL['nzf_ObslugaRachunku']=(boolean) false;
        $zapytanieSQL['nzf_Gotowkowa']=(boolean) false;
        $zapytanieSQL['nzf_TypPrzelewu']=(string) '';
        $zapytanieSQL['nzf_Transfer']=(boolean) false;
        $zapytanieSQL['nzf_LiczbaJednostek']=(int) 1;
        $zapytanieSQL['nzf_MetodaKasowa']=(boolean) false;
        $zapytanieSQL['nzf_IdTransakcjiVat']=(boolean) false;
        $zapytanieSQL['nzf_IzbaCelna']=(boolean) false;
        $zapytanieSQL['nzf_PodtypPP']=(boolean) false;
        $zapytanieSQL['nzf_VATPierwotnyWaluta']=(float) 0.0000;
        $zapytanieSQL['nzf_VATPierwotny']=(float) 0.0000;
        $zapytanieSQL['nzf_VATPozostaloWaluta']=(float) 0.0000;
        $zapytanieSQL['nzf_VATPozostalo']=(float) 0.0000;
        $zapytanieSQL['nzf_MechanizmPodzielonejPlatnosci']=(int) 0;
 

        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['nzf_Id'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
          $this->showProperty($zapytanieSQL);   
           

        $zapytanieSQL['nzf_Id']=(int)  $this->nzf_Id+1;
        $zapytanieSQL['nzf_Data']=(string) $this->dataWystawienia->format('Y-m-d');
        $zapytanieSQL['nzf_TerminPlatnosci']=(string) '';
        $zapytanieSQL['nzf_Typ']=(int) 42;
        $zapytanieSQL['nzf_WartoscPierwotna']=(float) 0.0000;
        $zapytanieSQL['nzf_WartoscPierwotnaWaluta']=(float) 0.0000;
        //$zapytanieSQL['nzf_Tytulem']=(varchar) ;
                  
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['nzf_Id'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL);   
        
            
    }
  

    private function genVat__EwidVAT():void
    {
        
        
        $tabela='vat__EwidVAT';
        //echo ' <font color="green">'.$tabela.'</font> ';
        $temp=array();
        $temp=$this->connect->query('SELECT max(ev_Id) FROM '.$tabela.';');
        $this->ev_Id=$temp[0]+1;
        /*
        if($this->dkr_Id==null){
            $temp=$this->connect->query('SELECT max(dkr_Id) FROM dkr__Dokument;');
            $this->dkr_Id=$temp[0]+1;
        }
        
        $this->ev_Id=$this->dkr_Id;
        */
        
        $ostatniDnienMiesiaca=$this->getOstatniDzienMiesiaca();
        $ostatniDzienRoku=DateTime::createFromFormat('Y-m-d H:i:s', $this->dataWystawienia->format('Y').'-12-31 00:00:00');
        
        $zapytanieSQL['ev_Id']=(int) $this->ev_Id;
        $zapytanieSQL['ev_NrDokumentu']=(string) $this->numerDokumentu; // varchar
        $zapytanieSQL['ev_Rodzaj']=(int) 1;
        $zapytanieSQL['ev_IdKh']=(int) $this->kontrachent->listaKontrachentow[$this->nip]['adr_IdObiektu'];
        
        
        $temp=$this->connect->query('select adr_Nazwa, adr_NazwaPelna, adr_Adres, adr_Miejscowosc, adr_Kod from adr__Ewid where adr_Id='.$this->kontrachent->listaKontrachentow[$this->nip]['adr_Id'].';');
        $zapytanieSQL['ev_NazwaKh']=(string) $temp[0]; // varchar
        $zapytanieSQL['ev_NazwaPelnaKh']=(string) $temp[1]; // varchar
        $zapytanieSQL['ev_UlicaKh']=(string) $temp[2]; // varchar
        $zapytanieSQL['ev_MiastoKh']=(string) $temp[3]; // varchar
        $zapytanieSQL['ev_KodPocztowyKh']=(string) $temp[4]; // varchar
        
        $zapytanieSQL['ev_NIPKh']=(string) $this->nip; // varchar    
        $zapytanieSQL['ev_DataWpisu']=$this->dataWystawienia;
        $zapytanieSQL['ev_DataSZ']=$this->dataWystawienia;
        $zapytanieSQL['ev_MiesiacOdliczVAT']=$ostatniDnienMiesiaca;
        
        $zapytanieSQL['ev_IdKategorii']=(int) 2;
        $zapytanieSQL['ev_Opis']=(string) 'paliwo'; // varchar
        $zapytanieSQL['ev_Netto']=(float) $this->wartoscDokumentuNetto; // money
        $zapytanieSQL['ev_KwotaVAT']=(float) $this->wartoscDokumentuVAT; // money
        $zapytanieSQL['ev_Brutto']=(float) $this->wartoscDokumentuBrutto; // money
        $zapytanieSQL['ev_IdTypuEwidVAT']=(int) 2;
        $zapytanieSQL['ev_FakturaRR']=(int) 0;
        $zapytanieSQL['ev_DataPlatnosciRR']=$ostatniDzienRoku;
        $zapytanieSQL['ev_Uwagi']=(string) ''; // varchar
        $zapytanieSQL['ev_RodzajZakupu']=(int) 3;
        $zapytanieSQL['ev_SposobOdliczania']=(int) 0;// Lp. 24
        $zapytanieSQL['ev_TypObiektu']=(int) 11;// Lp. 25
        
        $zapytanieSQL['ev_IdObiektu']=(int) $this->dkr_Id;// Lp. 26
        $zapytanieSQL['ev_Ksiegowany']=(boolean) true; //(bit) 1; // Lp. 27
        $zapytanieSQL['ev_Importowany']=(boolean) false; //(bit) 0; // Lp. 28
        $zapytanieSQL['ev_KorektaSN']=(boolean) false; //(bit) 0; // Lp. 29
        $zapytanieSQL['ev_ImportPochodzenie']=(int) 0;// Lp. 32
        $zapytanieSQL['ev_TransakcjaVAT']=(int) 0;// Lp. 33
        $zapytanieSQL['ev_NieWliczanyDoPB']=(boolean) false; //(bit) 0; // Lp. 35
        $zapytanieSQL['ev_IdOkresuRoku']=(int) 2;// Lp. 36
        $zapytanieSQL['ev_MalyPojazd']=(int) 4;// Lp. 37
        $zapytanieSQL['ev_PlatnoscKredyt']=(boolean) false; //(bit) 0; // Lp. 38
        $zapytanieSQL['ev_TerminPlatnosci']=$ostatniDzienRoku;
        //$zapytanieSQL['ev_NrDokumentuSort']=(string) 000000000000000000000000027061/0000000000000000000000000412/00000000000000000000000015; // varchar// Lp. 41
        $zapytanieSQL['ev_AutoPrzeliczaj']=(boolean) true; //(bit) 1; // Lp. 42
        $zapytanieSQL['ev_RozliczanaUsluga']=(boolean) false; //(bit) 0; // Lp. 43
        $zapytanieSQL['ev_DokVatMarza']=(boolean) false; //(bit) 0; // Lp. 44
        $zapytanieSQL['ev_DataZakDostawy']=$this->dataWystawienia;
        $zapytanieSQL['ev_WlaczKorygowanieVAT']=(boolean) false; //(bit) 0; // Lp. 46
        $zapytanieSQL['ev_KorektaVATTyp']=(int) 0;// Lp. 47
        
        $dataPrzyszla=DateTime::createFromFormat('Y-m-d H:i:s', $this->dataWystawienia->format('Y-m-d').' 00:00:00');
        $dataPrzyszla->modify('+489 days');
        
        $zapytanieSQL['ev_KorektaVATTerminKorekty']=$dataPrzyszla;// Lp. 49
        $zapytanieSQL['ev_KorektaVATPlatnoscCzesciowa']=(boolean) false; //(bit) 0; // Lp. 52
        $zapytanieSQL['ev_VatMetodaKasowa']=(boolean) false; //(bit) 0; // Lp. 55
        $zapytanieSQL['ev_CelZakupu']=(int) 0;// Lp. 58
        $zapytanieSQL['ev_PodtypDok']=(int) 0;// Lp. 59
        $zapytanieSQL['ev_PrzypadekSzczegolnyPIT']=(int) 1;// Lp. 62
        
       
        
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        
        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['ev_Id'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL);   
    }
    
    private function genDkr__Dokument():void
    {
        $tabela='dkr__Dokument';
        //echo ' <font color="green">'.$tabela.'</font> ';
        $temp=array();
        if(empty($this->dkr_Id)){
            $temp=$this->connect->query('SELECT max(dkr_Id) FROM '.$tabela.';');
            $this->dkr_Id=$temp[0]+1;
        }
        
        $temp=$this->connect->query("SELECT max(dkr_Numer) FROM dkr__Dokument where dkr_Rejestr='1';");
        $dkr_Numer=$temp[0]+1;
        $this->dkr_Numer=$dkr_Numer;
   
        $zapytanieSQL['dkr_Id']=(int) $this->dkr_Id;
        $zapytanieSQL['dkr_IdRoku']=(int) $this->dkr_IdRoku;
        $zapytanieSQL['dkr_DataDekretacji']=$this->dataWystawienia;
        $zapytanieSQL['dkr_DataDokumentu']=$this->dataWystawienia;
        $zapytanieSQL['dkr_DataOperacji']=$this->dataWystawienia;
        $zapytanieSQL['dkr_Status']=(int) 1;
        $zapytanieSQL['dkr_Rejestr']=(string) '1'; // varchar
        $zapytanieSQL['dkr_Numer']=(int) $dkr_Numer;
        $zapytanieSQL['dkr_NrPelny']=(string) '1-'.$dkr_Numer; // varchar
        $zapytanieSQL['dkr_DokumentZrodlowy']=(string) $this->numerDokumentu; // varchar
        $zapytanieSQL['dkr_KontrolaBilansowania']=(int) 1;
        $zapytanieSQL['dkr_Waluta']=(string) 'PLN'; // char
        $zapytanieSQL['dkr_WalutaWyswietlana']=(string) 'PLN'; // varchar
        $zapytanieSQL['dkr_KursDokumentu']=(float) 1.0000; // money
        $zapytanieSQL['dkr_LiczbaJednostek']=(int) 1;
        $zapytanieSQL['dkr_RodzajKursu']=(int) 1;
        $zapytanieSQL['dkr_DataKursu']=$this->getDataKursu();
        $zapytanieSQL['dkr_IdBanku']=(int) 1;
        $zapytanieSQL['dkr_Kwota']=(float) 2*$this->wartoscDokumentuBrutto; // money
        $zapytanieSQL['dkr_KwotaWaluta']=(float) 2*$this->wartoscDokumentuBrutto; // money
        $zapytanieSQL['dkr_Uwagi']=(string) ''; // varchar
        $zapytanieSQL['dkr_IdKategorii']=(int) 2;
        $zapytanieSQL['dkr_Dekretowal']=(string) $this->uz_Identyfika; // varchar
        //$zapytanieSQL['dkr_Ksiegowal']=(string) $this->uz_Identyfika; // varchar
        //$zapytanieSQL['dkr_Ksiegowal']=$this->uz_Id;
        //$zapytanieSQL['dkr_TypObiektu']=(int) $this->kontrachent->listaKontrachentow[$this->nip]['adr_IdHistoria'];
        $zapytanieSQL['dkr_TypObiektu']=(int) 6;
        $zapytanieSQL['dkr_IdObiektu']=(int) $this->ev_Id;
        $zapytanieSQL['dkr_ImportPochodzenie']=(int) 0;
        $zapytanieSQL['dkr_IdKh']=(int) $this->kontrachent->listaKontrachentow[$this->nip]['adr_IdObiektu'];
        $zapytanieSQL['dkr_SymbolKh']=(string) $this->kontrachent->listaKontrachentow[$this->nip]['Symbol']; // varchar
        $zapytanieSQL['dkr_Podtyp']=(int) 0;
        $zapytanieSQL['dkr_TrybWprowadzaniaRozrachunkow']=(int) 2;
        $zapytanieSQL['dkr_WalutaKursWyswietlany']=(string) '1,0000'; // varchar
        $zapytanieSQL['dkr_KwotaWn']=(float) 2*$this->wartoscDokumentuBrutto; // money
        $zapytanieSQL['dkr_KwotaMa']=(float) 2*$this->wartoscDokumentuBrutto; // money
        $zapytanieSQL['dkr_KwotaWalutaWn']=(float) 2*$this->wartoscDokumentuBrutto; // money
        $zapytanieSQL['dkr_KwotaWalutaMa']=(float) 2*$this->wartoscDokumentuBrutto; // money
        $zapytanieSQL['dkr_Storno']=(int) 0;
        $zapytanieSQL['dkr_Opis']=(string) 'paliwo'; // varchar
        $zapytanieSQL['dkr_RodzajDowodu']=(int) 10;

            
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
    private function obliczeniaDekret():array{

        //obliczenia dla VAT
        $wartosci=array();
        $wartosci['23']['netto']=$this->obliczWiekszePol($this->wartoscDokumentuNetto,false);
        $wartosci['npo']['netto']=$this->wartoscDokumentuNetto-$wartosci['23']['netto'];
        $wartosci['23']['VAT']=$this->obliczWiekszePol($this->wartoscDokumentuVAT,false);
        $wartosci['npo']['VAT']=$this->wartoscDokumentuVAT-$wartosci['23']['VAT'];
        $wartosci['23']['brutto']=$wartosci['23']['netto']+$wartosci['23']['VAT'];
        $wartosci['npo']['brutto']=$wartosci['npo']['netto']+$wartosci['npo']['VAT'];
        $wartosci['221-23']=$wartosci['23']['VAT'];
        $wartosci['401']=round(($this->wartoscDokumentuNetto+$wartosci['23']['VAT'])*0.2,2);
        $wartosci['409']=$this->wartoscDokumentuBrutto-$wartosci['401']-$wartosci['221-23'];
        
        return $wartosci;
 
    }
    
    
    private function genDkr_Pozycja():void
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
        $zapytanieSQL['dko_Konto']=(string) '221-23'; // varchar// Lp. 6
        unset($zapytanieSQL['dko_KwotaMa']); // money// Lp. 9
        unset($zapytanieSQL['dko_KwotaMaWaluta']); // money// Lp. 10
        
        $zapytanieSQL['dko_KwotaWn']=$this->wartosci['221-23'];
        $zapytanieSQL['dko_KwotaWnWaluta']=$this->wartosci['221-23'];
        $zapytanieSQL['dko_Grupa']=(int) 1;// Lp. 18
        $zapytanieSQL['dko_LpWiersza']=(int) 2;// Lp. 19
        $zapytanieSQL['dko_TypWiersza']=(int) 0;// Lp. 20
        $zapytanieSQL['dko_IdStawkiVAT']=(int) 100001;
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        //echo $q.'<br>';
        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['dko_Id'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL);
        
        $zapytanieSQL['dko_Id']=(int) $this->dko_Id+2;// Lp. 1
        $zapytanieSQL['dko_Konto']=(string) '401'; // varchar// Lp. 6
        $zapytanieSQL['dko_KwotaWn']=(float) $this->wartosci['401'];
        $zapytanieSQL['dko_KwotaWnWaluta']=(float) $this->wartosci['401'];
        $zapytanieSQL['dko_Grupa']=(int) 1;// Lp. 18
        $zapytanieSQL['dko_LpWiersza']=(int) 3;// Lp. 19
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
        
        $zapytanieSQL['dko_Id']=(int) $this->dko_Id+3;// Lp. 1
        $zapytanieSQL['dko_Konto']=(string) '409'; // varchar// Lp. 6
        $zapytanieSQL['dko_KwotaWn']=(float) $this->wartosci['409'];
        $zapytanieSQL['dko_KwotaWnWaluta']=(float) $this->wartosci['409'];
        // $zapytanieSQL['dko_KwotaMa']=(float) $this->wartoscDokumentuBrutto; // money// Lp. 9
        // $zapytanieSQL['dko_KwotaMaWaluta']=(float) $this->wartoscDokumentuBrutto; // money// Lp. 10
        $zapytanieSQL['dko_Grupa']=(int) 1;// Lp. 18
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
        
        $this->nzd_IdDekretu=$this->dko_Id+4;
        $zapytanieSQL['dko_Id']=(int) $this->dko_Id+4;// Lp. 1
        $zapytanieSQL['dko_Konto']=(string) '210-'.$ko_Nr; // varchar// Lp. 6
        unset($zapytanieSQL['dko_KwotaWn']);
        unset($zapytanieSQL['dko_KwotaWnWaluta']);
        $zapytanieSQL['dko_KwotaMa']=(float) $this->wartoscDokumentuBrutto; // money// Lp. 9
        $zapytanieSQL['dko_KwotaMaWaluta']=(float) $this->wartoscDokumentuBrutto; // money// Lp. 10
        $zapytanieSQL['dko_Grupa']=(int) 2;// Lp. 18
        $zapytanieSQL['dko_LpWiersza']=(int) 5;// Lp. 19
        $zapytanieSQL['dko_TypWiersza']=(int) 1;// Lp. 20
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        //echo $q.'<br>';
        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['dko_Id'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL);
        
        $zapytanieSQL['dko_Id']=(int) $this->dko_Id+5;// Lp. 1
        $zapytanieSQL['dko_KwotaWn']=$this->wartoscDokumentuBrutto;;
        $zapytanieSQL['dko_KwotaWnWaluta']=$this->wartoscDokumentuBrutto;;
        unset($zapytanieSQL['dko_KwotaMa']);  // money// Lp. 9
        unset($zapytanieSQL['dko_KwotaMaWaluta']); // money// Lp. 10
        $zapytanieSQL['dko_Grupa']=(int) 2;// Lp. 18
        $zapytanieSQL['dko_LpWiersza']=(int) 6;// Lp. 19
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
    

    private function genNz_RozDekret():void
    {
        $tabela='nz_RozDekret';
        //echo ' <font color="green">'.$tabela.'</font> ';
        //echo '$this->nzf_Id '.$this->nzf_Id.'<br>';
        $temp=array();
        $zapytanieSQL['nzd_IdDekretu']=(int) $this->nzd_IdDekretu;// Lp. 1
        $zapytanieSQL['nzd_IdRozrachunku']=(int) $this->nzf_Id;// Lp. 2
        $zapytanieSQL['nzd_IdRoku']=(int) $this->dkr_IdRoku;// Lp. 3
        $zapytanieSQL['nzd_Status']=(int) 1;// Lp. 4
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        //echo $q.'<br>';
        //$this->showProperty($zapytanieSQL);
        

        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['nzd_IdDekretu'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL);
        
        $zapytanieSQL['nzd_IdDekretu']=(int) $this->nzd_IdDekretu+1;// Lp. 1
        $zapytanieSQL['nzd_IdRozrachunku']=(int) $this->nzf_Id+1;// Lp. 2
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        //echo $q.'<br>';
        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['nzd_IdDekretu'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL);
        
    }
    
    private function genVat_DaneVAT():void
    {
        $tabela='vat_DaneVAT';
        //echo ' <font color="green">'.$tabela.'</font> ';
        $temp=array();
        $temp=$this->connect->queryLong('SELECT TOP 2 dv_Id,dv_IdEwidVAT  FROM vat_DaneVAT order by  dv_Id DESC;');
        
        if($temp[0][1]==$temp[1][1])
            $dv_Id=$temp[0][0]+36;
        else
            $dv_Id=$temp[0][0]+42;
        
        $zapytanieSQL['dv_Id']=(int) $dv_Id;// Lp. 1
        $zapytanieSQL['dv_IdEwidVAT']=(int) $this->ev_Id;// Lp. 2
        $zapytanieSQL['dv_Typ']=(int) 3;// Lp. 3
        $zapytanieSQL['dv_IdStawkiVAT']=(int) 100001;// Lp. 4
        
        $zapytanieSQL['dv_KwotaVAT']= $this->wartosci['23']['VAT']; // money// Lp. 6
        $zapytanieSQL['dv_Netto']=$this->wartosci['23']['netto']; // money// Lp. 7
        $zapytanieSQL['dv_Brutto']=$this->wartosci['23']['brutto']; // money// Lp. 8
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
        
        $zapytanieSQL['dv_Id']=(int) $dv_Id+6;// Lp. 1
        $zapytanieSQL['dv_IdStawkiVAT']=(int) 9;// Lp. 4
        $zapytanieSQL['dv_KwotaVAT']=$this->wartosci['npo']['VAT']; // money// Lp. 6
        $zapytanieSQL['dv_Netto']=$this->wartosci['npo']['netto']; // money// Lp. 7
        $zapytanieSQL['dv_Brutto']= $this->wartosci['npo']['brutto']; // money// Lp. 8
        //echo $q.'<br>';
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        //echo $q;
        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['dv_Id'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL);
            
                
    }
    
    
    private function genDkr_SladRewizyjny():void
    {
        $tabela='dkr_SladRewizyjny';
        //echo ' <font color="green">'.$tabela.'</font> ';
        $temp=array();
        
        $unlock='SET IDENTITY_INSERT '.$tabela.' ON;';
        if($this->connect->query($unlock))
            echo 'dodblokowano '.$tabela.'; <br>';
        
        $temp=$this->connect->query('SELECT max(srw_id) FROM '.$tabela.';');        
 
        $zapytanieSQL['srw_Id']=(int) $temp[0]+1;// Lp. 1
        $zapytanieSQL['srw_NrDokumentu']=(string) '1-'.$this->dkr_Numer; // varchar// Lp. 2
        $zapytanieSQL['srw_Operacja']=(int) 0;// Lp. 3
        $zapytanieSQL['srw_Data']=new DateTime(null, new DateTimeZone('Europe/Warsaw'));// Lp. 4
        $zapytanieSQL['srw_Uzytkownik']=(string) $this->uz_Identyfika;; // varchar// Lp. 5
        $zapytanieSQL['srw_IdRoku']=(int) $this->dkr_IdRoku;// Lp. 6
        $zapytanieSQL['srw_DataDekretacji']= $this->dataWystawienia;// Lp. 7
        $zapytanieSQL['srw_DokumentZrodlowy']=(string) $this->numerDokumentu; // varchar// Lp. 8
        $zapytanieSQL['srw_Rejestr']=(string) 1; // varchar// Lp. 9

            
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        //echo $q;   
        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['srw_Id'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL);
         
        $lock='SET IDENTITY_INSERT '.$tabela.' OFF;';
        if($this->connect->query($unlock))
            echo 'zablokowano '.$tabela.'; <br>';
                        
    }
    
    private function genIns_Slad():void
    {
        $tabela='dkr_SladRewizyjny';
        //echo ' <font color="green">'.$tabela.'</font> ';
        $temp=array();
        $temp=$this->connect->query('SELECT max(srw_id) FROM '.$tabela.';');

        $zapytanieSQL['slad_Id']=(int) 3;// Lp. 1
        $zapytanieSQL['slad_TypObiektu']=(int) -12;// Lp. 2
        $zapytanieSQL['slad_Zdarzenie']=(int) 6;// Lp. 4
        $zapytanieSQL['slad_CzasZdarzenia']=new DateTime(null, new DateTimeZone('Europe/Warsaw'));// Lp. 5
        $zapytanieSQL['slad_IdUzytkownika']=(int) 1;// Lp. 6
        
        
        $q=$this->genSQLqueryFromTable($tabela, $zapytanieSQL);
        //echo $q;

        if($this->czyWypisac==False){
            if($s=$this->connect->query($q))
                echo 'dodano '. $tabela.':'.$zapytanieSQL['srw_Id'].' SELECT * FROM '.$tabela.'; <br>';
        }
        else
            $this->showProperty($zapytanieSQL);
            
                    
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
                
                if(is_bool($v)==true)
                    if($v==true)
                        $v=1;
                        else
                            $v=0;
                            
                            if($v instanceof DateTime)
                                //$v='GETDATE ()';
                                //$v="CONVERT(VARCHAR,'".$v->format('Y-m-d H:i:s')."',120)";
                                $v="CONVERT(VARCHAR,'".$v->format('Y-m-d H:i:s')."',120)";
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
    
    private function obliczWiekszePol($wartosc, $czyWieksze):float {
        $s=(int)($wartosc*100);
        if($s%2==0)
        {
            return $wartosc/2;
        }
        else {
            if($czyWieksze==true){
                $s+=1;
                return $s/=200;
            }
            else {
                $s-=1;
                return $s/=200;
            }
        }
        
    }
    
    
    private function getDataKursu():DateTime{
        $format='Y-m-d H:i:s';
        $test=DateTime::createFromFormat($format, $this->dataWystawienia->format($format));
        if($test>$this->dataSprzedazy)
            $test=$this->DataSprzedaży;
            
            if($test->format('w')>1)
                $test->modify('yesterday');
                else {
                    $test->modify('- 3 days');
                }
                return $test;
    }
    
    private function getOstatniDzienMiesiaca():DateTime{
        $format='Y-m-d H:i:s';//t
        $test=DateTime::createFromFormat($format, $this->dataWystawienia->format('Y-m-t').' 00:00:00');
        return $test;
    }
    
    private function showProperty($zapytanieSQL):void
    {
        echo '<table border="1">';
        $lp=1;
        foreach($zapytanieSQL As $k=>$v)
        {
            if($v instanceof DateTime)
                echo '<tr><td>'.$lp.'</td><td>'.$k.'</td><td> '.$v->format('Y-m-d H:i:s').'</td></tr>';
                else
                    if($v==null)
                        echo '<tr><td>'.$lp.'</td><td>'.$k.'</td><td> null</td></tr>';
                        else
                            echo '<tr><td>'.$lp.'</td><td>'.$k.'</td><td> '.$v.'</td></tr>';
            $lp++;
        }
       echo '</table><br>';
    }

    public function toString():string{
        return
        
        //'rokRozliczeniowy'.$this->rokRozliczeniowy.'<br><br>'.
            
        'uz_Id' .$this->uz_Id.'<br>'.
        'uz_Identyfika '.$this->uz_Identyfika.'<br>'.
        'uz_imie_i_nazwisko '.$this->uz_imie_i_nazwisko.'<br><br>'.
        
        'dataWystawienia '. $this->dataWystawienia->format('Y-m-d H:i:s').'<br>'.
        'dataSprzedazy '. $this->dataSprzedazy->format('Y-m-d H:i:s').'<br>'.
        'terminPlatnosci '. $this->terminPlatnosci->format('Y-m-d H:i:s').'<br><br>'.
        'nip '.$this->nip.'<br>'.
        'rodzajDokumentu '.$this->rodzajDokumentu.'<br>'.
        'numerDokumentu '.$this->numerDokumentu.'<br>'.
        'wartoscDokumentuNetto '.$this->wartoscDokumentuNetto.'<br>'.
        'wartoscDokumentuVAT '.$this->wartoscDokumentuVAT.'<br>'.
        'wartoscDokumentuBrutto '.$this->wartoscDokumentuBrutto.'<br>'.
        'formaPlatnosci '.$this->formaPlatnosci.'<br>'.
        'rejestr '.$this->rejestr.'<br><br>'.
            
            
        'nzf_Id '.$this->nzf_Id.'<br><br>'.
        
        'dkr_Id '.$this->dkr_Id.'<br><br>'.
        'dkr_IdRoku '.$this->dkr_IdRoku.'<br>'.
        'dkr_Numer '.$this->dkr_Numer.'<br>'.
            
        'dko_Id ' .$this->dko_Id.'<br><br>'.
        'dko_IdDokumentu ' .$this->dko_IdDokumentu.'<br>'.
            
        'ev_Id' .$this->ev_Id.'<br>'.
        'nzd_IdDekretu' .$this->nzd_IdDekretu.'<br>';
        
    }
}
?>


