<?php
require_once('MySQL.class.php'); 
$db = new database();
$strsql="SELECT * FROM report WHERE status='1' ORDER BY id desc;";
$report=$db->select($strsql);
?><?php
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
	case 'selectCam':
	$url = urldecode($_POST['url']);
	$url = 'http://' . str_replace('http://', '', $url); // Avoid accessing the file system
	echo file_get_contents($url);
	break;
	case 'select':	
	$strsql="SELECT * FROM report WHERE status='1' ORDER BY id desc;";
	$db->showDataAsJson($strsql);
	break;
	case 'selectbyid':	
	$strsql="SELECT * FROM report WHERE status='1' and id='".$_POST['id']."';";
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
<html>

<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no,target-densitydpi=device-dpi" name="viewport">
<title>Flood warning System</title>
<link href="_css/boilerplate.css" rel="stylesheet" type="text/css">
<link href="_css/layout.css" rel="stylesheet" type="text/css">
<link href="_css/style.css" rel="stylesheet" type="text/css">
<link href="_css/jqx.base.css" rel="stylesheet" type="text/css">
<link href="_css/jqx.ui-start.css" rel="stylesheet" type="text/css">
<script src="_asset/js/respond.min.js" type="text/javascript"></script>
<script src="https://maps.googleapis.com/maps/api/js?sensor=true&amp;libraries=places,weather&amp;language=th" type="text/javascript"></script>
<script src="_asset/js/jquery-1.8.2.min.js" type="text/javascript"></script>
<script src="_asset/js/jqx-all.js" type="text/javascript"></script>
<script src="_asset/js/geolocationmarker.js" type="text/javascript"></script>
<script type="text/javascript">
var map,GeoMarker,GMM,crosshairs;
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
		cam();
		initialpin();
		pushserver();
		crosshairs = new GGM.Marker({clickable:false,map: map,icon: '_images/cross-hairs.gif',flat: true});
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
		GeoMarker.setMarkerOptions({'animation':GGM.Animation.BOUNCE});
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
function cam()
{
		$.ajax({
				type: "POST",
				url:'index.php',
				dataType:'text',
				data:{mode:'selectCam',url:'http://hatyaicityclimate.org/flood/get/9,3,10,2,1,4,5,6'},
				success:function(data){
				var camdata = $.parseJSON($("#content-body", data).text());
				//console.log(camdata);
			
				var locate=new Array(8);
				for (i=0; i <8; i++)
				locate[i]=new Array(7);

				locate[0]['lat']=7.479676;
				locate[0]['lon']=100.438115;
				locate[0]['title']='สถานีเรดาร์สทิงพระ';
				locate[0]['photo']='http://hatyaicityclimate.org'+camdata[7].thumb;
				locate[0]['created']=camdata[7].created;
				locate[0]['cam']='http://hatyaicityclimate.org/flood/cam/6';
				locate[0]['water']='http://hatyaicityclimate.org/flood/level/6';
				
				locate[1]['lat']=6.995701;
				locate[1]['lon']=100.512797;
				locate[1]['title']='แก้มลิงคลองเรียน';
				locate[1]['photo']='http://hatyaicityclimate.org'+camdata[6].thumb;
				locate[1]['created']=camdata[6].created;
				locate[1]['cam']='http://hatyaicityclimate.org/flood/cam/5';
				locate[1]['water']='http://hatyaicityclimate.org/flood/level/5';

				locate[2]['lat']=6.996007;
				locate[2]['lon']=100.511697;
				locate[2]['title']='ต้นคลอง ร.6';
				locate[2]['photo']='http://hatyaicityclimate.org'+camdata[5].thumb;
				locate[2]['created']=camdata[5].created;
				locate[2]['cam']='http://hatyaicityclimate.org/flood/cam/4';
				locate[2]['water']='http://hatyaicityclimate.org/flood/level/4';
				
				locate[3]['lat']=6.988018;
				locate[3]['lon']=100.469911;
				locate[3]['title']='จันทร์วิโรจน์';
				locate[3]['photo']='http://hatyaicityclimate.org'+camdata[3].thumb;
				locate[3]['created']=camdata[3].created;
				locate[3]['cam']='http://hatyaicityclimate.org/flood/cam/2';
				locate[3]['water']='http://hatyaicityclimate.org/flood/level/2';

				locate[4]['lat']=7.00229;
				locate[4]['lon']=100.455768;
				locate[4]['title']='ที่ว่าการอำเภอหาดใหญ่';
				locate[4]['photo']='http://hatyaicityclimate.org'+camdata[4].thumb;
				locate[4]['created']=camdata[4].created;
				locate[4]['cam']='http://hatyaicityclimate.org/flood/cam/1';
				locate[4]['water']='http://hatyaicityclimate.org/flood/level/1';

				locate[5]['lat']=6.997764;
				locate[5]['lon']=100.446753;
				locate[5]['title']='คลอง ร.1';
				locate[5]['photo']='http://hatyaicityclimate.org'+camdata[2].thumb;
				locate[5]['created']=camdata[2].created;
				locate[5]['cam']='http://hatyaicityclimate.org/flood/cam/10';
				locate[5]['water']='http://hatyaicityclimate.org/flood/level/10';

				locate[6]['lat']=6.931933;
				locate[6]['lon']=100.439767;
				locate[6]['title']='บางศาลา';
				locate[6]['photo']='http://hatyaicityclimate.org'+camdata[1].thumb;
				locate[6]['created']=camdata[1].created;
				locate[6]['cam']='http://hatyaicityclimate.org/flood/cam/3';
				locate[6]['water']='http://hatyaicityclimate.org/flood/level/3';

				locate[7]['lat']=6.823436;
				locate[7]['lon']=100.438244;
				locate[7]['title']='ม่วงก็อง';
				locate[7]['photo']='http://hatyaicityclimate.org'+camdata[0].thumb;
				locate[7]['created']=camdata[0].created;
				locate[7]['cam']='http://hatyaicityclimate.org/flood/cam/9';
				locate[7]['water']='http://hatyaicityclimate.org/flood/level/9';

				
				for (i = 0; i < locate.length; i++) { 
					  var marker = new GGM.Marker({
						  position: new GGM.LatLng(locate[i]['lat'], locate[i]['lon']),
						  map: map,
						  animation:GGM.Animation.DROP,
						  icon:'_images/webcam.jpg'
						  });
					markersArray.push(marker);	
					GGM.event.addListener(marker, 'click', (function(marker, i) {
						return function() {
						contents= '<div><p>'+locate[i]['title']+'</p><br><a href="'+locate[i]['cam']+'" target="_blank"><img src="'+locate[i]['photo']+'"/></a></div>';
						contents+='<div>เมื่อ '+locate[i]['created']+'</div >';
						contents+='<div><a href="'+locate[i]['water']+'" target="_blank"><p>ระดับน้ำ</p></a></div >';
						infowindow.setContent(contents);
						infowindow.open(map, marker);
						}
					  })(marker, i));
					  
					}

				
				},
				error:console.log
		});	
}

