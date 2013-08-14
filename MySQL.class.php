<?php
final class database {
	var $con;
	function __construct($db=array()) {
		$default = array(
			'host' => 'localhost',
			'user' => 'root',
			'pass' => 'tgl0b',
			'db' => 'gis'
		);
		$db = array_merge($default,$db);
		$this->con=mysql_connect($db['host'],$db['user'],$db['pass'],true) or die ('Error connecting to MySQL');
		mysql_select_db($db['db'],$this->con) or die('Database '.$db['db'].' does not exist!');
		mysql_query("SET NAMES UTF8");
		date_default_timezone_set('Asia/Bangkok');
	}
	function __destruct() {
		mysql_close($this->con);
	}
	function select($s='') {
		$q=mysql_query($s,$this->con);
		$data=array();
		for($i=0;$i<mysql_num_rows($q) ;$i++){
		$data[]=mysql_fetch_assoc($q);
		}
		return $data;
	}
	function execute($s='') {
		if (mysql_query($s,$this->con)) return true;
		return false;
	}
	function showDataAsJson($strsql) {
		header("Content-type: application/json"); 	
		$q=mysql_query($strsql,$this->con);
		$data=array();
		while($row=mysql_fetch_assoc($q)){
			$data[]=$row;
		}
		if(function_exists('json_encode')){
			echo json_encode($data);
			}else{
			$json = new Services_JSON();	
			echo $json->encode($data);
			}	
	}
	function showDataAsXML($strsql) {
		header('Content-type: text/html; charset=utf-8');	
		$q=mysql_query($strsql,$this->con);
		$str = "<DATA> \n";
		while($row=mysql_fetch_assoc($q)){
			$str .= " \t<RECORDS> \n";
			
				foreach($row as $key=>$value){
				$str.= "\t \t<".$key.">".$value."</".$key."> \n";
					}
				
			$str.="\t</RECORDS>\n";
		}
		
		$str .= "</DATA>";
		$xml = simplexml_load_string($str);
		echo $xml->asXML();
	}
	function cutStr($str, $maxChars='', $holder=''){
    if (strlen($str) > $maxChars ){
			$str = iconv_substr($str, 0, $maxChars,"UTF-8") . $holder;
	} 
	return $str;
	} 
}