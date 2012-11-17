<?php

if (@txpinterface == 'admin')
    {
		if(gps('event') == 'image' || gps('event') == 'file') {
			add_privs('eblUpload', '1'); 
			register_callback('eblUpload', 'admin_side', 'head_end');
 			
			if(gps('step') == 'ebl_upload') {
				print_r($_FILES);
				ebl_uploadProcess();
			}
		}
    }

function eblUpload() {
	$out = 	<<<EOT

	<style>
		#uploadControl
			{
				 width: 550px;
				 margin: 0 auto
			}
			#uploadControls
				{
					padding: 5px 0;
					margin: 5px 0;
					border-bottom: 1px solid #EEE;
				}
		DIV#uploadControl DIV.fileStatusContainer
			{
				overflow: hidden;
				position: relative
			}
		DIV#uploadControl LABEL
			{
				width: 125px;
				padding: 3px 0 0 5px;
				float: left
			}
		DIV.progressBar
			{
				border: 1px solid #dddddd;
				background: #EEEEEE;
				height: 20px;
				width: 375px;
				margin: 2px 0;
				padding: 0;
				border-radius: 5px;
				float: left		
			}
			DIV.progressBar SPAN
				{
					background: #090;
					display: block;
					width: 0%;
					height: 100%;
					color: #FFF;
					lineheight: 2;
					border-radius: 5px
				}
			A.removeItem
				{
					float: left;
					width:  20px;
					height: 20px;
					background: #FEE;
					line-height: 17px;
					text-align: center;
					border-radius: 10px
				}
	</style>

	<script type="text/javascript" src="http://www.syserror.net/textpattern/dev/ebl_upload.js"></script>
			
EOT;


	echo $out.n;
	
}

function ebl_uploadProcess() {
	
	global $prefs;
	
	$upload_path = $prefs['tempdir'];

	$uploaded = 0;
	
  	foreach ($_FILES['uploads']['name'] as $i => $name) { 
        
        if ($_FILES['uploads']['error'][$i] == 4) { 
            continue;  
        } 
        
        if ($_FILES['uploads']['error'][$i] == 0) { 

             if ($_FILES['uploads']['size'][$i] > 99439443) { 
                $message[] = "$name exceeded file limit."; 
                continue;   
             }
			
			echo $upload_path.$name;
			
			if (move_uploaded_file($_FILES['uploads']['tmp_name'][$i], $upload_path.'/eblupload/'.$name)) {
				echo "Success";
			} else {
				echo "Fail";
			}
             
             $uploaded++; 
        } 
   } 
   
   echo $uploaded . ' files uploaded.'; 

}

?>