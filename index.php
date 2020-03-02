<html>
<head>
<meta charset="UTF-8">
</head>
<body>
	<div id="czas"></div>
	<div id="pamiec"></div>
	<form name="fullSearch" method="post" action="index.php">
        <!-- Podstawowe pole tekstowe -->
        <table>
        	<tr>
        		<td>nazwa bazy danych</td><td>
                                                <select name="baza">
                                                	<option selected>test</option>
                                                	<option>test_paliwo</option>
                                                	<option>test_kopia</option>
                                                	<option>ZR_SERVICE</option>
                                                </select></td>
        	</tr>
        	<tr>
        		<td>przesukaj</td><td>wszyskie tabele: <input type="radio" name="typ" value="0" checked></td>
        		</tr>
            <tr>
        		<td></td>			<td>kontrachent: <input type="radio" name="typ" value="1"></td>
        		</tr>   
        	<tr>
        		<td></td>			<td>got paliwo: <input type="radio" name="typ" value="2"></td>
        		</tr>   
        	<tr>
        		<td></td>			<td>got ogol: <input type="radio" name="typ" value="3"></td>
        		</tr>    
                	<tr>
        		<td></td>			<td>got bez vat: <input type="radio" name="typ" value="4"></td>
        		</tr>                          
                                            
        	
        </table>
        <input type="submit" value="Wyślij formularz">
        </form>
        <p>
<?php
function getmicrotime()
{
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
}
$time=getmicrotime();

function PHPOpenConnection($Database)
{
    $serverName = "AAADMIN-1\INSERTGT"; //serverName\instanceName
    // Since UID and PWD are not specified in the $connectionInfo array,
    // The connection will be attempted using Windows Authentication.
    //$Database='test_kopia';
    echo $Database.'<br>';
    $connectionInfo = array("Uid"=>"test1", "PWD"=>"test1","Database"=>$Database, "CharacterSet" => "UTF-8");
    // utf8_encode(
    $conn = sqlsrv_connect( $serverName, $connectionInfo);
    
    if( $conn ) {
        return $conn;
    }else{
        echo "Connection could not be established.<br /><p>";
        //$error=sqlsrv_errors();
        foreach( sqlsrv_errors() AS $k=>$v)
        {
            if(is_array($v))
            {
                foreach( $v AS $a=>$b){
                    echo $a.': '.$b.'<br>';
                }
            }
            else {
                echo $k.': '.$v.'<br>';
            }
        }
        echo '</p>';
    }
}

function zapiszDaneZTabele($table,$link):array // nagłówek + wartosci rekordów
{
    
    $wyniki=array();
    
    $query="SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$table."'";
    $r=sqlsrv_query($link, $query);
    if( $r === false)
    {
        echo "Error in query preparation/execution.\n";
        die( print_r( sqlsrv_errors(), true));
    }
    while($res=sqlsrv_fetch_array($r,SQLSRV_FETCH_NUMERIC))
    {
        for($i=0,$max=sizeof($res);$i<$max;$i++){
            if($res[$i] instanceof DateTime)
                $wyniki[][$i]=$res[$i]->format('y-m-d');
                else
                    $wyniki[][$i]=$res[$i];
        }
    
    }

    $query='SELECT * FROM '.$table.';';
    $r=sqlsrv_query($link, $query);
    if( $r === false)
    {
        echo "Error in query preparation/execution.\n";
        die( print_r( sqlsrv_errors(), true));
    }  
    while($res=sqlsrv_fetch_array($r,SQLSRV_FETCH_NUMERIC))
    {
        for($i=0,$max=sizeof($res);$i<$max;$i++){
            if($res[$i] instanceof DateTime)
                $wyniki[$i][]=$res[$i]->format('y-m-d');
                else
                    $wyniki[$i][]=$res[$i];
        }
    }
    return $wyniki;
}

