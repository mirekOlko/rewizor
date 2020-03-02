<?php
/*
require_once 'Connect.php';
$bazaDanych='ZR_SERV';
$bazaDanych='trial';
$con=new Connect('test1','test1',$bazaDanych); 




*/
function genSortDopelnienieZerami(string $string, int $calkowitaDlugosc):string{
    $wynik='';
    $test=$calkowitaDlugosc-strlen($string);
    for($i=0;$i<$test;$i++)
        $wynik.='0';
        
    $wynik.=$string; 
    return $wynik;
}
function genNrDokumentuSort(string $string):string{

    $wynik='';
    //if((int)($string))
    if(preg_match('/^[0-9]{1,30}$/D',$string))
    {
        $wynik=genSortDopelnienieZerami($string, 30);          
    }
    else{
        echo 'asdasd';
        $string = str_replace(' ', '', $string);
        $data=array();
        $data[0]='';
        $licznik=0;
        for($i=0,$max=strlen($string);$i<$max;$i++)
        {
            if($string[$i]=='/' || $string[$i]=='-'){
                $licznik++;
                $data[$licznik]=$string[$i];
                $licznik++;
                $data[$licznik]='';
                }
            else {
                $data[$licznik].=$string[$i];
                }
            
        }
        
        foreach($data As $k=>$v)
            echo $k.' '.$v.'<br>';
        //substr();
    }
    //echo $wynik.' '.strlen($wynik).'<br>';
    return $wynik;
}

$dane=array(
array('152331', '000000000000000000000000152331'),
array('5026000275788',    '000000000000000005026000275788'),
array('4020150626073840', '000000000000004020150626073840'),
array('103000300700004020150626073840','103000300700004020150626073840'),
array('FV01940','FV000000000000000000000000001940')
    
    
);

for($i=0;$i<sizeof($dane);$i++){ 
    $x=genNrDokumentuSort($dane[$i][0]);
    if($x===$dane[$i][1])
        echo $x.' ok<br>';
    
    else 
        echo $dane[$i][0].' '.$x.' źle<br>';
}
    
    
$d=DateTime::createFromFormat('Y-m-d', '2015-01-26');
$e=DateTime::createFromFormat('Y-m-d', '2016-05-29');
$int=$d->diff($e);
echo $int->format('%R%a days');

$text = '12563256';
$str='sfd';
$str = str_replace(' ', '', $str);

// this echoes "is is a Simple text." because 'i' is matched first
echo stristr($text,"^digit");


var_dump(round(3.4));
var_dump(round(3.5));
var_dump(round(3.6));
var_dump(round(3.6, 0));
var_dump(round(1.95583, 2));
var_dump(round(1241757, -3));
var_dump(round(5.045, 2));
var_dump(round(5.055, 2));
var_dump(round(5.053, 2));

$s=5.86;
$s*=100;
if($s%2==0)
    echo 'tak';
else {
    echo 'nie';
}


// this echoes "Simple text." because chars are case sensitive


/*
echo '<table border="1"><tr style="text-align: right; ">';
for($i=0;$i<sizeof($dane);$i++){
    for($j=0;$j<sizeof($dane[$i]);$j++){
        echo '<td>'.$dane[$i][$j].'</td><td>';
        $temp=explode("/",$dane[$i][$j]);
        $s='';
        $aa='';
        for($k=0;$k<sizeof($temp);$k++){
            if($d=(int)($temp[$k])){
                $aa.=' int ';
            }
            else {
                $aa.=' <font color="red">string</font> ';
            }
            $s.=strlen($temp[$k]).' ';
        }
        //echo $s;  
        echo $aa.'</td><td>';
        echo $s.'</td><td><p style="text-align:left; color=red;">'.$s.'</p>';    
        //}  
        echo '</td>';
    }
    echo '</tr><tr style="text-align: right; ">';
}
echo '</tr></table>';
*/
?>