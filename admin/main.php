<?php session_start();
if(!isset($_SESSION['ADMIN']) || empty($_SESSION['ADMIN'])){header("location:index.php");}
require_once('../MySQL.class.php'); 
$db = new database();
$strsql="SELECT * FROM report ORDER BY id desc;";
$report=$db->select($strsql);
?>
<?php
if(isset($_POST)&&!empty($_POST)):
switch($_POST['mode']):
	case 'add':
		$strsql="INSERT INTO report(
		address
		,lat
		,lon
		,title
		,detail
		,photo
		,name
		,status
		)VALUES(
		'".$_POST['address']."'
		,'".$_POST['lat']."'
		,'".$_POST['lon']."'
		,'".$_POST['title']."'
		,'".$_POST['detail']."'
		,'".$_POST['photo']."'
		,'".$_POST['name']."'
		,1
		);";
		if($db->execute($strsql)!=false){
		echo("Complete Insert");
		}else{
			echo(mysql_error());
		}
	break;
	case 'edit':
	if(!empty($_POST['photo'])){
		$strsql="UPDATE report SET
		address='".$_POST['address']."'
		,lat='".$_POST['lat']."'
		,lon='".$_POST['lon']."'
		,title='".$_POST['title']."'
		,detail='".$_POST['detail']."'
		,photo='".$_POST['photo']."'
		,name='".$_POST['name']."'
		,status='".$_POST['status']."'
		WHERE id='".$_POST['id']."'
		;";
	}else{
		$strsql="UPDATE report SET
		address='".$_POST['address']."'
		,lat='".$_POST['lat']."'
		,lon='".$_POST['lon']."'
		,title='".$_POST['title']."'
		,detail='".$_POST['detail']."'
		,name='".$_POST['name']."'
		,status='".$_POST['status']."'
		WHERE id='".$_POST['id']."'
		;";
		}
		if($db->execute($strsql)!=false){
		echo("Complete Update");
		}else{
			echo(mysql_error());
		}
	break;
	case 'del':	
	$strsql="DELETE FROM report WHERE id='".$_POST['id']."';";
	if($db->execute($strsql)!=false){
			echo("Complete Delete");
		}else{
			echo(mysql_error());
		}
	break;
	case 'select':	
	$strsql="SELECT * FROM report ORDER BY id desc;";
	$db->showDataAsJson($strsql);
	break;
	case 'selectbyid':	
	$strsql="SELECT * FROM report WHERE id='".$_POST['id']."';";
	$db->showDataAsJson($strsql);
	break;
	case 'push':
	$sleepTime = 1; //Seconds
	$data = "";
	$timeout = 0;
	while(!$data && $timeout < 10){
    $strsql="SELECT * FROM tmpreport;";
	$data=$db->select($strsql);
    if(!$data){
        //No new messages on the chat
        flush();
        //Wait for new Messages
        sleep($sleepTime);          
        $timeout += 1;
    }else{
        break;
    }
	}
	
	if($data){
		$strsql="SELECT * FROM report WHERE status=1 ORDER BY id desc;";
		$db->showDataAsJson($strsql);
		flush();
		sleep(10);
		$strsql="delete from tmpreport";
		$db->execute($strsql);

	}else{
		echo json_encode(array());
	}
	
	break;
