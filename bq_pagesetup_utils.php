<?php
 //it can take the field name action and perform the respective thingsvarious aspects of the page setuphello this is a general utility code for setting up
	// user is pinning a form from lsit page top drop doen
	// setstatus
	// printr($_GET);
	
	//  USER TEXT AREA TEMPLATE POPUP
	
	
	
	if(isset($_GET['action']) and $_GET['action']=="aipop"){
	
		include_once("bq_utils_ai_functions.php");
		$prompt = $_POST['aicontolprompt'] ?? '';
		 $res    = groqMistral($prompt, "");   // raw AI result
		 $cleanRes =  preg_replace('/<\/?strong>/i', '', $res);

	    echo "<div><script>
	        document.getElementById('aicontroltxt').value = " . json_encode($cleanRes) . ";
	    </script></div>";
		exit;
	}
	if(isset($_GET['action']) and $_GET['action']=="aitemplate"){
/*		printr($_GET);
		printr($_POST);*/
		
		$pgid=$_SESSION['currentpage']['head']['pgid'];
		//$table=$_SESSION['currentpage']['head']['tablename'];
		$id=$_SESSION['currentpage']['head']['id'];
		$sql="selrec fieldname,caption from _pb_pagefields where pgid='".$pgid."' and status='Active'";
		$rs = PW_sql2rsPS($sql);
		$arr=[];
		$table = getValueForPS("selrec tablename from _pb_pagehead where pgid=?","s",$pgid);
		if(isset($id) && !empty($id))$dsdata=getValueForPS("selrec * from ".$table. " where id='".$id."'");
		//echo "selrec * from ".$table. " where id='".$id."'";
		$str="for the place holders see below placeholder:fieldname. You must fill the fieldname in place holder.";
		while($ds=PW_fetchAssoc($rs)){
			$arr[$ds['caption']]=$ds['fieldname'];
			//$str.=$ds['fieldname'].":".$dsdata[$ds['fieldname']];//$ds['caption']."; ";
			$field = $ds['fieldname'] ?? '';
			$value = $dsdata[$field] ?? '';   // safe fallback
			$str .= $field . ":" . $value;			
		}
	
		// printr($arr);
		include_once("bq_utils_ai_functions.php");
		// $prompt= "You are a friend and have 
		// - to generate the business activity letters, invoice formats as per request in pure text 
		// - no preamble text
		// - no meta and no html and no Markdown), 
		// - strinctly no <strong> or no html tags. 
		// - Give meaningful place holders with [ ], 
		// - on top give [COMPANYNAME] left and [date] on right no other letter head details which i will fill.
		// - ".$str."
		// - no other template elements exceot from the prompt i wanted
		// - the template is for ".$_POST['temlateprompt']."'";
		
		$prompt="

You are a friend and have
- to generate the business activity letters, invoice formats as per request in pure text
- no preamble text
- no meta and no html and no Markdown),
- strinctly no or no html tags.
- Give meaningful place holders with [ ],
- PLACEHOLDER FORMAT RULE (STRICT):
	Placeholders must be written ONLY as [fieldname]
	Do NOT include labels, captions, or descriptions inside placeholders
	Do NOT use colon (:) inside placeholders
	Labels are for understanding only, NOT for output
	Example:
	WRONG: [Sales Engineer:salespersonname]
	CORRECT: [salespersonname]
