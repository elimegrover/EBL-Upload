<?

global $path_to_site, $img_dir, $ebl_upload_lang;

$GLOBALS['upload_path'] = "$path_to_site/$img_dir/batchupload";

define('EBL_UPLOAD_PREFIX', 'ebl_upload');

$ebl_upload_lang = array(
	'up_addit_img' => 'Upload Additional Images',
	'up_addit_file' => 'Upload Additional Files',
	'show-controls' => 'Show Controls',
	'hide-controls' => 'Hide Controls',
	'show_cntl' => 'Show Controls',
	'reset' => 'Reset',
	'upload' => 'Upload',
	'cancel' => 'Cancel',
	'queued' => 'Queued',
	'uploading' => 'Uploading',
	'complete' => 'Complete',
	'allcomplete' => 'Upload Complete!',
	'ttluploaded' => 'total files uploaded.',
	'click2process' => 'Click to process uploaded files.',
	'resize' => 'Resize Original?',
	'thumbnail' => 'Create Thumbnail?',
	'image_import' => 'Image Import',
	'file_import' => 'Import Files',
	'yes' => 'Yes',
	'no' => 'No',
	'crop' => 'Crop',
	'width' => 'Width',
	'height' => 'Height',
	'assign2cat' => 'Assign to Category',
	'ornewcat' => 'OR<br/>Enter new category name',
	'ttl2import' => 'Total files to be imported within the batch upload folder',
	'processuploads' => 'Process Uploaded Files',
	'importbttn' => 'Import',
	'progress' => 'Progress',
	'imported' => 'Files imported',
	'zerobyte' => 'Zero Byte File : Not added to queue',
	'tolarge' => 'File to Large: Not added to queue',
	'submityes' => 'Submitted Successfully',
	'submitfail' => 'Unable to submit',
	'uploadcomplete' => 'Uploads Complete',
	'completemessage' => 'Your files have been uploaded. ',
	'uploadagain' => 'Upload Additional Files'
);


if(gps('event') == 'image' || gps('event') == 'file') {
	register_callback('ebl_upload_enumerate_strings', 'l10n.enumerate_strings');
	register_callback('ebl_batchupload', 'admin_side', 'head_end');
}

if (gps('event') == "uploadfiles") { doUploads(); }

if (txpinterface == 'admin') 
{
	switch (gps('event'))
		{
		case 'eblpostimginfo':
			ebl_submitImgInfo();
			break;
		case 'eblpostfileinfo':
			ebl_submitFileInfo();
			break;
		case 'eblImport':
			ebl_Import();
			break;
		case 'batchUpload':
			eblUploadUI();
			break;
		case 'processUpload':
			ebl_processUpload(gps('uploadType'));
			break;
		case 'eblUploadInstall':
			ebl_upload_install();
			break;
	}
}


function ebl_upload_enumerate_strings ($event,$step = '',$pre = 0)
{
	global $ebl_upload_lang;
	
	$r = array(	
		'owner' => 'ebl_upload',
		'prefix' => EBL_UPLOAD_PREFIX,
		'lang' => 'en-gb',
		'event' => 'public',
		'strings' => $ebl_upload_lang
	);
	return $r;
}

function ebl_upload_gTxt ($what,$args = array())
{
	global $ebl_upload_lang, $textarray;
	
	$key = strtolower(EBL_UPLOAD_PREFIX . '-' . $what);

	if (isset($textarray[$key])) {
		$str = $textarray[$key];
	} else {
		$key = strtolower($what);
		if (isset($ebl_upload_lang[$key])) {
			$str = $ebl_upload_lang[$key];
		} elseif (isset($textarray[$key])) {
			$str = $textarray[$key];
		} else {
			$str = $what;
		}
	}
	if (! empty($args)) {
		$str = strtr($str,$args);
	}
	return $str;
}