endswitch;
endif;
if(isset($_POST)&&!empty($_POST))exit();
?>
<!doctype html>
<!--[if lt IE 7]> <html class="ie6 oldie"> <![endif]-->
<!--[if IE 7]>    <html class="ie7 oldie"> <![endif]-->
<!--[if IE 8]>    <html class="ie8 oldie"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="">
<!--<![endif]-->
<script src="https://maps.googleapis.com/maps/api/js?sensor=true&libraries=places,weather&language=th"></script>
<script src="../_asset/js/jquery-1.8.2.min.js"></script>
<script src="../_asset/js/jqx-all.js"></script>
<script src="../_asset/js/geolocationmarker.js"></script>
<script type="text/javascript">
var map,GeoMarker,GMM,crosshairs;
var mode='add';
GGM=new Object(google.maps);
var marker, i;
var markersArray = [];
var layoutArray = [];	
var infowindow = new GGM.InfoWindow();
var preinfo = new GGM.InfoWindow();
function initialize() {
		var contents;
        var mapOptions = {
        zoom: 17,
        center: new GGM.LatLng(0,0),
        mapTypeId: GGM.MapTypeId.ROADMAP
        };
		
        map = new GGM.Map($("#map_canvas")[0],mapOptions);
		
		GGM.Map.prototype.clearLayout = function() {
		  for (var i = 0; i < layoutArray.length; i++ ) {
			layoutArray[i].setMap(null);
		  }
		}
		GGM.Map.prototype.clearMarker = function() {
		  for (var i = 0; i < markersArray.length; i++ ) {
			markersArray[i].setMap(null);
		  }
		}
		initialpin();
		pushserver();		
		crosshairs = new GGM.Marker({clickable:false,map: map,icon: '../_images/cross-hairs.gif',flat: true});
		crosshairs.bindTo('position', map, 'center');


/////////////
var controlDiv = document.createElement('DIV');
$(controlDiv).addClass('gmap-control-container').addClass('gmnoprint');
          
var controlUI = document.createElement('DIV');
$(controlUI).addClass('gmap-control');
$(controlUI).text('Traffic');
$(controlDiv).append(controlUI);
          
var legend = '<ul>'
           + '<li><span style="background-color: #30ac3e">&nbsp;&nbsp;</span><span style="color: #30ac3e"> &gt; 80 km per hour</span></li>'
           + '<li><span style="background-color: #ffcf00">&nbsp;&nbsp;</span><span style="color: #ffcf00"> 40 - 80 km per hour</span></li>'
           + '<li><span style="background-color: #ff0000">&nbsp;&nbsp;</span><span style="color: #ff0000"> &lt; 40 km per hour</span></li>'
           + '<li><span style="background-color: #c0c0c0">&nbsp;&nbsp;</span><span style="color: #c0c0c0"> No data available</span></li>'
           + '</ul>';
          
var controlLegend = document.createElement('DIV');
		$(controlLegend).addClass('gmap-control-legend');
		$(controlLegend).html(legend);
		$(controlLegend).hide();
		$(controlDiv).append(controlLegend);
				  
		// Set hover toggle event
		$(controlUI)
			.mouseenter(function() {
				$(controlLegend).show();
			})
			.mouseleave(function() {
				$(controlLegend).hide();
			});
		/////////////		
		var trafficLayer = new GGM.TrafficLayer();
		trafficLayer.setMap(map);
		
		var weatherLayer = new GGM.weather.WeatherLayer({temperatureUnits: GGM.weather.TemperatureUnit.CELSIUS});
		weatherLayer.setMap(map);
		/*
		var cloudLayer = new GGM.weather.CloudLayer();
		cloudLayer.setMap(map);*/
				 
		GGM.event.addDomListener(controlUI, 'click', function() {
			if (typeof trafficLayer.getMap() == 'undefined' || trafficLayer.getMap() === null) {
				$(controlUI).addClass('gmap-control-active');
				trafficLayer.setMap(map);
			} else {
				trafficLayer.setMap(null);
				$(controlUI).removeClass('gmap-control-active');
			}
		});
				  
		map.controls[GGM.ControlPosition.TOP_RIGHT].push(controlDiv);
		
			
    
        GeoMarker = new GeolocationMarker();
		GeoMarker.setMarkerOptions({'animation':GGM.Animation.BOUNCE,'icon':new google.maps.MarkerImage(
        '../_images/gpsloc.png',
        new google.maps.Size(110, 84),
        null,
        new google.maps.Point(27, 21),
        new google.maps.Size(55, 42))});
        GeoMarker.setCircleOptions({fillColor: '#808080'});

        GGM.event.addListenerOnce(GeoMarker, 'position_changed', function() {
			
          map.setCenter(this.getPosition());
          map.fitBounds(this.getBounds());
        });

        GGM.event.addListener(GeoMarker, 'geolocation_error', function(e) {
          alert('There was an error obtaining your position. Message: ' + e.message);
        });

		navigator.geolocation.getCurrentPosition(function(position) {
			var latitude = position.coords.latitude;
			var longitude = position.coords.longitude;			
			var location = new GGM.LatLng(latitude,longitude);	
			var request = {
					  location: location,
					  radius: 1000,
					  types: ['']
					  };	  
			var service = new GGM.places.PlacesService(map);
			service.nearbySearch(request, callback);
			
		});	

		GeoMarker.setMap(map);
}
function callback(results, status) {
						if (status == GGM.places.PlacesServiceStatus.OK) {
						  for (var i = 0; i < results.length; i++) {
							createMarker(results[i]);
						  }
						}
						/*if (status == GGM.places.PlacesServiceStatus.ZERO_RESULTS){
					   alert('zero results near this location');
						}*/
			}
