/**
 * @author Eric Limegrover
 */

	 	var formdata, xhr, nukelist = [];
	 	
	 	$(document).ready( function(){
		 	
	 		// Change the Upload Input to accept Multiple
			$('#image-upload').attr('multiple','TRUE');
			// Give the Upload Input a proper Name
			$('#image-upload').attr('name','uploads[]');
			// Nuke the original upload processor and replace with my own
			$('input[name="step"]').val('ebl_upload');	
			// Create Form Data Field
			formdata = new FormData();			
			// On File Select, trigger 
			$('#image-upload').change(function() { displaySelectedFiles(this); });
			// Create Upload List Container
			$('<div id="uploadControl"></div>').insertAfter('.upload-form');
			$('INPUT[value="Upload"]').replaceWith('<INPUT type="button" id="uploadNow" class="smallerbox" value="Upload"/>');
			$('<input type="reset" value="Reset" class="smallerbox resetUploadQueue">').insertAfter('#uploadNow');
			$('.resetUploadQueue').click(function() { $('#uploadControl *').remove(); });
			$('#uploadNow').click(function() {
				uploadFiles();
				$('#image-upload').removeAttr('disabled'); 
			});
			$('#image_control FORM').removeAttr('action');
			
			// Since we cannot edit the file-input upload val, we'll just mark the file as do-not upload
			$('.removeItem').live('click', function(){
				if($(this).hasClass('removedFile')) {
					$(this).text('-').removeClass('removedFile');
					filename = $(this).val('rel');
					nukelist.splice(nukelist.indexOf(filename), 1);
				}  else {
					$(this).text('+').addClass('removedFile');
					filename = $(this).val('rel');
					nukelist.push(filename);
					
					for(var i=0;i<nukelist.length;i++){
						console.log("It is: " + i);
					}					
				}
			});
			
	 	});
		
	 	function displaySelectedFiles(thing) {
	 		
	 		$('<div id="uploadControls"></div>').appendTo('#uploadControl');
	 		
	 		if(!$('#uploadControl').hasClass('filesListed')) {
				$('<a id="abort">Cancel</a>').appendTo('#uploadControls');
				$('<a id="clearQueue">Reset</a>').appendTo('#uploadControls');
				$('#uploadControl').addClass('filesListed');
			}
			
			$('#image-upload').attr('disabled', 'TRUE');
			
			for (var i = 0, len = thing.files.length; i < len; i++) {
					file = thing.files[i];
					
					fileStatusContainer =	'<div class="fileStatusContainer">' +
												'<label>'+file.name+'</label>'+
												'<div class="progressBar"><span progress="'+file.name+'"></span></div>'+
												'<a class="removeItem" rel="'+file.name+'">-</a>'+
											'</div>';

					
					$(fileStatusContainer).appendTo('#uploadControl');
							
					console.log(thing.files[i]);
			}
		}

		function uploadFiles(){
	 		var arrayContents;
			
			thing = document.getElementById('image-upload');
			
			for (var i = 0, len = thing.files.length; i < len; i++) {

					file = thing.files[i];
					//console.log(file.name);
					blahblah = nukelist.join();
					
					console.log(blahblah);
				/** Make sure it is permitted to be uploaded **/
					if(nukelist.indexOf(file.name)) {
					console.log(nukelist.indexOf(file.name)+ '|'+ file.name);					
					//uploadFile(file);
					}
			}
		}		

		function uploadFile(file){
			var xhr = new XMLHttpRequest();
			console.log('loaded');
			
			xhr.upload.addEventListener("loadstart", loadStartFunction, false); 
 
			xhr.addEventListener("load", transferComplete, false);  
			xhr.addEventListener("error", transferFailed, false);  
			xhr.addEventListener("abort", transferCanceled, false); 
			
			xhr.open("POST", "index.php?event=image&step=ebl_upload", true);
			xhr.setRequestHeader('UP-FILENAME', file.name);
			xhr.setRequestHeader('UP-SIZE', file.size);
			xhr.setRequestHeader('UP-TYPE', file.type);	
			
			// Show State Change 
		    xhr.onreadystatechange = function(){
		    	console.info("readyState: ", this.readyState);
		    	if (this.readyState == 4) {
		      		if ((this.status >= 200 && this.status < 300) || this.status == 304) {
		        		if (this.responseText != "") {
							
						}
					}
				}
			};
			// Show File Start
			function loadStartFunction(evt){
					
			}
			
			// Progress Handler
			xhr.upload.onprogress = function(event) {
				k = 1024;
			    if (event.lengthComputable) {
					
					completedKB 	= Number(event.loaded/k).toFixed() + "/"+ Number(event.total/k).toFixed() + "kB";
					
					percent 		= parseInt((event.loaded / event.total * 100));
									
					$('SPAN[progress="'+file.name+'"]').css({width: percent+'%'}).text(percent);
					
			    }
			}
			
			// Upload Complete
			function transferComplete(evt)	{
				$('SPAN[progress="'+file.name+'"]').css({width: '100%'}).text('100% :: Complete');
			}
			
			// Upload the file
			xhr.send(file);

			// Abort Function
			$('#abort').click(function() {
				console.log('cancelling');
				xhr.abort();
				clearQueue();
				$('#abort').remove();
			});
			
			$('#clearQueue').live('click',function(){ clearQueue() });

			function clearQueue() {
				console.log('clearQueue');
				$('#image-upload').val('');
			}
		}


		function transferFailed(evt) 	{
			console.log("3::");
			console.log(evt);
		}
		function transferCanceled(evt) 	{
			console.log("4::");
			console.log(evt);
		}