- on top give [COMPANYNAME] left and [date] on right no other letter head details which i will fill.
- for the place holders see below placeholder:fieldname. You must fill the fieldname in place holder.
".$str."
- no other template elements except from the prompt i wanted
-the template is for ".$_POST['temlateprompt']."'";
//- the template is for develop an appointment letter for senior executive
		
		//echo nl2br($prompt);
		$res = json_encode($pgid."/".groqMistral($prompt,""));
		//echo $pgid. $res;
		echo "<script>
		// v=document.getElementById('templateres').innerHTML
		document.getElementById('temptxt').value=".$res."
		</script>
		";
		
		exit;
	}
	if(isset($_GET['action']) and $_GET['action']=="showsession"){
		checkDesignMode();
	}
	if(isset($_GET['action']) and $_GET['action']=="showsession"){
		checkDesignMode();
		echo "<div class='m-2 p-0 border border-0' style='width:450px'>";
		printr($_SESSION);
		echo "</div>";
		exit;
	}
	if(isset($_GET['action']) and $_GET['action']=="showsession"){
		checkDesignMode();
		echo "<div class='m-2 p-0 border border-0' style='width:450px'>";
		printr($_SESSION);
		echo "</div>";
		echo "SSSSSSSS";
		exit;
	}
	if(isset($_GET['action']) and $_GET['action']=="setstatus"){
		$status="Active";
		if($_GET['status']=="Active") $status='Inactive';
		$bqswitch='do_bq.php?bqkey='.pw_enc("pw=bq_pagesetup_utils.php&action=setstatus&table=".$_GET['table']."&status=".$status."&id=".$_GET['id']).'"';

		$ds=getValueForPS("selrec id,status from  ".$_GET['table']." where id='".$_GET['id']."'");
		if($_GET['status']=="Active"){
			echo '<i class="primary fs-5 bi bi-toggle-off  text-secondary ms-2"
			hx-get='.$bqswitch.'
					hx-target=""
					hx-swap="outerHTML"
					></i>';
			$ds['status']="Inactive";
		}else{
			echo '<i class="primary fs-5 bi bi-toggle-on  text-primary ms-2"
			hx-get='.$bqswitch.'
					hx-target=""
					hx-swap="outerHTML"
					></i>';
			$ds['status']="Active";
		}
		updateRecord($ds,$_GET['table']);
		toastfade('Record status updated',"mainContent",0,0);
		exit;
	} 
	if(isset($_GET['action']) and $_GET['action']=="textpopup"){
		echo "<div>
			<div class='bq-head bq-popup'><b>".hsc($_GET['caption'])."</b></div>
				  <div class='flex-fill d-flex flex-column mt-1'>
				  <textarea id='poptxt' class='mt-1 p-2 border rounded flex-fill'  rows=17></textarea>
					<button class='mt-1 btn btn-sm btn-primary border' onclick='txtpushback();closeAllPops()' accesskey='v' >Save back (alt+v)</button>
				</div>
			</div>
				<script>
					o=document.getElementById('".$_GET['fld']."')
					v=o.value
					o=document.getElementById('poptxt')
					if(o)o.value=v
					function txtpushback(){
						v=document.getElementById('poptxt').value
						o=document.getElementById('".$_GET['fld']."')
						o.value=v;
					}
				</script>";
		exit;
	}
	
	if(isset($_GET['action']) and $_GET['action']=="templatepopup"){
//	printr($_GET);
		include_once("bq_utils_ai_functions.php");
		$caption = $_SESSION['currentpage']['head']['caption'] ?? 'content';
		$module = $_SESSION['activepage']['head']['module'];
		//$template = "Develop a professional template for {$caption}.";
		$template = "Generate exactly one meaningful and professional prompt sentence that instructs how to build a communication template for the '".$caption."' form in the '".$module."' module; the output must be a single sentence only and should clearly mention the key details the template should include, with no extra text.";

		$aitempprompt=groqMistral($template, "");
		echo "<div>
			<div class='bq-head bq-popup'><b>".hsc($_GET['caption'])."</b></div>
				  <div class='flex-fill d-flex flex-column mt-1'>
						<div class='d-flex'>
							
							<textarea id='temlateprompt' name='temlateprompt'class='mt-1 p-2 border rounded flex-fill' >".$aitempprompt."</textarea>
							<button class='mt-1 btn btn-sm btn-primary border' 
							hx-post='do_bq/".pw_enc("pw=bq_pagesetup_utils.php&action=aitemplate")."'
							hx-target='#templateres'
							hx-include='#temlateprompt, #temptxt'
							hx-swap='innerHTML'>Get template</button>
						</div>
					  <textarea id='temptxt' name='temptxt' class='mt-1 p-2 border rounded flex-fill'  rows=15></textarea>
					  <div class='d-flex'>
							<button class='mt-1 btn btn-sm btn-primary border' onclick='txtpushback();closeAllPops()' accesskey='v' >Save back (alt+v)</button>
							
						</div>
					<div id='templateres'></div>
				</div>
			</div>
				<script>
					o=document.getElementById('".$_GET['fld']."')
					v=o.value
					o=document.getElementById('temptxt')
					if(o)o.value=v
					function txtpushback(){
						v=document.getElementById('temptxt').value
						o=document.getElementById('".$_GET['fld']."')
						o.value=v;
					}
				</script>";
		exit;
	}
	if(isset($_GET['action']) and $_GET['action']=="aicontrolpopup"){
		echo "<div>
			<div class='bq-head bq-popup'><b>".hsc($_GET['caption'])."</b></div>
				  <div class='flex-fill d-flex flex-column mt-1'>
						<div class='d-flex'>
							<input value='".$_GET['combosql']."' id='aicontolprompt' name='aicontolprompt' class='mt-1 p-2 border rounded flex-fill'>
							<button class='mt-1 btn btn-sm btn-primary border' 
							hx-post='do_bq/".pw_enc("pw=bq_pagesetup_utils.php&action=aipop")."'
							hx-target='#aicontrolres'
							hx-include='#aicontolprompt, #aicontroltxt'
							hx-swap='innerHTML'>Proceed</button>
						</div>
					  <textarea id='aicontroltxt' name='aicontroltxt' class='mt-1 p-2 border rounded flex-fill'  rows=15></textarea>
					  <div class='d-flex'>
							<button class='mt-1 btn btn-sm btn-primary border' onclick='txtpushback();closeAllPops()' accesskey='v' >Save back (alt+v)</button>
							
						</div>
					<div id='aicontrolres'></div>
				</div>
			</div>
				<script>
					o=document.getElementById('".$_GET['fld']."')
					v=o.value
					o=document.getElementById('aicontroltxt')
					if(o)o.value=v
					function txtpushback(){
						v=document.getElementById('aicontroltxt').value
						o=document.getElementById('".$_GET['fld']."')
						o.value=v;
					}
				</script>";
		exit;
	}
	
	if(isset($_GET['action']) and $_GET['action']=="pinform"){
		if(!isset($_SESSION['pins'])) $_SESSION['pins']="";
		echo ".";
		$_SESSION['pins']=str_replace($_GET['pgid'],"",$_SESSION['pins']);// remove if exists
		$_SESSION['pins']=$_GET['pgid'].",".$_SESSION['pins']; // add the pgid
		$_SESSION['pins']=str_replace(",,",",",$_SESSION['pins']); // remove double comma
		$_SESSION['pins']=str_replace(",,",",",$_SESSION['pins']); // remove double comma
		$temp=explode(",",$_SESSION['pins']);
		$str="";
		$i=0;
		foreach($temp as $t){
			$i++;
			if($i<7 and strlen($t)>4){
				$caption=getvalueforps("selrec caption from _pb_pagehead where pgid=?","s",$t);
				$bqformlink = pw_enc("pw=do_bqshell.php&pgid=".$t);
				if($caption!="")$str.="	<a class='btn btn-white btn-sm' hx-get='do_bq.php?bqkey=".$bqformlink."' hx-target='#mainContent' hx-swap='innerHTML' ><b>".$caption."</b></a>";
			}
		}
		$formcaption = $_GET['caption'];
		echo "<script>
		  (function() {
		    var px = document.getElementById('pins');
		    if (!px) return;
		    px.innerHTML = " . json_encode($str) . ";
		    if (window.htmx && htmx.process) { htmx.process(px); } // bind hx-* on new nodes
		  })();
		</script>";
		echo toastfade("{$formcaption} form is pinned", "editPanel");
		exit;
	}
	if(isset($_GET['action']) and $_GET['action']=="aihelp"){
		
		// generates AI help for the given form
		$thisPage = setcurrentpageSession($_GET['pgid']);
		include_once("bq_utils_ai_functions.php");
		$captions="";
		if(!isset($thisPage['fields'])){
			echo toast("Please define the fields for the form","danger");
			exit;
		}
		foreach($thisPage['fields'] as $field){
			$captions.="fieldname:".$field['fieldname']."; caption:".$field['caption']."; id:".$field['id'].". ";
		}

$prompt =
"You are an expert in understanding enterprise data-entry forms.

TASK
Generate clear, user-friendly help text for:
1) the overall form (pagehead.help), and
2) each field (fields[].help)

