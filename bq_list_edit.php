<?php
	$dovar="plumbee";
	include_once("bq_list_edit_controls.php");
	// showing the popup thread
	// printr($_GET);
	
	// pre edit load
	if(isset($_SESSION['activepage']['head']['pgid']) and file_exists("segment/".$_SESSION['activepage']['head']['pgid'].".php")){
		include_once("segment/".$_SESSION['activepage']['head']['pgid'].".php");
		if(function_exists('plx_preEditLoad')) {
			plx_preEditLoad("Preedit 3444",'sample id');
		}
	}
	// preview=yes if u just want to see form edit layout
	if (!isset($_GET['preview']) || $_GET['preview'] !== "yes") {
	    include_once "bq_list_edit_action.php";
	}
	if (isset($_GET['preview']) && $_GET['preview'] == "yes") {
		$_SESSION['activepage']=setcurrentpageSession($_GET['pgid']);  
	}
	
	if ((isset($_GET['pfid']) && $_GET['pfid'] != "") && (isset($_GET['pgid']) && $_GET['pgid'] !=$_GET['pfid'])) {
		$_SESSION['currentpage']=setcurrentpageSession($_GET['pfid']);  
	}
	// checking of if the form is marked as on design mode only
	if(isset($_SESSION['activepage']['head']['tags'])){
		$ondesign=isFoundIn($_SESSION['activepage']['head']['tags'],"On design mode only");
		if($ondesign and $_SESSION['designmode']!="on"){
			echo toast("Form '".$_SESSION['activepage']['head']['caption']."' is available on design mode only..","danger");
			exit;
		}
	}
	$dragdropfieldsurl = pw_enc("pw=bq_fw_pagesetup.php");
	$geaarwheel="";
	if(isset($_GET['fromtb']) && $_GET['fromtb'] =='_pb_pagehead'){
		$currentpage_caption="Page Head (".$_GET['pgid'].")";
	}
	else{
		if(isset($_GET['pfid']) && $_GET['pfid']!=''){
			$currentpage_caption =getValueForPS("selrec caption from _pb_pagehead where pgid=?","s",$_GET['pfid']);;
		}
		else {
			$currentpage_caption=$_SESSION['currentpage']['head']['caption'];
		}
	}
	$sufix="";
	if($_SESSION['designmode']=="on"){
		$activepage=$_SESSION['activepage']['head']['caption'];
		if(isset($_GET['pgid']) and $_GET['pgid']=="pb_pagefields") $sufix=' - Fields';
	    	$geaarwheel =  "
		    <span title='Page setup (".$activepage.")'
		    	<i class='ms-2 bi bi-gear' 
		    	hx-get='do_bq.php?bqkey=".$dragdropfieldsurl."' 
		    	hx-target='#editPanel'
		    	hx-swap='innerHTML'
		    	onclick= 'setSideWidth(\"30%\")'></i>
		    </span>";
		    $geaarwheel="";
	    }
	    echo "<div>".$geaarwheel."<span ><h5 class='ms-1'>".$currentpage_caption.$sufix."</h5></span></div>";
	$pageSetuplink = $geaarwheel ?? '';
	if(isset($_GET['action']) && $_GET['action']=='edit'){
		
		$_SESSION['activepage']=$_SESSION['currentpage'];
		if(isset($_GET['pgid']) and $_GET['pgid']!="") {	// edit/add from  for page fields edit...
			$_SESSION['activepage']=setcurrentpageSession($_GET['pgid']);  
		}
		if(isset($_GET['pfid']) and $_GET['pfid']!="") {	// edit/add from  link other than  from direct list will have pgid degined...
			$_SESSION['activepage']=setcurrentpageSession($_GET['pfid']);  
		}
		$_GET['rty'] = $_GET['rty'] ?? '';
		if($_GET['rty']=='readonlytag'){
			echo toast("Record is Readonly.","danger");
		}
		
		//printr($_GET);
		// when the editi link through link
		$id=$_GET['hid'];
		$dataDS=array();
		$activepagetable=$_SESSION['activepage']['head']['tablename'];
		if($id!=""){
			$sql="selrec * from ".$activepagetable." where id=?";
			//echo $sql;//exit;
			$dataDS=getValueForPS($sql,"s",$id);
			if($_SESSION['currentpage']['head']['readonlyon']!=''){
				$dataDS = $dataDS ?? '';
			 	$condition   = replaceDS2message($dataDS,$_SESSION['currentpage']['head']['readonlyon']);
			 	//printr($condition);exit;
			 	//if($condition!="") $show=eval("if(".$condition."){return 'OK';}else{return 'No';}");
			 	if($condition!="") $show=evaluateCondition($condition, $dataDS, $debug);
			 	if($show == "OK"){
			 		$_GET['rty']='readonlytag';
			 	}
			}
			//printr($dataDS);exit;
			$_SESSION['activepage']['meta']['olddata']=$dataDS;
			if(isset($dataDS) and isset($dataDS['id'])=="" ){
				echo displayError("Data not found","1.Potentially the record is deleted by another user");
				exit;
			}
		}
	}
	if(isset($_GET['action']) && $_GET['action']=='pgheadedit'){
	//	printr($_GET);
		$sql="selrec * from ".$_GET['fromtb']." where id=?";
		//echo $sql;//exit;
		$dataDS=getValueForPS($sql,"s",$_GET['hid']);
		if(isset($_GET['hpgd']) and $_GET['hpgd']!="") {	// edit/add from  link other than  from direct list will have pgid degined...
		//printr($_GET);
			$_SESSION['activepage']=setcurrentpageSession1('pb_pagehead');  
		}
		$id=$_GET['hid'];
		$dataDS=array();
		if($id!=""){
			$sql="selrec * from ".$_SESSION['activepage']['head']['tablename']." where id=?";
			 //echo $_SESSION['activepage']['head']['tablename']."====".$id."====".$sql;//exit;
			$dataDS=getValueForPS($sql,"s",$id);
			//printr($dataDS);exit;
			$_SESSION['activepage']['meta']['olddata']=$dataDS;
			if(isset($dataDS) and isset($dataDS['id'])=="" ){
				echo displayError("Data not found","2.Potentially the record is deleted by another user");
				exit;
			}
		}
	}
	
	// start of attachment
	if(isset($_GET['action']) && $_GET['action']=='attach'){
		if(isset($_GET['hid']) && !empty($_GET['hid'])){
			$id=$_GET['hid'];
			$table = $_SESSION['activepage']['head']['tablename'];
			echo "<tr><td>".displayImageBasedExt($table,$id)."</td></tr>";
			echo "<tr><td>".fileupload()."</td></tr></table>";
			getFormAttachments($table,$id);
			exit;
		}else{
			$id=$_GET['hid'];
			$table = $_SESSION['activepage']['head']['tablename'];
			echo "<tr><td>".fileupload()."</td></tr></table>";
			getFormAttachments($table,$id);
			exit;			
		}
		//$bqkeyupload=pw_enc("pw=bq_list_edit.php&action=attach&typ=upload");
	}
	// end of attachments

	echo '
	<style>
		input::-webkit-outer-spin-button,
		input::-webkit-inner-spin-button {
			-webkit-appearance: none;
			margin: 0;
		}
		/* Firefox */
		input[type=number] {
			-moz-appearance: textfield;
		}
		.bg-gray-300 { background-color: #dee2e6 !important; }
	</style>
	<div></div>
	<script>
		// update bottom footer right notifications
		document.getElementById("notifications").innerHTML ="Edit:'. $_SESSION['notifications'].'"
		setSideWidth("30%")
		showTemp("list_bottom","<div  class=\'ps-2 p-1 w-100 bg-secondary text-white\'>This is sample xxx '.date("i-s").'</div>")
		function onlyDigits(el){
		el.value = el.value.replace(/[^0-9]/g, "");
	}
	</script>';
	edit_form();

	function edit_form(){
		$bqkey ="";
		$bqkey=pw_enc("pw=bq_list_edit.php&action=new");
		// set link to add ot edit
		$_GET['action'] = $_GET['action'] ??'';
		if($_GET['action']=="edit" || $_GET['action']=="pgheadedit"){
			$bqkey=pw_enc("pw=bq_list_edit.php&action=update&id=".$_SESSION['activepage']['meta']['olddata']['id']);
		} 
		$bqkeyinsert=pw_enc("pw=bq_list_edit.php&action=insert");
		if($_GET['action']=="edit") $bqkeydelete=pw_enc("pw=bq_list_edit.php&action=delete&pgid=".$_SESSION['activepage']['head']['pgid']."&id=".$_SESSION['activepage']['meta']['olddata']['id']);
		$search = "";
		$search = $_SESSION['currentpage']['meta']['usersearch']??'';		
		$linkedid = $linkedto = "";
		//$linkedid = $_SESSION['activepage']['meta']['olddata']['linkedid']??'';
		if(isset($_SESSION['activepage']['meta']['olddata']['linkedid'])){
			$linkedid = $_SESSION['activepage']['meta']['olddata']['linkedid'];
		}
		//$linkedto = $_SESSION['activepage']['meta']['olddata']['linkedto']??'';
		if(isset($_SESSION['activepage']['meta']['olddata']['linkedto'])){
			$linkedto = $_SESSION['activepage']['meta']['olddata']['linkedto'];
		}
		if(isset($_GET['parentid'])){
			$linkedid = $_GET['parentid'];
		}
		//$linkedto = $_SESSION['activepage']['meta']['olddata']['linkedto']??'';
		if(isset($_GET['parenttable'])){
			$linkedto = $_GET['parenttable'];
		}		
		$parentpgid = "";
		$parentpgid = isset($_SESSION['activepage']['head']['pgid']);
		// added parentid & parenttable to retain the childrecords of the parent e.g customers -> links -> meeting tasks
		$bqkey1=pw_enc("pw=bq_list_table.php&rty=list&fromedit=1&txt_s=".$search."&parentid=".$linkedid."&parenttable=".$linkedto."&parentpgid=".$parentpgid);
		$bqkeyattach=pw_enc("pw=bq_list_edit.php&action=attach");
		// div to display errors	
		echo "<div id='editdatadiv' class='m-1' style='position:relative;height:100%'>
			  <div id='editerrordiv' onclick=\"this.style.display='none'\"
    		   style='display:none;font-size:16px;width:98%;min-height:32px;position:absolute;left:0;top:0;z-index:2000;cursor:pointer'>
			</div>			  ";
		
		echo makeTabs();
		echo openForm();
		echo "<div class='m-2' id='tabsdiv'>
		<table id='edittable' class='w-100 table table-sm table-xsm table-borderless'>";
		$oldPara="xxx";
		$_SESSION['sufixtofield']=array();
		$dsdata= [];
		foreach($_SESSION['activepage']['fields'] as $k=>$v){
			if(isset($_SESSION['activepage']['fields']['tags']) && $_SESSION['activepage']['fields']['tags']!=''){
				$_SESSION['activepage']['fields']['tags'] = $_SESSION['activepage']['fields']['tags'] ?? '';
			}	
			if(isfoundin(($v['tags']),"SEQUENCER") and $_GET['action']=="new"){
				$pg = $_SESSION['activepage']['head']['pgid'];
				$newSeqNo = getfieldSequencer($v,$pg);
				$v['pbdefault'] = $newSeqNo;
				$v['controltype'] = "READ ONLY";
			}
			$v['id'] = $v['id'] ?? '';
			$v['fieldname'] = $v['fieldname'] ?? '';
			$v['paraname'] = $v['paraname'] ?? '';
			$v['tabname'] = $v['tabname'] ?? '';
			$v['prefix']=substr($v['id'],11,100);
			$_SESSION['sufixtofield'][$v['prefix']]=$v['fieldname'];
			if(is_null($v['paraname']))$v['paraname']="";
			if(is_null($v['tabname'])) $v['tabname']="";
			if($v['paraname']=="") $v['paraname']=$oldPara;
			$style="";
			if($v['tabname']!="General") $style=" style='display:none' ";
			if($oldPara!=$v['paraname'] ){
				echo "<tr  tabname='".$v['tabname']."' ".$style." >
				<td colspan=2 style='height:40px;'><strong>---| ".$v['paraname']." |---</strong><br></td>
				</tr>";
			}
			$show = "ALL"; // default behavior — show control
			if (!empty($v['hideon'])) {
				if (isset($_GET['hid'])) {
					$id = $_GET['hid']; 
					$dsdata = getValueForPS("selrec * from " . $_SESSION['activepage']['head']['tablename'] . " where id=?", "s", $id);
				}
				// Build and evaluate condition dynamically
				$condition = replaceDS2message($dsdata, $v['hideon']);
				if (!empty($condition)) {
					try {
						$result = eval("return ($condition) ? 'OK' : 'NO';");
						$show = $result;
					} catch (Throwable $e) {
						$show = "ALL"; // if eval fails, fallback to showing all
					}
				}
			}
			
			// Now decide when to display controls
			if ($show === 'OK') {
				// Display ONLY this control
				displayControl($k, $v);
			} elseif ($show === 'ALL') {
				// Display everything normally
				displayControl($k, $v);
			} else {
				// $show == 'NO' → skip this control
				continue;
			}
			$oldPara=$v['paraname'];
		}
		echo "</table>";
		if(function_exists('plx_postEditLoad')) plx_postEditLoad("Post edit 3833","Sample ID");
		// bottom submit band in edit side
		echo "<div id='fileattach'></div>
		<div class='mt-auto position-sticky bottom-0 bg-white border-top p-2 shadow-sm' style='z-index:5000'>";
		if(isset($_GET['rty']) && $_GET['rty']!='readonlytag'){
			if($_GET['action']=="edit" || $_GET['action']=='pgheadedit'){
			//$hx_on="	hx-on=\"htmx:afterOnLoad: htmx.ajax('GET','do_bq.php?bqkey={$bqkey1}','#bqScroller')\"";
				$hx_on="	hx-on=\"htmx:afterRequest: htmx.ajax('GET','do_bq.php?bqkey={$bqkey1}','#bqScroller')\"";
				if(isset($_GET) && (!empty($_GET['pgid']) || !empty($_GET['hpgd']))) $hx_on = "";
				// for php pages added by laxmikanth on 06-02-2026
				if(isset($_GET) && $_GET['rty']=='php'){
					$url = $_SESSION['landing_page'] ?? '';
					$hx_on = 'hx-on::after-request="htmx.ajax(\'GET\', \''.$url.'\', \'#bqScroller\')"';					
				}
				echo "<button onclick='closeAllPops();'
					accesskey='s'
					type='submit'
					class='btn btn-primary btn-sm me-2'
					value='Update'
					hx-post='do_bq.php?bqkey={$bqkey}'
					hx-target='#editerrordiv'
					hx-swap='innerHTML' 
					hx-indicator='#wspinner'"
					.$hx_on."
				><i class='bi bi-floppy fs-7 ms-1 me-1'></i> Update
				<span id='wspinner' class='htmx-indicator'>
        			<span class='spinner-border spinner-border-sm ms-2' role='status' aria-hidden='true'></span>
    				</span>
				</button>";
			}
			if($_GET['action']=="new" and !isset($_GET['preview'])){
				$hx_on ="";
				$hx_on="hx-on=\"htmx:afterRequest: htmx.ajax('GET','do_bq.php?bqkey={$bqkey1}','#bqScroller')\"";
				// for php pages added by laxmikanth on 06-02-2026
				if(isset($_GET) && $_GET['rty']=='php'){
					$url = $_SESSION['landing_page'] ?? '';
					$hx_on = 'hx-on::after-request="htmx.ajax(\'GET\', \''.$url.'\', \'#bqScroller\')"';					
				}				
				echo "<button
					accesskey='s'
					type='submit'
					class='btn btn-primary btn-sm'
					value='Insert'
					hx-post='do_bq.php?bqkey={$bqkeyinsert}'
					hx-target='#updatemsg'
					hx-swap='innerHTML' ".$hx_on.">
					<i class='bi bi-floppy fs-7 ms-1 me-1'></i> Insert
					<span id='spiner1' class='htmx-indicator'>
					<span class='spinner-border spinner-border-sm' role='status' aria-label='Loading'></span>
					</span>
					</button>";
			}
			// temporarily suspended the delete button as discussed with Sastry Sir
			if(!isfoundin(strtoupper($_SESSION['activepage']['head']['tags']),"NO DELETE") and ($_GET['action']=="edit")){	
				$hx_on ="";
				$hx_on="hx-on=\"htmx:afterRequest: htmx.ajax('GET','do_bq.php?bqkey={$bqkey1}','#bqScroller')\"";
				// for php pages added by laxmikanth on 06-02-2026
				if(isset($_GET) && $_GET['rty']=='php'){
					$url = $_SESSION['landing_page'] ?? '';
					$hx_on = 'hx-on::after-request="htmx.ajax(\'GET\', \''.$url.'\', \'#bqScroller\')"';					
				}				
				echo "<button
					type='submit'
					class='btn btn-secondary btn-sm'
					value='Delete'
					hx-post='do_bq.php?bqkey={$bqkeydelete}'
					hx-target='#editerrordiv'
					hx-swap='innerHTML' ".$hx_on."
					hx-confirm='Delete this record?'
					<span class='badge text-bg-danger'><i class='bi bi-x-circle'></i></span>
					Delete</button>";
			}
		}
		
		echo "</div>";
		echo "</div>"; // close of editdata div
		echo "</form></div>
		<div id='updatemsg' style='z-index:6000;' ></div>";
	}
	function displayControl($k, $v) {
		$_GET['rty'] = $_GET['rty'] ??'';
		if($_GET['rty']=='readonlytag'){
			$v['controltype'] = "READ ONLY";
		}
		$v['controltype'] = $v['controltype'] ?? '';
		switch (strtoupper($v['controltype'])) {
			case "READ ONLY":
				echo cont_text($k, $v);
				break;
			case "TEXT BOX":
			case "SMS":
			case "EMAIL":
				echo cont_text($k, $v);
				break;
			case "TEXT AREA":
				echo cont_textarea($k, $v);
				break;
			case "TEXTAREA SMALL":
				echo cont_textarea($k, $v);
				break;
			case "TEXTAREA LARGE":
				echo cont_textarea($k, $v);
				break;				
			case "TEXT AREA SMALL":
				echo cont_textarea($k, $v);
				break;
			case "LABEL":
				echo cont_lable($k, $v);
				break;
			case "THREAD":
				echo cont_thread($k, $v);
				break;
			case "GEOLOCATION":
				echo cont_geo($k, $v);
				break;
			case "GEO ATTENDENCE":
				echo cont_geolocation($k, $v);
				break;
				
			case "COMBO":
				echo cont_Combo($k, $v);
				break;
			case "SQL PICKER":
				echo cont_SqlPicker($k, $v);
				break;
			case "URL":
				echo cont_url($k, $v);
				break;
			case "MULTI SELECT":
				echo cont_multiselect($k, $v);
				break;
			case "DATE NORMAL":
				echo cont_DateNormal($k, $v);
				break;
			case "RANGE SLIDER":
				echo cont_RangeSlider($k, $v);
				break;
			case "SIGNATURE":
				echo cont_Signature($k, $v);
				break;
			case "AI CONTROL":
				echo cont_Ai($k, $v);
				break;
			case "PERCENTAGE":
				echo cont_percentage($k, $v);
				break;
			case "CHECKLIST":
				echo cont_Checklist($k, $v);
				break;
			case "LANGUAGE TRANSLATE":
				echo cont_Translate($k, $v);
				break;
	    }
	}	
	
	
	echo '<script>
			(function () {
  var box = document.getElementById("locationInput");
  if (!("geolocation" in navigator)) return;

  var best = null;
  var stopAt = Date.now() + 30000; // try up to ~30s
  var watchId = navigator.geolocation.watchPosition(onPos, noop, {
    enableHighAccuracy: true,
    timeout: 20000,
    maximumAge: 0
  });

  function onPos(p) {
    if (!best || p.coords.accuracy < best.coords.accuracy) best = p;
    if (best.coords.accuracy <= 100 || Date.now() > stopAt) {
      navigator.geolocation.clearWatch(watchId);
      box.value = best.coords.latitude.toFixed(6) + ', ' + best.coords.longitude.toFixed(6);
    }
  }
  function noop() {}
})();
		  </script>';
echo '
<script>
function closestMultiSelect(el){
  while (el && el !== document) {
    if (el.classList && el.classList.contains("multi-select")) return el;
    el = el.parentNode;
  }
  return null;
}

function toggleAll(btn, check){
  var wrap = closestMultiSelect(btn);
  if (!wrap) return;
  var boxes = wrap.querySelectorAll(\'input.topic[type="checkbox"]\');
  boxes.forEach(function(cb){ cb.checked = !!check; });
}

function filterList(input){
  var wrap = closestMultiSelect(input);
  if (!wrap) return;
  var q = (input.value || "").toLowerCase();
  var rows = wrap.querySelectorAll(\'.form-check\');
  rows.forEach(function(row){
    var label = row.querySelector("label");
    var txt = label ? label.textContent.toLowerCase() : "";
    row.style.display = txt.indexOf(q) !== -1 ? "" : "none";
  });
}
</script>
';		  
?>