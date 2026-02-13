<?php
	include_once("bq_indi_engine.php");
	include_once("bq_list_edit_controls.php");
	
	// define('ROWSPERPAGE',13);
	define('PHPPAGE','bq_list_table.php');
	$pgid=$_GET['pgid']??null;
	$pgno=$_GET['pgno']??null;
	//printr($_GET);
	if(isset($_GET['action']) and $_GET['action']=="viewatt"){
		$filename = "";
		$filename = getValueForPS("selrec filename from ".$_GET['t']." where id=?","s",pw_dec($_GET['hid']));
		echo "<div class='bq-popup'>
		        <div class='d-flex align-items-center gap-2 '>
		            <span class='file-name' style='max-width:160px;' title='$filename'><b>$filename
		            </span>
		        </div>
        	</div>";		
		displayImageBasedExt($_GET['t'],pw_dec($_GET['hid']));
		exit;
	}	
	// exit;
	if (isset($_GET['action']) && $_GET['action'] === 'Delete') {
		include_once 'bq_list_edit_le_action.php';
		$x_file = array();
		$x_file = getValueForPS("selrec encfilename,filename from ".$_SESSION['currentpage_le']['head']['tablename']." where id=?","s",$_GET['id']);
		if($x_file['encfilename']) @unlink(UPLOAD_DIR_PATH.getUploadFilePath($x_file['encfilename']));
		$sql="delrec from ".$_SESSION['currentpage_le']['head']['tablename']. " where id='".$_GET['id']."'";
		PW_execute($sql);
		echo toast("Deleted..");
		echo "
		<script>
		requestAnimationFrame(() => htmx.trigger('#editPanel_le', 'refresh'));
		</script>";	
		$_SESSION['activepage'] = $_SESSION['currentpage'];
		exit;
	}	
	
	if (isset($_GET['action']) && $_GET['action'] === 'Insert') {
		include_once 'bq_list_edit_le_action.php';
		echo "Inserted
		<script>
		requestAnimationFrame(() => htmx.trigger('#editPanel_le', 'refresh'));
		</script>";
		echo "Inserted";
		$_SESSION['activepage'] = $_SESSION['currentpage'];
		exit;
	}	
	//printr($_GET);
	if (isset($_GET['action']) && $_GET['action'] === 'Update') {
		include_once 'bq_list_edit_le_action.php';
		echo "Updated
		<script>
		requestAnimationFrame(() => htmx.trigger('#editPanel_le', 'refresh'));
		</script>";
		$_SESSION['activepage'] = $_SESSION['currentpage'];
		exit;
	}
	//printr($pgid);
	//printr($_GET);
	$currentPage_le=array();
	$currentPage_le['head']=getValueforPS("selrec * from _pb_pagehead where pgid='".$pgid."' limit 0,1");
	// echo "selrec * from _pb_pagehead where pgid='".$pgid."' limit 0,1";
	$sql="selrec * from _pb_pagefields where pgid=? and status=? order by slno"; 
	$rs  = PW_sql2rsPS($sql,"ss",$currentPage_le['head']['pgid'],'Active');
	while ($ds = PW_fetchAssoc($rs)) {
		$currentPage_le['fields'][$ds['fieldname']]=$ds;
		$tags=strtoupper($currentPage_le['fields'][$ds['fieldname']]['tags']??'');
	}
	$childtablename = getValueForPS("selrec tablename from _pb_pagehead where pgid=?","s",$pgid);
	$currentPage_le['head']['childtablename'] = $childtablename;
	$_SESSION['currentpage_le']=$currentPage_le;
	
	if (isset($_GET['action'])) {
		// Show add new form
		if ($_GET['action'] == "list_le" && empty($_GET['t'])) {
			//$_SESSION['currentpage_le']= setcurrentpageSession($pgid);
			$updatelink1 = "pw=bq_list_table_le.php&action=list_le";
			$linkid= $_GET['linkid']??'';
			$list_leaddnew_url="pw=bq_list_table_le.php&pgid=".$_GET['pgid']."&parenttable=".$_GET['parenttable']."&parentid=".$_GET['parentid']."&parentpgid=".$_GET['parentpgid']."&action=list_le&childtablename=".$_GET['childtablename']."&linkid=".$linkid;
			list_table_rows();
			/*	echo "<div id='editPanel_le' class='w-100 border border-0' style='position:relative;'
						hx-get='do_bq.php?bqkey=".pw_enc($list_leaddnew_url)."'
			     hx-trigger='refresh'
			     hx-target='#editPanel_le'
			     hx-swap='innerHTML'
			     >";*/
    		echo "<div id='editPanel_le' class='w-100 border border-0' style='position:relative;'>";
			edit_table_rows(); // this will show blank form since no rowid
			echo "</div>";
			$_SESSION['activepage']=$_SESSION['currentpage'];
			exit;
		}
		// Show edit form
		if ($_GET['action'] == "edit_le" && isset($_GET['t']) && $_GET['t'] == 1) {
			// printr($_SESSION['currentpage_le']);
			edit_table_rows();
			$_SESSION['activepage']=$_SESSION['currentpage'];
			exit;
		}
	}
	
	function edit_table_rows(){
		// detect if it's blank mode or edit mode
		$id = null;
		if (isset($_GET['rowid']) && $_GET['t'] == 1) {
			$id = $_SESSION['currentpage_le']['meta']['rowids'][$_GET['rowid']] ?? null;
			$id = pw_dec($_GET['hid']);
		}
		$dataDS = [];
		if (!empty($id)) {
			$sql = "selrec * from " . $_SESSION['currentpage_le']['head']['tablename'] . " where id='" . $id . "'";
			$dataDS = getValueForPS($sql);
		}
		// store old data only if editing an existing record
		$_SESSION['currentpage_le']['meta']['olddata'] = $dataDS;
		$_SESSION['activepage'] = $_SESSION['currentpage_le'];	
		include_once("bq_list_edit_controls.php");
		echo makeTabs();
		echo "<div class='p-2' id='edit2'>";  // overall edit div
		echo openForm();
		//added by anjali on 23-12-2025
		if(file_exists("segment/".$_SESSION['currentpage_le']['head']['pgid'].".php")){
			include_once("segment/".$_SESSION['currentpage_le']['head']['pgid'].".php");
			$id = $_GET['parentid'] ?? null;   // define once
			if (function_exists('plx_preEditLoad')) {
			    plx_preEditLoad($_SESSION['currentpage_le']['head']['tablename'],$id);
			}
		} 
		echo "<table class='table table-xsm table-sm table-borderless'>";
		foreach($_SESSION['currentpage_le']['fields'] as $k=>$v){
			$v['prefix']=substr($v['id'],11,100);
			$_SESSION['sufixtofield'][$v['prefix']]=$v['fieldname'];
			if(is_null($v['paraname']))$v['paraname']="";
			if(is_null($v['tabname'])) $v['tabname']="";
			$style="";
		
			if($v['tabname']!="General") $style=" style='display:none' ";
			
			// linked fields must be always in hidden form			
			if(isFoundIn("LINKEDID/LINKEDID2/LINKEDTO/LINKEDTO2",strtoupper($v['fieldname']))){
				$_SESSION['currentpage_le']['fields'][$v['fieldname']]['controltype']=="READ ONLY";
				$v['controltype']="READ ONLY";
			}
			
		//	printr($v);
			if(strtoupper($v['controltype'])=="READ ONLY") echo  cont_text($k,$v);
			if(strtoupper($v['controltype'])=="TEXT BOX" || strtoupper($v['controltype'])=="TEXTBOX") echo  cont_text($k,$v);
			if(strtoupper($v['controltype'])=="SMS") echo  cont_text($k,$v);
			if(strtoupper($v['controltype'])=="EMAIL") echo  cont_text($k,$v);
			if(strtoupper($v['controltype'])=="TEXTAREA" or strtoupper($v['controltype'])=="TEXT AREA" or 
			strtoupper($v['controltype'])==   "TEXTAREA SMALL" or strtoupper($v['controltype'])=="TEXTAREA LARGE") echo  cont_textarea($k,$v);
			if(strtoupper($v['controltype'])=="LABEL") echo  cont_lable($k,$v);
			if(strtoupper($v['controltype'])=="THREAD") echo  cont_thread($k,$v);
			if(strtoupper($v['controltype'])=="GEOLOCATION") echo  cont_geo($k,$v);
			if(strtoupper($v['controltype'])=="GEO ATTENDENCE") echo  cont_geolocation($k,$v);
			if(strtoupper($v['controltype'])=="SIGNATURE"){
			echo	$v['controltype'];
			echo  cont_Signature($k, $v);
			} 
			if(strtoupper($v['controltype'])=="COMBO") echo  cont_Combo($k,$v);
			if(strtoupper($v['controltype'])=="AI CONTROL") echo  cont_Ai($k,$v);

			if(strtoupper($v['controltype'])=="SQL PICKER") echo  cont_SqlPicker($k,$v);
			if(strtoupper($v['controltype'])=="URL") echo  cont_url($k,$v);
			if(strtoupper($v['controltype'])=="MULTI SELECT") echo  cont_multiselect($k,$v);
			if(strtoupper($v['controltype'])=="DATE NORMAL") echo  cont_DateNormal($k,$v);
			if(strtoupper($v['controltype'])=="CHECKLIST") echo  cont_Checklist($k,$v);
			if(isfoundin(($v['tags']),"SEQUENCER") ){
				$pg = $_SESSION['activepage']['head']['pgid'];
				$newSeqNo = getfieldSequencer($v,$pg);
				$v['pbdefault'] = $newSeqNo;
				$v['controltype'] = "READ ONLY";
				echo  cont_text($k,$v);
			}else{
				if(strtoupper($v['controltype'])=="TEXT BOX") cont_text($k,$v);
			}
		}
		if(isset($_GET['action']) && ($_GET['action']=='attach' or $_GET['action']=='edit_le' or $_GET['action']=='list_le')){
			if(isset($_GET['hid']) && !empty($_GET['hid'])){
				if($_GET['action']!='list_le')$id=pw_dec($_GET['hid']);
				if($_GET['action']=='list_le')$id=$_GET['hid'];
				$table = $_SESSION['currentpage_le']['head']['tablename'];
				$style="";
				if($v['tabname']!="General") $style="style='display:none'";				
				//echo "<tr style='display:none' tabname='attach'><td>".displayImageBasedExt($table,$id)."</td></tr>";
				echo "<tr style='display:none;' tabname='attach'><td>".fileupload()."</td></tr>";
			}else{
				echo "<tr style='display:none;' tabname='attach'><td>".fileupload()."</td></tr>";
				//exit;			
			}
		}
		
		echo "</table>";
		//added by anjali on 23-12-2025.
		if(file_exists("segment/".$_SESSION['currentpage_le']['head']['pgid'].".php")){
			include_once("segment/".$_SESSION['currentpage_le']['head']['pgid'].".php");
			$id = $_GET['parentid'] ?? null;   // define once
			if (function_exists('plx_postEditLoad')) {
			    plx_postEditLoad($_SESSION['currentpage_le']['head']['tablename'],$id);
			}
		       } 
		echo "<div id='fileattach'></div>";
		echo "<div name='editerrordiv_le' id='editerrordiv_le' onclick=\"this.style.display='none'\"
    		   style='display:none;font-size:16px;width:98%;min-height:32px;position:absolute;left:0;top:0;z-index:2000;cursor:pointer'></div>";
		
		if(isset($dataDS['id']) && $dataDS['id']!=''){
			//printr($_GET);
			$updatelink="pw=bq_list_table_le.php&action=Update&id=".$dataDS['id'];
/*			$hx_on ="";
			printr($_GET);
			if(isset($_GET) && $_GET['rty']=='php'){
				$url = $_SESSION['landing_page'] ?? '';
				$hx_on = 'hx-on::after-request="htmx.ajax(\'GET\', \''.$url.'\', \'#bqScroller\')"';					
			}*/				
			echo "<button 
		    accesskey='s'
		    type='submit'
		    class='btn btn-primary btn-sm me-2'
		    value='Update'
		    hx-post='do_bq.php?bqkey=".pw_enc($updatelink)."'
		    hx-target= '#editerrordiv_le'
		    hx-swap='innerHTML'>Update</button>";//hx-on=\"htmx:afterRequest: htmx.trigger('body','refreshList')\"
		}
		
		if(empty($dataDS['id'])){
			$insertlink="pw=bq_list_table_le.php&action=Insert";
			echo "<button 
			    type='submit'
			    class='btn btn-primary btn-sm me-2'
			    value='Insert'
			    hx-post='do_bq.php?bqkey=".pw_enc($insertlink)."'
			    hx-target= '#editerrordiv_le'
			    hx-swap='innerHTML'>Insert</button>";//hx-on=\"htmx:afterRequest: htmx.trigger('body','refreshList')\"
		}
		if(isset($dataDS['id']) && $dataDS['id']!=''){
			$deletelink="pw=bq_list_table_le.php&action=Delete&id=".$dataDS['id'];
			echo "<button 
		    type='submit'
		    class='btn btn-danger btn-sm me-2'
		    value='Delete'
		    hx-post='do_bq.php?bqkey=".pw_enc($deletelink)."'
		    hx-target= '#editerrordiv_le'
		    hx-swap='innerHTML'
		    hx-confirm='Are you sure you want to delete this record?''
		   >Delete</button>";// hx-on=\"htmx:afterRequest: htmx.trigger('body','refreshList')\"
		} 
		echo "</form>";
		
		echo "</div>
		<div name='editerrordiv_le' id='editerrordiv_le' onclick=\"this.style.display='none'\"
    		   style='display:none;font-size:16px;width:98%;min-height:32px;position:absolute;left:0;top:0;z-index:2000;cursor:pointer'></div>
		";	 // overall edit div - end	
	}
	function list_table_rows(){
		$basefilter = $_SESSION['currentpage_le']['head']['basefilter'];
		$fieldcaptions=array();
		$listfields = $_SESSION['currentpage_le']['head']['listfields'];
		$listfields = trim(str_replace(" ","",$listfields));
		$fields = explode(",",$listfields);
		foreach($fields as $field) {
			$fieldcaptions[] = $_SESSION['currentpage_le']['fields'][$field]['caption']??null;
		}
		$listfields = $_SESSION['currentpage_le']['head']['listfields'];
		$listfields = trim(str_replace(" ","",$listfields));
		$fields = explode(",",$listfields);
		foreach($fields as $field) {
			$fieldcaptions[] = $_SESSION['currentpage_le']['fields'][$field]['caption']??null;
		}
		$fieldsCount = count($fieldcaptions);
		//hx-target='#editPanel'
	    if($_SESSION['designmode']=="on"){
	    	$le_formsetup = "";
	    	// $le_formsetup = "<i class='ms-2 bi bi-gear' // hx-get='do_bq.php?bqkey=".$pagesetupurl."' // hx-target='#pageList' // hx-swap='innerHTML' // onclick= 'setSideWidth(\"30%\")'></i>&nbsp;";
			//$pagesetupurl = pw_enc("pw=bq_fw_pagesetup.php&action=list_le");
			$pagesetupurl = pw_enc("pw=bq_anitha.php&action=list_le&pgid=".$_SESSION['currentpage_le']['head']['pgid']."&tablename=".$_SESSION['currentpage_le']['head']['tablename']."&chid=".$_SESSION['currentpage_le']['head']['id']);
			//$pagesetupurl = pw_enc("pw=do_bqshell.php&from=list_le");
			// 	$le_formsetup = "
			// <div style='position:relative; display:inline-block;'>
			
			
			//     <span id='menus_le' accesskey='x'
			//         hx-get='do_bq.php?bqkey=".$pagesetupurl."'
			//         hx-target='#pageList_le'
			//         hx-swap='innerHTML'
			//         onclick=\"toggleDisplay('pageList_le');closeAllPops()\">
			
			//         <i class='bi bi-gear'></i>
			//     </span>
			
			// </div>
			
			// <div id='pageList_le'
			//      onclick=\"closeAllPops();hidediv('pageList_le');\"
			//      class='bq-moreaction'
			//      style='z-index:7000;
			//             position:absolute;
			//             top:37px;
			//             left:2px;
			//             width:200px;
			//             max-height:400px;
			//             overflow-y:auto;
			//             display:none;
			//             background-color:white;'>
			//             </div>
			// ";
	    }
		// add new button in the list edit form plx_preList logic added by anjali on 22-12-2025
		$list_leaddnew_url = $linkid = $src = "" ;
		if($_GET['pgid']=='pb_pagelinks') $src = "&src=pagesetup";
		$linkid= $_GET['linkid']??'';
		$list_leaddnew_url="pw=bq_list_table_le.php&pgid=".$_GET['pgid']."&parenttable=".$_GET['parenttable']."&parentid=".$_GET['parentid']."&parentpgid=".$_GET['parentpgid']."&action=list_le&childtablename=".$_GET['childtablename']."&linkid=".$linkid."".$src;
		//echo $list_leaddnew_url;
		$bqkey_listle=pw_enc($list_leaddnew_url);
		$aichecklist="pw=bq_ai_popup.php&pgid=".$_GET['pgid']."&parenttable=".$_GET['parenttable']."&parentid=".$_GET['parentid']."&parentpgid=".$_GET['parentpgid']."&action=checklist&childtablename=".$_GET['childtablename']."&caption=Checklist&linkid=".$linkid."".$src;
		$bqkey_aichecklist=pw_enc($aichecklist);

		$multiattach_link = "";
		if(isset($_GET['pgid']) && $_GET['pgid']=='_pb_attachments'){
			$multiattach_link = "";
			$multiattach_link ='<button id="multiattachments" onclick="closeAllPops();"  title = "Click to Add Multi Attachments" hx-get = "do_bq.php" hx-target	= "#editPanel"	hx-swap	= "innerHTML" hx-vals= \'{"bqkey":"'.pw_enc("pw=bq_multiattachments.php&action=attach&hid=".$_GET['parentid']).'"}\' class="btn btn-primary btn-sm" style="padding: 2px 6px; font-size: 12px;"> Add Files</button>';
		}
		/* style='display:flex; gap:6px;'
		if(isset($_GET['pgid']) && $_GET['pgid']=='_pb_attachdefinition'){
			$multiattach_link = "";
			$multiattach_link ='<button id="multiattachments" onclick="closeAllPops();"  title = "Click to Add Multi Attachments" hx-get = "do_bq.php" hx-target	= "#editPanel"	hx-swap	= "innerHTML" hx-vals= \'{"bqkey":"'.pw_enc("pw=bq_multiattachments.php&action=attach&hid=".$_GET['parentid']).'"}\' class="btn btn-primary btn-sm" style="padding: 2px 6px; font-size: 12px;"> Add Files</button>';
		}*/
		//$str .="(".$_SESSION['currentpage_le']['head']['pgid'].")".$le_formsetup;
		$str = "";
		$str = "<div id='listdiv_le' class='p-0 xborder-4 mb-0 border-bottom border-1' style='height:160px;overflow-x:auto' >
				  <table style='width:100%' class='table table-bordered table-sm'>
					<tr>
						<th class='bq-ellipse' colspan=5 title='".$_SESSION['currentpage_le']['head']['pgid']."'>
						<div style='display:flex; justify-content:space-between; align-items:center; width:100%;flex-wrap: wrap;'>
							<!-- Left side -->
							<div>
								".$_SESSION['currentpage_le']['head']['caption']."";
		if(strtoupper($_SESSION['designmode'])=="ON")	$str .="(".$_SESSION['currentpage_le']['head']['pgid'].")";
		$str .="</div>";
		if($_SESSION['currentpage_le']['head']['pgid']=='_pb_templates'){
			$tempfield=getValueForPS("selrec id  from _pb_pagefields where fieldname='template'  and pgid='_pb_templates' ");
			$v['prefix']=substr($tempfield,11,100);
			$templatepopup = pw_enc("pw=bq_pagesetup_utils.php&fld=txa_".$v['prefix']."&action=templatepopup&caption=Template");

			$str .= "<div>
			<button class='btn btn-primary btn-sm'
			hx-get='do_bq.php?bqkey=$templatepopup'
			hx-target='#allpops'
			title='AI Template'
			onclick='showdiv(\"allpops\");setPopAll(62,129,850,483);'
			style='padding:2px 8px; font-size:12px;'
			>AI</button>
			</div>";
		}
		if($_SESSION['currentpage_le']['head']['pgid']=='_pb_checklist1'){
			$str .= "<div>
			<button class='btn btn-primary btn-sm'
			hx-get='do_bq.php?bqkey=$bqkey_aichecklist'
			hx-target='#allpops'
			onclick='showdiv(\"allpops\");setPopAll(62,129,850,483);'
			style='padding:2px 8px; font-size:12px;' title='Click to AI Checklist'
			>AI</button>
			</div>";
		}
		if($_SESSION['currentpage_le']['head']['pgid']=='_pb_swflow'){
			include_once("bq_utils_ai_functions.php");
			$formcaption=$_SESSION['activepage']['head']['caption'];
			$formmodule=$_SESSION['activepage']['head']['module'];
			$formpgid=$_SESSION['activepage']['head']['pgid'];
			$formtb=$_SESSION['activepage']['head']['tablename'];
			//$stageCount = rand(3, 6);
			//printr($formpgid);
			// $promptaiwf="Create a relevant workflow of 3 stages to demonstrate for the form '".$formcaption."' which is part of module '".$formmodule."' in stages: stage1 (save,sendback,forward), stage2 (sendback,forward,hold), stage3 (sendback,approve,reject). give output as sentence.strict rule: Don't give any explanation for save,sendback,forward,hold,approve,reject";
			// $promptaiwf = "Create a relevant 3-stage workflow for the form '".$formcaption."' in module '".$formmodule."'. For each stage, provide a brief description of the stage purpose, and explicitly list the allowed actions exactly as: Stage 1 (save, forward), Stage 2 (sendback, forward, hold), Stage 3 (sendback, approve, reject). Do not explain or describe what any action does. Do not provide outputs for actions. Output as sentences.";
			// $promptaiwf = "Create a Beeq ".$formcaption." workflow with '".$stageCount."' stages for the form '".$formcaption."' in module '".$formmodule."'. The output must start exactly with: Create a Beeq ".$formcaption." workflow with '".$stageCount."' stages:. Use exactly these allowed actions per stage and do not add, remove, or change any actions: Stage 1 allowed actions: [save, forward]; Stage 2 allowed actions: [sendback, forward, hold]; Stage 3 allowed actions: [sendback, approve, reject]. For each stage, include: Stage X: <Stage Name> <one short sentence>. Allowed actions must be listed only as the exact bracketed lists given above, with no extra actions and no descriptions. Do not explain or define any actions. Do not use 'Here is' or 'Here’s'.";
			/*$promptaiwf = "Create a Beeq ".$formcaption." workflow with '".$stageCount."' stages for the form '".$formcaption."' in module '".$formmodule."'. The output must start exactly with: Create a Beeq ".$formcaption." workflow with '".$stageCount."' stages:. Follow these strict rules for actions: Stage 1 allowed actions must be exactly: [save, forward]. Final Stage (Stage ".$stageCount.") allowed actions must be exactly: [sendback, approve, reject]. All intermediate stages (Stage 2 to Stage ".($stageCount - 1).") allowed actions must be exactly: [save, forward, sendback]. Do not add, remove, or change any actions. For each stage, include: Stage X: <Stage Name> <one short sentence>. Allowed actions must be listed only as the exact bracketed lists given above, with no extra actions and no descriptions. Do not explain or define any actions. Do not use 'Here is' or 'Here’s'.";*/
			$stageCount = 5; // or rand(3,5)

$promptaiwf = "You are a Beeq Workflow Generator operating in STRICT-FORMAT mode.

INPUT:
- form_name: '".$formcaption."'
- module_name: '".$formmodule."'

GOAL:
Generate a Beeq workflow description.

CRITICAL NON-NEGOTIABLE RULES:

1. The output MUST start exactly with:
Create a Beeq '".$formcaption."' workflow with '".$stageCount."' stages:

2. Output must be ONE paragraph only.

3. Each stage must be written in this format:
Stage X <Stage Name> where <short explanation> and can <Action1> or <Action2> or <Action3>.

4. Use ONLY these exact action keywords (case-sensitive, no extra text around them):
Save, Send Forward, Send Back, Approve, Reject, Post, Hold.

5. Stage action rules (MANDATORY):
- Stage 1 MUST include 'Send Forward' and MAY include 'Save' .
- Stage 1 MUST NOT include 'Send Back', 'Approve', or 'Reject'.
- Stages 2 to ".($stageCount - 1)." MUST include 'Send Forward' and MAY include 'Send Back', or 'Save'.
- Final Stage (Stage ".$stageCount.") MUST NOT include 'Send Forward' and MAY include 'Send Back', 'Approve', 'Reject', 'Hold', or 'Save'.

6. Do NOT repeat the header.
7. Do NOT use square brackets.
8. Do NOT use commas between actions, only 'or'.
9. Do NOT add recipients or destinations to actions (no 'to Sales Team', no 'to Customer').

If any rule is violated, the output is INVALID. Regenerate internally until valid.

Generate now.";


			 $aiWFprompt=groqMistral($promptaiwf, "");
			 //printr($promptaiwf);
			$bqformlinkaiwflow=pw_enc("pw=bq_ai_tools_set_workflow.php&pgid=_pb_swflow&parenttable=".$_SESSION['currentpage']['head']['tablename']."&parentid=".$_GET['parentid']."&parentpgid=".$_SESSION['currentpage']['head']['pgid']."&src=pagesetup&action=list_le&childtablename=".$_GET['childtablename']."&linkid=".$linkid."&wfaiprompt=".$aiWFprompt);
			$str .= "<div>
			<button class='btn btn-primary btn-sm'
			hx-get='do_bq.php?bqkey=$bqformlinkaiwflow'
			hx-target='#allpops'
			onclick='showdiv(\"allpops\");setPopAll(62,129,850,483);'
			style='padding:2px 8px; font-size:12px;' title='AI Workflow'
			>AI</button>
			</div>";
		}
		// <!-- Right side (buttons) -->
		$str .= "<div>
					<button class='btn btn-primary btn-sm'
						hx-get='do_bq.php?bqkey=$bqkey_listle'
						hx-target='#editPanel'
						hx-swap='innerHTML'
						onclick='closeAllPops();'
						style='padding:2px 8px; font-size:12px;' title='Click to Add New Record'
						>Add New</button>
						".$multiattach_link."
				</div>
			</div>
			</th>
		</tr>";
		if(file_exists("segment/".$_SESSION['currentpage_le']['head']['pgid'].".php")){
			include_once("segment/".$_SESSION['currentpage_le']['head']['pgid'].".php");
			if(function_exists('plx_preList')) {
				plx_preList($_SESSION['currentpage_le']['head']);
			}
		}
		
			$str.="<tr>";
			for ($k = 0; $k < 4; $k++) { // number of list fields to display
				if($k>0)$str .= "<th class='bg-light' style='width:10%'>".$fieldcaptions[$k]."</th>";
			}
			
			$str .= "<th class='bg-light' style='width:1%' nowrap colspan=2>#</th></tr>";  // showing captions done
			if(!empty($_SESSION['currentpage_le']['head']['basefilter'])){
				$basefilter = $_SESSION['currentpage_le']['head']['basefilter'];
			}else{
				//$basefilter = $_SESSION['activepage']['head']['basefilter'];
				$basefilter = " 1 ";
			}
			if(isset($_SESSION['currentpage_le']['head']['baseorder'])){
				$baseorder = $_SESSION['currentpage_le']['head']['baseorder'];
			}else{
				// $baseorder = $_SESSION['activepage']['head']['baseorder'];
				$baseorder = " id";
			}
			$tablename = $_SESSION['currentpage_le']['head']['childtablename'];
			if(isset($_GET['childtablename']))$tablename = $_GET['childtablename'];
			if(trim($basefilter)=="" or trim($basefilter)=="...") $basefilter=" 1 ";
			//printr($basefilter);
			$parenttablename = "";
			$parenttablename = $_GET['parenttable'];
		//	printr($_GET);
			if(isset($_GET['src']) && $_GET['src']=='pagesetup' && isset($_GET['pgid']) && $_GET['pgid']!='_pb_swflow'){
				$parenttablename = "_pb_pagehead";$tablename = "_pb_pagelinks";
			}
			if($_GET['childtablename'] =='_pb_templates'){
				//$parenttablename = $_GET['parenttable'];
				$tablename = $_GET['childtablename'];
			}
			if($_GET['childtablename'] =='_pb_attachdefinition'){
				//$parenttablename = $_GET['parenttable'];
				$tablename = $_GET['childtablename'];
				$parenttablename = $_GET['parenttable'];
			}
			if($_GET['childtablename'] =='_pb_child' and $_GET['pgid'] =='_pb_triggers'){
				//$parenttablename = $_GET['parenttable'];
				$tablename = $_GET['childtablename'];
			}
			if($_GET['childtablename'] =='_pb_lookups' and $_GET['pgid'] =='_pb_checklist1'){
				//$parenttablename = $_GET['parenttable'];
				$tablename = $_GET['childtablename'];
			}
			
			$sql="selrec ".$_SESSION['currentpage_le']['head']['listfields'].",encfilename from ".$tablename. " where (linkedto=? and linkedid=?) and ".$basefilter."  order by ".$baseorder ."";
			//echo "selrec ".$_SESSION['currentpage_le']['head']['listfields'].",encfilename from ".$tablename. " where (linkedto='".$parenttablename."' and linkedid='".$_GET['parentid']."') and ".$basefilter."  order by ".$baseorder ."";
			//$tablename = "_pb_templates";
			//$sql="selrec * from ".$tablename." where (linkedto=? and linkedid=?) and ".$basefilter."  order by ".$baseorder ."";
			//echo $sql;
			//echo date('H:i:s').$sql;//Check Point
			//echo $tablename."====".$parenttablename."===".$_GET['parentid'];
			//echo $parenttablename."xxx".$_GET['parentid'];
			$rs = PW_sql2rsPS($sql,"ss",$parenttablename,$_GET['parentid']);
			$rowno=0;
			$title="";$bqfulledit ="";
			while($ds=PW_fetchArray($rs)){
				$_SESSION['currentpage_le']['meta']['rowids'][$rowno]=$ds['id'];
				if(strtoupper($_SESSION['designmode'])=="ON")$bqfulledit=pw_enc("pw=bq_list_fulledit.php&rty=fulledit&src=listle&tb=".$tablename."&hid=".$ds['id']."");
				$bqkey=pw_enc("pw=bq_list_table_le.php&pgid=".$_GET['pgid']."&rowid=".$rowno."&hid=".pw_enc($ds['id'])."&action=edit_le&t=1");
				$title="";
				if(isset($ds['4']) and $ds['4']!="")$title=$fieldcaptions[4].":".$ds['4'];
				if(isset($ds['5']) and $ds['5']!="")$title="\n".$fieldcaptions[5];
				if(isset($ds['6']) and $ds['6']!="")$title="\n".$fieldcaptions[6];
				$str .= '<tr>
							<td  title="Click to edit"  style="max-width:px;cursor:pointer" hx-get="do_bq.php?bqkey='.$bqkey.'"
							hx-target="#editPanel_le" hx-swap="innerHTML">'.safeStr($ds[1]).'</td>
							<td class="bq-ellipse2" style="max-width:10px;">'.safeStr($ds[2]).'</td>
							<td class="bq-ellipse2" style="max-width:10px">'.safeStr($ds[3]).'</td>';
					
				if(strtoupper($_SESSION['designmode'])=="ON"){
					$str .= "<td width='1%' nowrap hx-get='do_bq.php?bqkey=".$bqfulledit."' hx-target='#editPanel' hx-swap='innerHTML' style='padding:5px;'><i class='bi bi-pencil fs-6 m-2' title='Full Edit'></i></td>";
				}					
				if(!empty($ds['encfilename'])){
					$ext = strtoupper(pathinfo(basename($ds['encfilename']), PATHINFO_EXTENSION));
					// Bootstrap icon mapping
					$iconClass = "bi-file-earmark"; // default icon    	
					switch($ext) {
						case 'JPG':
						case 'JPEG':
						case 'PNG':
						case 'GIF':
						case 'BMP':
						$iconClass = "bi-image"; 
						break;
						case 'PDF':
						$iconClass = "bi-file-earmark-pdf"; 
						break;
						case 'XLS':
						case 'XLSX':
						$iconClass = "bi-file-earmark-excel"; 
						break;
						case 'DOC':
						case 'DOCX':
						$iconClass = "bi-file-earmark-word"; 
						break;
						case 'PPT':
						case 'PPTX':
						$iconClass = "bi-file-earmark-ppt"; 
						break;
						case 'TXT':
						case 'VTT':
						$iconClass = "bi-file-earmark-text"; 
						break;
						default:
						$iconClass = "bi-file-earmark"; 
					}
					// If image type
					if(in_array($ext, ['JPG','JPEG','PNG','GIF','BMP'])) {
						$bqkey=pw_enc("pw=bq_list_table_le.php&action=viewatt&t=".$tablename."&rowid=".$rowno."&hid=".pw_enc($ds['id']));
						$lineImage = 
						"<i class='bi $iconClass' fs-4
						hx-get='do_bq.php?bqkey=\"$bqkey\"'
						hx-target=\"#allpops\"
						hx-swap=\"innerHTML\"
						onclick='showdiv(\"allpops\");setPopAll(100,80,900,500);'
						></i>";
					} else {
						// Other file types
						$bqkey=pw_enc("pw=bq_list_table_le.php&action=viewatt&t=".$tablename."&rowid=".$rowno."&hid=".pw_enc($ds['id']));
						$lineImage = 
						"<i class='bi $iconClass' fs-4
						hx-get='do_bq.php?bqkey=\"$bqkey\"'
						hx-target=\"#allpops\"
						hx-swap=\"innerHTML\"
						onclick='showdiv(\"allpops\");setPopAll(100,80,900,500);'
						></i>";
					}
					if(isset($ds['txt1']) and $ds['txt1']=='Esign Generated'){
						$signclass='<i class="bi bi-shield-check text-success" title="E-Signed"></i>';
					}
					else{
						$signclass="";
					}
					$str .= "<td width='1%' nowrap >".$lineImage." ".$signclass.'</td>';
				}
					$str .= '</tr>';
					$rowno++;
			}
			$str .= "</table></div>";
			echo $str;
			//added by anjali on 23-12-2025.
		if(file_exists("segment/".$_SESSION['currentpage_le']['head']['pgid'].".php")){
			include_once("segment/".$_SESSION['currentpage_le']['head']['pgid'].".php");
			if(function_exists('plx_postList')) {
				plx_postList($_SESSION['currentpage_le']['head']);
			 }
		}
		//echo "<div id='editPanel_le' class='w-100 border border-0'></div>";
	}	
	
?>
