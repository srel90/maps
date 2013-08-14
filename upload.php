<?php
ini_set('display_errors',"1");
$allowedExts = array("gif", "jpeg", "jpg", "png");
$extension = pathinfo($_FILES["txtimage"]["name"], PATHINFO_EXTENSION);
$guid=newguid();
if (in_array($extension, $allowedExts))
  {
  move_uploaded_file($_FILES["txtimage"]["tmp_name"],$_SERVER['DOCUMENT_ROOT']."/maps/upload/" . $guid . $_FILES["txtimage"]["name"]);
  echo "upload/" . $guid . $_FILES["txtimage"]["name"];
  }
else
  {
  echo "1";
  }
function newguid() { 
    $s = strtoupper(md5(uniqid(rand(),true))); 
    $guidText = 
        substr($s,0,8) . '-' . 
        substr($s,8,4) . '-' . 
        substr($s,12,4). '-' . 
        substr($s,16,4). '-' . 
        substr($s,20); 
    return $guidText;
}  
?>