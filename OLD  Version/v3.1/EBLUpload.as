package
{
	import flash.display.SimpleButton;
	import flash.display.Sprite;
	import flash.external.*;
	import flash.events.*;
	import flash.net.FileReferenceList;
	import flash.net.FileReference;
	import flash.net.URLRequest;
	import flash.net.URLRequestMethod;
	import flash.net.URLVariables;
	import flash.events.MouseEvent;
	import flash.net.FileFilter;

	public class EBLUpload extends Sprite
	{
		private var _fileref:FileReferenceList = new FileReferenceList();
		private var file:FileReference = null;
		private var file_queue:Array = new Array();
		private var critError:Number = 0; // critical stop error. 0 = continue, 1 = stop
		private var cancelUpload:Number = 0; // 0 if its a go, > if it's not.
		private var valid:String;
		private var fileIdArray:Array = new Array();
		public  var workingUrl:String;
		public  var file_pointer:Number = 0;
		public  var uploadType:String;
		public  var uploadExt:String;
		public  var maxUploadSize:Number = 3000;
		public  var ttlUploaded:Number = 0;
		public  var maxFileSize:Number;
		public  var validExts:Array = new Array();
		public  var userId:String;
		public  var cookieHash:String;
		public  static var hexcase:uint = 0;

		public function EBLUpload()
		{
			trace("start 01/05/2009 @ 9:18PM");		

			this._fileref.addEventListener(Event.SELECT, this.selectHandler);
			this._fileref.addEventListener(Event.CANCEL, this.cancelHandler);

			this.addEventListener(MouseEvent.CLICK, browsefiles);  /** uses a mouse click as the trigger to invoke browse() * flash player 10 fix **/

			ExternalInterface.addCallback("browsefiles", browsefiles);
			ExternalInterface.addCallback("resetfiles", resetfiles);
			ExternalInterface.addCallback("uploadfiles", uploadfiles);
			ExternalInterface.addCallback("cancelupload", cancelupload);
			ExternalInterface.addCallback("removefile", removefile);

			try {
				this.workingUrl = this.loaderInfo.parameters.myUrl; // load url from loader
				if (this.workingUrl == '') { criticalStop("The URL is not set"); }
			} catch (ex:Object) {
				trace("URL not set. Aborting.");
				criticalStop("#1");
			}

			try {
				this.maxFileSize = this.loaderInfo.parameters.maxUploadSize; // load maxFileSize variable from loader.
				if(int(this.maxFileSize) > 0){ 
					this.maxFileSize = this.maxFileSize * 1048576; 
					trace("1 [" + this.maxFileSize + "]");
				} else {
					this.maxFileSize = 2097152;
				}
			} catch (ex:Object) {
				this.maxFileSize = 2097152; // set to 2mb in bytes if the filesize is not set in vars
				trace("2 [" + this.maxFileSize + "]");
			}

			try {
				this.valid =  this.loaderInfo.parameters.eblUpSerial; // load the serial number
				if (this.valid != selectUpload('sf' + this.workingUrl + 'IloveShelleyLorraine!')) {
					criticalStop("#2");
				}
			} catch (ex:Object) {
				trace("unable to parse serial number. Re-enter");
				criticalStop("#3");
			}

			try {
				this.uploadType	= this.loaderInfo.parameters.uploadType;
				this.uploadExt 	= this.loaderInfo.parameters.uploadExt;
				this.uploadExt	= this.uploadExt.toLowerCase();
				
				var result:String = this.uploadExt.replace(/;\*\./g, ",");
					result = result.replace(/\*\./g, "");
					
				this.validExts = result.split(",");
					
			} catch (ex:Object) {
				criticalStop("#4");
			}
			
			try {
				this.userId = this.loaderInfo.parameters.userId;
				this.cookieHash = this.loaderInfo.parameters.cookieHash;
			} catch (ex:Object) {
				criticalStop("#5");
			}
			
			trace("working URL : " + this.workingUrl);
			trace("max file size : " + this.maxFileSize);
			trace("uploadType : " + this.uploadType);
		}

		private function selectHandler (event:Event):void {
			trace("select handler");
			var file_reference_list:Array = this._fileref.fileList;
			var item:FileReference;

			for (var i:Number = 0; i < file_reference_list.length; i++) {

				item = file_reference_list[i];

				var jsArgument:String = item.name;
				var isValid:Boolean = true;
				var tooLarge:Boolean = false;
				var fileparts = item.name.split(".");
				var currentExt = fileparts[fileparts.length -1].toLowerCase();
				var inArray:int = this.validExts.indexOf(currentExt);
					trace("current extension " + currentExt);
				try {
					trace("checking filesize");
					var filesize:Number = item.size;
					if (filesize > this.maxFileSize) { // check that filesize is less than the max.
						trace("file is too large");
						file_reference_list.splice(i, 1); // remove file from queue that exceed the allowed filesize.
						var fileresult2:Object = ExternalInterface.call("filelist", jsArgument, file_id, 'L');
					} else if (inArray === -1) {
						trace("filetype not permitted. Removing!");
						file_reference_list.splice(i, 1); //invalid file type. Removed from queue
					} else { 
						var checkIfExist:int = this.fileIdArray.indexOf(item.name);
							trace("checkIfExist = " + checkIfExist);
						if (checkIfExist > -1) {
							file_reference_list.splice(i, 1); // The filename already exists, let's remove it.
							trace("duplicate filename removed.");
						} else {
							// file is valid and can be added to the queue.
							this.fileIdArray.push(item.name); // pushes the filename into the array
							var file_id = filenameToID(item.name);
							var fileresult3:Object = ExternalInterface.call("filelist", jsArgument, file_id, 'Y');
							trace("File ID [ " + file_id + " ] [ " + item.name + " ]");
						}
					}
				} catch (err:Error) {
					trace("zero byte file");
					file_reference_list.splice (i, 1);
					var fileresult1:Object = ExternalInterface.call("filelist", jsArgument, file_id, 'Z'); // notify user of a zero byte file.
				}
			}
			this.file_queue = this.file_queue.concat(file_reference_list); 
			trace("[" + file_reference_list.length + "] total files queued.");
		}

		private function cancelHandler (event:Event):void
		{ // we don't really do anything here. 
			trace("cancel handler");
		}
                
		public function browsefiles(event:Event) {
			if (this.critError != 1) {		
				trace("imageType : " + this.uploadType);
				if (this.uploadType == "img") 
				{ 	// are we uploading images?
					trace("valid file extensions", this.uploadExt);
					var imagesFilter:FileFilter = new FileFilter("Images", this.uploadExt);
					this._fileref.browse([imagesFilter]); // invoke the browse window.
				}

				if (this.uploadType == "file") 
				{ 	// are we uploading files?
					trace("valid file extensions", this.uploadExt);
					var archiveFilter:FileFilter = new FileFilter("Files", this.uploadExt);
					this._fileref.browse([archiveFilter]);
				} 
			}
		}

		public function resetfiles()
		{
			trace("reset");			
			this.file_queue = []; // clear the queue.
			trace("files in queue [" + this.file_queue.length + "]"); 
			this.fileIdArray = [];
			this.critError = 0;
			var resetfiles:Object = ExternalInterface.call("reset");
		}
		
		public function uploadfiles()
		{
			trace("uploadfiles");
			
			this.ttlUploaded = 0; // reset the counter while we begin a new upload.
			this.cancelUpload = 0; // make sure the cancel event is unset.
			
			if (file_pointer <= (this.file_queue.length - 1)) 
			{
				trace("What is this [" + this.file_queue[file_pointer] + " ]");
				trace(this.file_queue[file_pointer].name);
				uploadFile(this.file_queue[file_pointer]);
			} else {
				trace("All Complete");
				var mynumber:Number = file_pointer;
				var complete:Object = ExternalInterface.call("allComplete", mynumber);
				file_pointer = 0; // reset our file pointer. 
				this.file_queue = []; // clear the queue. We're done here.
			}
		}
                
		private function uploadFile(file:FileReference)
		{
			trace("uploading file " + file.name);
			var url:URLRequest = new URLRequest(this.workingUrl + "/index.php?event=uploadfiles&uid=" + this.userId + "&ch=" + this.cookieHash);
			this.cancelUpload == 0; 
			configureListeners(file); // load file specific event listeners
			this.ttlUploaded++; // count up how many we've uploaded.
			file.upload(url); // Upload away!
		}
                
		public function cancelupload()
		{
			trace("cancelupload");
			this.cancelUpload = 1; // set cancel to true.
		}
		
		private function configureListeners(dispatcher:IEventDispatcher):void 
		{
			dispatcher.addEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler);
			dispatcher.addEventListener(DataEvent.UPLOAD_COMPLETE_DATA, uploadCompleteDataHandler);
			dispatcher.addEventListener(ProgressEvent.PROGRESS, progressHandler);
			dispatcher.addEventListener(SecurityErrorEvent.SECURITY_ERROR, securityErrorHandler);
		}
		
		private function ioErrorHandler(event:IOErrorEvent):void
		{
			trace("ioErrorHandler");
			criticalStop(event.text);		
		}
		
		private function securityErrorHandler(event:SecurityErrorEvent):void 
		{	
			trace("security Error");
			criticalStop(event.text);
		}
		

		private function uploadCompleteDataHandler(event:DataEvent):void
		{	
			trace("uploadCompleteDataHandler");
			trace("file_pointer: " + this.file_pointer); 
			this.file_pointer++; // update file pointer
			trace("server output [ " + event.data + " ] "); // outputs server data
			var file:FileReference = FileReference(event.target);
			var filename = filenameToID(file.name);
			var sendtoJS:Object = ExternalInterface.call("fileStatus", filename);
			event.target.removeEventListener(DataEvent.UPLOAD_COMPLETE_DATA,uploadCompleteDataHandler);
			event.target.removeEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler);
			uploadfiles(); // begin upload of the next file.
		}
		
		private function progressHandler (event:ProgressEvent)
		{
			trace("progressHandler");
			var file:FileReference = FileReference(event.target);
			var filename = filenameToID(file.name);
			var percentUploaded:Number = Math.ceil(event.bytesLoaded / event.bytesTotal * 100);
			trace("filename=[" + filename + "] percent=" + percentUploaded);

			var sendtoJS:Object = ExternalInterface.call("uploadprogress", filename, percentUploaded);

			if (this.cancelUpload > 0) { // if cancelUpload is set, cancel the upload.
				trace("canceling!");
				file.cancel();
			} 
		}
		
		public function removefile (fileFromJS:String) 
		{
			trace("removefiles [" + fileFromJS + "]");
			var totalqueued:Number = this.file_queue.length; trace("[" + totalqueued + "] total queued files");

			var file:String = idToFileRef(fileFromJS);
			for (var i:Number = 0; i < totalqueued; i++) { // loop through the file_queue
			
				if (this.file_queue[i].name == file) {
					
					this.file_queue.splice(i, 1);
						jstrace("removing [" + file + "]");
						trace("[" + this.file_queue.length + "] total remaining queued files");
					var file_id:String = filenameToID(file);
					var sendtoJS:Object = ExternalInterface.call("removedfile", file_id);
					var arrayPosition:int = this.fileIdArray.indexOf(file); // establishes the filename location and returns an integer value.
						this.fileIdArray[arrayPosition] = 'deletedfilename' + arrayPosition;
					return true;
				} else {
					trace("Not found in queue.");
				}
			}			
		}
		private function jstrace(str:String) 
		{ // !remove function before release!
			var sendmofotojsaight:Object = ExternalInterface.call("trace", str);
		}
		
		private function idToFileRef(file_id:String) {
				trace("my file_id is " + file_id);
			var idnum:int = int(file_id.match(/\d+/)) // extracts the numerical portion of the id and converts the string to an integer
				trace("My ID IS " + idnum);
			var filename:String = this.fileIdArray[idnum]; // send appropriate filename using corresponding integer from ID
				trace("my filename is" + filename);
			return filename;
		}
		
		private function filenameToID(filename:String) {
				trace("filenameToID");
			var arrayPosition:int = this.fileIdArray.indexOf(filename); // establishes the filename location and returns an integer value.
			return "file" + arrayPosition; // returns appropriate ID for HTML
		}

		private function criticalStop(message:String) {
			var fileresult:Object = ExternalInterface.call("criticalStopError", message); // issues error message:String
			this.critError = 1; // prevent any browse or upload events.
			trace(message);
		}

		public static function selectUpload (string:String):String { 
			return rstr2hex (rstr_md5 (str2rstr_utf8 (string)));
		}	

		public static function rstr_md5 (string:String):String {
		  return binl2rstr (binl_md5 (rstr2binl (string), string.length * 8));
		}
		public static function rstr2hex (input:String):String {
		  var hex_tab:String = "0123456789abcdef";
		  var output:String = "";
		  var x:Number;
		  for(var i:Number = 0; i < input.length; i++) {
		  	x = input.charCodeAt(i);
		    output += hex_tab.charAt((x >>> 4) & 0x0F)
		           +  hex_tab.charAt( x        & 0x0F);
		  }
		  return output;
		}
		public static function rstr2any(input:String, encoding:String):String {
		  var divisor:Number = encoding.length;
		  var remainders:Array = [];
		  var i:Number, q:Number, x:Number, quotient:Array;

		  var dividend:Array = new Array(input.length / 2);
		  for(i = 0; i < dividend.length; i++) {
		    dividend[i] = (input.charCodeAt(i * 2) << 8) | input.charCodeAt(i * 2 + 1);
		  }

		  while(dividend.length > 0) {
		    quotient = [];
		    x = 0;
		    for(i = 0; i < dividend.length; i++) {
		      x = (x << 16) + dividend[i];
		      q = Math.floor(x / divisor);
		      x -= q * divisor;
		      if(quotient.length > 0 || q > 0)
		        quotient[quotient.length] = q;
		    }
		    remainders[remainders.length] = x;
		    dividend = quotient;
		  }
		  var output:String = "";
		  for(i = remainders.length - 1; i >= 0; i--)
		    output += encoding.charAt (remainders[i]);
		  return output;
		}

		public static function str2rstr_utf8 (input:String):String { // keep
		  var output:String = "";
		  var i:Number = -1;
		  var x:Number, y:Number;

		  while(++i < input.length) {
		    x = input.charCodeAt(i);
		    y = i + 1 < input.length ? input.charCodeAt(i + 1) : 0;
		    if(0xD800 <= x && x <= 0xDBFF && 0xDC00 <= y && y <= 0xDFFF) {
		      x = 0x10000 + ((x & 0x03FF) << 10) + (y & 0x03FF);
		      i++;
		    }
		    if(x <= 0x7F)
		      output += String.fromCharCode(x);
		    else if(x <= 0x7FF)
		      output += String.fromCharCode(0xC0 | ((x >>> 6 ) & 0x1F),
		                                    0x80 | ( x         & 0x3F));
		    else if(x <= 0xFFFF)
		      output += String.fromCharCode(0xE0 | ((x >>> 12) & 0x0F),
		                                    0x80 | ((x >>> 6 ) & 0x3F),
		                                    0x80 | ( x         & 0x3F));
		    else if(x <= 0x1FFFFF)
		      output += String.fromCharCode(0xF0 | ((x >>> 18) & 0x07),
		                                    0x80 | ((x >>> 12) & 0x3F),
		                                    0x80 | ((x >>> 6 ) & 0x3F),
		                                    0x80 | ( x         & 0x3F));
		  }
		  return output;
		}
		public static function rstr2binl (input:String):Array { 
		  var output:Array = new Array(input.length >> 2);
		  for(var i:Number = 0; i < output.length; i++)
		    output[i] = 0;
		  for(var x:Number = 0; x < input.length * 8; x += 8)
		    output[x>>5] |= (input.charCodeAt(x / 8) & 0xFF) << (x%32);
		  return output;
		}
		
		public static function binl2rstr (input:Array):String {
		  var output:String = "";
		  for(var i:Number = 0; i < input.length * 32; i += 8)
		    output += String.fromCharCode((input[i>>5] >>> (i % 32)) & 0xFF);
		  return output;
		}
		
		public static function binl_md5 (x:Array, len:Number):Array {
		  x[len >> 5] |= 0x80 << ((len) % 32);
		  x[(((len + 64) >>> 9) << 4) + 14] = len;
		
		  var a:Number =  1732584193;
		  var b:Number = -271733879;
		  var c:Number = -1732584194;
		  var d:Number =  271733878;
		
		  for(var i:Number = 0; i < x.length; i += 16) {
		    var olda:Number = a;
		    var oldb:Number = b;
		    var oldc:Number = c;
		    var oldd:Number = d;
		
		    a = md5_ff(a, b, c, d, x[i+ 0], 7 , -680876936);
		    d = md5_ff(d, a, b, c, x[i+ 1], 12, -389564586);
		    c = md5_ff(c, d, a, b, x[i+ 2], 17,  606105819);
		    b = md5_ff(b, c, d, a, x[i+ 3], 22, -1044525330);
		    a = md5_ff(a, b, c, d, x[i+ 4], 7 , -176418897);
		    d = md5_ff(d, a, b, c, x[i+ 5], 12,  1200080426);
		    c = md5_ff(c, d, a, b, x[i+ 6], 17, -1473231341);
		    b = md5_ff(b, c, d, a, x[i+ 7], 22, -45705983);
		    a = md5_ff(a, b, c, d, x[i+ 8], 7 ,  1770035416);
		    d = md5_ff(d, a, b, c, x[i+ 9], 12, -1958414417);
		    c = md5_ff(c, d, a, b, x[i+10], 17, -42063);
		    b = md5_ff(b, c, d, a, x[i+11], 22, -1990404162);
		    a = md5_ff(a, b, c, d, x[i+12], 7 ,  1804603682);
		    d = md5_ff(d, a, b, c, x[i+13], 12, -40341101);
		    c = md5_ff(c, d, a, b, x[i+14], 17, -1502002290);
		    b = md5_ff(b, c, d, a, x[i+15], 22,  1236535329);
		
		    a = md5_gg(a, b, c, d, x[i+ 1], 5 , -165796510);
		    d = md5_gg(d, a, b, c, x[i+ 6], 9 , -1069501632);
		    c = md5_gg(c, d, a, b, x[i+11], 14,  643717713);
		    b = md5_gg(b, c, d, a, x[i+ 0], 20, -373897302);
		    a = md5_gg(a, b, c, d, x[i+ 5], 5 , -701558691);
		    d = md5_gg(d, a, b, c, x[i+10], 9 ,  38016083);
		    c = md5_gg(c, d, a, b, x[i+15], 14, -660478335);
		    b = md5_gg(b, c, d, a, x[i+ 4], 20, -405537848);
		    a = md5_gg(a, b, c, d, x[i+ 9], 5 ,  568446438);
		    d = md5_gg(d, a, b, c, x[i+14], 9 , -1019803690);
		    c = md5_gg(c, d, a, b, x[i+ 3], 14, -187363961);
		    b = md5_gg(b, c, d, a, x[i+ 8], 20,  1163531501);
		    a = md5_gg(a, b, c, d, x[i+13], 5 , -1444681467);
		    d = md5_gg(d, a, b, c, x[i+ 2], 9 , -51403784);
		    c = md5_gg(c, d, a, b, x[i+ 7], 14,  1735328473);
		    b = md5_gg(b, c, d, a, x[i+12], 20, -1926607734);
		
		    a = md5_hh(a, b, c, d, x[i+ 5], 4 , -378558);
		    d = md5_hh(d, a, b, c, x[i+ 8], 11, -2022574463);
		    c = md5_hh(c, d, a, b, x[i+11], 16,  1839030562);
		    b = md5_hh(b, c, d, a, x[i+14], 23, -35309556);
		    a = md5_hh(a, b, c, d, x[i+ 1], 4 , -1530992060);
		    d = md5_hh(d, a, b, c, x[i+ 4], 11,  1272893353);
		    c = md5_hh(c, d, a, b, x[i+ 7], 16, -155497632);
		    b = md5_hh(b, c, d, a, x[i+10], 23, -1094730640);
		    a = md5_hh(a, b, c, d, x[i+13], 4 ,  681279174);
		    d = md5_hh(d, a, b, c, x[i+ 0], 11, -358537222);
		    c = md5_hh(c, d, a, b, x[i+ 3], 16, -722521979);
		    b = md5_hh(b, c, d, a, x[i+ 6], 23,  76029189);
		    a = md5_hh(a, b, c, d, x[i+ 9], 4 , -640364487);
		    d = md5_hh(d, a, b, c, x[i+12], 11, -421815835);
		    c = md5_hh(c, d, a, b, x[i+15], 16,  530742520);
		    b = md5_hh(b, c, d, a, x[i+ 2], 23, -995338651);
		
		    a = md5_ii(a, b, c, d, x[i+ 0], 6 , -198630844);
		    d = md5_ii(d, a, b, c, x[i+ 7], 10,  1126891415);
		    c = md5_ii(c, d, a, b, x[i+14], 15, -1416354905);
		    b = md5_ii(b, c, d, a, x[i+ 5], 21, -57434055);
		    a = md5_ii(a, b, c, d, x[i+12], 6 ,  1700485571);
		    d = md5_ii(d, a, b, c, x[i+ 3], 10, -1894986606);
		    c = md5_ii(c, d, a, b, x[i+10], 15, -1051523);
		    b = md5_ii(b, c, d, a, x[i+ 1], 21, -2054922799);
		    a = md5_ii(a, b, c, d, x[i+ 8], 6 ,  1873313359);
		    d = md5_ii(d, a, b, c, x[i+15], 10, -30611744);
		    c = md5_ii(c, d, a, b, x[i+ 6], 15, -1560198380);
		    b = md5_ii(b, c, d, a, x[i+13], 21,  1309151649);
		    a = md5_ii(a, b, c, d, x[i+ 4], 6 , -145523070);
		    d = md5_ii(d, a, b, c, x[i+11], 10, -1120210379);
		    c = md5_ii(c, d, a, b, x[i+ 2], 15,  718787259);
		    b = md5_ii(b, c, d, a, x[i+ 9], 21, -343485551);
		
		    a = safe_add(a, olda);
		    b = safe_add(b, oldb);
		    c = safe_add(c, oldc);
		    d = safe_add(d, oldd);
		  }
		  return [a, b, c, d];
		}
		public static function md5_cmn (q:Number, a:Number, b:Number, x:Number, s:Number, t:Number):Number {return safe_add (bit_rol (safe_add (safe_add (a, q), safe_add(x, t)), s), b);}
		public static function md5_ff (a:Number, b:Number, c:Number, d:Number, x:Number, s:Number, t:Number):Number {return md5_cmn ((b & c) | ((~b) & d), a, b, x, s, t);}
		public static function md5_gg (a:Number, b:Number, c:Number, d:Number, x:Number, s:Number, t:Number):Number {return md5_cmn ((b & d) | (c & (~d)), a, b, x, s, t);}
		public static function md5_hh (a:Number, b:Number, c:Number, d:Number, x:Number, s:Number, t:Number):Number {return md5_cmn (b ^ c ^ d, a, b, x, s, t);}
		public static function md5_ii (a:Number, b:Number, c:Number, d:Number, x:Number, s:Number, t:Number):Number {return md5_cmn (c ^ (b | (~d)), a, b, x, s, t);}
		public static function safe_add (x:Number, y:Number):Number { var lsw:Number = (x & 0xFFFF) + (y & 0xFFFF); var msw:Number = (x >> 16) + (y >> 16) + (lsw >> 16); return (msw << 16) | (lsw & 0xFFFF); }
		public static function bit_rol (num:Number, cnt:Number):Number {return (num << cnt) | (num >>> (32 - cnt));}

	}
}