INPUTS
- Form id: '{$thisPage['head']['id']}'
- Form caption: '{$thisPage['head']['caption']}'
- Field captions (comma-separated): '{$captions}'

RULES
- Use the form caption to infer the form’s purpose and context.
- pagehead.help: 3 to 5 short sentences describing what the form is for, who uses it, and any key cautions (validation, workflow, approvals, privacy).
- fields[].help: 2 to 3 short sentences per field explaining what to enter, expected format/examples, and any important rules (mandatory, uniqueness, dependencies). If a field is unclear, write safe generic guidance without guessing specific business rules.
- Keep language simple, action-oriented, and consistent across fields.
- Do not include markdown, HTML, or special formatting.

OUTPUT FORMAT (STRICT)
You MUST output ONLY valid JSON.
NO text before the JSON.
NO text after the JSON.
NO explanations, NO comments, NO code fences.
Your output MUST start with '{' or '[' and MUST end with '}' or ']'.
If you cannot provide JSON, output '[]'.

JSON SCHEMA
{
  \"pagehead\": {
    \"id\": \"<form id>\",
    \"caption\": \"<form caption>\",
    \"help\": \"<form help>\"
  },
  \"fields\": [
    {
      \"id\": \"<field id or empty string if unknown>\",
      \"fieldname\": \"<fieldname or derived from caption>\",
      \"caption\": \"<field caption>\",
      \"help\": \"<field help>\"
    }
  ]
}

