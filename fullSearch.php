<html>
<head>
<meta charset="UTF-8">
</head>
<body>
	<div id="czas"></div>
	<div id="pamiec"></div>
	<form name="fullSearch" method="post" action="fullSearch.php">
        <!-- Podstawowe pole tekstowe -->
        <table>
        	<tr>
        		<td>nazwa bazy danych</td><td>
                                                <select name="baza">
                                                	<option selected>test</option>
                                                	<option>test_kopia</option>
                                                	<option>ZA_SERVICE</option>
                                                </select></td>
        	</tr>
        	<tr>
        		<td>szukany tekst</td><td><input name="search"></td>
        	</tr>
        	<tr>
        		<td>typ szukania</td><td>string: <input type="radio" name="typ" value="string" checked></td>
        		</tr>
            <tr>
        		<td></td>			<td>int: <input type="radio" name="typ" value="int"></td>
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

function searchString($szukaj,$con,$bazaDanych)
{
    //$szukaj='kp/kw';
    $query1="SELECT TABLE_NAME FROM ".$bazaDanych.".INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE'";
    $r1=sqlsrv_query($con->getLink(), $query1);
    if( $r1 === false)
        $con->erroorsDatabase();
        else
        {
            while($s1=sqlsrv_fetch_array($r1,SQLSRV_FETCH_NUMERIC)){
                $tabela1=$s1[0];
                $query2="SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$tabela1."'";
                $r2=sqlsrv_query($con->getLink(), $query2);
                if( $r2 === false)
                    $con->erroorsDatabase();
                    else
                    {
                        //echo '</p><p><font color="red">Select * From '.$tabela1.';</font><br> ';
                        $x='';
                        while($s2=sqlsrv_fetch_array($r2,SQLSRV_FETCH_NUMERIC)){
                            //echo $s2[0].' '.$s2[1].'<br>';
                            //$types[$s2[1]]=1;
                            if($s2[1]=='varchar' || $s2[1]=='char' || $s2[1]=='text' || $s2[1]=='ntext' || $s2[1]=='nvarchar' || $s2[1]=='nchar')
                            {
                                $query3='SELECT count(*) FROM '.$tabela1.' WHERE '.$s2[0].' like '."'%".$szukaj."%';";
                                $r3=sqlsrv_query($con->getLink(), $query3);
                                if( $r3 === false){
                                    $con->erroorsDatabase();
                                    echo $query3.'<br>';
                                }
                                else
                                {
                                    //echo '<p>';+
                                    
                                    $s3=sqlsrv_fetch_array($r3,SQLSRV_FETCH_NUMERIC);
                                    if((int)($s3[0])>0)
                                        if($x!=$tabela1){
                                            echo '<font color="red">Select * From '.$tabela1.' WHERE '.$s2[0].' like '."'%".$szukaj."%';".'</font> <br> ';
                                            $x=$tabela1;
                                    }
                                    
                                }
                            }
                        }
                    }
                    
                    
            }
        }
}

function searchInt($szukaj,$con,$bazaDanych)
{
    //$szukaj='kp/kw';
    $query1="SELECT TABLE_NAME FROM ".$bazaDanych.".INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE'";
    $r1=sqlsrv_query($con->getLink(), $query1);
    if( $r1 === false)
        $con->erroorsDatabase();
        else
        {
            while($s1=sqlsrv_fetch_array($r1,SQLSRV_FETCH_NUMERIC)){
                $tabela1=$s1[0];
                $query2="SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$tabela1."'";
                $r2=sqlsrv_query($con->getLink(), $query2);
                if( $r2 === false)
                    $con->erroorsDatabase();
                    else
                    {
                        //echo '</p><p><font color="red">Select * From '.$tabela1.';</font><br> ';
                        $x='';
                        while($s2=sqlsrv_fetch_array($r2,SQLSRV_FETCH_NUMERIC)){
                            //echo $s2[0].' '.$s2[1].'<br>';
                            //$types[$s2[1]]=1;
                            if($s2[1]=='int' || $s2[1]=='float')
                            {
                                $query3='SELECT count(*) FROM '.$tabela1.' WHERE '.$s2[0].'='.$szukaj.';';
                                $r3=sqlsrv_query($con->getLink(), $query3);
                                if( $r3 === false){
                                    $con->erroorsDatabase();
                                    echo $query3.'<br>';
                                }
                                else
                                {
                                    //echo '<p>';+
                                    
                                    $s3=sqlsrv_fetch_array($r3,SQLSRV_FETCH_NUMERIC);
                                    if((int)($s3[0])>0)
                                        if($x!=$tabela1){
                                            echo '<font color="red">Select * From '.$tabela1.' WHERE '.$s2[0].'='.$szukaj.';</font> <br> ';
                                            $x=$tabela1;
                                    }
                                    
                                }
                            }
                        }
                    }
                    
                    
            }
        }
}

require_once 'Connect.php';

if(isset($_POST['baza']) && isset($_POST['search']) && isset($_POST['typ']))
{
    $bazaDanych=$_POST['baza'];
    $con=new Connect('test1','test1',$bazaDanych); 
    
    if($_POST['typ']=='string')
        $szukaj=(string)($_POST['search']);
    else
        $szukaj=(int)($_POST['search']);
    
    echo $_POST['baza'].' "'.$szukaj.'" jako '.gettype($szukaj).'</p><p>';
        
    if(is_string($szukaj))
        searchString($szukaj,$con,$bazaDanych);
    else 
        searchInt($szukaj,$con,$bazaDanych);
        

/*
 * 

$types=array();


echo '<br>';    
foreach ($types As $k=>$v)
{
    echo $k.'<br>';
}

    
isset($_POST['baza'])
    


while($wyn=$con->query($query)){
for($i=0,$max=sizeof($wyn);$i<$max;$i++)
    {
        echo $wyn[$i].'<br>';
    }
}
*/
    $end=getmicrotime()-$time;
    echo '
        <script type="text/javascript">
    		document.getElementById("czas").innerHTML = "'.$end.' s <br> '.(memory_get_usage()/1000000).' MB";
            
         </script>
    ';
}
?>
</p>
</body></html>