function createMarker(place) {
			var placeLoc = place.geometry.location;
			var marker = new GGM.Marker({
			  map: map,
			  position: place.geometry.location,
			  animation:GGM.Animation.DROP,
			  icon:place.icon
			});			
			layoutArray.push(marker);
			GGM.event.addListener(marker, 'click', function() {
				var contents;
				if(place.photos){
				contents= '<div id="pic"><img src="'+place.photos[0].getUrl({'maxWidth': 200})+'"/><p>'+place.name+'</p></div>';
				}else{
				contents= place.name;
				}
				infowindow.setContent(contents);
				infowindow.open(map, this);
			});
			
}
function initialpin()
{
		$.ajax({
			type: "POST",
			url:'main.php',
			dataType:'text',
			data:{mode:'select'},
			success:function(data){
				var locate=[];			
					locate=$.parseJSON(data);
					for (i = 0; i < locate.length; i++) { 
					  var marker = new GGM.Marker({
						  position: new GGM.LatLng(locate[i]['lat'], locate[i]['lon']),
						  map: map,
						  animation:GGM.Animation.DROP
						  });
					markersArray.push(marker);	
					GGM.event.addListener(marker, 'click', (function(marker, i) {
						return function() {
						if(locate[i]['photo'] != ''){
							contents= '<div id="pic"><img src="../'+locate[i]['photo']+'"/><p>'+locate[i]['title']+'</p></div>';
						}else{
							contents= locate[i]['title'];
						}	
						  infowindow.setContent(contents);
						  infowindow.open(map, marker);
						}
					  })(marker, i));
					  
					}
					
					
			},
			error:console.log
		});
}
function pushserver()
{

		var datastring=new Object();
		datastring.mode='push';
		$.ajax({
			type: "POST",
			url:'main.php',
			dataType:'text',
			async: true,
			data:datastring,
			success:function(data){
				console.log(data);
				var locatep=[];
				var html='';					
					locatep=$.parseJSON(data);
					if(locatep.length!=0){
					map.clearMarker();
					for (i = 0; i < locatep.length; i++) { 
					  var marker = new GGM.Marker({
						  position: new GGM.LatLng(locatep[i]['lat'], locatep[i]['lon']),
						  map: map,
						  animation:GGM.Animation.DROP
						  });
					markersArray.push(marker);	
					GGM.event.addListener(marker, 'click', (function(marker, i) {
						return function() {
						if(locatep[i]['photo'] != ''){
							contents= '<div id="pic"><img src="../'+locatep[i]['photo']+'"/><p>'+locatep[i]['title']+'</p></div>';
						}else{
							contents= locatep[i]['title'];
						}	
						  infowindow.setContent(contents);
						  infowindow.open(map, marker);
						}
					  })(marker, i));
					html+='<li><div style="position: relative;"><a href="javascript:gotolocation('+locatep[i]['id']+');" id="location'+locatep[i]['id']+'" style="text-decoration:none">';
					html+=''+(i+1)+'. '+locatep[i]['title']+'';
					html+='</a>';
					html+='<div style="text-align: right;top: 0;right: 0;position: absolute;">';
  html+='<img src="../_images/Edit.png" width="16" height="16" alt="edit" style="cursor:pointer;" onClick="edit('+locatep[i]['id']+')">';
  html+='<img src="../_images/delete.png" width="16" height="16" alt="delete" style="cursor:pointer;" onClick="del('+locatep[i]['id']+')"></div></div></li>';
					}//for
					
					html+='<a href="javascript:showmorelist();"><li style="text-align: center;">More</li></a>';
					$('#listplace').html(html);
					
					$("#listplace li:gt(5)").hide();
					$("#listplace li:last").show();
					}//if(locatep.length!=0)
					
					setTimeout(pushserver(),1000);
			},
			error:console.log
		});
}
</script>
<script type="text/javascript">
$(function(e) {
	GGM.event.addDomListener(window, 'load', initialize);	
    $('#jqxTabs').jqxTabs({ width: '100%', position: 'top', theme: 'ui-start',selectionTracker: true });
	$("#LayoutWindow").jqxWindow({ width: '90%', resizable: false, theme: 'ui-start', autoOpen: false,isModal:true, modalOpacity: 0.7 ,cancelButton: $("#btnCloseLayout")}); 
	$("#ReportWindow").jqxWindow({ width: '90%', resizable: false, theme: 'ui-start', autoOpen: false,isModal:true, modalOpacity: 0.7 ,cancelButton: $("#btnCloseReport")});
	$("#btnCloseLayout,#btnCloseReport,#btnSubmit").jqxButton({theme: 'ui-start',height:30});
	$("#jqxProgressBar").jqxProgressBar({ width: '100px', height: 10, value: 0, theme: 'ui-start' });
	
	$("#listplace li:gt(5)").hide();
	$("#listplace li:last").show();
	
	$('#btnrefresh').click(function(e) {
        map.setCenter(GeoMarker.getPosition({'animation':google.maps.Animation.BOUNCE}));
        map.fitBounds(GeoMarker.getBounds());
    });
	$("input[type='radio']").click(function(e) {
		map.clearLayout();		
		var placescustom;
		placescustom=getCHKValue('layout');	
		var location = GeoMarker.getPosition();	
		var request = {
			location: location,
			radius: 10000,
			types: placescustom
			};			  	  
		var service = new GGM.places.PlacesService(map);
		service.nearbySearch(request, callback);
		$('#LayoutWindow').jqxWindow('close');		
    });
	$('#bt-layer').click(function(e) {
		$("#LayoutWindow").jqxWindow('show');
    });
	$('#bt-report').click(function(e) {
		mode='add';
		var position=crosshairs.getPosition();
var address=$.parseJSON(ajax('http://maps.googleapis.com/maps/api/geocode/json',({latlng:position.lat()+','+position.lng(),sensor:'true',language:'th'}),'GET'));
		var apxaddress='';
		if(address.status='OK'){
		apxaddress=address.results[0].formatted_address;
		}
		reset($('#frmReport'));
		$('#txtaddress').val(apxaddress);
		$('#txtlat').val(position.lat());
		$('#txtlon').val(position.lng());
		$("#ReportWindow").jqxWindow('show');
    });
	$('#btnSubmit').click(function(e) {
		uploadfile();		
    });
});
function getCHKValue(chkName){   
    var chk = document.getElementsByName(chkName);
	var v = new Array();
    for(i=0;i < chk.length;i++)
    {   
        if(chk[i].checked) {
			v.push(chk[i].value);
			}
    }
	v.push('');
    return v;
}
function ajax(url,data,type){
	
	var response=$.ajax({
        url: url,
        type: type,
        data: data,
		dataType: "text",
		async: false
    }).responseText;
	return response;
}
window.reset = function (e) {
    e.wrap('<form>').closest('form').get(0).reset();
    e.unwrap();
}
function uploadfile(){
	var myXhr;
	var formData = new FormData($('#frmReport')[0]);
	$.ajax({
		url: '../upload.php',  //server script to process data
		type: 'POST',
		xhr: function() {  // custom xhr
			myXhr = $.ajaxSettings.xhr();
			myXhr.upload.onprogress = function updateProgress(e) {
			var loaded = (e.loaded / e.total);
			if (myXhr.upload) {
			$('#jqxProgressBar').jqxProgressBar({ value: Math.round(loaded * 100) });
			}
			}
			
			return myXhr;
		},
		success: completeHandler = function(dataresponse) {
			var data = new Object();
				data.id=$('#txtid').val();
				data.name=$('#txtname').val();
				data.address=$('#txtaddress').val();
				data.title=$('#txttitle').val();
				data.detail=$('#txtdescription').val();
				data.lat=$('#txtlat').val();
				data.lon=$('#txtlon').val();
				data.status=getRDOValue('rdostatus');
				data.mode=mode;		
			if($.trim(dataresponse)!='1'){
				data.photo=dataresponse;
			}else{
				data.photo='';	
				}
				var response=ajax('main.php',data,'POST');
				alert(response);
			$("#jqxProgressBar").jqxProgressBar({value: 0});
			$('#ReportWindow').jqxWindow('close');			
		},
		error: errorHandler = function(data) {
			alert('เกิดข้อผิดพลาดบางประการไม่สามารถอับโหลดรูปได้');
		},
		data: formData,
		cache: false,
		contentType: false,
		processData: false
	}, 'json');
	
}

