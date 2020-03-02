<?php

/** 
 * @author aaadmin
 * 
 */
class Connect
{

    private $conUserName;
    private $conUserPassword;
    private $databaseName;
    private $link;
    
    
    public function getDatabaseName()    {
        return $this->databaseName;    }

    public function getLink()    {
        return $this->link;    }



    public function __construct($conUserName,$conUserPassword,$databaseName)
    {
        $this->databaseName = $databaseName;
        $this->conUserName = $conUserName;
        $this->conUserPassword = $conUserPassword; 
        $this->link();
        
    }
    public function disconnect():void
    {
        sqlsrv_close($this->link);
    }
    
    public function query($query){
        //$query="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$table."'";
        $r=sqlsrv_query($this->getLink(), $query);
        if( $r === false)
            $this->erroorsDatabase();
        else
        {   $s=sqlsrv_fetch_array($r,SQLSRV_FETCH_NUMERIC);
            if(is_array($s))
                return $s;
            else 
                return true;
        }
    }
    
    public function queryLong($query):array{
        //$query="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$table."'";
        $r=sqlsrv_query($this->getLink(), $query);
        if( $r === false)
            $this->erroorsDatabase();
            else
            {   
                $wyn=array();
                while($s=sqlsrv_fetch_array($r,SQLSRV_FETCH_NUMERIC)){
                    $wyn[]=$s;
                    }
                return $wyn;  
            }
    }
    
    
    public function erroorsDatabase():void{
        echo "error<p>";
        $errors=sqlsrv_errors();
        foreach( $errors as $error ) {
            echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
            echo "code: ".$error[ 'code']."<br />";
            echo "message: ".$error[ 'message']."<br />";
        }
       /* foreach( sqlsrv_errors() AS $k=>$v)
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
        */
        echo '</p>';
        
    }
    private function link():void
    {
        $serverName = "AAADMIN-1\INSERTGT"; //serverName\instanceName
        // Since UID and PWD are not specified in the $connectionInfo array,
        // The connection will be attempted using Windows Authentication.
        $connectionInfo = array("Uid"=>$this->conUserName, "PWD"=>$this->conUserPassword,"Database"=>$this->databaseName, "CharacterSet" => "UTF-8");
        // utf8_encode(
        $this->link = sqlsrv_connect( $serverName, $connectionInfo);
        if(!$this->link) 
            $this->erroorsDatabase();
    }
}

?>