function initialpin()
{
		$.ajax({
			type: "POST",
			url:'index.php',
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
							contents= '<div id="pic"><img src="'+locate[i]['photo']+'"/><p>'+locate[i]['title']+'</p></div>';
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
			url:'index.php',
			dataType:'text',
			async: true,
    		cache: false,
			data:datastring,
			success:function(data){
				//console.log(data);
				var locatep=[];
				var html='';					
					locatep=$.parseJSON(data);
					if(locatep.length!=0){
					map.clearMarker();
					cam();
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
							contents= '<div id="pic"><img src="'+locatep[i]['photo']+'"/><p>'+locatep[i]['title']+'</p></div>';
						}else{
							contents= locatep[i]['title'];
						}	
						  infowindow.setContent(contents);
						  infowindow.open(map, marker);
						}
					  })(marker, i));
					html+='<a href="javascript:gotolocation('+locatep[i]['id']+');" id="location'+locatep[i]['id']+'" style="text-decoration:none">';
					html+='<li>'+(i+1)+'. '+locatep[i]['title']+'</li>';
					html+='</a>';
					
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
		
		var position=crosshairs.getPosition();
		geocoder = new google.maps.Geocoder();
var address=$.parseJSON(ajax('http://maps.googleapis.com/maps/api/geocode/json',({latlng:position.lat()+','+position.lng(),sensor:'true',language:'th'}),'GET'));		var apxaddress='';
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
		url: 'upload.php',  //server script to process data
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
				data.name=$('#txtname').val();
				data.address=$('#txtaddress').val();
				data.title=$('#txttitle').val();
				data.detail=$('#txtdescription').val();
				data.lat=$('#txtlat').val();
				data.lon=$('#txtlon').val();
				data.mode='add';		
			if($.trim(dataresponse)!='1'){
				data.photo=dataresponse;
			}else{
				data.photo='';	
				}
				var response=ajax('index.php',data,'POST');
				alert(response);
			$("#jqxProgressBar").jqxProgressBar({value: 0});
			$('#ReportWindow').jqxWindow('close');			
		},
		error: errorHandler = function(data) {
			alert('Photo upload has a problem!');
		},
		data: formData,
		cache: false,
		contentType: false,
		processData: false
	}, 'json');
	
}