function ebl_batchUpload ()
{
	global $ebl_upload_serial;
	
	$where = gps('event');
	$activetab = ($where == 'image') ? ebl_upload_gTxt('up_addit_img') : ebl_upload_gTxt('up_addit_img');
	$showcontrol = ebl_upload_gTxt('show-controls');
	$hidecontrol = ebl_upload_gTxt('hide-controls');
	
	$uploadType = ($where == 'image') ? 'img' : 'file';

	$step = gps('step');

	if ($step != 'image_edit' && $step != 'file_edit') {
		$ebl_upload = <<<EOD
$(document).ready(function() {

$('<div id="eblUpload"><h3>{$activetab } [ <span></span> ] </h3></div>').insertBefore('.upload-form');
$('<a/>').attr({'href':'#', 'class':'ebltrigger','id':'hideiframe'}).text('$hidecontrol').appendTo('#eblUpload span').hide();
$('<a/>').attr({'href':'#', 'class':'ebltrigger','id':'showiframe'}).text('$showcontrol').appendTo('#eblUpload span');

$('.ebltrigger').click(
function(){
   $('#ebl_upload_content').toggle();
    $('.search-form').toggle();
    $('#hideiframe').toggle();$('#showiframe').toggle();
    $('#list').toggle();
    $("select[name=qty]").parent().slideToggle('fast');$('.prev-next').toggle();
     return false; 
}).appendTo("#eblUpload span");

$('.ebltrigger').click(function() {

$("#list").load("index.php?event=$where #list");
		
		$().ajaxSend(function(r,s){  
		 $('#list').addClass('reloading');
		});  
   
		$().ajaxStop(function(r,s){  
		 $('#list').removeClass('reloading'); 
		});

 return false;
});


$('<iframe src="index.php?event=batchUpload&type=$uploadType" style="border: none; "></iframe>').attr({ 'id':'ebl_upload_content','width':'100%','height':'375px','frameBorder':'0','scrolling':'no'}).insertAfter("#eblUpload");

$('#ebl_upload_content').hide();

$('.upload-form').hide();
});
EOD;

		$ebl_upload_notinst = <<<EOD
$(document).ready(function() {
$('<div id="eblUpload"><h3>EBL_Upload Not fully installed.</h3></div>').insertBefore('.upload-form');
});
EOD;

		echo '<style>#eblUpload { text-align: center; }</style>' . n . '<script type="text/javascript">';
		echo (strlen($ebl_upload_serial) > 0) ? $ebl_upload : $ebl_upload_notinst;
		echo '</script>';
	}
}

