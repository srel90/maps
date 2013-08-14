<?php
session_start();
require_once('../MySQL.class.php'); 
$db = new database();
?>
<?php
if(isset($_POST)&&!empty($_POST)):
switch($_POST['mode']):
	case 'checkadmin':
	$strsql="select * from admin where username='".$_POST['username']."' and password='".md5($_POST['password'])."' and status=1";
	$data=$db->select($strsql);
	if(count($data)!=0){$_SESSION['ADMIN']=$data;}
	$db->showDataAsJson($strsql);	
	break;
endswitch;
endif;
if(isset($_POST)&&!empty($_POST))exit();
?>
<!DOCTYPE html>
<head>
<meta charset="utf-8">
<title>Flood warning System</title>
<link rel="stylesheet" type="text/css" href="../_css/adminstyle.css" />
<script src="../_asset/js/jquery-1.8.2.min.js"></script>
<script src="http://www.modernizr.com/downloads/modernizr-latest.js"></script>
<script src="../_asset/js/placeholder.js"></script>
<script type="text/javascript">
$(function(){
	$('#btnlogin').click(function(e) {
        var data=$.parseJSON(ajax('index.php',({username:$('#txtusername').val(),password:$('#txtpassword').val(),mode:'checkadmin'}),'POST'));
		if(data.length!=0){
			$(window.location).attr('href', 'main.php');
		}else{
			alert('Username or Password is invalid.');
		}
    });
});
function ajax(url,data,type){
	var response=$.ajax({
        url: url,
        type: type,
        data: data,
		dataType: "json",
		async: false
    }).responseText;
	return response;
}
</script>

</head>
<body>
<form id="slick-login">
<label for="username">username</label><input id="txtusername" type="text" name="username" class="placeholder" placeholder="e-mail">
<label for="password">password</label><input id="txtpassword" type="password" name="password" class="placeholder" placeholder="password">
<input name="Button" type="button" id="btnlogin" value="Log In">
</form>
</body>
</html>