function ostatnioDodaneRekordy($link,string $table,int $liczba, int $krotnosc):array // nagłówek + wartosci rekordów
{
    $wyniki=array();
    $id='';
    if($krotnosc<1)
        $krotnosc=1;
    
    $query="SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$table."'";
    $r=sqlsrv_query($link, $query);
    if( $r === false)
    {
        echo "Error in query preparation/execution.\n";
        die( print_r( sqlsrv_errors(), true));
    }
    $j=0;
    while($res=sqlsrv_fetch_array($r,SQLSRV_FETCH_NUMERIC))
    {
        for($i=0,$max=sizeof($res);$i<$max;$i++){
            if($res[$i] instanceof DateTime)
                $wyniki[$j][$i]=$res[$i]->format('Y-m-d H:i:s');
                else
                    {
                        if($res[$i]==NULL)
                            $wyniki[$j][$i]='NULL';
                            else
                                $wyniki[$j][$i]=$res[$i];
                    }
            if($id=='')
                $id=$res[$i];
                
        }
        $j++;
        
    }
    
    
    $query='SELECT * FROM '.$table.';'; 
    $query='SELECT TOP '.$liczba*$krotnosc.' * FROM '.$table.' ORDER BY dbo.'.$table.'.'.$id.'  DESC;';
    //echo $query.'<br>';
    $r=sqlsrv_query($link, $query);
    if( $r === false)
    {
        echo "Error in query preparation/execution.\n";
        die( print_r( sqlsrv_errors(), true));
    }
    $liczba=$liczba+2;
    while($res=sqlsrv_fetch_array($r,SQLSRV_FETCH_NUMERIC))
    {
        for($i=0,$max=sizeof($res);$i<$max;$i++){
            if($res[$i] instanceof DateTime)
                $wyniki[$i][$liczba]=$res[$i]->format('Y-m-d H:i:s');
                else
                {
                        $wyniki[$i][$liczba]=$res[$i];
                }
        }
        $liczba--;
    }
    return $wyniki;
}

function nazwyKolumnZTabel($link, string $nazwa):array{
     
        $wyniki=array();
        
        $query="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$nazwa."'";
        $r=sqlsrv_query($link, $query);
        if( $r === false)
        {
            echo "Error in query preparation/execution.\n";
            die( print_r( sqlsrv_errors(), true));
        }
        while($res=sqlsrv_fetch_array($r,SQLSRV_FETCH_NUMERIC))
                $wyniki[][0]=$res[0];
        
        return $wyniki;
}

function listaTabelWszystkich($link,$bazaDanych):array{
    
    $wyniki=array();
    $query="SELECT TABLE_NAME FROM ".$bazaDanych.".INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE'";
    $r=sqlsrv_query($link, $query);
    if( $r === false)
    {
        echo "Error in query preparation/execution.\n";
        die( print_r( sqlsrv_errors(), true));
    }
    $licznik=0;
    while($res=sqlsrv_fetch_array($r,SQLSRV_FETCH_NUMERIC))
    {
        $wyniki[$licznik][0]=$res[0];
        
        $query="SELECT COUNT(*) FROM ".$res[0];
        $k=sqlsrv_query($link, $query);
        $x=sqlsrv_fetch_array($k,SQLSRV_FETCH_NUMERIC);
        $wyniki[$licznik][1]=$x[0];       
        
        $licznik++;
    }
    return $wyniki;
}

function listaTabelNiepustych($link,$bazaDanych):array{
    
    $wyniki=array();
    $query="SELECT TABLE_NAME FROM ".$bazaDanych.".INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE'";
    $r=sqlsrv_query($link, $query);
    if( $r === false)
    {
        echo "Error in query preparation/execution.\n";
        die( print_r( sqlsrv_errors(), true));
    }
    $licznik=0;
    $i=0;
    while($res=sqlsrv_fetch_array($r,SQLSRV_FETCH_NUMERIC))
    {

        $query="SELECT COUNT(*) FROM ".$res[0];
        $k=sqlsrv_query($link, $query);
        $x=sqlsrv_fetch_array($k,SQLSRV_FETCH_NUMERIC);
        if($x[0]>0)
        {
            $wyniki[$i][0]=$licznik;
            $wyniki[$i][1]=$res[0];
            $wyniki[$i][2]=$x[0];
            
            $i++;
        }
        $licznik++;
        
    }
    return $wyniki;
}

function drukujTabele($wyniki):void{
    echo '<p><table border="1">';

    for($i=0,$max=sizeof($wyniki),$k=1;$i<$max;$i++,$k++)
    {
        echo '<tr><td>'.$k.'</td>';
        for($j=0,$maxa=sizeof($wyniki[$i]);$j<$maxa;$j++)
        {
            echo '<td>'.$wyniki[$i][$j].'</td>';
        }
        echo '</tr>';
    }
    echo '</table></p>';
}