function gotolocation(id){	
	var content=$.parseJSON(ajax('main.php',({mode:'selectbyid',id:id}),'POST'));
	if(content.length!=0){
	var contents='';
	if(content[0]['photo'] != ''){
		contents= '<div id="pic"><img src="../'+content[0]['photo']+'"/><p>'+content[0]['title']+'</p></div>';
	}else{
		contents= content[0]['title'];
	}
	var position=new GGM.LatLng(content[0].lat,content[0].lon);
	
	var info = new GGM.InfoWindow({
    content: contents,
	position :position
	});
    preinfo.close();
    preinfo = info;
	map.setCenter(position);
	info.open(map);
	}else{
		alert("There is no data for your current location.");
		}
}
var from = 5, step = 5;
function showmorelist() {
	
  $('#listplace').find('li:lt(' + (from + step) + '):not(li:lt(' + from + '))').slideDown();
  from += step;
  $("#listplace li:last").show();

  var $t = $('.rightcontent');
  $t.animate({"scrollTop": $('.rightcontent')[0].scrollHeight}, "slow");
}
function edit(id){
	mode='edit';
	var data=$.parseJSON(ajax('main.php',({id:id,mode:'selectbyid'}),'POST'));
		reset($('#frmReport'));
		$('#txtid').val(data[0].id);
		$('#txtname').val(data[0].name);
		$('#txttitle').val(data[0].title);
		$('#txtdescription').val(data[0].detail);
		$('#txtaddress').val(data[0].address);
		$('#txtlat').val(data[0].lat);
		$('#txtlon').val(data[0].lon);
		setRDOValue('rdostatus',data[0].status);
		$("#ReportWindow").jqxWindow('show');
}
function del(id){
	if(confirm('Do you really want to delete this location?')){
	var response=ajax('main.php',({id:id,mode:'del'}),'POST');	
	alert(response);
	}
}
function getRDOValue(radioName){   
    var rdo = document.getElementsByName(radioName);
    for(i=0;i < rdo.length;i++)
    {   
        if(rdo[i].checked) {return rdo[i].value;}
    }
    return null;
}
function setRDOValue(radioName,radioValue){  
    var rdo = document.getElementsByName(radioName);
    for(i=0;i < rdo.length;i++)
    {   
        if(rdo[i].value==radioValue)
		{
		rdo[i].checked = true;
		break;
		}
    }
    return null;
}
</script>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no,target-densitydpi=device-dpi" />
<title>Flood warning System</title>
<link href="../_css/boilerplate.css" rel="stylesheet" type="text/css">
<link href="../_css/layout.css" rel="stylesheet" type="text/css">
<link href="../_css/style.css" rel="stylesheet" type="text/css">
<link href="../_css/jqx.base.css" rel="stylesheet" type="text/css">
<link href="../_css/jqx.ui-start.css" rel="stylesheet" type="text/css">
<!--[if lt IE 9]>
<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script src="../_asset/js/respond.min.js"></script>
</head>
<body>
<div class="gridContainer clearfix">
<div id="header">
  <p>Welcome : <?php echo $_SESSION['ADMIN'][0]['name'];?></p></div>