function eblUploadUI ()
{
	global $siteurl,$maxUpload,$ebl_upload_serial,$ebl_valid_img_type,$ebl_valid_file_type;
	
	$eblUploadType = gps('type');
	$validType = ($eblUploadType == 'img') ? $ebl_valid_img_type : $ebl_valid_file_type;
	$uploadExt = '*.' . str_replace(',',';*.',$validType);
	
	list ($userId,$cookieHash) = split(',',cs('txp_login'));
	
	$queued = ebl_upload_gTxt('queued');
	$zerobyte = ebl_upload_gTxt('zerobyte');
	$tolarge = ebl_upload_gTxt('tolarge');
	$uploading = ebl_upload_gTxt('uploading');
	$complete = ebl_upload_gTxt('complete');
	$allcomplete = ebl_upload_gTxt('allcomplete');
	$ttluploaded = ebl_upload_gTxt('ttluploaded');
	$click2process = ebl_upload_gTxt('click2process');
	$browse = gTxt('browse');
	$reset = ebl_upload_gTxt('reset');
	$upload = ebl_upload_gTxt('upload');
	$cancel = ebl_upload_gTxt('cancel');
	$filename = gTxt('filename');
	$status = gTxt('status');
	$progress = ebl_upload_gTxt('progress');
	
	echo <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <title>EBL Upload</title>
 <script type="text/javascript" src="?event=ebl_swfobject"></script>
 <script type="text/javascript">
	var flashvars = {myUrl:"http://$siteurl",maxUploadSize:"$maxUpload",eblUpSerial:"$ebl_upload_serial",uploadType:"$eblUploadType",uploadExt:"$uploadExt",cookieHash:"$cookieHash",userId:"$userId"};
	var params = {};
		params.quality = "best";
		params.wmode = "transparent";
		params.allowfullscreen = "false";
		params.allowscriptaccess = "always";
	var attributes = {};
		attributes.id = "mycom";
		attributes.name = "mycom";
		attributes.styleclass = "mycom";
	swfobject.embedSWF("?event=ebluploadswf", "flashcontent", "100", "40", "9.0.0", false, flashvars, params, attributes);
 </script>
 <script type="text/javascript">

	function trace( msg ){
		if( typeof( jsTrace ) != 'undefined' ){
			jsTrace.send( msg );
		}
	};

	window.onload = function() {
		document.getElementById('upload').disabled = true; 
		document.getElementById('reset').disabled = true; 
		document.getElementById('cancel').disabled = true; 
		trace("onload!");
		document.getElementById("browse").onclick = function() {
			trace("browse"); 
			var obj = document.getElementById("mycom");
			obj.browsefiles();
		}
		document.getElementById("reset").onclick = function() {
			trace("reset");
			var obj = document.getElementById("mycom");
			obj.resetfiles();
		}
		document.getElementById("upload").onclick = function() {
			trace("upload");
			var obj = document.getElementById("mycom");
			obj.uploadfiles();
			document.getElementById('cancel').disabled = false; 
		}
		document.getElementById("cancel").onclick = function() {
			trace("cancel");
			var obj = document.getElementById("mycom");
			obj.cancelupload();
		}
	};

	function filelist(str, num, added) {
			trace(added);
			document.getElementById('upload').disabled = false; 
			document.getElementById('reset').disabled = false; 
		var e = document.getElementById('filelist');
		var li = document.createElement('li'); // list item
			li.setAttribute('id', num);
		var an = document.createElement('a');
			an.setAttribute('name', num);
		var mc = document.createElement('div');
			mc.className = 'listcontainer';			
		var f = document.createElement('span'); //first span

			f.className = 'filename';
		if(added === "Y") {
		var a = document.createElement('a'); // clickable link for removing individual files
			a.innerHTML = str; // filename.
			a.setAttribute('href', '#');
			a.setAttribute('name', num);
			a.onclick = function(){removefile(this.name)};
		} else {
		var a = document.createElement('span');
			a.innerHTML = str;
		}
			f.appendChild(a); // insert link into span 1
		var s = document.createElement('span'); //second span
			s.className = 'status'; // set class status
			s.setAttribute('id', num+"actv");
		if(added === "Y") { trace(added);
			s.innerHTML = '$queued'; // set initial file status
		} 
		if(added === "Z") { trace("zero byte");
			s.innerHTML = '$zerobyte';
		} 
		if(added === "L") { trace("too large");
			s.innerHTML = '$tolarge';
		}
		var p = document.createElement('span'); // third span
			p.setAttribute('id', num+"stat");
		if(added === "Y") { 
			p.className = 'progress'; // set class status for 3rd block
		} 
			p.innerHTML = "&nbsp;"; // give it some fake content so it'll expand. 

			mc.appendChild(f); // insert first span
			mc.appendChild(s); // insert second span
			mc.appendChild(p); // insert third span
			li.appendChild(an); // insert anchor 
			li.appendChild(mc);
			e.appendChild(li); // insert new complete liste item into OL id = filelist
	}

	function reset() {
		trace("reset");
			document.getElementById('upload').disabled = true; 
			document.getElementById('reset').disabled = true; 
			document.getElementById('cancel').disabled = true; 
		var x = document.getElementById("filelist");
		if (x.hasChildNodes())
		{
		    while ( x.childNodes.length >= 1 )
		    {
		        x.removeChild( x.firstChild ); 
		    } 
		}
		
		var y = document.getElementById("eblAppStatus");
			y.className = "";
		if (y.hasChildNodes())
		{
		    while ( y.childNodes.length >= 1 )
		    {
		        y.removeChild( y.firstChild );       
		    } 
		}
	}

	function removefile(id) {
		trace("removefile" + id);
		var obj  = document.getElementById("mycom");
			obj.removefile(id);
	}

	function removedfile(id) {
		trace( 'removedfile invoked ' + id );
		var a = document.getElementById('filelist');
		var b = document.getElementById(id);
			a.removeChild(b);
	}

	function uploadprogress(id, percent) {
		trace( 'uploadprogress invoked [' + id + "] [" + percent + "]");
		var x=document.getElementById(id+"stat");
			x.style.width = "" + percent * 2 + "px";
		var y=document.getElementById(id+"actv");
		    y.innerHTML = '$uploading';
		if(percent == 100) {
			y.innerHTML = '$complete'; // Redundancy is good.
		}
		location.href="#" + id;
	}

	function fileStatus(id) {
		trace('fileStatus');
		var x=document.getElementById(id+"actv");
			x.innerHTML = '$complete';
		var x=document.getElementById(id+"stat");
			x.style.width = "200px"; // that redundancy thing again.
	}

	function criticalStopError(critErr) {

		var x = document.getElementById('eblAppStatus');
			x.className = 'errMsg';
		var erp = document.createElement('p');
			erp.innerHTML = "Error - [ " + critErr + " ]";
			x.appendChild(erp);
			document.getElementById('eblUploadControls').style.display='none';
	}
	
	function allComplete(ttl) { 
		var x = document.getElementById('eblAppStatus');
			x.className = 'completeMsg';
			x.innerHTML = '';
		var msg = document.createElement('p');
			msg.innerHTML = '$allcomplete [' + ttl + '] $ttluploaded<br/>';
		var a = document.createElement('a');
			a.setAttribute('href', 'index.php?event=processUpload&uploadType=$eblUploadType'); 
			a.innerHTML = '$click2process';
			msg.appendChild(a);
			x.innerHTML = '';
			x.appendChild(msg);
			document.getElementById('upload').disabled = true; 
			document.getElementById('reset').disabled = true; 
			document.getElementById('cancel').disabled = true; 
	}

	-->
</script>
<style>
#eblUploadUI{margin:0 auto;width:750px;text-align:center}
#eblUploadControls TABLE { margin: 0 auto; text-align: center; }
#eblUploadControls TD{text-align:center}
#eblUploadControls INPUT{margin:5px}
#browse,#upload,#cancel,#reset,#flashcontent{width:100px;height:25px;line-height:25px}
#browse,#upload{font-weight:bold}
#bttnContainer{position:relative}
#browse{position:absolute;top:0;left:0;z-index:999}
#flashcontent,#mycom{position:absolute;top:5px;left:5px;z-index:1000;height:25px}
#eblBrowse{width:110px;height:25px}
#filelist{font-size:15px;list-style-position:outside;list-style-type:decimal}
#filelistHeader { clear: both; overflow: auto; list-style: none; }
#uploadstatus { overflow: auto; height: 250px; width: 100%; border:1px solid #DDD;}
LI { text-align: left; }

.filename,.status,.progress { display: inline-block; }
.filename 	{ width: 200px; }
.status 	{ width: 150px; background: #FFF; padding: 0 5px;}
.progress	{ width: 1px; background: #770000;}

.completeMsg { background: #FAF8CC; height: 40px; padding: 2px; }
.errMsg { background: #FAF8CC; height: 40px;}
.errMsg P { line-height: 40px; }
IFRAME { border: none; }
</style>
<link href="http://www.syserror.net/bannister/textpattern/textpattern.css" rel="stylesheet" type="text/css" />

</head>

<body>
<div id="eblUploadUI">
 <div id="eblUploadControls">
  <table><tr>
   <td id="eblBrowse">
   	<div id="bttnContainer">
     <div id="underButton"><input type="button" name="browse" id="browse" value="$browse" class="smallerbox" /></div>
     <div id="flashcontent"></div>
    </div>
   </td>
   <td><input type="button" name="reset"  id="reset"  value="$reset"  class="smallerbox" /></td>
   <td><input type="button" name="upload" id="upload" value="$upload" class="smallerbox" /></td>
   <td><input type="button" name="cancel" id="cancel" value="$cancel" class="smallerbox" /></td>
  </tr></table>
 </div>
 <div id="eblAppStatus"></div>
 <ul id="filelistHeader">
  <li><div class="listcontainer"><span class="filename"><b>$filename</b></span><span class="status"><b>$status</b></span><span class="left"><b>$progress</b></span></div></li>    
 </ul>
 <div id="uploadstatus"> 
  <ol id="filelist">
  </ol>
 </div>
</div>
</body>
</html>
EOT;
	exit();
}
;

function doUploads ()
{
	global $upload_path;
	
	$userId = gps('uid');
	$cookieHash = gps('ch');
	$nonce = safe_field('nonce','txp_users',"name='" . doSlash($userId) . "' AND last_access > DATE_SUB(NOW(), INTERVAL 30 DAY)");
	if ($nonce == md5($userId . pack('H*',$cookieHash))) {
		$dir = $upload_path;
		$uploadfile = str_replace(' ','-',$dir . '/' . basename($_FILES['Filedata']['name']));
		if (move_uploaded_file($_FILES['Filedata']['tmp_name'],$uploadfile)) {
			header("Expires: mon, 06 jan 1990 00:00:01 gmt");
			header("Pragma: no-cache");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			echo "Success";
		} else {
			header('HTTP/1.1 404 Not Found');
		}
	} else {
		header('HTTP/1.1 403 Forbidden');
	}
	exit();
}

function ebl_categoryList ($catType = '')
{
	$type = ($catType == 'img') ? 'image' : 'file';
	
	$results = safe_rows('*','txp_category','type="' . $type . '"',$debug = '');
	
	$i = 0;
	$out = '<option value="">--</option>';
	if ($results) {
		foreach ($results as $catList) {
			extract($catList);
			$i ++;
			$out .= ($name != 'root') ? "<option value=\"$name\" >$name</option>" . n : '';
		}
	}
	return $out;
}

function ebl_processUpload ($uploadType = '')
{
	global $prefs; echo "$uploadType is my upload type!";
	
	extract($prefs);
	
	$imagePath = $path_to_site . '/' . $img_dir . '/';
	
	$css = ($uploadType == 'img') ? 'width: 215px; float: left; margin: 5px;' : 'width: 350px; margin: 5px auto;';
	$resize = ebl_upload_gTxt('resize');
	$yes = ebl_upload_gTxt('yes');
	$no = ebl_upload_gTxt('no');
	$crop = ebl_upload_gTxt('crop');
	$width = ebl_upload_gTxt('width');
	$height = ebl_upload_gTxt('height');
	$thumbnail = ebl_upload_gTxt('thumbnail');
	$image_import = ebl_upload_gTxt('image_import');
	$file_import = ebl_upload_gTxt('file_import');
	$ttl2import = ebl_upload_gTxt('ttl2import');
	$importbttn = ebl_upload_gTxt('importbttn');
	$assign2cat = ebl_upload_gTxt('assign2cat');
	$ornewcat = ebl_upload_gTxt('ornewcat');
	$processuploads = ebl_upload_gTxt('processuploads');
	
	echo <<<EOT
'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <title>Process Image Uploads</title>
 <style>
  #eblUploadUI{margin:0 auto;width:750px;text-align:center;overflow: auto}
  #completeUpload { clear: both; text-align: center; }
  #completeUpload P { margin: 20px 0 0; }
  SELECT { width: 100px; }
  .eblImgCol {border:1px solid #DDD; height: 150px; $css}
 </style>
 <link href="textpattern.css" rel="stylesheet" type="text/css" />
</head>
<body>
 <div id="eblUploadUI">
  <form action="index.php?event=eblImport" method="post" >
  <input name="eblUploadType" type="hidden" value="$uploadType" />
EOT;

	if ($uploadType == 'img') {
		echo <<<EOT
  <div class="eblImgCol">
   <p><strong>$resize'</strong></p>
   <p>
    <label for="resizeYes">$yes</label>
     <input type="radio" name="eblResize" id="resizeYes" value="Y" />
    <label for="resizeNo">$no</label>
     <input type="radio" name="eblResize" id="resizeNo" value="N" checked="checked"/>
    <label for="imgCrop">$crop</label>
     <input type="radio" name="eblResize" id="imgCrop" value="C" />
   </p>
   <p>
    <label for="eblResizeW">$width </label>
     <input name="eblResizeW" type="text" id="eblResizeW" size="5" maxlength="4" /> 
    <label for="eblResizeH">$height</label>
     <input name="eblResizeH" type="text" id="eblResizeH" size="5" maxlength="4" />
   </p>
  </div>

  <div class="eblImgCol">
   <p>$thumbnail</strong></p>
   <p>
    <label for="thumbYes">$yes</label>
     <input type="radio" name="eblThumb" id="thumbYes" value="Y" />
    <label for="thumbNo">$no</label>
     <input type="radio" name="eblThumb" id="thumbNo" value="N" checked="checked" /> 
    <label for="thumbCrop">$crop</label>
     <input type="radio" name="eblThumb" id="thumbCrop" value="C" />
   </p>
   <p>
   <label for="thumbW">$width</label>
    <input name="eblThumbW" type="text" id="thumbW" size="5" maxlength="4" />
   <label for="thumbH">$height</label>
    <input name="eblThumbH" type="text" id="thumbH" size="5" maxlength="4" />
   </p>
  </div> 
EOT;
	}
	
	$disUploadType = ($uploadType == 'img') ? $image_import : $file_import;
	$displayCats = ebl_categoryList($uploadType);
	
	echo <<<EOT
  <div class="eblImgCol">
   <p><strong>$disUploadType</strong></p>
   <p>
    <label for="imgCat">$assign2cat</label>
    <select name="eblImportCatList" >
    $displayCats;
    </select>
   </p>
   <p>$ornewcat</p><input name="eblImportNewCat" type="text" />
  </div>
  <div id="completeUpload">
EOT;

	$ttlUploaded = count(ebl_uploadedFileSearch($uploadType));
	
	echo '[' . $ttlUploaded . '] ' . $ttl2import . '</p>';
	
	if ($ttlUploaded > 0) {
		echo '<p>' . '<label for="importImages">' . $processuploads . '&nbsp;</label>' . '<input type="submit" name="importImages" id="importImages" value="' . $importbttn . '" />' . '</p>';
	}
	
	echo '  </form> 
 </div>
</div>

</body>
</html>';
	exit();
}

function ebl_uploadedFileSearch ($eblUploadType)
{
	global $file_base_path,$path_to_site,$img_dir,$ebl_valid_file_type,$ebl_valid_img_type;
	
	$fileList = Array();
	
	$dir = $path_to_site . '/' . $img_dir . '/batchupload/';
	
	if ($eblUploadType == 'img') {
		$validType = explode(',',$ebl_valid_img_type);
	} elseif ($eblUploadType == 'file') {
		$validType = explode(',',$ebl_valid_file_type);
	}
	
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) != false) {
				$ext = end(explode(".",$file));
				if (in_array($ext,$validType)) {
					$fileList[] = $file;
				} else {
					if ($file != '.' && $file != '..') {
						unlink($dir . $file);
					}
				}
			}
			closedir($dh);
		}
	}
	
	return $fileList;
}

function ebl_Import ()
{
	global $prefs;
	
	extract($prefs);
	
	$step = '';
	$event = '';
	
	include (txpath . '/include/txp_file.php');
	
	set_time_limit('300');
	
	$dir = $path_to_site . '/' . $img_dir . '/batchupload/';
	
	$fc = 0;
	$eblUploadType = gps('eblUploadType');
	if ($eblUploadType == 'img') {
		$fc = 0;
		$validType = explode(',',$ebl_valid_img_type);
		if (gps('eblThumb') != "N") {
			$mkthumb = TRUE;
			$tmbwidth = (gps('eblThumbW') > 10) ? gps('eblThumbW') : 100;
			$tmbheight = (gps('eblThumbH') > 10) ? gps('eblThumbH') : 100;
			$crop = (gps('eblThumb') == "C") ? TRUE : FALSE;
		} else {
			$mkthumb = FALSE;
		}
		if (gps('eblResize') != "N") {
			$rszimg = TRUE;
			$rszwidth = (gps('eblResizeW') > 10) ? gps('eblResizeW') : 640;
			$rszheight = (gps('eblResizeH') > 10) ? gps('eblResizeH') : 480;
			$crop = (gps('eblResize') == "C") ? TRUE : FALSE;
		} else {
			$rszimg = FALSE;
		}
	} elseif ($eblUploadType == 'file') {
		$validType = explode(',',$ebl_valid_file_type);
	}
	
	$catname = gps('eblImportCatList');
	$catname = ($catname != '') ? doSlash($catname) : doSlash(gps('eblImportNewCat'));
	
	$submityes = ebl_upload_gTxt('submityes');
	$submitno = ebl_upload_gTxt('submitfail');
	$uploadcomplete = ebl_upload_gTxt('uploadcomplete');
	$completemessage = ebl_upload_gTxt('completemessage');
	$uploadagain = ebl_upload_gTxt('uploadagain');
	
	echo <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Process Image Uploads</title>
<style>
#endText   { width: 700px; height: 40px; margin: 0 auto 10px; }
#inputCont { width: 700px; height: 300px;margin: 0 auto; overflow: auto; }
#inputCont form 
           { width: 600px; margin: 20px 0; height: 150px; clear: both;}
label      { display: inline-block; width: 150px; text-align: right;}
input      { width: 400px; margin: 5px 0; }
textarea   { width: 400px; }
</style>
<link href="http://www.syserror.net/bannister/textpattern/textpattern.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript">

	function submitImgInfo(id) {
		var a = document.getElementById('imgalt' + id);
		var b = document.getElementById('imgcapt' + id);
		var s = document.getElementById('submit' + id);
		$.ajax({
			type: "POST",
			url: "index.php?event=eblpostimginfo&imgid="+id,
			data: 	"&alt=" + a.value + "&caption=" + b.value,
			success: function(html){
				if(html.match(/submitted/)) {
					s.disabled = true;
					a.disabled = true;
					b.disabled = true;
					s.value = '$submityes';
				} else {
					alert("$submitno [" + html + "]")
				}
			}
		});
	}
	
	function submitFileInfo(id) {
		var a = document.getElementById('filedesc' + id);
		var s = document.getElementById('submit' + id);
		$.ajax({
			type: "POST",
			url: "index.php?event=eblpostfileinfo&fileid="+id,
			data: "&filedesc=" + a.value,
			success: function(html){
				if(html.match(/submitted/)) {
					s.disabled = true;
					a.disabled = true;
					s.value = '$submityes';
				} else {
					alert("$submitno [" + html + "]")
				}
			}
		});
	}

</script>
</head>
<body>
<div id="endText">
	<h1>$uploadcomplete</h1>
	<p>$completemessage <a href="index.php?event=batchUpload&type=$eblUploadType">$uploadagain</a></p>
</div>
<div id="inputCont">
EOT;

	$result = n;
	
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (filetype($dir . '/' . $file) == 'file') {
					$ext = strtolower(substr($file,- 3));
					if (in_array($ext,$validType)) {
						$fc++;
						if ($eblUploadType == 'file') {

							$id = ebl_import_file($file,$catname);
							
							if($id) {
								$copyresult = ebl_moveUploadedFiles($file,$id,'file');
								
								$result .=	'<form name="' . $file . '" method="post" action="#">'.n.
											" <p>Filename [<b>$file</b>] imported [$copyresult]</p>".n.
											' <label for="textarea">Description</label>'.n.
								 			'  <textarea name="filedesc" id="filedesc'.$id.'"></textarea>'.n. 
								 			' <label for="submit">Save</label>'.n.
								 			'  <input type="button" name="'.$id.'" class="smallerboxsp" value="Submit" id="submit'.$id.'" class="submitMe" onclick="submitFileInfo(this.name)"/>'.n. 
								 			'</form>'.n;
								 			
							} else {
								$result .= '<p>' . gTxt('file_already_exists',array('{name}' => $file)).'</p>';
								$fc--;
							}
							unlink("$dir/$file");

						} elseif ($eblUploadType == 'img') {
							$importresult = ($rszimg) ? $importresult = ebl_importImg($file,$mkthumb,$rszimg,$catname,$rszwidth,$rszheight,$crop) : $importresult = ebl_importImg($file,$mkthumb,$rszimg,$catname);
							
							$id = ($importresult == 'YES') ? mysql_insert_id() : '';
							$rsz = ($rszimg) ? ebl_goresize($file,$rszwidth,$rszheight,'rsz',$id,$crop) : ebl_moveUploadedFiles($file,$id,'img');
							$tmb = ($mkthumb) ? ebl_goresize($file,$tmbwidth,$tmbheight,'tmb',$id,$crop) : 'NO';
							
							$result .=	'<form name="'.$file.'" method="post" action="#">'.n.
										" <p>Filename [<b>$file</b>] Imported [<b>$importresult</b>] Resized [<b>$rsz</b> ] Thumbnailed [<b>$tmb</b>]</p>".n.
										' <label for="textfield">'.gTxt('alt_text').'</label>'.n.
										'  <input name="imgalt" type="text" id="imgalt'.$id.'" value="" size="30" /><br/>'.n.
										' <label for="textarea">'.gTxt('caption').'</label>'.n.
										'  <textarea name="imgcapt" id="imgcapt'.$id.'"></textarea>'.n.
										' <label for="submit">Save</label>'.n.
										'  <input type="button" name="'.$id.'" class="smallerboxsp" value="Submit" id="submit'.$id.'" class="submitMe" onclick="submitImgInfo(this.name)"/>'.n.
										'</form>'.n;
										
							unlink("$dir/$file");
						}
					}
				}
			}
			closedir($dh);
			echo $result;
			echo "imported [ $fc ] total files";
		} else {
			echo 'Error Opening Dir';
		}
	} else {
		echo $dir;
		echo 'Error Opening Dir';
	}
	echo '</div></body></html>';
	exit();
}

