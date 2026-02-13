<?php

// protect current and active pages
	define("OLDCURRENTPAGE",$_SESSION['currentpage']);
	$_SESSION['currentpage']=$_SESSION['activepage']; // this is reset back at the end
	if(isset($_GET['action']) and $_GET['action']=="threadpop"){
		$oldmessage=$_SESSION['currentpage']['meta']['olddata'][$_GET['control']];
		if(isset($oldmessage) and $oldmessage!=""){
			$arr=json_decode($oldmessage,true);
				if(isset($arr)){
				$str="";
				foreach($arr as $k=>$v){
					date_default_timezone_set("Asia/Kolkata");
					$localTime = date("d-m-Y H-i-s", $v['time']);
					$str.="<div class=' p-1 b-1 border-bottom border-secondary'>";
					
					if(isset($v['userid']))$str.="<i class='bi bi-person-fill'></i> ".$v['userid'];
					if(isset($v['username']))$str.=$v['username']."<br>";
					if(isset($v['message']))$str.="<i class='bi bi-chat-left-text'></i> ".nl2br($v['message'])."<br>";
					$str.="<small>";
					if(isset($v['time']))$str.="@ ".$localTime;
					if(isset($v['userip']))$str.=";  Ip: ".$v['userip'];
					$str.="</small>";
					$atr=safeStr($str);
					$str.="</div>";
				}
				echo
				"<div class='border border-1 border-primary shadow alert alert-primary p-1 ' 
				style='width:400px;height:300px;overflow:auto'>".$str;
				"</div>";
			}
		}
		exit;
	}
	
	if(isset($_GET['action']) && $_GET['action']=='delete'){
		$pgds=getValueForPS("selrec * from _pb_pagehead where pgid=?","s",$_GET['pgid']);
		$table=$pgds['tablename'];
		$idx=$_GET['id'];
		$reply=checkDeleteChildren($table,$idx);
		if(function_exists('plx_preDelete')){
			plx_preDelete($table,$idx);	
		} 
		if($reply==""){	// NO CHILD RECORD FOUND SO CAN BE deleted
			$sql="delrec from ".$table." where id='".$idx."'";
			pw_execute($sql);
			$_SESSION['currentpage'] = OLDCURRENTPAGE; //returning back to the preserved current page
			echo '
			...
			<script>
				// display the error div and close after n seconds
				el = document.getElementById("editerrordiv");
				document.getElementById("editerrordiv").style.display="";
				el.innerHTML="'.$sql.'";
				setTimeout(function() {
					var el = document.getElementById("editerrordiv");
					if (el) {
						el.style.display = "none";
					}
				}, 4000); // 4000 ms = 4 seconds
				
				o=document.getElementById("editPanel");
				o.innerHTML="<div class=\"p-2 border border-1 border-secondary m-2 alert alert-success\"><i class=\"bi bi-check-circle\"></i> Record deleted OK4322 '.date("H:i:s").'</div>"
			</script>';
			exit;
		}
		if($reply!=""){
		echo '
			...
			<script>
				// display the error div and close after n seconds
				el = document.getElementById("editerrordiv");
				document.getElementById("editerrordiv").style.display="";
				el.innerHTML="'.$sql.'";
				setTimeout(function() {
					var el = document.getElementById("editerrordiv");
					if (el) {
						el.style.display = "none";
					}
				}, 4000); // 4000 ms = 4 seconds
				
				o=document.getElementById("editPanel");
				o.innerHTML="<div class=\"p-2 border border-1 border-secondary m-2 alert alert-danger\"><i class=\"bi bi-exclamation-triangle\"></i> '.$reply." .. ".date("H:i:s").'</div>"
			</script>';
		}
		exit;
	}	// end of delete
	
	// enc dec threat detection
	$threat="";
	foreach ($_POST as $key => $value) {
		$conttype=substr($key,0,4);
		$contname=str_replace($conttype,"",$key);
		echo "<div><div>";  // do not delte this line look crazy
		
		if($conttype=="spi_" or $conttype=="ron_"){
			$encfield="enc_".$contname;
			$decValue=pw_dec($_POST[$encfield]);
			if($decValue!=$value){
				$threat.=$key."-(".$decValue." vs ".$value.")\\n";
			}
		}
	    if (strpos($key, 'enc_') === 0) {	// remove all enc_controls from post
	        unset($_POST[$key]);
	    }
	}
	
	if($threat!=""){
		echo "
		<script>
			alert('Erx 43344: ".$threat."');
		</script>
		";
	}				
	// enc threat detection -- end
	
	// displaying thread control in popup
	foreach($_POST as $k=>$v){
		if(!isset($k))unset($_POST[$k]);
		if($k!="xref" && $k!="form_xref"){
			$controlprefix=substr($k,0,4);
			$kprefix=substr($k,4,100);	// removing txt_.txa_cmb_ etc
			unset($_POST[$k]);
			if($controlprefix=="thc_"){ // thread control, het old data and concat
				
				$thcFieldName=$_SESSION['sufixtofield'][$kprefix];
				$prevdata=$_SESSION['currentpage']['meta']['olddata'][$thcFieldName]??'';
				$thread=array();
				$time=time(); // linux time
				$threadArray=array();
				if($prevdata!=""){
					$threadArray=json_decode($prevdata,true);
				}
				if($v!=''){
					$newthread=array();
					$newthread['time']=$time;
					$newthread['message']=$v;
					if(isset($_SESSION['user_userid'])) $newthread['userid']=$_SESSION['user_userid'];
					if(isset($_SESSION['user_entityname']))$newthread['username']=$_SESSION['user_entityname'];
					$newthread['userip']=$_SERVER['REMOTE_ADDR'];
					$new[$time]=$newthread;
					if (!is_array($threadArray)) {
	        			$threadArray = [];
	    			}
	    			
					$threadArray = array_merge($new, $threadArray);
					$v=json_encode($threadArray);
				}
				else{
					$v=	$prevdata;
				}
				
			}
			$_POST[$_SESSION['sufixtofield'][$kprefix]]=$v;

			if(isFoundin($k,"cmb_")){
				$temp = explode("::",$v);
				$_POST[$_SESSION['sufixtofield'][$kprefix]]=$temp[0];
			}
			if(isFoundin($k,"mul_")){
				$v=implode("~",$v);
				$_POST[$_SESSION['sufixtofield'][$kprefix]]=$v;
			}
		}
	} 
	
	// post error handling
	$postErrors="";$jserrors="";
	//printr($_POST);
	
	if(isset($_SESSION['currentpage']['head']['validations'])){
		//Added by Siva on 04-02-2026 for form Level Validations
		$postErrors.=formvalidations($_SESSION['currentpage']);
	}
	foreach($_POST as $k=>$v){
		if($k!="xref" and $k!='form_xref'){
			$postErrors.=fieldjsvalidations($k,$v,$_SESSION['currentpage']);
			$err="";
			$err.=validatePost($k,$v,$_SESSION['currentpage']);
			if($err!="")$postErrors.=$err;
		}
	}
	if(strlen($postErrors)>10){
		/*$postErrors='<div class="alert alert-warning d-flex align-items-start gap-2 shadow-sm p-3 rounded-3 w-100 m-0" role="alert"
			                  onclick="this.remove()" style="cursor:pointer"><b>Form errors Observed EX5400</b><br>".$postErrors."<br><br><i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i> Action suspended !! <span class="border border-1 p-1 border-secondary rounded">Click me to close</span></div>';*/
		$postErrorsHtml = '
		<div class="alert alert-warning d-flex align-items-start gap-2 shadow-sm p-3 rounded-3 w-100 m-0"
		     role="alert"
		     onclick="this.remove()"
		     style="cursor:pointer">
		    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning me-2"></i>
		    <div>
		        <b>Form errors observed (EX5400)</b><br>
		        '.$postErrors.'
		        <div class="mt-2">
		            Action suspended !!
		            <span class="border border-1 p-1 border-secondary rounded">
		                Click me to close
		            </span>
		        </div>
		    </div>
		</div>';	                  
		echo '
		<script>
			// display the error div and close after n seconds
			(function () {
				var html = ' . json_encode($postErrorsHtml) . ';
				var el = document.getElementById("editerrordiv");
				if (el) {
					el.style.display = "block";
					el.innerHTML = html;
				}
				var listel = document.getElementById("editerrordiv_le");
				if (listel) {
					listel.style.display = "block";
					listel.innerHTML = html;
				}
				setTimeout(function () {
					if (el) el.style.display = "none";
					if (listel) listel.style.display = "none";
				}, 4000);
			})(); // 4000 ms = 4 seconds
			
			function CheckFile(flname,optionx,sampfsize,val,id,typ="") {
				alert(flname);
				// file size checking
				//alert(val.substring(-10));
				if(optionx!=""&& typ=="")	CheckFileOptions(flname,optionx);
				if(optionx!=""&& typ!="")	CheckFileOptions1(flname,optionx,id);
				if(sampfsize!="")CheckFilesize(flname,sampfsize);
				var isValidFile = CheckExtension(flname);
				if (isValidFile==1){
					fileUpload  = document.getElementById(flname);
					var ext     = fileUpload.files[0].name;
					if (typeof (fileUpload.files) != "undefined") {
						var size_mb = parseFloat(fileUpload.files[0].size / 1024/1024).toFixed(2);
						var size_bytes = parseFloat(fileUpload.files[0].size).toFixed(2);
						if(size_bytes>".IMAGE_UPLOAD_LIMIT."){
							l = ".IMAGE_UPLOAD_LIMIT."/1000000;
							alert("Upload limit "+l+"MB exceeded.. Current file size "+size_mb+" MB... Please resize the image");
							fileUpload.value="";
						}
					} else {
						alert("This browser does not support HTML5.");
					}
				}
				if (isValidFile==0){
					fileUpload  = document.getElementById(flname);
					var ext     = fileUpload.files[0].name;
					if (typeof (fileUpload.files) != "undefined") {
						var size_mb = parseFloat(fileUpload.files[0].size / 1024/1024).toFixed(2);
						var size_bytes = parseFloat(fileUpload.files[0].size).toFixed(2);
						if(size_bytes>".DOCUMENT_UPLOAD_LIMIT."){
							l = ".DOCUMENT_UPLOAD_LIMIT."/1000000;
							alert("Upload limit " +l+ "MB exceeded.. Current file size "+size_mb+" MB... Please reduce the attachment size");
							fileUpload.value="";
						}
					} else {
						alert("This browser does not support HTML5.");
					}
				}
				document.getElementById("div_"+id).innerHTML = ": "+val.slice(-25);
	
			}
			function CheckFilesize(flname,sampfsize){
				fileUpload     = document.getElementById(flname);
				var size_bytes = parseFloat(fileUpload.files[0].size / 1024).toFixed(2);
				if(size_bytes>parseFloat(sampfsize)){
					alert("Upload limit " +sampfsize+ " KB exceeded..Current file size "+size_bytes+" KB..Please resize the attachment.");
					fileUpload.value="";
					return false;
				}
				else return true;
			}
			function CheckExtension(flname) {
				// checking wether the uploaded file is image or not 
				fileUpload = document.getElementById(flname);
				var filePath = fileUpload.value;
				var ext = filePath.substring(filePath.lastIndexOf(".") + 1).toLowerCase();
				var isValidFile = 0;
				if (ext == "bmp" || ext == "gif" || ext == "png" || ext == "jpg" || ext == "jpeg") {
					isValidFile = 1;
				}	
	           return isValidFile;
			}
			function CheckFileOptions(flname,optionx){
				//uploaded file checking based on defined options
				if(optionx!=""){
					fileUpload = document.getElementById(flname);
					var filePath = fileUpload.value;
					var ext = filePath.substring(filePath.lastIndexOf(".") + 1).toUpperCase();
					if(optionx!="Only Images"){
						if(optionx.indexOf(ext)>=0){
							return true;
						}	
						else {
							alert("Please upload only "+optionx+" Files");
							fileUpload.value="";
							return false;
						}
					}	
					if(optionx=="Only Images"){
						if (ext != "BMP" && ext != "GIF" && ext != "PNG" && ext != "JPEG" && ext != "JPG") {
							alert("Please upload only Image Files");
							fileUpload.value="";
						}	
					}
				}	
			}
			function CheckFileOptions1(flname,optionx,id){
				//uploaded file checking based on defined options
				if(optionx!=""){
					fileUpload = document.getElementById(flname);
					var filePath = fileUpload.value;
					var ext = filePath.substring(filePath.lastIndexOf(".") + 1).toUpperCase();
					optionx = optionx.replace("While Insert", "");
					optionx = optionx.replace("While Update", "");
					if(optionx.indexOf(ext)=="-1"){
						alert("Please upload only "+optionx+" Files");
							fileUpload.value="";
							return false;
					}
				}	
			}
		</script>';
		exit;  // if error fpund stop saving
	}
	// update posted data into table
	if(isset($_GET['action']) && $_GET['action']=='update'){
		$table=$_SESSION['currentpage']['head']['tablename'];
		$fieldexists=getValueForPS("SHOCOL FROM ".$table." LIKE 'signature'");
	//	printr($fieldexists);
		$updatedata = array();
		$_POST['id'] = $_GET['id'];
		// thread control management
		$updatedata = array_map('trim',$_POST);
		unset($updatedata['form_xref']);
		unset($updatedata['xref']);
		//if (!empty($_FILES)) {
/*		printr($_FILES);
		exit;*/
		if (!empty($_FILES) && $_FILES['userfile']['error'] == UPLOAD_ERR_OK) {
			// Call your existing upload function
			$msg = uload($_FILES, "userfile", "", "", UPLOAD_DIR_PATH);
			if (!empty($_SESSION['enc_user_uploadedfile'])) {
				echo "<div class='alert alert-success'>File uploaded successfully: " .
				htmlspecialchars($_SESSION['user_uploadedfile']) . "</div>";
				//echo "<script>setTimeout(() => closeOverlay(), 1500);</script>";
				// Only set filename fields when a new file is uploaded
				$updatedata['filename'] = $_SESSION['user_uploadedfile'];
				$updatedata['encfilename'] = $_SESSION['enc_user_uploadedfile'];				
			} else {
				echo "<div class='alert alert-danger'>Upload failed</div>";
			}
			// $msg may already contain JS alerts from uload()
			if ($msg) echo $msg;
		}else {
			//  No new upload — preserve old file info
			$updatedata['filename'] = $_SESSION['currentpage']['meta']['olddata']['filename'] ?? '';
			$updatedata['encfilename'] = $_SESSION['currentpage']['meta']['olddata']['encfilename'] ?? '';
		}
		//$updatedata['filename'] = $_SESSION['user_uploadedfile'] ?? '';
		//$updatedata['encfilename'] = $_SESSION['enc_user_uploadedfile'] ?? '';
		if(function_exists('plx_preUpdate')) {
			plx_preUpdate($_SESSION['currentpage']['head']['tablename'],$_SESSION['currentpage']['meta']['olddata']['id']);
			
		}
		// handling linkedto and ids
		// for these we send encrypted data to client in read only so we specially decrypt hete
		if(isset($updatedata['linkedto'])) $updatedata['linkedto']=pw_dec($updatedata['linkedto']);
		if(isset($updatedata['linkedto2'])) $updatedata['linkedto2']=pw_dec($updatedata['linkedto2']);
		if(isset($updatedata['linkedid'])) $updatedata['linkedid']=pw_dec($updatedata['linkedid']);
		if(isset($updatedata['linkedid2'])) $updatedata['linkedid2']=pw_dec($updatedata['linkedid2']);
		
		if ($fieldexists) {
			if (!empty($_SESSION['signature'])) {
				$updatedata['signature'] = $_SESSION['signature'];
			
			} else {
				$updatedata['signature'] = $_SESSION['currentpage']['meta']['olddata']['signature'] ?? '';
			
			}
		}
		
	//	$updatedata['signature'] = $_SESSION['signature'];
	//	printr($updatedata);exit;
		updateRecord($updatedata,$_SESSION['currentpage']['head']['tablename']);
		$id = $_GET['id'];
		uploadFormAttachments($_SESSION['activepage']['head']['pgid'],$id,$_SESSION['currentpage']['head']['tablename']);
		$_SESSION['currentpage'] = OLDCURRENTPAGE; //returning back to the preserved current page
		/*if(function_exists('plx_postUpdate')) {
			plx_postUpdate($_SESSION['currentpage']['head']['tablename'],$_SESSION['currentpage']['meta']['olddata']['id'],$_SESSION['currentpage']['meta']['olddata']);
		}*/
		$dsPage=$_SESSION['activepage']['head'];
		$triggers_count = getValueForPS("selrec count(id) from _pb_child where recordtype='Triggers' and status='Active' and txt2 like '%While Update%' and txt6=?","s",$_SESSION['activepage']['head']['pgid']);
		if($triggers_count>0){
			include_once("bq_mess_triggers.php");
			$dsData = getValueForPS("selrec * from ".$_SESSION['currentpage']['head']['tablename']." where id=?","s",$_GET['id']);
			if(isset($_SESSION['activepage']['meta']['olddata'])){
				$ActiveSession=$_SESSION['activepage']['meta']['olddata'];
			}else{
				$ActiveSession=$dsData;
			}
			executeTriggers($dsPage,$_GET['id'],$dsData,$ActiveSession);
		}
		if(file_exists("segment/".$_SESSION['activepage']['head']['pgid'].".php")){
			include_once("segment/".$_SESSION['activepage']['head']['pgid'].".php");
			if(function_exists('plx_postUpdate')) {
			//	printr($_SESSION['activepage']['meta']['olddata']);
				plx_postUpdate($_SESSION['activepage']['head']['tablename'],$_GET['id'],$_SESSION['activepage']['meta']['olddata']);
			}
			if(function_exists('plx_postInsertUpdate')){
				plx_postInsertUpdate($_SESSION['activepage']['head']['tablename'],$_GET['id']);
			}			
		}
		
		/*if(function_exists('plx_postInsertUpdate')) {
			plx_postInsertUpdate("plx_postInsertUpdate 4444","ID");
		}*/
		$_SESSION['enc_user_uploadedfile'] = "";
		$_SESSION['user_uploadedfile'] = "";
		if($fieldexists)$_SESSION['signature']='';
		toastfade('Record updated',"editPanel",0,0);
		echo "<script>
				o=document.getElementById('editPanel');
				o.innerHTML=''
			</script>";
		SequencerInsertion($_GET['id'],$_SESSION['activepage']['head']['tablename']);
		echo '<script>
				htmx.trigger("#mainContent", "load");
		</script>';
		exit();
	}
	if(isset($_GET['action']) && $_GET['action']=='insert'){
		$table=$_SESSION['activepage']['head']['tablename'];
		$insertdata = array();
		//printr($_POST);
		$insertdata = array_map('trim',$_POST);
		//printr($insertdata);exit;
		unset($insertdata['xref']);
		unset($insertdata['form_xref']);
		if (!empty($_FILES)) { 
			// Call your existing upload function
			$msg = uload($_FILES, "userfile", "", "", UPLOAD_DIR_PATH);
			if (!empty($_SESSION['enc_user_uploadedfile'])) {
				echo "<div class='alert alert-success'>File uploaded successfully: " .
				htmlspecialchars($_SESSION['user_uploadedfile']) . "</div>";
				//echo "<script>setTimeout(() => closeOverlay(), 1500);</script>";
				} else {
				echo "<div class='alert alert-danger'>Upload failed</div>";
			}
			// $msg may already contain JS alerts from uload()
			if ($msg) echo $msg;
		}
		foreach($insertdata as $key => $value){
			if(isFoundin($key,"cmb_")){
				$temp = explode("::",$value);
				$insertdata[str_replace("cmb_","",$key)] = $temp[0];
				unset($insertdata[$key]);
			}
		}
		if(file_exists("segment/".$_SESSION['activepage']['head']['pgid'].".php")){
			include_once("segment/".$_SESSION['activepage']['head']['pgid'].".php");
			if(function_exists('plx_preInsert')) {
				plx_preInsert($_SESSION['activepage']['head']);
			}
		}
		$insertdata['filename'] = $_SESSION['user_uploadedfile'] ?? '';
		$insertdata['encfilename'] = $_SESSION['enc_user_uploadedfile'] ?? '';	
		// handling linkedto and ids
		// for these we send encrypted data to client in read only so we specially decrypt hete
		if(isset($insertdata['linkedto'])) $insertdata['linkedto']=pw_dec($insertdata['linkedto']);
		if(isset($insertdata['linkedto2'])) $insertdata['linkedto2']=pw_dec($insertdata['linkedto2']);
		if(isset($insertdata['linkedid'])) $insertdata['linkedid']=pw_dec($insertdata['linkedid']);
		if(isset($insertdata['linkedid2'])) $insertdata['linkedid2']=pw_dec($insertdata['linkedid2']);
		$fieldexists=getValueForPS("SHOCOL FROM ".$table." LIKE 'signature'");
		if($fieldexists) $insertdata['signature'] = $_SESSION['signature'];
		//echo toast($_SESSION['currentpage']['head']['tablename']);
		//insertRecord($insertdata,$_SESSION['currentpage']['head']['tablename']);
		insertID($insertdata,$_SESSION['currentpage']['head']['tablename'],$id);
		if($fieldexists)$_SESSION['signature']='';
		$id = $_SESSION['lastinsertedid'];
		updatefieldSequencer($_SESSION['activepage']['head']['pgid']);
		uploadFormAttachments($_SESSION['activepage']['head']['pgid'],$id,$_SESSION['currentpage']['head']['tablename']);
		$_SESSION['currentpage'] = OLDCURRENTPAGE; //returning back to the preserved current page
		if(file_exists("segment/".$_SESSION['activepage']['head']['pgid'].".php")){
			include_once("segment/".$_SESSION['activepage']['head']['pgid'].".php");
			if(function_exists('plx_postInsert')) {
			//	printr($_SESSION['activepage']['meta']['olddata']);
				$_GET['id'] = $_GET['id'] ?? '';
				$_SESSION['currentpage']['meta']['olddata'] = $_SESSION['currentpage']['meta']['olddata'] ?? '';
				plx_postUpdate($_SESSION['currentpage']['head']['tablename'],$_GET['id'],$_SESSION['currentpage']['meta']['olddata']);
			}
			if(function_exists('plx_postInsertUpdate')) {
				plx_postInsertUpdate($_SESSION['currentpage']['head']['tablename'],$_SESSION['lastinsertedid'],$id);
			}		
		}
		$dsPage=$_SESSION['currentpage']['head'];
		$triggers_count = getValueForPS("selrec count(id) from _pb_child where recordtype='Triggers' and status='Active' and txt2 like '%While Insert%' and txt6=?","s",$dsPage['pgid']);
		if($triggers_count>0){
			include_once("bq_mess_triggers.php");
			$dsData = getValueForPS("selrec * from ".$_SESSION['currentpage']['head']['tablename']." where id=?","s",$id);
			executeTriggers($dsPage,$id,$dsData);
		}
		//echo "Data Inserted";
		echo "
		<script>
			o=document.getElementById('editPanel');
			o.innerHTML='<div class=\"p-2 border border-1 border-secondary m-2 alert alert-success\"><i class=\"bi bi-check-circle\"></i> Record Inserted OK-2922 ".date("H:i:s")."</div>'
		</script>
		
		";		
		exit();
	}
	
	function fieldjsvalidationsold($k,$v){
		// $vsafe=$v;
		$fieldInfo = $_SESSION['currentpage']['fields'][$k] ?? [];
		$validations = $fieldInfo['validations'] ?? '';
		$caption = $fieldInfo['caption'] ?? $k;
		$message=""; 
		if (trim($validations) == '') return "";
		$vError = "";
		$lines = explode(PHP_EOL, trim($validations));
		
		//if($validations!=""){
			$lines = explode(PHP_EOL, $validations);
			foreach($lines as $k1=>$v1){
				$evalArr=explode("::",$v1);
				$eval=$evalArr[0];
				$message=$evalArr[1];
				foreach($_POST as $kk=>$vv){
					$eval=str_replace("[".$kk."]",$vv,$eval);
				}
				try{
					$result = eval("return ($eval);");
				}catch (Throwable $e) {
					return "<li><i class='bi bi-exclamation-triangle-fill'></i> Error in validation definition or null value submitted</li>"; 
				}
				if($result){
						$caption=$_SESSION['currentpage']['fields'][$k]['caption'].":".$v;
					 return "<li><i class='bi bi-exclamation-triangle-fill'></i> Scr Err:". trim($caption)."<br>".trim($message)."</li>"; 
				}	
			}
		//}
		return "";
	}
	



	function getFormAttachments($tablename,$id){
		// Multi Attachments Display
		// $_REQDATA : Request data
		$ds =getValueForPS("selrec * from ".$tablename." where id=?","s",$id);
		
		//list($pg,$id,$hpg,$hid,$rty) = explode("::",pw_dec($_SESSION['editPage']));
		$str ="";
		$pageFormId = $_SESSION['currentpage']['head']['id'] ; 
		$dsPage = getValueForPS("selrec * from _pb_pagehead where id=?","s",$pageFormId);
		if(!isFoundIn($dsPage['tags'],"Has Separate Multi Attachments") or $_REQDATA['typ']=='multiformuploads'){ /// Added on 01-05-2023 for showing form attachemnts and Has attachemnts in a single page
		echo openForm();
			$str .="<table>";
			$att_sql = "selrec * from _pb_attachdefinition where pgid=? order by slno";
			$att_res = PW_sql2rsPS($att_sql,"s",$dsPage['pgid']);
			//$str .= "<tr><td>".displayAttachmentVersions($dsPage['tablename'],$ds['id'])."</td></tr>";
			while($att_ds=PW_fetchArray($att_res)){
				$recds = getValueForPS("selrec * from _pb_attachments where subject=? and linkedto=? and linkedid=?","sss",$att_ds['code'],$tablename,$id); //  To display existing attachment
				$req = $mand = "";
				if($att_ds['attach_preferences']!=''){
					$att_tags = strtoupper($att_ds['attach_preferences']);
					//if($rty=='addrec' && $att_tags=='IREQUIRED' )$req = "tags = 'isrequired'";
					//if($rty=='edtrec' && $att_tags=='EREQUIRED' && $recds['id']=='')$req = "tags = 'isrequired'";
					if($att_tags=='AREQUIRED' && $recds['id']=='')$req = "tags = 'isrequired'";
					$mand = img('mandatory.png','Mandatory',"  class='mandatory2' ");
				}
				
				//  Added filesize to restrict upload limit to userdefined value..(31-03-2018)
				$fSize = "";
				if($att_ds['allowedfilesize']!='')$fSize = $att_ds['allowedfilesize'];
	
				$att_inputtag  = uploadbutton($fSize,$req,$att_ds); // Multi attach
				//printr($att_inputtag);//exit;
				/*if($_REQDATA['rtype']=='website_form'){
					$att_ds['name']="";
					$att_inputtag = "";
				}*/
				if(isFoundIn($dsPage['tags'],"No Modify Multi Attachments") && $rty=='edtrec' && $recds['encfilename']!='') $att_inputtag = ""; // Added on (18-07-2018)
				$att_box = ""; 
				$att_ds['optionbox'] = $att_ds['optionbox'] ?? '';
				if($att_ds['optionbox']=='Yes'){
					$recds['optionbox'] = $recds['optionbox'] ?? '';
					$att_box  = "<br>".fw_input("box".$att_ds['id'],"type='text' class='txt' placeholder='Enter ".$att_ds['name']." No'",$recds['optionbox'])."<br>";
					$_SESSION['clientEditControls'].="[[box".$att_ds['id']."]]";  //  Keep all edit controls om session 10apr 17 sastry
				}	
				$att_combo = "";
				$att_ds['optioncombo'] = $att_ds['optioncombo'] ?? '';
				if($att_ds['optioncombo']!=''){
					$recds['optioncombo'] = $recds['optioncombo'] ?? '';
					$att_combo = "<br>".getFastCombo("cmb".$att_ds['id'],$att_ds['optioncombo'],$att_ds['optioncombo'],$recds['optioncombo'])."<br>";
					$_SESSION['clientEditControls'].="[[cmb".$att_ds['id']."]]";  //  Keep all edit controls om session 10apr 17 sastry	
				}	
				$str .= "<tr><td class='table-sub-content'><b>".$mand.$att_ds['name']."</b><span class='small-label'>".nl2br($att_ds['explanation']).$att_combo.$att_box."</span> <br>";
				$downloadLink = "";
				//  Image display
				$recds['encfilename'] = $recds['encfilename'] ?? '';
				if($recds['encfilename']!=''){
					$ext  = strtoupper(pathinfo(basename($recds['encfilename']), PATHINFO_EXTENSION));
					
					$style='margin-top:5px;';
					//if(isFoundIn($dsPage['tags'],"Annotation")) $annot_link = anc("pw=Fw_utils.php&rty=annotation&hid=".$recds['id']."&hpg=".$dsPage['tablename'],"Annotation","popf"," class='btn btn-primary btn-xs' style='margin-top:5px;'");
					if(isFoundIn($dsPage['tags'],"Has Attachment Download")) $downloadLink = anc("pw=Fw_download.php&rq2=_pb_attachments&rq3=".$recds['id']."&rq4=attachment","Download","popf","class='btn btn-info btn-xs' style='margin-top:5px;'");
					// external drive 
					if($ext=="" and $_REQDATA['rtype']!='website_form' and isFoundIn($recds['encfilename'],":::")){
						$downloadLink = "&nbsp;".anc("pw=Mod_utl_gdrive.php&head=nocssjs&rty=download&rq1=".$recds['encfilename'],"Download","editf"," class='btn btn-info btn-xs' style='margin-top:5px;'")."&nbsp;";
						// $annot_link = "";
						$style = "";
					}
					if($ext=='JPG' or $ext=='PNG' or $ext=='GIF' or $ext=='JPEG' or $ext=='BMP'){
						$str .= "".anc("pw=Fw_imageview.php&rty=getimage&rq1=".$recds['encfilename']."&rq2=_pb_attachments&rq3=".$recds['id']."&rq4=popup&rq5=".$recds['subject'],"<img src='do?".pw_enc("pw=Fw_imageview.php&rq1=".$recds['encfilename'])."' style='width:100px;' title='Click to View'>","popf")."<br>&nbsp;".$att_inputtag."&nbsp;";
						if($downloadLink==''){
							$str .= anc("pw=Fw_imageview.php&rty=getimage&rq1=".$recds['encfilename']."&rq2=_pb_attachments&rq3=".$recds['id']."&rq4=popup&rq5=".$recds['subject'],"View","popf"," class='btn btn-info btn-xs' style='margin-top:5px;'");
						}else{
							$str .= $downloadLink;
						}
						// $str .= "&nbsp;".$annot_link."&nbsp;"; 
				
					}else {  // Not images
						$str .= "&nbsp;".$att_inputtag."&nbsp;";
						//if($downloadLink==''  and $_REQDATA['rtype']!='website_form'){
						if($downloadLink==''){
							$str .= anc("pw=Fw_imageview.php&rty=getimage&rq1=".$recds['encfilename']."&rq2=_pb_attachments&rq3=".$recds['id']."&rq4=popup","View","popf"," class='btn btn-info btn-xs'")."&nbsp;";
						}else{
							$str .= $downloadLink;
						}
						$style='';
					}
					
				//	}
					//  To delete the attachment (13-04-2018)
					if(!isFoundIn($dsPage['tags'],"No Delete Multi Attachments") and $_REQDATA['rtype']!='website_form') $str .= anc("pw=Fw_listedit.php&rty=delattach&rq1=".$recds['encfilename']."&rq2=".$recds['id']."&rq3=_pb_attachments&rq4=delrecord","Delete","editf"," class='btn btn-primary btn-xs'  style='".$style."'")."<br>&nbsp;File: ".$recds['filename']."<hr>";
				}else{
					/*if($_REQDATA['rtype']=='website_form'){
						$str .="<hr></td></tr>"; 	
					}else{*/
				    	$str .= $att_inputtag."cxsdfassdfsd<hr></td></tr>";
					//}
				}
				
			}
			// Plumbee Attachments (by nancy on 22-12-2021)
			$_REQDATA['rty'] = "editrec";
			 $_REQDATA['typ'] = "";
			if($_REQDATA['rty']=='edtrec' and $_REQDATA['typ']<>'list2' and $_SESSION['PW_CONSTANTS']['EXTERNALDRIVENAME']==""){
				$attcnt1=getValueForPS("selrec count(*) from _pb_attachments where linkedto='".$tablename."' and linkedid='".$id."'");
				if($attcnt1>0){
					$plumbee_attch = "selrec *  from _pb_attachments where  linkedto=? and linkedid=?";
					$plumbeeatt_res = PW_sql2rsPS($plumbee_attch,"ss",$tablename,$id);
					$recatt = "";
					$str .="<tr><td class='header-lg'>Child attachments</td></tr>";
					while($plumbeeatt_ds=PW_fetchArray($plumbeeatt_res)){
						if($plumbeeatt_ds['encfilename']!=''){
							$ext  = strtoupper(pathinfo(basename($plumbeeatt_ds['encfilename']), PATHINFO_EXTENSION));
							if($ext=='JPG' or $ext=='PNG' or $ext=='GIF' or $ext=='JPEG' or $ext=='BMP'){
								$recatt = anc("pw=Fw_imageview.php&rty=getimage&rq1=".$plumbeeatt_ds['encfilename']."&rq2=_pb_attachments&rq3=".$plumbeeatt_ds['id']."&rq4=popup&rq5=Photo",img("image-icon.svg","Click To View","width='14'"),"popf")."</td>";
							}else{
								$img_name = "image-icon";
								if($ext=='PDF')$img_name = "pdf-icon";
								if($ext=='XLS' || $ext=='XLSX' || $ext=='CSV')$img_name = "excel-icon";
								if($ext=='DOC' || $ext=='DOCX')$img_name = "word-icon";
								if($ext=='PPT')$img_name = "ppt-icon";
								if($ext=='TXT')$img_name = "document-icon";
								$recatt = anc("pw=Fw_imageview.php&rty=getimage&rq1=".$plumbeeatt_ds['encfilename']."&rq2=_pb_attachments&rq3=".$plumbeeatt_ds['id'],img($img_name.".png","Click To View","width=14"),"popf");
							}
						}
						$str .="<tr><td class='table-sub-content'><b>".$plumbeeatt_ds['subject']."</b>&nbsp;&nbsp;&nbsp;".$recatt."</td></tr>";
					}
				}
			}
		}
		$str .= "</table></form>";
		echo $str;
	}
	
	function displayAttachmentVersions($table,$id){
		$sql = "selrec * from _pb_attachments_version where linkedto=? and linkedid=? order by versionno";
		$res = PW_sql2rsPS($sql,"ss",$table,$id);
		echo "selrec * from _pb_attachments_version where linkedto='".$table."' and linkedid='".$id."' order by versionno";
		if(PW_num_rows($res)>0) $astr = "<table><th>Attachment Versions</th>";
		while($ads=PW_fetchAssoc($res)){
			$ext  = strtoupper(pathinfo(basename($ads['encfilename']), PATHINFO_EXTENSION));
			if(strlen($ads['filename'])>45) $ads['filename'] = substr($ads['filename'],0,40).".".$ext;
			if($ext=='JPG' or $ext=='PNG' or $ext=='GIF' or $ext=='JPEG' or $ext=='BMP'){
				$astr .="<tr><td><b>".$ads['versionno']."</b>.&nbsp;".anc("pw=Fw_imageview.php&rty=getimage&rq1=".$ads['encfilename']."&rq2=_pb_attachments_version&rq3=".$ads['id']."&rq4=popup&rq5=Photo",displaydate($ads['versiondate'])." / ".$ads['filename'],"popf")."</td></tr>";
			}else{
				$img_name = "image-icon";
				if($ext=='PDF')$img_name = "pdf-icon";
				if($ext=='XLS' || $ext=='XLSX')$img_name = "excel-icon";
				if($ext=='DOC' || $ext=='DOCX')$img_name = "word-icon";
				if($ext=='PPT')$img_name = "ppt-icon";
				if($ext=='TXT')$img_name = "document-icon";
				$astr .="<tr><td><b>".$ads['versionno']."</b>.&nbsp;".anc("pw=Fw_imageview.php&rty=getimage&rq1=".$ads['encfilename']."&rq2=_pb_attachments_version&rq3=".$ads['id'],displaydate($ads['versiondate'])." / ".$ads['filename'],"popf")."</td></tr>";
			}
		}
		if(PW_num_rows($res)>0) $astr .="</table>";
		return ($astr);
	}
	
	function uploadbutton($fSize,$req,$att_ds){
		$fSize = "100";
		$str="<div  class='btn btn-primary btn-xs' style='margin-top:0px;' onclick='document.getElementById(\"att_".$att_ds['id']."\").click();'>".img("upload-file.svg","Upload file"," style='width:12px;margin-right:5px;'").$att_ds['name']." <span id='div_+-".$att_ds['id']."'><span></div>";
		$str.="<input style='display:none'  name='att_".$att_ds['id']."' id='att_".$att_ds['id']."'  type='file' multiple='multiple' ".$req." cap='".$att_ds['name']." (Attachment)' size=10 onchange='CheckFile(\"att_".$att_ds['id']."\",\"".$att_ds['allowedfiletype']."\",\"".$fSize."\",this.value,\"".$att_ds['id']."\");' secu=1>";
		return $str;
	}
	
	function uploadFormAttachments($pg,$recid,$table){
		// To insert Multi Attachments
		//list($pg,$id,$hpg,$hid) = explode("::",pw_dec($_SESSION['editPage']));
		//$table = $_SESSION['uni_pagehead']['tablename'] ;
		$att_sql = "selrec * from _pb_attachdefinition where pgid=? order by slno";
		$att_res = PW_sql2rsPS($att_sql,"s",$pg);
		while($att_ds=PW_fetchArray($att_res)){
			printr($_FILES);
			uload_attach($_FILES,"att_".$att_ds['id']);
			//$msg = uload($_FILES, "att_".$att_ds['id'], $recid, $table, UPLOAD_DIR_PATH);
			//if($_SESSION['user_uploadedfile']!='' and $_SESSION['enc_user_uploadedfile']!=''){
				// Deleting old records
				$old_recds = getValueForPS("selrec id,encfilename from _pb_attachments where subject=? and linkedto=? and linkedid=?","sss",$att_ds['code'],$table,$recid);
				if($old_recds['id']){
					PW_execute("delrec from _pb_attachments where id='".$old_recds['id']."'");
					$old_recds['encfilename'] = getUploadFilePath($old_recds['encfilename']);
					unlink(UPLOAD_DIR_PATH.$old_recds['encfilename']);
					unlink(UPLOAD_DIR_PATH."small_".$old_recds['encfilename']);
				}
				if($_POST['cmb'.$att_ds['id']]!=''){
					$optcmb = explode("::",$_POSTDATA['cmb'.$att_ds['id']]);
				}	
				$extArr = explode(".",$_SESSION['user_uploadedfile']);
				$ds['today'] 		  = date('Y-m-d');
				$ds['subject'] 		  = $att_ds['code'];
				$ds['explanation']	  = $att_ds['explanation'];
				$ds['attachmenttype'] = strtoupper($extArr[1]);
				$ds['optionbox']	  = $_POSTDATA['box'.$att_ds['id']];
				$ds['optioncombo']	  = $optcmb[0];
				$ds['filename'] 	  = $_SESSION['user_uploadedfile'];
				$ds['encfilename']    = $_SESSION['enc_user_uploadedfile'];
				$ds['filesize']		  = $_SESSION['user_uploadfilesize'];
				$ds['linkedto']  	  = $table;
				$ds['linkedid']  	  = $recid;
				$ds['recordtype']  	  = "Plumbee Attachments";
				$ds['txt1']			  = $att_ds['name'];
				// file size added on (07-10-2022)
				$result1 = PW_sql2rsPS("SHOREC COLUMNS FROM _pb_attachments LIKE 'filesize'");
				$exists1 = (PW_num_rows($result1))?TRUE:FALSE;
				if(!$exists1){
					 PW_execute("ALTTAB TABLE _pb_attachments ADD filesize integer(20)");
				}
				//printr($ds);//exit;
				insertRecord($ds,"_pb_attachments");  //  Insert into attachments
				$ext = pathinfo($ds['encfilename'], PATHINFO_EXTENSION);
				$ext = strtolower($ext);
				$_SESSION['user_uploadedfile'] = "";
				$_SESSION['enc_user_uploadedfile'] = "";
				$_SESSION['user_uploadfilesize']  = "";
			//}	
		}	
		
	}
	
	function uload_attach($userUploadedFile,$fileInputName="userfile",$id="",$table="",$dir=""){
		printr($userUploadedFile);//exit;
		printr($fileInputName);
		//	Description: Upload the file with validations of size,extentions 
		//	$userUploadedFile : array : array of $_FILES
		//	$fileInputName : sring : input file type file name 
		//	$id : string : record id it will use for if any previous file exists it will be remove
		//	$table : string : table name for the object getting the previous file record filename
		//	$dir : sring :   
		if($dir==""){ $dir=UPLOAD_DIR_PATH;}
		$file_tmp_name = $userUploadedFile[$fileInputName]["tmp_name"];
		$file_name = $userUploadedFile[$fileInputName]["name"];
		$file_size = $userUploadedFile[$fileInputName]["size"];
		
		// $file_mime = strtoupper(fileMime($file_tmp_name));
		$file_mime = "";
		if($file_mime=="PHP" or $file_mime=='ASCII'){
			$_SESSION['user_uploadedfile'] = $_SESSION['enc_user_uploadedfile'] = $_SESSION['user_uploadfilesize'] = "";
			return  "\n<script>alert('Invalid file type');hideprogress();</script>\n";
		}
		$err = "";
		$ext = pathinfo(basename($file_name), PATHINFO_EXTENSION);
		$errorUP  = "";
	    $fname = $file_name;
	    $dots    = substr_count($fname,".");
	    $isSlash  = strpos("XXX".$fname,"/");
	    $isRSlash  = strpos("XXX".$fname,"\\");
	    if($dots>1 or $isSlash>0 or $isRSlash>0){
	      $errorUP.= "File name : ".$v." has error 7888::  Double dots or slash marks found";
	    }
	    if($errorUP != "" ){
			show_formErrors($errorUP);
			exit;	
	    } 
		$upload_file_size_limit = DOCUMENT_UPLOAD_LIMIT;
		$allowedExtensions = array("jpg","jpeg","gif","png","bmp");
		$allowedExtensions1 = array("doc","docx","xls","xlsx","csv","ppt","pptx","pdf","txt","eml","msg","opus");
		$notAllowedExtensions = array("php","js","exe","bat");
		if (in_array(strtolower($ext), $allowedExtensions)){
			$upload_file_size_limit = IMAGE_UPLOAD_LIMIT;
		}elseif (in_array(strtolower($ext), $allowedExtensions1)){
			$upload_file_size_limit = DOCUMENT_UPLOAD_LIMIT;
		}
		$allowedTypes = [
			// Images
			'jpg'  => ['image/jpeg','image/jpeg'],
			'jpeg' => ['image/jpeg','image/jpeg'],
			'png'  => ['image/png'],
			'gif'  => ['image/gif'],
			'bmp'  => ['image/bmp'],
		
			// Documents
			'pdf'  => ['application/pdf'],
			'doc'  => ['application/msword'],
			'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
			'xls'  => ['application/vnd.ms-excel'],
			'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
			'csv'  => ['text/csv', 'text/plain'],
			'ppt'  => ['application/vnd.ms-powerpoint'],
			'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
			'txt'  => ['text/plain'],
		
			// Emails
			'eml'  => ['message/rfc822'],
			'msg'  => ['application/vnd.ms-outlook'],
		
			// Audio
			'opus' => ['audio/opus'],
		
			// Archives
			'zip'  => ['application/zip'],
		
			// CAD files
			'dwg'  => ['application/acad'],
			'dxf'  => ['image/vnd.dxf'],
			'sldprt' => ['application/sldworks'],
			'prt'  => ['application/octet-stream'],
			'stp'  => ['application/step', 'application/x-step'],
			'step' => ['application/step', 'application/x-step'],
			'igs'  => ['application/iges', 'model/iges'],
			'x_t'  => ['application/x-t'],
		];
		$allowedExtensions = array("jpg","jpeg","gif","png","bmp");
		$allowedExtensions1 = array("doc","docx","xls","xlsx","csv","ppt","pptx","pdf","txt","eml","msg","opus");
		$notAllowedExtensions = array("php","js","exe","bat");
		if (in_array(strtolower($ext), $allowedExtensions)){
			$upload_file_size_limit = IMAGE_UPLOAD_LIMIT;
		}elseif (in_array(strtolower($ext), $allowedExtensions1)){
			$upload_file_size_limit = DOCUMENT_UPLOAD_LIMIT;
		}
		$extLower = strtolower($ext);
		// Check extension
		if($id=='' && $file_tmp_name){
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$file_tmp = $file_tmp_name;
			$realMime = finfo_file($finfo, $file_tmp); // $file_tmp = $_FILES['yourfile']['tmp_name']
			finfo_close($finfo);
			
			// Check MIME against allowed list for this extension
/*			if (!in_array(strtolower($realMime), $allowedTypes[$extLower])) {
				//$err = "Invalid or mismatched file type: expected ".implode(", ", $allowedTypes[$extLower])." but got ".$realMime;
				$err = "This file type is not supported. Please upload valid file";
				fw_output( "<script>alert('".$err."');showObject('aeform');</script>\n","script");
				exit;
			}*/			
			if (!array_key_exists($extLower, $allowedTypes)) {
				fw_output("<script>alert('".$extLower." files cannot be Uploaded.".$id.$file_tmp_name."');showObject('aeform');</script>\n","script");
				exit;
			}
		}
		if ($id != '') {
			if($_FILES[$fileInputName]['name']){
				$checkrealpath	=  $file_tmp_name;
				$encfile = $_FILES[$fileInputName]['name'];
			    if ($encfile) {
			        $extLower = strtolower(pathinfo($encfile, PATHINFO_EXTENSION));
					if (empty($extLower)) {
						$err = "Could not detect file extension from uploaded file: ".$encfile;
						exit;
					}
					// Ensure extension exists in allowed list
					if (!isset($allowedTypes[$extLower])) {
						$err = "Extension .$extLower not allowed for upload.";
						fw_output("<script>alert('".$err."');showObject('aeform');</script>\n", "script");
						exit;
					}
			        // Detect MIME from tmp file
					$finfo    = finfo_open(FILEINFO_MIME_TYPE);
					$realMime = finfo_file($finfo, $checkrealpath);
					finfo_close($finfo);
					$realMime   = strtolower($realMime);
					$allowedForExt = array_map('strtolower', $allowedTypes[$extLower]);
					if (!in_array($realMime, $allowedForExt)) {
						//$err = "Invalid or mismatched file type: expected [".implode(", ", $allowedForExt)."] but got [".$realMime."]";
						$err = "This file type is not supported. Please upload valid file";
						fw_output("<script>alert('".$err."');showObject('aeform');</script>\n", "script");
						exit;
					}
			    }
			}
		}
		// if($file_size> $upload_file_size_limit){
		if($file_size> 20000000){
			fw_output("<script>alert('Upload file size limited to .....".$upload_file_size_limit." bytes...".$file_name." exceeds the file xxxxlimit and is hhh');
			hideprogress();
			</script>\n","script");
			exit;
		}
		/* MIME TYPE VALIDATION */
/*		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$file_tmp = $file_tmp_name;
		$realMime = finfo_file($finfo, $file_tmp); // $file_tmp = $_FILES['yourfile']['tmp_name']
		finfo_close($finfo);
		
		// Check MIME against allowed list for this extension
		if (!in_array(strtolower($realMime), $allowedTypes[$extLower])) {
			$err = "Invalid or mismatched file type: expected ".implode(", ", $allowedTypes[$extLower])." but got ".$realMime;
			fw_output( "<script>alert('".$err."');hideprogress();</script>\n","script");
			exit;
		}*/		
		$err="";
		
		$chkExtentionsArray = array('PDF','XLS','XLSX','DOC','CSV','DOCX','PPT','PPTX','TXT','ZIP','PNG','GIF','JPG','JPEG','BMP','EML','DWG','DXF','SLDPRT','PRT','STP','IGS','MSG','OPUS','STEP','X_T'); // STEP','X_T' igs are cad files
		if (!in_array(strtoupper($ext), $chkExtentionsArray) && $ext<>'') {
			fw_output("<script>alert('".$ext." files cannot be Uploaded.');hideprogress();</script>\n","script");
 			 exit;
		}
		
		if (in_array(strtolower($ext), $notAllowedExtensions)) {
			$err = "PHP/JS files cannot be Uploaded.....";
			return "<script>alert('PHP/JS files cannot be Uploaded.....');hideprogress();</script>\n";
			// exit($err);
		}
		
		if($file_name){
			if(strlen($file_name)>45) $file_name = substr($file_name,0,45).".".$ext;
			$_SESSION['user_uploadedfile'] 		= $file_name;
			$_SESSION['user_uploadfilesize'] 	= $file_size; // file size added on (07-10-2022)
			$fileDir = date("YM");
			if($_REQUEST['pgid']=="pb_pagehead" or $_REQUEST['pgid']=="pb_pagefields" or $_REQUEST['pgid']=="pb_pagelinks" or $_REQUEST['pgid']=="_pb_pagefieldsall") $fileDir="framewo";
			if($_REQUEST['hpg']=="pb_pagehead" or $_REQUEST['hpg']=="pb_pagefields" or $_REQUEST['hpg']=="pb_pagelinks" or $_REQUEST['hpg']=="_pb_pagefieldsall" or $_REQUEST['hpg']=="_pb_pagelinksofheads") $fileDir="framewo";
			if($_REQUEST['pgid']=="_pb_pagelinksofheads" or $_REQUEST['hpg']=="_pb_pagelinksofheads") $fileDir="framewo";
			if(isFoundIn("org_modules_localadmin//_pb_submodules_localadmin//org_modules//_pb_submodules",$_REQUEST['pgid'])) $fileDir="framewo";;
			
			createUploadMonthFolder();
			$_SESSION['enc_user_uploadedfile']  = $fileDir.getRandomStr(10).".".$ext;
			//-----------------
			set_time_limit(240);
				//  Google drive upload in some conditions.  Mod_utl_gdrive is th eprogram slaw gopal
			$pg_tags = $_SESSION['uni_pagehead']['tags'];
		 
			if(strlen($_SESSION['PW_CONSTANTS']['EXTERNALDRIVENAME'])>2 &&  !isFoundIn($pg_tags,"Upload to application drive")){
 				// move_uploaded_file($file_tmp_name, $dir.$fileDir."/".$_SESSION['enc_user_uploadedfile'])
				// copy($dir.$fileDir."/".$_SESSION['enc_user_uploadedfile'],"temp/".basename($file_name));
				$_REQDATA['rty']	= "upload";
				$fileToUpload		= $file_tmp_name;
				$driveFileName		= $userUploadedFile[$fileInputName]["name"];
				include_once("Mod_utl_gdrive.php");
				
				//  After uploading to gdriv the file is is saved to $_SESSION['PW_ExternalDriveUploadID']
				//  above 4 line by sastry Nov 2021 for google drive upload
				
			}else{
				if (move_uploaded_file($file_tmp_name, $dir.$fileDir."/".$_SESSION['enc_user_uploadedfile'])) {
					/* 	// suspended
						if (in_array(strtolower($ext), $allowedExtensions)) {
							createThumbnail($_SESSION['enc_user_uploadedfile'],$dir.$fileDir."/");
						}
					*/
		        }else{
					$_SESSION['user_uploadedfile'] 		= "";
					$_SESSION['enc_user_uploadedfile']  = ""; 
					$_SESSION['PW_ExternalDriveUploadID'] = "";
					$_SESSION['user_uploadfilesize'] = "";
				}
			}
			
			
			//----------------
			if($table and $id){
				if(isset($_POST['attcahmentversions'])){
					// if checking the retain old attchments for attachment versions
					$tempDS = getValueForPS("selrec filename,encfilename,linkedid,linkedto from ".$table." where id=? and tenent=?","ss",$id,TENENT);
					$versionCount = getValueForPS("selrec max(versionno) from  _pb_attachments_version where linkedid=? and linkedto=?","ss",$id,$table);
					$verAttDS = array();
					$verAttDS['encfilename'] =  $tempDS['encfilename'];
					$verAttDS['filename'] 	 =  $tempDS['filename'];
					$verAttDS['subject'] 	 =  $tempDS['filename'].LASTGDRIVEFILEID;
					$verAttDS['linkedid'] 	 =  $id;
					$verAttDS['linkedto'] 	 =  $table;
					$verAttDS['linkedid2'] 	 =  $tempDS['linkedid'];
					$verAttDS['linkedto2'] 	 =  $tempDS['linkedto'];
					$verAttDS['versiondate'] =  date('Y-m-d');
					$verAttDS['versionno'] 	 =  ($versionCount+1);
					
					$verAttDS['versioncomments']  =  $_POST['versioncomments'];
					// inserting into attachment versions
					if($verAttDS['encfilename']) insertRecord($verAttDS,"_pb_attachments_version");
				}else{				
					$encfilename = getValueForPS("selrec encfilename from ".$table." where id=? and tenent=?","ss",$id,TENENT);
					$encfilename = getUploadFilePath($encfilename);
					@unlink($dir.$encfilename);
					
					//@unlink($dir."small_".$encfilename);
				}
			}

		}
    }
	
?>	