<div id="contents">
  <div id="map_canvas"></div>
  <div id="shadow"></div>
  <div id="infopane">
  <div class="rightcontent" style="overflow:scroll">
  <div class="righttopbar"><p>News</p></div>
  <ul id="listplace">
  <?php $i=1;
  foreach($report as $item):?>
  <li><div style="position: relative;"><a href="javascript:gotolocation(<?php echo $item['id']?>);" id="location<?php echo $item['id']?>" style="text-decoration:none">
  <?php echo $i++;?>. <?php echo $item['title'];?>
  </a><div style="text-align: right;top: 0;right: 0;position: absolute;">
  <img src="../_images/Edit.png" width="16" height="16" alt="edit" style="cursor:pointer;" onClick="edit(<?php echo $item['id']?>)">
  <img src="../_images/delete.png" width="16" height="16" alt="delete" style="cursor:pointer;" onClick="del(<?php echo $item['id']?>)"></div></div></li>
  <?php endforeach;?>
  <a href="javascript:showmorelist();"><li style="text-align: center;">More</li></a>
  </ul>

  </div>
  </div>
  <div id="control">
    <div id="btnrefresh" class="tools-btn" title="Current location"></div>
    <div id="bt-layer" class="tools-btn" title="Layout"></div>
    <div id="bt-report" class="tools-btn" title="Report"></div>
  </div>