function ebl_submitImgInfo ()
{
	$id = doSlash(gps('imgid'));
	$caption = doSlash(gps('caption'));
	$alt = doSlash(gps('alt'));
	
	$rs = safe_update("txp_image","caption = '$caption', alt = '$alt'","id = $id");
	
	echo ($rs) ? 'submitted' : mysql_error();
	
	exit();
}

function ebl_submitFileInfo ()
{
	$id = doSlash(gps('fileid'));
	$description = doSlash(gps('filedesc'));
	
	$rs = safe_update("txp_file","description = '$description'","id = $id");

	echo ($rs) ? 'submitted' : mysql_error();
	
	exit();
}

function ebl_importImg ($filename,$tmb,$rszimg,$catname,$rszwidth = '',$rszheight = '',$crop = '')
{
	global $txp_user,$path_to_site,$img_dir;
	
	$path = $path_to_site . '/' . $img_dir . '/batchupload/';
	
	list ($width,$height) = getimagesize($path . $filename);
	
	if ($rszimg) {
		if (! $crop) {
			$scale = min($rszwidth / $width,$rszheight / $height);
			if ($scale < 1) {
				$width = ceil($scale * $width);
				$height = ceil($scale * $height);
			}
		} else {
			if ($rszwidth < $width) {
				$width = $rszwidth;
			}
			if ($rszheight < $height) {
				$height = $rszheight;
			}
		}
	}
	
	$thumb = ($tmb == "YES") ? '1' : '0';
	$ext = strtolower(substr($filename,- 3));
	
	$check = safe_field("name","txp_category","name='$catname' and type='image'");
	$showcheck = ($check) ? 'TRUE' : 'FALSE';

	if (!$check) {
		
		if ($catname != '') {
			safe_insert("txp_category","name='$catname', title='$catname', type='image', parent='root'");
			rebuild_tree('root',1,'image');
		}
	}
	
	$rs = safe_insert(
						"txp_image",
						"w = '$width',
						 h = '$height',
						 category = '$catname',
						 ext = '.$ext',
						`name` = '$filename',
						`date` = now(),
						 caption = '',
						 alt = '',
						 author = '$txp_user',
						 thumbnail = '$thumb'");
						 
	$result = ($rs) ? 'YES' : 'FAIL';
	
	return $result;
}

function ebl_import_file($file,$catname) {

	global $path_to_site,$img_dir;
	
	$path = $path_to_site . '/' . $img_dir . '/batchupload/';

	$size = @filesize(build_file_path($path,$file));

	$id = @file_db_add($file,$catname,'','',$size);
	
	if ($id) {
		if ($catname != '') {
			$check = safe_field("name","txp_category","name='$catname' and type='file'");
			if(!$check) {
				safe_insert("txp_category","name='$catname', title='$catname', type='file', parent='root'");
				rebuild_tree('root',1,'file');
			}
		}
		return $id;
	} else {
		return FALSE;
	}
}

function ebl_goresize ($filename,$maxwidth,$maxheight,$imgtype,$id,$crop)
{
	global $upload_path,$path_to_site,$img_dir;
	
	register_shutdown_function('ebl_freeMem');
	
	$imagedir = $path_to_site . '/' . $img_dir . '/';
	$path = $upload_path . '/';
	$ext = strtolower(substr($filename,- 3));
	
	$imageinfo = getimagesize($path . $filename);
	$memoryNeeded = @round(($imageinfo[0] * $imageinfo[1] * $imageinfo['bits'] * $imageinfo['channels'] / 8 + pow(2,16)) * 3);
	$memoryNeeded = $memoryNeeded * 1.5;
	
	if (function_exists('memory_get_usage') && memory_get_usage() + $memoryNeeded > (integer) ini_get('memory_limit') * pow(1024,2)) {
		@ini_set('memory_limit',(integer) ini_get('memory_limit') + ceil(((memory_get_usage() + $memoryNeeded) - (integer) ini_get('memory_limit') * pow(1024,2)) / pow(1024,2)) . 'M');
	}
	
	switch ($ext) {
		case 'jpg':
			$srcimage = imagecreatefromjpeg($path . $filename);
			break;
		case 'gif':
			$srcimage = imagecreatefromgif($path . $filename);
			break;
		case 'png':
			$srcimage = imagecreatefrompng($path . $filename);
			break;
		case 'bmp':
			return 'NO';
			break;
		case 'tif':
			return 'NO';
			break;
	}
	
	list ($width,$height) = getimagesize($path . $filename);
	
	if (gps('eblThumb') != 'C') {
		$scale = min($maxwidth / $width,$maxheight / $height);
		
		if ($scale < 1) {
			$newwidth = ceil($scale * $width);
			$newheight = ceil($scale * $height);
		} else {
			@imagedestroy($srcimage);
			$copyresult = ebl_moveUploadedFiles($filename,$id,'img');
			return '&#60;';
		}
		
		$thumb = imagecreatetruecolor($newwidth,$newheight);
		
		imagealphablending($thumb,false);
		
		if (! imagecopyresampled($thumb,$srcimage,0,0,0,0,$newwidth,$newheight,$width,$height)) {
			return 'OOM';
		}
		
	} elseif (gps('eblThumb') == 'C') {
		if ($maxwidth > $width) {
			$maxwidth = $width;
		}
		
		if ($maxheight > $height) {
			$maxheight = $height;
		}
		
		$thumb = imagecreatetruecolor($maxwidth,$maxheight);
		
		$src_x = $src_y = 0;
		$src_w = $width;
		$src_h = $height;
		$cmp_x = $width / $maxwidth;
		$cmp_y = $height / $maxheight;
		
		if ($cmp_x > $cmp_y) {
			$src_w = round(($width / $cmp_x * $cmp_y));
			$src_x = round(($width - ($width / $cmp_x * $cmp_y)) / 2);
		} elseif ($cmp_y > $cmp_x) {
			$src_h = round(($height / $cmp_y * $cmp_x));
			$src_y = round(($height - ($height / $cmp_y * $cmp_x)) / 2);
		}
		
		imagecopyresampled($thumb,$srcimage,0,0,$src_x,$src_y,$maxwidth,$maxheight,$src_w,$src_h);
	}
	
	if ($imgtype == 'tmb') {
		$resultname = $id . 't.' . $ext;
	}
	
	if ($imgtype == 'rsz') {
		$resultname = $id . '.' . $ext;
	}
	
	switch ($ext) {
		case ($ext == 'jpg' || $ext == 'jpeg'):
			$fileresult = imagejpeg($thumb,$imagedir . $resultname,'100');
			break;
		case "gif":
			$fileresult = imagegif($thumb,$imagedir . $resultname);
			break;
		case "png":
			$fileresult = imagepng($thumb,$imagedir . $resultname);
			break;
	}
	
	imagedestroy($srcimage);
	imagedestroy($thumb);
	
	if ($fileresult) {
		return "YES";
	} else {
		return "ERR";
	}
}

function ebl_moveUploadedFiles ($file,$id,$type)
{
	global $upload_path,$path_to_site,$img_dir,$file_base_path;
	
	$from = $upload_path . '/' . $file;
	
	if ($type == 'img') {
		$to = $path_to_site . '/' . $img_dir . '/' . $id . '.' . strtolower(substr($file,- 3));
	} else {
		$to = $file_base_path . '/' . $file;
	}
	
	return (copy($from,$to)) ? 'YES' : 'ERROR';
}

function ebl_freeMem ()
{
	global $srcimage,$thumb;
	
	if ($srcimage) { 
		imagedestroy($srcimage);
	}
	
	if ($thumb) {
		imagedestroy($thumb);
	}
}

function ebl_upload_install ()
{
	global $ebl_upload_serial, $upload_path;
	
	if (!$ebl_upload_serial) {

		$err = array();
		
		if (@set_pref('ebl_valid_img_type','gif,bmp,jpg,png','admin',1)) {
			@safe_update('txp_prefs',"position = '21'","name like 'ebl_valid_img_type'");
		} else {
			$err[] = mysql_error();
		}
		if (@set_pref('ebl_valid_file_type','zip,rar,pdf,rtf,txt','admin',1)) {
			@safe_update('txp_prefs',"position = '41'","name like 'ebl_valid_file_type'");
		} else {
			$err[] = mysql_error();
		}
		if (@set_pref('ebl_upload_serial','####','admin',1)) {
			@safe_update('txp_prefs',"position = '121'","name like 'ebl_upload_serial'");
		} else {
			$err[] = mysql_error();
		}
		if (!empty($err)) {	
			echo '<h1>DB Update Failed.</h1><p><ul><li>' . join('</li><li>',$err) . '</li></ul></p>';
		} else {
			
			if(!file_exists($upload_path)) {
				$mkdir = (mkdir($upload_path)) ? TRUE : FALSE;
				
				if($mkdir) { 
					echo "<p><b>$upload_path</b> created</p>"; 
				} else {
					echo "<p><b>$upload_path</b> not created. Please see documentation for help.</p>"; 
				}
			} else {
				echo "<p><b>$upload_path</b></b> already exists.</p>";
			}
			
			if(!is_writable($upload_path)) {
				$chmod = (chmod($upload_path,755)) ? TRUE : FALSE;

				if($chmod) { 
					echo "<p><b>$upload_path</b> permissions set.</p>"; 
				} else {
					echo "<p><b>$upload_path</b> permissions not set. Please see documentation for help.</p>"; 
				}				
			} else {
				echo "<p><b>$upload_path</b> permissions previously set.</p>";
			}

				
			echo '<h1>DB Update Sucessful.</h1><p>You may now change your settings in the Advanced Preferences field</p>';
		}
	} else {
		echo "<p>Previously Installed. Aborting.</p>";
	}
}

?>