function drukujArraySQL($wyniki):void{
    /*
    echo '<p><table border="1">';
    
    for($i=0,$max=sizeof($wyniki),$k=1;$i<$max;$i++,$k++)
    {
        echo '<tr><td>'.$k.'</td>';
        for($j=0,$maxa=sizeof($wyniki[$i]);$j<$maxa;$j++)
        {
            echo '<td>'.$wyniki[$i][$j].'</td>';
        }
        echo '</tr>';
    }
    echo '</table></p>';
    */
    echo '<p>';
    
    for($i=0,$max=sizeof($wyniki),$k=1;$i<$max;$i++,$k++)
    {
        // $zapytanieSQL['ko_Poziom']=(int) 1;
        /*
          if(gettype($wyniki[$i][3])!='NULL')
            if($wyniki[$i][1]=='bit'){
                if($wyniki[$i][3]==1)
                    echo '$zapytanieSQL['."'".$wyniki[$i][0]."']=(boolean) true; //(".$wyniki[$i][1].') '.$wyniki[$i][3].';<br>';
                else
                    echo '$zapytanieSQL['."'".$wyniki[$i][0]."']=(boolean) false; //(".$wyniki[$i][1].') '.$wyniki[$i][3].';<br>';
            }
            else
                echo '$zapytanieSQL['."'".$wyniki[$i][0]."']=(".$wyniki[$i][1].') '.$wyniki[$i][3].';<br>';
         */
        if(gettype($wyniki[$i][3])!='NULL')
            switch($wyniki[$i][1]){
                case 'bit':
                    if($wyniki[$i][3]==1)
                        echo '$zapytanieSQL['."'".$wyniki[$i][0]."']=(boolean) true; //(".$wyniki[$i][1].') '.$wyniki[$i][3].'; // Lp. '.$k.'<br>';
                    else
                        echo '$zapytanieSQL['."'".$wyniki[$i][0]."']=(boolean) false; //(".$wyniki[$i][1].') '.$wyniki[$i][3].'; // Lp. '.$k.'<br>';
                    break;
                case 'char':
                case 'varchar':
                    echo '$zapytanieSQL['."'".$wyniki[$i][0]."']=(".'string) '.$wyniki[$i][3].'; // '.$wyniki[$i][1].'// Lp. '.$k.'<br>';
                    break;
                case 'money':
                    echo '$zapytanieSQL['."'".$wyniki[$i][0]."']=(".'float) '.$wyniki[$i][3].'; // '.$wyniki[$i][1].'// Lp. '.$k.'<br>';
                    break;
                default:
                    echo '$zapytanieSQL['."'".$wyniki[$i][0]."']=(".$wyniki[$i][1].') '.$wyniki[$i][3].';// Lp. '.$k.'<br>';
            }
        
        
    }
    echo '</p>';
    
}

function insertIntoGen(string $nazwaTabeli,array $wyniki):void{
    //INSERT INTO table_name (column1, column2, column3, ...)
    echo '<p>INSERT INTO '.$nazwaTabeli.' ('.$wyniki[0][0];
    for($i=1,$max=sizeof($wyniki);$i<$max;$i++)
    {
        echo ', '.$wyniki[$i][0];
    }
    echo ')</p>';
}

function insertIntoGenA(string $nazwaTabeli,array $wyniki):void{
    //INSERT INTO table_name (column1, column2, column3, ...)
    $sa='';
    for($i=0,$max=sizeof($wyniki);$i<$max;$i++)
    {
        if($wyniki[$i][3]!=null)
            if($sa=='')
                $sa='<p>INSERT INTO '.$nazwaTabeli.' ('.$wyniki[$i][0];
            else 
                $sa.=', '.$wyniki[$i][0];
    }
    echo $sa.')</p>';
}