</div>
<div id="LayoutWindow">
  <div ><span id="HLabel">Layout</span></div>
  <div style="overflow: scroll;">
    <p style="padding-left:20px">
      <input type="radio" name="layout" value="doctor" id="layout_0">
      doctor <br>
      <input type="radio" name="layout" value="fire_station" id="layout_1">
      fire_station <br>
      <input type="radio" name="layout" value="food" id="layout_2">
      food <br>
      <input type="radio" name="layout" value="health" id="layout_3">
      health <br>
      <input type="radio" name="layout" value="hospital" id="layout_4">
      hospital <br>
      <input type="radio" name="layout" value="pharmacy" id="layout_5">
      pharmacy <br>
      <input type="radio" name="layout" value="police" id="layout_6">
      police <br>
      <input type="radio" name="layout" value="school" id="layout_7">
      school <br>
      <input name="layout" type="radio" id="layout_8" value="''" checked="CHECKED">
      none <br>
      <input name="" type="button" id="btnCloseLayout" value="Cancel">
    </p>
  </div>
</div>
<div id="ReportWindow">
  <div ><span id="HLabel">Report</span></div>
  <div style="overflow: scroll;">
    <form action="" method="get" enctype="multipart/form-data" id="frmReport">
      <table border="0">
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td><input type="hidden" name="txtid" id="txtid"></td>
        </tr>
        <tr>
          <td>ชื่อ</td>
          <td>:</td>
          <td><input type="text" name="txtname" id="txtname"></td>
        </tr>
        <tr>
          <td>ที่อยู่</td>
          <td>:</td>
          <td><textarea name="txtaddress" id="txtaddress"></textarea></td>
        </tr>
        <tr>
          <td>พิกัด</td>
          <td>:</td>
          <td><input type="text" name="txtlat" id="txtlat"></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td><input type="text" name="txtlon" id="txtlon"></td>
        </tr>
        <tr>
          <td>รายงานเรื่อง</td>
          <td>:</td>
          <td><input type="text" name="txttitle" id="txttitle"></td>
        </tr>
        <tr>
          <td>รายละเอียด</td>
          <td>:</td>
          <td><textarea name="txtdescription" id="txtdescription"></textarea></td>
        </tr>
        <tr>
          <td>รูปภาพ</td>
          <td>:</td>
          <td><input type="file" accept="image/*" capture="camera" name="txtimage" id="txtimage"></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td><div id="jqxProgressBar"></div></td>
        </tr>
        <tr>
          <td>สถานะ</td>
          <td>:</td>
          <td>
            <input name="rdostatus" type="radio" id="rdostatus_0" value="1" checked="CHECKED">
            Active
            <input name="rdostatus" type="radio" id="rdostatus_1" value="0">
          Inactive</td>
        </tr>
        <tr>
          <td colspan="3">&nbsp;</td>
        </tr>
        <tr>
          <td colspan="3"><input type="button" name="btnSubmit" id="btnSubmit" value="Submit">
            <input name="btnCloseReport" type="button" id="btnCloseReport" value="Cancel"></td>
        </tr>
      </table>
    </form>
  </div>
</div>
</body>
</html>