function gotolocation(id){	
	var content=$.parseJSON(ajax('index.php',({mode:'selectbyid',id:id}),'POST'));
	if(content.length!=0){
	var contents='';
	if(content[0]['photo'] != ''){
		contents= '<div id="pic"><img src="'+content[0]['photo']+'"/><p>'+content[0]['title']+'</p></div>';
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

</script>
</head>

<body>

<div class="gridContainer clearfix">
	<div id="header">
		<p>Disaster warning system</p>
	</div>
	<div id="contents">
		<div id="map_canvas">
		</div>
		<div id="shadow">
		</div>
		<div id="infopane">
			<div class="rightcontent" style="overflow: scroll">
				<div class="righttopbar">
					<p>News</p>
				</div>
				<ul id="listplace">
					<?php $i=1;
  foreach($report as $item):?>
					<a id="location<?php echo $item['id']?>" href="javascript:gotolocation(<?php echo $item['id']?>);" style="text-decoration: none">
					<li><?php echo $i++;?>. <?php echo $item['title'];?></li>
					</a><?php endforeach;?><a href="javascript:showmorelist();">
					<li style="text-align: center;">More</li>
					</a>
				</ul>
			</div>
		</div>
		<div id="control">
			<div id="btnrefresh" class="tools-btn" title="Current location">
			</div>
			<div id="bt-layer" class="tools-btn" title="Layout">
			</div>
			<div id="bt-report" class="tools-btn" title="Report">
			</div>
		</div>
	</div>
	<div id="LayoutWindow">
		<div>
			<span id="HLabel">Layout</span></div>
		<div style="overflow: scroll;">
			<p style="padding-left: 20px">
			<input id="layout_0" name="layout" type="radio" value="doctor"> doctor
			<br>
			<input id="layout_1" name="layout" type="radio" value="fire_station"> 
			fire_station <br>
			<input id="layout_2" name="layout" type="radio" value="food"> food
			<br><input id="layout_3" name="layout" type="radio" value="health"> 
			health <br>
			<input id="layout_4" name="layout" type="radio" value="hospital"> hospital
			<br>
			<input id="layout_5" name="layout" type="radio" value="pharmacy"> pharmacy
			<br><input id="layout_6" name="layout" type="radio" value="police"> 
			police <br>
			<input id="layout_7" name="layout" type="radio" value="school"> school
			<br>
			<input id="layout_8" checked="CHECKED" name="layout" type="radio" value="''"> 
			none <br>
			<input id="btnCloseLayout" name="" type="button" value="Cancel"> </p>
		</div>
	</div>
	<div id="ReportWindow">
		<div>
			<span id="HLabel">Report</span></div>
		<div style="overflow: scroll;">
			<form id="frmReport" action="" enctype="multipart/form-data" method="get">
				<table border="0">
					<tr>
						<td>Name</td>
						<td>:</td>
						<td><input id="txtname" name="txtname" type="text"></td>
					</tr>
					<tr>
						<td>Address</td>
						<td>:</td>
						<td><textarea id="txtaddress" name="txtaddress"></textarea></td>
					</tr>
					<tr>
						<td>Coordinates</td>
						<td>:</td>
						<td><input id="txtlat" name="txtlat" type="text"></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td><input id="txtlon" name="txtlon" type="text"></td>
					</tr>
					<tr>
						<td>Report</td>
						<td>:</td>
						<td><input id="txttitle" name="txttitle" type="text"></td>
					</tr>
					<tr>
						<td>Description</td>
						<td>:</td>
						<td><textarea id="txtdescription" name="txtdescription"></textarea></td>
					</tr>
					<tr>
						<td>Photo</td>
						<td>:</td>
						<td>
						<input id="txtimage" accept="image/*" capture="camera" name="txtimage" type="file"></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>
						<div id="jqxProgressBar">
						</div>
						</td>
					</tr>
					<tr>
						<td colspan="3">
						<input id="btnSubmit" name="btnSubmit" type="button" value="Submit">
						<input id="btnCloseReport" name="btnCloseReport" type="button" value="Cancel"></td>
					</tr>
				</table>
			</form>
		</div>
	</div>
</div>

</body>

</html>