IMPORTANT NOTES
- The top-level keys must be exactly: pagehead, fields.
- Do not miss any field caption from the input list.
- If field id / fieldname are not available, use \"\" for id and derive a reasonable fieldname from the caption (lowercase, underscores, no spaces).";


		$result = groqMistral($prompt,"");
		$arr=json_decode($result,true);
		$a=$arr['pagehead'];
		
		
		$formhelp="";
		foreach($arr['fields'] as $a){
			$formhelp.=$a['caption'].", ";
		}
				echo toast("<h5>".$a['caption']."</h5>".$a['help'],"danger");

		$result = groqMistral("These captions belong to a form collecting data.  Based on the data collection data captions, understand the overall functioning of the form and give a about 80 words help. let the help like explanation and not as bullets. Just give the help as you understood without any pre or post comments. avoid preamble like 'The form appears to be designed for task management and automation' etc. avoid like 'appears to be ' instead of explaining more about fields try to understand the gist of the form. The reply must be in pure text where required with new line.  The data captions are:",$formhelp);
		// echo $formhelp;
		$result = preg_replace('/<br\s*\/?>/i', "\n", $result);

		echo toast("<b>Suggested help</b>".
		    "<textarea id='ai_help_text' class='form-control' rows='6'>".$result."</textarea>"
		    );
		
		
		echo "<button id='aihelpfull' class='btn btn-white btn-sm' hx-post='do_bq.php?bqkey=".pw_enc("pw=bq_pagesetup_utils.php&action=fullhelp&pgid=".$_GET['pgid']."&help=".($result))."' hx-target='#aihelpfull' ".$result."' hx-swap='innerHTML'>Save: Form full help <i class='bi bi-arrow-right-circle fs-5' ></i></button>";
		
		
		foreach($arr['fields'] as $a){
			echo toast(
				"<H6>".$a['caption']."</H6>".
				"<textarea id='help_".$a['id']."' class='form-control' rows='4'>".$a['help'].
				"</textarea>"
			);
			echo "<button id='div_".$a['id']."' class='btn btn-white btn-sm' hx-get='do_bq.php' hx-vals='js:{
		     bqkey: \"".pw_enc("pw=bq_pagesetup_utils.php&action=fieldaihelp&hid=".$a['id'])."\", help: document.getElementById(\"help_".$a['id']."\").value}' hx-target='#div_".$a['id']."' hx-swap='innerHTML'>Save: ".$a['caption']." <i class='bi bi-arrow-right-circle fs-5' ></i></button>";
		 //echo "<button id='div_".$a['id']."' class='btn btn-white btn-sm' hx-get='do_bq.php?bqkey=".pw_enc("pw=bq_pagesetup_utils.php&action=fieldaihelp&hid=".$a['id']."&help=".$a['help'])."' hx-target='#div_".$a['id']."' hx-swap='innerHTML'>Save: ".$a['caption']." <i class='bi bi-arrow-right-circle fs-5' ></i></button>";
		}
		exit;
		
	}
	if(isset($_GET['action']) and $_GET['action']=="fullhelp"){
		// printr($_GET);
		$sql="selrec id,help,pgid,caption from _pb_pagehead where pgid=?";
		$ds=getValueForPS($sql,"s",$_GET['pgid']);
		$ds['help']=$_GET['help'];
		 if (isset($_POST['help'])) {
	        $ds['help'] = $_POST['help'];
	    }
		updaterecord($ds,"_pb_pagehead");
		echo ("Page full help done.");
		exit;
	}
	
	if(isset($_GET['action']) and $_GET['action']=="fieldaihelp"){
		$sql="selrec id,controltype,help,fieldname,caption,pgid from _pb_pagefields where id=?";
		$ds=getValueForPS($sql,"s",$_GET['hid']);
		$ds['help']=$_GET['help'];
		updateRecord($ds,"_pb_pagefields");
		// update current page
		$_SESSION['currentpage'] = setcurrentpageSession($ds['pgid']);
		echo "<button class='btn btn-primary border-1 btn-sm m-0 p-1'>Help updated.. <i class='bi bi-magic fs-5'></i></button>";
		exit;
	}
	if(isset($_GET['action']) and $_GET['action']=="settag"){
		
		if($_GET['param']=="required"){	// toggle required
			$fieldds=getValueForPS("selrec id,tags,caption from _pb_pagefields where id=?","s",$_GET['fid']);
			if(isFoundIn($fieldds['tags'],"Is Required")){
				$fieldds['tags']=str_replace("Is Required","",$fieldds['tags']);
				$fieldds['tags']=str_replace("~~","~",$fieldds['tags']);
				updaterecord($fieldds,"_pb_pagefields");
				$_SESSION['currentpage']['fields'][$_GET['fieldname']]['tags']=$fieldds['tags'];
				echo toast("field : ".$fieldds['caption']." set as non-mandatory.");
				exit;
			}else{
				$fieldds['tags'].="~Is Required";
				$fieldds['tags']=str_replace("~~","~",$fieldds['tags']);
				updaterecord($fieldds,"_pb_pagefields");
				$_SESSION['currentpage']['fields'][$_GET['fieldname']]['tags']=$fieldds['tags'];
				echo toast("field : ".$fieldds['caption']." set as mandatory.");
				exit;
			}
			
		}//Is Searchable
		if($_GET['param']=="searchable"){	// toggle required
			$fieldds=getValueForPS("selrec id,tags,caption from _pb_pagefields where id=?","s",$_GET['fid']);
			if(isFoundIn($fieldds['tags'],"Is Searchable")){
				$fieldds['tags']=str_replace("Is Searchable","",$fieldds['tags']);
				$fieldds['tags']=str_replace("~~","~",$fieldds['tags']);
				updaterecord($fieldds,"_pb_pagefields");
				$_SESSION['currentpage']['fields'][$_GET['fieldname']]['tags']=$fieldds['tags'];
				echo toast("field : ".$fieldds['caption']." removed searchable.");
				exit;
			}else{
				$fieldds['tags'].="~Is Searchable";
				$fieldds['tags']=str_replace("~~","~",$fieldds['tags']);
				updaterecord($fieldds,"_pb_pagefields");
				$_SESSION['currentpage']['fields'][$_GET['fieldname']]['tags']=$fieldds['tags'];
				echo toast("field : ".$fieldds['caption']." set as searchable.");
				// printr($fieldds);
				exit;
			}
			
		}		
		exit;
	}
	if(isset($_GET['action']) and $_GET['action']=="changecontrol"){
		$fieldds=getValueForPS("selrec * from _pb_pagefields where id=?","s",$_GET['fid']);
		$fieldds['controltype']=$_GET['ctrl'];
		updaterecord($fieldds,"_pb_pagefields");
		$_SESSION['currentpage']['fields'][$_GET['fieldname']]['controltype']=$_GET['ctrl'];
		echo toast("field : ".$fieldds['caption']." set as ".$_GET['ctrl']);
		exit;
	}
	if(isset($_GET['action']) and $_GET['action']=="movetab"){
		$fieldds=getValueForPS("selrec * from _pb_pagefields where id=?","s",$_GET['fid']);
		$fieldds['tabname']=$_GET['tab'];
		updaterecord($fieldds,"_pb_pagefields");
		$_SESSION['currentpage']['fields'][$_GET['fieldname']]['tabname']=$_GET['tab'];
		echo toast("Field moved to tab : ".$_GET['tab'],"success");
		exit;
	}
	if(isset($_GET['action']) and $_GET['action']=="deletefld"){
		$fieldds ="";
		$fieldds=getValueForPS("selrec * from _pb_pagefields where id=?","s",$_GET['fldid']);
		$fld_delete_sql = "delrec from _pb_pagefields where id='".$_GET['fldid']."'";
		pw_execute($fld_delete_sql);
		echo toast("Field ".$fieldds['caption']." Deleted.");
		$_SESSION['activepage'] = setcurrentpageSession($_GET['pgid']);
		
		echo "<script>
			setTimeout(() => {
			closeAllPops();
			}, 1000);
		</script>";
		exit;
	}
    //array not converstion the string

	$fieldds = getValueForPS("selrec fieldname, pgid, caption, controltype from _pb_pagefields where id=?","s",
	                         $_GET['fid']);
	$pgid       = $fieldds['pgid'] ?? '';
	$caption    = $fieldds['caption'] ?? '';
	$controltype= $fieldds['controltype'] ?? '';
	$pageds = getValueForPS("selrec caption from _pb_pagehead where pgid=?","s",$pgid);
    $pagecaption = $pageds['caption'] ?? '';
	echo "<div class='bq-head bq-popup' style='line-height:17px'><b>Field Setup</b></div>
	      <h5 style='margin-top:10px;'>{$pagecaption} - {$caption} : {$controltype}</h5>";
	showtabs();			//  tab moment can be controlled here
	showcontroltypes(); //  the control types can be changed here
	$bqkey_editor=pw_enc("pw=bq_list_edit.php&pgid=pb_pagefields&hid=".$_GET['fid']."&action=edit");
	$bqkey_dellink=pw_enc("pw=bq_pagesetup_utils.php&pgid=".$_SESSION['activepage']['head']['pgid']."&fldid=".$_GET['fid']."&action=deletefld");
	$fldcaption = "";
	//$fldcaption = $_SESSION['activepage']['fields'][$_GET['fieldname']]['caption'];
	$fldcaption = $fieldds['caption']??'';
	echo "
		<div class='d-flex justify-content-center flex-wrap gap-3 mt-3'>
			<button class='btn btn-sm d-flex align-items-center justify-content-center rounded-pill shadow-sm px-2 py-1'
				style='background-color:#4b4b4b; color:white; min-width:180px; font-size:0.9rem;'
				hx-get='do_bq.php?bqkey=".$bqkey_editor."'
				hx-target='#editPanel'
				hx-swap='innerHTML'>
				<i class='bi bi-pencil-square me-2'></i>
				<span class='text-nowrap'>Edit ".$fldcaption."</span>
			</button>
			<button class='btn btn-sm d-flex align-items-center justify-content-center rounded-pill shadow-sm px-2 py-1'
				style='background-color:#dc3545; color:white; min-width:180px; font-size:0.9rem;'
				hx-get='do_bq.php?bqkey=".$bqkey_dellink."&action=delete'
				hx-target='#utilsresults'
				hx-swap='innerHTML'
				hx-confirm='Are you sure you want to delete this column {$fldcaption} ?'>
				<i class='bi bi-trash-fill me-2'></i>
				<span class='text-nowrap'>Delete {$fldcaption}</span>
			</button>
		</div>
	<div id='utilsresults'></div>"; //   the results of action appear here
	//  this function is used to handle the field edit and set different types of controls are different types of flags
	function showcontroltypes(){
		echo "<h6 class='mt-4'>Set control types</h6>
		<div class='ps-3'>";
			echo "<button class='btn btn-white btn-sm me-2 mb-1 border-bottom border-1 border-secondary'
			hx-get='do_bq.php?bqkey=".pw_enc("pw=".$_GET['pw']."&action=changecontrol&ctrl=Text Box&fieldname=".$_GET['fieldname']."&fid=".$_GET['fid'])."' hx-swap='innerHTML' hx-target='#utilsresults'>Text box</button>";
			echo "<button class='btn btn-white btn-sm me-2 mb-1 border-bottom border-1 border-secondary' hx-get='do_bq.php?bqkey=".pw_enc("pw=".$_GET['pw']."&action=changecontrol&ctrl=Read Only&fieldname=".$_GET['fieldname']."&fid=".$_GET['fid'])."'  hx-swap='innerHTML' hx-target='#utilsresults'>Read Only</button>";
		
			echo "<button class='btn btn-white btn-sm me-2 mb-1 border-bottom border-1 border-secondary'	hx-get='do_bq.php?bqkey=".pw_enc("pw=".$_GET['pw']."&action=changecontrol&ctrl=Text Area&fieldname=".$_GET['fieldname']."&fid=".$_GET['fid'])."' hx-swap='innerHTML' hx-target='#utilsresults'	>Text Area</button>";

			echo "<button class='btn btn-white btn-sm me-2 mb-1 border-bottom border-1 border-secondary'	hx-get='do_bq.php?bqkey=".pw_enc("pw=".$_GET['pw']."&action=changecontrol&ctrl=Email&fieldname=".$_GET['fieldname']."&fid=".$_GET['fid'])."' hx-swap='innerHTML' hx-target='#utilsresults'	>Email</button>";

			echo "<button class='btn btn-white btn-sm me-2 mb-1 border-bottom border-1 border-secondary'	hx-get='do_bq.php?bqkey=".pw_enc("pw=".$_GET['pw']."&action=changecontrol&ctrl=SMS&fieldname=".$_GET['fieldname']."&fid=".$_GET['fid'])."' hx-swap='innerHTML' hx-target='#utilsresults'	>Mobile</button>";
			
			echo "<button class='btn btn-white btn-sm me-2 mb-1 border-bottom border-1 border-secondary' 	hx-get='do_bq.php?bqkey=".pw_enc("pw=".$_GET['pw']."&action=settag&param=required&fieldname=".$_GET['fieldname']."&fid=".$_GET['fid'])."' hx-swap='innerHTML' hx-target='#utilsresults'	>Mandatory (toggle)</button>";
			
			echo "<button class='btn btn-white btn-sm me-2 mb-1 border-bottom border-1 border-secondary' 	hx-get='do_bq.php?bqkey=".pw_enc("pw=".$_GET['pw']."&action=settag&param=searchable&fieldname=".$_GET['fieldname']."&fid=".$_GET['fid'])."' hx-swap='innerHTML' hx-target='#utilsresults'	>Searchable (toggle)</button>";
			
			
		echo "</div>";
	}
	function showtabs(){
		$pgid=$_GET['fpgid']??'';
		$tabs = $tlist = "";
		$fieldds=getValueForPS("selrec fieldname,pgid from _pb_pagefields where id=?","s",$_GET['fid']);
		$tlist = getValueForPS("selrec group_concat(distinct tabname) from _pb_pagefields where pgid=? and tabname not in ('General','XXX')","s",$pgid);
		if($tlist!="") $tlist = str_replace(",","/",$tlist);
		$tlist = "General/".$tlist."/XXX";
		//echo $tlist;
		$tabs = explode("/",$tlist);
		//printr($tabs);
		$displaytabs = "";
		echo "<h6 class='mt-4'>Set tabs </h6>
		<div class='ps-3'>";
		foreach($tabs as $t){
			$tabname = "Tab ".$t;
				$displaytabs = "<button class='btn btn-white btn-sm me-2 mb-1 border-bottom border-1 border-secondary' hx-get='do_bq.php?bqkey=".pw_enc("pw=".$_GET['pw']."&action=movetab&tab=".$t."&fieldname=".$_GET['fieldname']."&fid=".$_GET['fid'])."' hx-swap='innerHTML' hx-target='#utilsresults'>".$tabname."</button>";				
			echo $displaytabs;
		}
		echo "</div>";
	}
?>