if(isset($_POST['baza']) && isset($_POST['typ'])){
    $link=PHPOpenConnection($_POST['baza']);
    $table='dkr_Pozycja';
    $wyniki=zapiszDaneZTabele($table, $link);
    //drukujTabele($wyniki);
    
    //$s=3;
    switch($_POST['typ'])
    {
        case 0:
    
            $bazaDanych='test';
            $wyniki=listaTabelWszystkich($link, $bazaDanych);
            drukujTabele($wyniki);
        
            break;
        case 1: // kontrachent
            $dodanieKontrachenta=array(
                array('pk_PlanKont',2),
                array('adr__Ewid',3),
                array('xkh_Ewid',4),
                array('xpk_Ewid',34),
                array('adr_Historia',3),
                array('tel__Ewid',2),
                array('kh__Kontrahent',1),
                array('ins_Slad',2),
                
                );      
            
            for($i=0,$max=sizeof($dodanieKontrachenta);$i<$max;$i++)
            {
                $iloscWstawionych=1;
                $wyniki=ostatnioDodaneRekordy($link, $dodanieKontrachenta[$i][0], $dodanieKontrachenta[$i][1], $iloscWstawionych);
                echo  $dodanieKontrachenta[$i][0].'<br>';
                echo 'SELECT * FROM '.$dodanieKontrachenta[$i][0].';<br>';
                insertIntoGen($dodanieKontrachenta[$i][0], $wyniki);
                insertIntoGenA($dodanieKontrachenta[$i][0], $wyniki);
                drukujTabele($wyniki);
            }
        break;
        case 2:
            $dodanieFaktury=array(
            array('nz_RozDekret',2),
            array('nz__Finanse',2), 
            array('vat__EwidVAT',1),
            array('dkr_Pozycja',6),
            array('vat_DaneVAT',2),
            
            array('dkr_SladRewizyjny',1),
            array('dkr__Dokument',1),
            array('ins_Slad',1)    
            
            );
            
            for($i=0,$max=sizeof($dodanieFaktury);$i<$max;$i++)
            {
                $iloscWstawionych=1;
                $wyniki=ostatnioDodaneRekordy($link, $dodanieFaktury[$i][0], $dodanieFaktury[$i][1], $iloscWstawionych);
                echo  $dodanieFaktury[$i][0].'<br>';
                echo '<font color="red">SELECT * FROM '.$dodanieFaktury[$i][0].';</font><br>';
                //insertIntoGen($dodanieFaktury[$i][0], $wyniki);
                //insertIntoGenA($dodanieFaktury[$i][0], $wyniki);
                echo '<table><tr><td>';
                drukujTabele($wyniki);
                echo '</td><td>';
                drukujArraySQL($wyniki);
                echo '</td></tr></table>';
                
            }
            break;
        case 3: //faktura gotówkowa zwykła
            $dodanieFaktury=array(
            array('nz_RozDekret',2),
            array('nz__Finanse',2),
            array('vat__EwidVAT',1),
            array('dkr_Pozycja',5),
            array('vat_DaneVAT',1),
            array('dkr_SladRewizyjny',1),
            array('dkr__Dokument',1),
            // brak tabeli ins Slad
            
            
            );
            
            for($i=0,$max=sizeof($dodanieFaktury);$i<$max;$i++)
            {
                $iloscWstawionych=1;
                $wyniki=ostatnioDodaneRekordy($link, $dodanieFaktury[$i][0], $dodanieFaktury[$i][1], $iloscWstawionych);
                echo  $dodanieFaktury[$i][0].'<br>';
                echo '<font color="red">SELECT * FROM '.$dodanieFaktury[$i][0].';</font><br>';
                //insertIntoGen($dodanieFaktury[$i][0], $wyniki);
                //insertIntoGenA($dodanieFaktury[$i][0], $wyniki);
                echo '<table><tr><td>';
                drukujTabele($wyniki);
                echo '</td><td>';
                drukujArraySQL($wyniki);
                echo '</td></tr></table>';
                
            }
            break;
        case 4: //faktura gotówkowa bez vat
            $dodanieFaktury=array(
            array('nz_RozDekret',2),
            array('nz__Finanse',2),
            array('vat__EwidVAT',1),
            array('dkr_Pozycja',4),
            array('vat_DaneVAT',1),
            array('dkr_SladRewizyjny',1),
            array('dkr__Dokument',1),
            
            
            
            );
            
            for($i=0,$max=sizeof($dodanieFaktury);$i<$max;$i++)
            {
                $iloscWstawionych=1;
                $wyniki=ostatnioDodaneRekordy($link, $dodanieFaktury[$i][0], $dodanieFaktury[$i][1], $iloscWstawionych);
                echo  $dodanieFaktury[$i][0].'<br>';
                echo '<font color="red">SELECT * FROM '.$dodanieFaktury[$i][0].';</font><br>';
                //insertIntoGen($dodanieFaktury[$i][0], $wyniki);
                //insertIntoGenA($dodanieFaktury[$i][0], $wyniki);
                echo '<table><tr><td>';
                drukujTabele($wyniki);
                echo '</td><td>';
                drukujArraySQL($wyniki);
                echo '</td></tr></table>';
                
            }
            break;
    }
    $v=new DateTime(null, new DateTimeZone('Europe/Warsaw'));
    echo $v->format('Y-m-d H:i:s');
    
    sqlsrv_close($link);
}


$end=getmicrotime()-$time;
echo '
        <script type="text/javascript">
    		document.getElementById("czas").innerHTML = "'.$end.' s <br> '.(memory_get_usage()/1000000).' MB";
            
         </script>
    ';

?>
</p>
</body></html>

