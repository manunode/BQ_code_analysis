<?php
	include_once("bq_indi_engine.php");
	//printr($_GET);
	checkDesignMode();
	
	if(isset($_GET['action']) && $_GET['action']=='list_le'){
		$_SESSION['currentpage']['head']['pgid']=$_SESSION['currentpage_le']['head']['pgid'];
		$_SESSION['currentpage']['head']['tablename']=$_SESSION['currentpage_le']['head']['tablename'];
		$_SESSION['currentpage']['head']['id']=$_SESSION['currentpage_le']['head']['id'];
	}

	// inactivate child link
	if (isset($_GET['action']) && $_GET['action'] === 'inactivatechild'){
		$ds=getValueForPS("selrec id,caption,status,linktype  from _pb_pagelinks where id=?","s",$_GET['hid']);
		$ds['status']='Inactive';
		updaterecord($ds,"_pb_pagelinks");
		$_GET['action'] = 'childforms';
	}
	// activate child link
	if (isset($_GET['action']) && $_GET['action'] === 'activatechild'){
		$ds=getValueForPS("selrec id,caption,status,linktype  from _pb_pagelinks where id=?","s",$_GET['hid']);
		$ds['status']='Active';
		updaterecord($ds,"_pb_pagelinks");
		$_GET['action'] = 'childforms';
	}
	// add existing link to parent form
	if (isset($_GET['action']) && $_GET['action'] === 'addchild'){
		$parentpgid	= $_SESSION['currentpage']['head']['pgid'];
		$childpgid	= $_GET['pgid'];
		$childDS=getValueForPS("Selrec * from _pb_pagehead where pgid=?","s",$childpgid);
		$parentDS=getValueForPS("Selrec * from _pb_pagehead where pgid=?","s",$parentpgid);
		insertChildLink($parentDS);
		$_GET['action'] = 'childforms';
	}
	
	// delete page link
	if (isset($_GET['action']) && $_GET['action'] === 'deletelink'){
		$delsql = "";
		//$delsql = "delrec from _pb_pagelinks where pgid='".$_GET['hpg']."' and id='".$_GET['hid']."'";
		//pw_execute($delsql);
		//echo $delsql;
		delChildLink();
		hsc(toastfade("Link Deleted Successfully","editPanel"));
		exit;
	}
	
	// add new page link to the parent form create page head , page fields
	if (isset($_GET['action']) && $_GET['action'] === 'addlink'){
		//printr($_GET);
		$parentpgid=$_SESSION['currentpage']['head']['pgid'];
		$childpgid=$_GET['pgid'];
		$childDS=getValueForPS("Selrec * from _pb_pagehead where pgid=?","s",$childpgid);
		$parentDS=getValueForPS("Selrec * from _pb_pagehead where pgid=?","s",$parentpgid);
		insertChildLink($parentDS,"New");
		$_GET['action'] = 'childforms';
		//exit;
	}
	// user want to select child object from parent also showing current child links
	if (isset($_GET['action']) && ($_GET['action'] === 'childforms')){
		// show all forms having _child_ in pgid
		// if it is already a child to parent then mark accordingly
		$parentcaption=$_SESSION['currentpage']['head']['caption'];
		echo "<div class='m-1 p-1'>
			<h5>".$parentcaption." (Set child objects) </h5>
			<div class='bg-warning-subtle p-2 border-bottom border-1 border-dark mb-1'>
				<small >Note : Each parent form can have many child forms such as a employee can have educational qualifications, family details, work experience etc</small>
			</div>
			<div class='mb-2'>
		        <input autofocus type='text' id='childFormSearch' class='form-control form-control-sm' style='max-width:300px;' placeholder='Search child forms...'  onkeyup='filterChildForms(this.value)'>
		    </div>";
			// printr($_GET);
		echo "<table class='table table-striped table-sm' style='border:1px solid #ccc' id='childFormsTable'>
				<tr><td colspan=2><b>Existing Child Forms Links</b><small> These child forms are already present. You can remove the link or inactivate link.</small></td></tr>";
			// Show exiting links of the form
		$sql="selrec pgid,id,caption,linktype,status,url from  _pb_pagelinks where pgid =? order by caption";
		$rs=PW_sql2rsPS($sql,"s",$_GET['hpg']);
    	while($ds=PW_fetchAssoc($rs)){
			$bqkeyDroplink=pw_enc("pw=bq_fw_pagesetup.php&action=deletelink&hid=".$ds['id']."&hpg=".$_GET['hpg']."&childlink=".$ds['url']);
			echo "<tr><td class='ps-4' title={$ds['url']}>
			<i class='bi bi-star-fill fs-6 text-danger'></i>  ".$ds['caption']." </td>";
			if($ds['status']=="Active"){
				$bqkeyinactivate=pw_enc("pw=bq_fw_pagesetup.php&action=inactivatechild&hid=".$ds['id']."&hpg=".$_GET['hpg']);
				echo "<td  style='cursor:pointer' width='1%' nowrap 
						title='Click to inactivate'>
						<i class='bi bi-toggle-on fs-4 text-secondary' 
						hx-get='do_bq.php?bqkey=".$bqkeyinactivate."' 
						hx-target='#editPanel' 
						hx-swap='innerHTML' 
						hx-confirm='Are you sure you want to inactivate ".$ds['caption']." as child from ".$ds['caption']." form ?'></i>
						<span title='Delete Link' class='p-1 m-1 ps-1 pe-1 text-secondary fs-6 bi bi-trash' hx-get='do_bq.php?bqkey=".$bqkeyDroplink."' hx-target='#editPanel' hx-confirm='Are you sure you want to delete this link \"".$ds['caption']."\" ?'>
						</span>
					 </td>
				  </tr>";
		    }else{
				$bqkeyactivate=pw_enc("pw=bq_fw_pagesetup.php&action=activatechild&hid=".$ds['id']."&hpg=".$_GET['hpg']);
				echo "<td  style='cursor:pointer' width='1%' nowrap title='Click to activate'>
							<i class='bi bi-toggle-off fs-4 text-secondary' 
							hx-get='do_bq.php?bqkey=".$bqkeyactivate."' 
							hx-target='#editPanel' hx-swap='innerHTML'  
							hx-confirm='Are you sure you want to inactivate ".$ds['caption']." as child from ".$ds['caption']." form ?'></i>
							<span title='Delete Link' class='p-1 m-1 ps-1 pe-1 text-secondary fs-6 bi bi-trash' hx-get='do_bq.php?bqkey=".$bqkeyDroplink."' hx-target='#editPanel' hx-confirm='Are you sure you want to delete this link \"".$ds['caption']."\" ?'>
							</span>
					 </td>
				 </tr>";
		    }
    	}
		// Show new std links
		$sql = "selrec id,pgid,caption,tablename from _pb_pagehead where pgid<>'' and tags NOT LIKE '%Direct Landing%' and tags like '%Is child form%' and (url='' or url is NULL) and (status='Active' or status='Under testing')  order by FIELD (pgid,'standard_travelplan','standard_timesheet','standard_spares','standard_specifications','standard_review','standard_repairs','standard_params','standard_movements','standard_expenditure','standard_estimation','standard_events','standard_feedback','standard_contacts','standard_attachments','standard_experience','standard_qualifications','standard_workdone','standard_policies') desc,caption";		
		$rs=PW_sql2rsPS($sql);
		$standardRows = "";
		$otherRows = "";
		while ($ds = PW_fetchAssoc($rs)) {
			$bqkey = pw_enc("pw=bq_fw_pagesetup.php&action=addchild&pgid={$ds['pgid']}&hpg={$_GET['hpg']}&hid={$ds['id']}");
			$bqkeyaddlink = pw_enc("pw=bq_fw_pagesetup.php&action=addlink&hid={$ds['id']}&hpg={$_GET['hpg']}&pgid={$ds['pgid']}");
			// Check if standard or child page
			$isRestricted = (
				strpos($ds['pgid'], 'xxxstandard_') === 0
			);
		    // Add New Link (ONLY if not restricted)
			$actions = "
			<span title='Add New Link (Parent form _ child form'
			class='p-1 m-1 ps-1 pe-1 text-secondary bi bi-link-45deg fs-4'
			hx-get='do_bq.php?bqkey={$bqkeyaddlink}'
			hx-target='#editPanel'
			hx-confirm='Are you sure you want to create new link for \"{$parentcaption}\" ?'>
			</span>";

// commented by anitha said by sastry sir
			// Toggle icon (always visible)
		   // if (!$isRestricted) {
			  //  $actions .= "xxx
					// <i class='bi bi-plus-circle-fill fs-4 text-secondary'
					// title='Adds this child form \nto parent for directly'
					// hx-get='do_bq.php?bqkey={$bqkey}'
					// hx-target='#editPanel'
					// hx-swap='innerHTML'
					// hx-confirm='Are you sure you want to set {$ds['caption']} as child to {$parentcaption}?'>
					// </i>";
		   // }
		    $icon="<i class='bi bi-star-fill fs-6 text-secondary'></i>";
		    if ($isRestricted) $icon="<i class='bi bi-star-fill fs-6 text-primary'></i>";
		    $rowHtml = "<tr>
				<td class='ps-4' title='".$ds['pgid']."'>".$icon. "&nbsp;". $ds['caption']." (". $ds['tablename'].")</td>
				<td style='cursor:pointer' width='1%' nowrap>{$actions}</td>
			</tr>";
		    if ($isRestricted) {
		        $standardRows .= $rowHtml;
		    } else {
		        $otherRows .= $rowHtml;
		    }
		}		
		echo "<tr><td colspan=2><b>Standard Forms </b><small> A new form with parent and child names concatenated will be created and that new form is linked.</small></td></tr>";
		echo $standardRows ?: "<tr><td colspan=2 class='text-muted ps-4'>No standard forms found.</td></tr>";
		echo "<tr><td colspan=2><b>Other Child Forms</b>
		<small> User can set following forms as links or as direct child forms.</small></td></tr>";
		echo $otherRows ?: "<tr><td colspan=2 class='text-muted ps-4'>No other child forms found.</td></tr>";			
		echo "</table>";
		echo "</div>";
		echo "<script>
			let filterTimeout;
			function filterChildForms(value){
			    clearTimeout(filterTimeout);   // prevent multiple rapid calls
			    filterTimeout = setTimeout(function(){
			        var filter = value.toLowerCase();
			        // ONLY target the required table
			        var rows = document.querySelectorAll('#childFormsTable tr');
			        rows.forEach(function(row){
			            var firstCell = row.querySelector('td.ps-4');
			            if(!firstCell) return;
			            var text = firstCell.textContent.toLowerCase();
			            row.style.display = text.includes(filter) ? '' : 'none';
			        });
			    }, 500);  // delay by 500ms
			}
		</script>";

		exit;
		
	}
	if (isset($_GET['fieldname']) && $_GET['action'] === 'fieldhelp'){
		$help=$_SESSION['activepage']['fields'][$_GET['fieldname']]['help']."";
		$caption="<strong>".$_SESSION['activepage']['fields'][$_GET['fieldname']]['caption']."</strong> (Help)<hr>";
		if(strlen($help)>10){
			$help=toast($caption.$help);
		}else{
			$help=toast($caption."No specific help available","danger");
		}
		echo "<div class='bq-head bq-popup'><b>Help</b></div>
			   <div style='padding-top:10px'>".$help."</div>";
		exit;
	}
	if (isset($_GET['rty']) && $_GET['rty'] === 'replicate_form') {
		$bqkey = pw_enc("pw=bq_fw_pagesetup.php&rty=replicate_save&pgid=".$_GET['pgid']);
	    echo "<div id='replicateDiv' class='bq-setup bq-setup-double p-2' onclick='closeAllPops();'>
					<form id='replicate' name='replicate' style='width:100%;text-align:center'>
			        	<input type='text' id='newname' name='newname' placeholder='Enter name' class='form-control form-control-sm mb-2 text-center' style='max-width:90%; border-radius:6px;'>
			        		<button hx-post='do_bq.php' hx-vals='{\"rty\":\"replicate_save\", \"bqkey\":\"$bqkey\"}' hx-target='#replicateDiv' hx-swap='outerHTML' id='submitBtn' name='submitBtn' value='Replicate' class='btn btn-light btn-sm'>Replicate</button>
			        </form>
	    </div>";
	    exit;
	}
	
	if (isset($_POST['rty']) && $_POST['rty'] === 'replicate_save') {
		$bqkey = $_POST['bqkey'] ?? '';
	    $newname = trim($_POST['newname'] ?? '');
	    if ($newname === '') {
	        echo "<div id='replicateDiv' class='bq-setup bq-setup-double p-2 text-center' onclick='closeAllPops();'>
	        		<div class='text-danger small mb-2'>Enter a name</div>
			            <input type='text' id='newname' name='newname' placeholder='eg.. _bq_cus' class='form-control form-control-sm mb-2 text-center'
			                   style='max-width:90%; border-radius:6px;'>
			                		<button class='btn btn-light btn-sm' hx-post='do_bq.php' hx-vals='{\"rty\":\"replicate_save\", \"bqkey\":\"$bqkey\"}'
			                    hx-target='#replicateDiv' hx-swap='outerHTML'>Replicate</button>
	        		</div>";
	        exit;
	    }
	
	    // Replace this with actual DB replication logic
	    $newpgid = $_POST['newname'];
	    //Page Head copying _pb_pagehead
	    $hsql = "selrec * from _pb_pagehead where pgid=? ";
	    $hrs=PW_sql2rsPS($hsql,"s",$_POST['pgid']);
	    while($hds=PW_fetchAssoc($hrs)){
	    	$hds['pgid']			=	$newpgid;
	    	$hds['caption']			=	$newpgid;
	    	if($hds['toplinkpages']!="") $hds['toplinkpages']	=	$hds['toplinkpages'].";".$newpgid.",".$newpgid;
	    	insertID($hds,"_pb_pagehead",$id);
	    }	
	    //Page Fields copying _pb_pagehead
	    $fsql = "selrec * from _pb_pagefields where pgid=? ";
	    $frs=PW_sql2rsPS($fsql,"s",$_POST['pgid']);
	    while($fds=PW_fetchAssoc($frs)){
	    	$fds['linkedid']	=	$id;
	    	$fds['linkedto']	=	"_pb_pagehead";
	    	$fds['pgid']		=	$newpgid;
	    	insertRecord($fds,"_pb_pagefields");
	    }	
	    //Page Linkes copying _pb_pagehead
	    $lsql = "selrec * from _pb_pagelinks where pgid=? ";
	    $lrs=PW_sql2rsPS($lsql,"s",$_POST['pgid']);
	    while($lds=PW_fetchAssoc($lrs)){
	    	$lds['linkedid']	=	$id;
	    	$lds['linkedto']	=	"_pb_pagehead";
	    	$lds['pgid']		=	$newpgid;
	    	insertRecord($lds,"_pb_pagelinks");
	    }	
	    
	    $bqformlink = "pw=do_bqshell.php&pgid=".$newpgid;
		$caps = "Go to ".$newpgid." ";
		
		echo "<div id='replicateDiv' style='background:none;' onclick='closeAllPops();'>
				<div class='bq-setup p-2' style='background:#27F542!important' xtabindex='0' title=\"".htmlspecialchars($caps)."\"
			        hx-get='do_bq.php?bqkey=".pw_enc($bqformlink)."' hx-target='#mainContent' hx-swap='innerHTML' onclick='hideMenus(); setActiveLink(this);'>
			        <div class='bq-setup-radius'>
			        	<i class='bi bi-file-text'  style='color:#5c5c5c'></i>
			        </div>
			        <span style='font-weight:bold;text-align:center'>".htmlspecialchars($caps)."</span>
			      </div>
	    	  </div>";
	    exit;
	}

	// ----------------------------------------------------
	//  Normal full page rendering
	// ----------------------------------------------------
echo "<b style='padding:5px'>".$_SESSION['currentpage']['head']['caption'].": (".$_SESSION['currentpage']['head']['pgid'].")</b>";

$bqkey = pw_enc("pw=bq_pagesetup_dragdropfields.php&rty=drapdropfields&pgid=" . $_SESSION['currentpage']['head']['pgid']);
$listbqkey = pw_enc("pw=bq_pagesetup_draglistfields.php&rty=drapdropfields&pgid=" . $_SESSION['currentpage']['head']['pgid']);
$duplicatebqkey = pw_enc("pw=bq_fw_pagesetup.php&rty=replicate_form&pgid=" . $_SESSION['currentpage']['head']['pgid']);
//$bqkey_db=pw_enc("pw=bq_fw_form_builder.php&action=dbsetup");
$bqkey_db = pw_enc("pw=bq_fw_form_builder.php&action=showcols&tablename={$_SESSION['currentpage']['head']['tablename']}");

$fieldsetupkey = pw_enc("pw=do_bq_setup_fields.php&hpg=".$_SESSION['currentpage']['head']['pgid']);
$childformkey = pw_enc("pw=bq_fw_pagesetup.php&action=childforms&hpg=".$_SESSION['currentpage']['head']['pgid']);
$pagesetupkey ="";
$psparentid   = getValueForPS("Selrec id from _pb_pagehead where pgid=?","s",$_SESSION['currentpage']['head']['pgid']);
//echo "{".$_SESSION['currentpage']['head']['pgid']."<br>}";
//$pagesetupkey = pw_enc("pw=bq_list_edit.php&action=edit&hid=".$psparentid."&fromtb=_pb_pagehead&src=pagesetup&hpg=".$_SESSION['currentpage']['head']['pgid']);
$pagesetupkey = pw_enc("pw=bq_list_edit.php&action=pgheadedit&hid=".$psparentid."&fromtb=_pb_pagehead&src=pagesetup&hpgd=".$_SESSION['currentpage']['head']['pgid']."&pgid=".$_SESSION['currentpage']['head']['pgid']);

//$pagelinkskey  = 

echo "<div class='p-2 d-flex flex-wrap gap-1' tabindex='0' id='hkutils'>";

	// Pagehead
echo "<div hx-get='do_bq.php?bqkey=".$pagesetupkey."' hx-target='#editPanel' hx-swap='innerHTML' onclick='closeAllPops();'
        class='bq-setup p-2 position-relative' id='hkp' tabindex='0' role='button' >
          <span class='hotkey-badge text-dark'>
			<span style='position:absolute;top:-32px;right:7px'><b>P</b></span>
		  </span>
	        <div class='bq-setup-radius'>
	        	<i class='bi bi-pencil-square' style='color:#5c5c5c'></i>
	        </div>
        <span style='font-weight:bold;text-align:center'>Page Head</span>
      </div>";

	// Arrange fields
echo "<a href='do_bq.php?bqkey={$bqkey}' target='if1'  onclick='closeAllPops();' class='bq-setup p-2' tabindex='0' role='button'>
        <div class='bq-setup-radius'>
        	<i class='bi bi-file-earmark-text' style='color:#5c5c5c'></i>
        </div>
        <span style='font-weight:bold;text-align:center'>Arrange edit fields</span>
      </a>";

	// Arrange list fields
echo "<a href='do_bq.php?bqkey={$listbqkey}' target='if1'  onclick='closeAllPops();' class='bq-setup p-2' tabindex='0' role='button'>
        <div class='bq-setup-radius'>
        <i class='bi bi-list-task' style='color:#5c5c5c'></i>
        </div>
        <span style='font-weight:bold;text-align:center'>Arrange List Fields</span>
      </a>"; 

	// Child forms
echo "<div hx-get='do_bq.php?bqkey=".$childformkey."' hx-target='#editPanel' hx-swap='innerHTML' onclick='closeAllPops();' class='bq-setup p-2 position-relative' id='hkc' tabindex='0' role='button'>
        <span class='hotkey-badge text-dark'>
			<span style='position:absolute;top:-32px;right:7px'><b>C</b></span>
		</span>
	    <div class='bq-setup-radius'>
	    	<i class='bi bi-files' style='color:#5c5c5c'></i>
	    </div>
        <span style='font-weight:bold;text-align:center'>Child forms</span>
      </div>";

// Database setup
echo "<div class='bq-setup p-2 position-relative' tabindex='0' onclick='closeAllPops();' hx-get='do_bq.php?bqkey={$bqkey_db}' 
        hx-target='#editPanel' hx-swap='innerHTML'>
        <span class='hotkey-badge text-dark'>
			<span style='position:absolute;top:-32px;right:7px'><b>D</b></span>
		</span>
        <div id='hkd' class='bq-setup-radius'>
        <i class='bi bi-database' style='color:#5c5c5c'></i>
        </div>
        <span style='font-weight:bold;text-align:center'>DB Setup</span>
      </div>";


// Replicate form (HTMX)
echo "<div id='replicateDiv' class='bq-setup p-2' onclick='closeAllPops();'>
        <a onclick='closeAllPops();' hx-get='do_bq.php?bqkey={$duplicatebqkey}' hx-target='#replicateDiv' hx-swap='outerHTML'
           style='text-decoration:none; color:inherit; display:flex; flex-direction:column; 
                  align-items:center; justify-content:center;'>
                  <div class='bq-setup-radius'>
            <i class='bi bi-clipboard2-plus' style='color:#5c5c5c'></i>
            </div>
            <span style='font-weight:bold;text-align:center'>Replicate Form</span>
        </a>
      </div>";

//Fields setup
$bq_setup = pw_enc("pw=bq_setup_fields.php&rty=setfields&hpg=".$_SESSION['currentpage']['head']['pgid']);
$bq_setFields = "<div hx-get='do_bq.php?bqkey=".$bq_setup."' id='hkf' hx-target='#allpops'  onclick='showdiv(\"allpops\");
				setPopAll(100,80,1000,515);' style='text-decoration:none; color:inherit; display:flex; flex-direction:column; align-items:center; justify-content:center;'><div class='bq-setup-radius' >
           <i class='bi bi-layout-text-sidebar' style='color:#5c5c5c'></i>
           </div></div>";
           
echo "<div id='fieldsetup' class='bq-setup p-2 position-relative'>
          <span class='hotkey-badge text-dark'>
			<span style='position:absolute;top:-32px;right:7px'><b>F</b></span>
		  </span>
           ".$bq_setFields."
           <span style='font-weight:bold;text-align:center'>Fields Setup</span>
      </div>";

	$pageDs = getValueForPS("selrec id,tablename,caption,pgid,role from _pb_pagehead where pgid=?","s",$_SESSION['currentpage']['head']['pgid']);
	$recid  = $pageDs['id'];
	//$table  = $pageDs['tablename'];
	$bq_dataporting = pw_enc("pw=bq_excelstructure.php&rty=exceltemplate&typ=formexcelupload&pgid=".$_SESSION['currentpage']['head']['pgid']."&id=".$recid."");

echo "<div id='dataporting'
          class='bq-setup p-2' onclick='closeAllPops();'>
	        <div class='bq-setup-radius' hx-get='do_bq.php?bqkey=".$bq_dataporting."' hx-target='#editPanel' hx-swap='innerHTML'
	        	><i class='bi bi-database-up' style='color:#5c5c5c'></i>
	         </div>
           <span style='font-weight:bold;text-align:center'>Data Porting</span>
      </div>";
      
// Edit Field Help
	
    $bq_editfieldhelp = pw_enc("pw=bq_list_editfieldhelp.php&rty=editfldhelp&hpg=".$_SESSION['currentpage']['head']['pgid']."");
	$bq_editfielddiv = "<div hx-get='do_bq.php?bqkey=".$bq_editfieldhelp."' id='hkf1' hx-target='#allpops' onclick='showdiv(\"allpops\"); 
		setPopAll(100,80,1000,515);' style='text-decoration:none; color:inherit; display:flex; flex-direction:column; align-items:center; justify-content:center;'><div class='bq-setup-radius'><i class='bi bi-layout-text-sidebar' style='color:#5c5c5c'></i></div></div>";
		
	echo "<div id='editfieldhelp' class='bq-setup p-2 position-relative'>".$bq_editfielddiv."<span style='font-weight:bold;text-align:center'>Full page Help</span></div>";
	
	$dsPagetrigger=getValueForPS("selrec id,tablename,caption,pgid,role from _pb_pagehead where pgid=?","s","_pb_triggers");
	$bqformlinktriggers="pw=bq_list_table_le.php&pgid=_pb_triggers&parenttable=".$_SESSION['currentpage']['head']['tablename']."&parentid=".$recid."&parentpgid=".$_SESSION['currentpage']['head']['pgid']."&src=pagesetup&action=list_le&childtablename=".$dsPagetrigger['tablename']."&linkid=".$recid;
	
	echo "<div id='triggers' class='bq-setup p-2''>
		        <div class='bq-setup-radius' hx-get='do_bq.php?bqkey=".pw_enc($bqformlinktriggers)."' hx-target='#editPanel' hx-swap='innerHTML'>
		           <i class='bi bi-clipboard2-plus' style='color:#5c5c5c'></i>
		         </div>
	           <span style='font-weight:bold;text-align:center'>Triggers</span>
	      </div>";
	      
	$directformKey=("pw=do_bq_pageset_newform.php&action=formfields&fname=".$_SESSION['currentpage']['head']['pgid']."&tablename=".$_SESSION['currentpage']['head']['tablename']);  

	echo "<div id='triggers' class='bq-setup p-2 position-relative'>
				<span class='hotkey-badge text-dark'>
				<span style='position:absolute;top:-32px;right:7px'><b>E</b></span>
				</span>
		        <div id='hke' class='bq-setup-radius' hx-get='do_bq.php?bqkey=".pw_enc($directformKey)."' hx-target='#mainContent' hx-swap='innerHTML'>
		           <i class='bi bi-file-text-fill' style='color:#5c5c5c'></i>
		         </div>
	           <span style='font-weight:bold;text-align:center'>Edit Form</span>
	      </div>";
	      
	$bqformpagelinks="pw=bq_list_table_le.php&pgid=pb_pagelinks&parenttable=".$_SESSION['currentpage']['head']['tablename']."&parentid=".$recid."&parentpgid=".$_SESSION['currentpage']['head']['pgid']."&src=pagesetup&action=list_le&childtablename=_pb_pagelinks&linkid=".$recid;
	echo "<div id='triggers' class='bq-setup p-2 position-relative'>
				<span class='hotkey-badge text-dark'>
					<span style='position:absolute;top:-32px;right:7px'><b>L</b></span>
				</span>
		        <div id='hkl' class='bq-setup-radius' hx-get='do_bq.php?bqkey=".pw_enc($bqformpagelinks)."' hx-target='#editPanel' hx-swap='innerHTML'>
		           <i class='bi bi-link-45deg' style='color:#5c5c5c'></i>
		         </div>
	           <span style='font-weight:bold;text-align:center'>Page Links</span>
	      </div>";	      

 
	$dsPagewflow=getValueForPS("selrec id,tablename,caption,pgid,role from _pb_pagehead where pgid=?","s","_pb_swflow");
	$bqformlinkwflow="pw=bq_list_table_le.php&pgid=_pb_swflow&parenttable=".$_SESSION['currentpage']['head']['tablename']."&parentid=".$recid."&parentpgid=".$_SESSION['currentpage']['head']['pgid']."&src=pagesetup&action=list_le&childtablename=".$dsPagewflow['tablename']."&linkid=".$recid;
	
	echo "<div id='workflow' class='bq-setup p-2''>
		        <div class='bq-setup-radius' hx-get='do_bq.php?bqkey=".pw_enc($bqformlinkwflow)."' hx-target='#editPanel' hx-swap='innerHTML'>
		           <i class='bi bi-diagram-3-fill style='color:#5c5c5c'></i>
		         </div>
	           <span style='font-weight:bold;text-align:center'>Workflow</span>
	      </div>";
   	//echo "</div>";
   	$bqformlinkaiwflow="pw=bq_ai_tools_set_workflow.php&pgid=_pb_swflow&parenttable=".$_SESSION['currentpage']['head']['tablename']."&parentid=".$recid."&parentpgid=".$_SESSION['currentpage']['head']['pgid']."&src=pagesetup&action=list_le&childtablename=".$dsPagewflow['tablename']."&linkid=".$recid;
   	echo "<div id='aiworkflow' class='bq-setup p-2''>
		        <div class='bq-setup-radius' hx-get='do_bq.php?bqkey=".pw_enc($bqformlinkaiwflow)."' hx-target='#editPanel' hx-swap='innerHTML' onclick='setSideWidth(\"60%\");'>
		           <i class='bi bi-diagram-3-fill style='color:#5c5c5c'></i>
		         </div>
	           <span style='font-weight:bold;text-align:center'>AI Workflow</span>
	      </div>";
   	// Templates
   	$dsPageTemps=getValueForPS("selrec id,tablename,caption,pgid,role from _pb_pagehead where pgid=?","s","_pb_templates");
	$bqformlinktemps="pw=bq_list_table_le.php&pgid=_pb_templates&parenttable=".$_SESSION['currentpage']['head']['tablename']."&parentid=".$recid."&parentpgid=".$_SESSION['currentpage']['head']['pgid']."&src=pagesetup&action=list_le&childtablename=".$dsPageTemps['tablename']."&linkid=".$recid;
	
	echo "<div id='temflow' class='bq-setup p-2''>
		        <div class='bq-setup-radius' hx-get='do_bq.php?bqkey=".pw_enc($bqformlinktemps)."' hx-target='#editPanel' hx-swap='innerHTML'>
		           <i class='bi bi-layout-three-columns' style='color:#5c5c5c'></i>
		         </div>
	           <span style='font-weight:bold;text-align:center'>xxTemplates</span>
	      </div>";
	      
	//Create Code Segment
	$bqcreatecodeSegment="pw=bq_codesegement.php&rty=createcodesegment&rq1=portal&pgid=".$_SESSION['currentpage']['head']['pgid']."&tablename=".$_SESSION['currentpage']['head']['tablename'];
	
	echo "<div id='createcs' class='bq-setup p-2''>
		        <div class='bq-setup-radius' hx-get='do_bq.php?bqkey=".pw_enc($bqcreatecodeSegment)."' id='hkf' hx-target='#allpops'  onclick='showdiv(\"allpops\");
				setPopAll(100,80,1000,515);'>
		           <i class='bi bi-clipboard2' style='color:#5c5c5c'></i>
		         </div>
	           <span style='font-weight:bold;text-align:center'>Create Code Segment</span>
	      </div>";      
	      
	//Edit Code Segment
	$bqeditcodeSegment="pw=bq_codesegement.php&rty=editcodesegment&rq1=portal&pgid=".$_SESSION['currentpage']['head']['pgid']."&tablename=".$_SESSION['currentpage']['head']['tablename'];
	
	echo "<div id='editcs' class='bq-setup p-2''>
		        <div class='bq-setup-radius' hx-get='do_bq.php?bqkey=".pw_enc($bqeditcodeSegment)."'  hx-target='#allpops'  onclick='showdiv(\"allpops\");
				setPopAll(100,80,1000,550);'>
		           <i class='bi bi-pencil' style='color:#5c5c5c'></i>
		         </div>
	           <span style='font-weight:bold;text-align:center'>Edit Code Segment</span>
	      </div>";
	 //added Ai Help anjali on 29-01-2026. 
	echo "<div id='editcs' class='bq-setup p-2''>
		        <div class='bq-setup-radius' hx-get='do_bq.php?bqkey=".pw_enc("pw=bq_pagesetup_utils.php&action=aihelp&pgid=".$_SESSION['currentpage']['head']['pgid'])."' hx-target='#editPanel'  ;
				>
		           <i class='bi bi-question-circle'></i>
		         </div>
	           <span style='font-weight:bold;text-align:center'>AI Help</span>
	      </div>";
	
	
	
	$pageDs = getValueForPS("selrec id,tablename,caption,pgid,role from _pb_pagehead where pgid=?","s",$_SESSION['currentpage']['head']['pgid']);
	$recid  = $pageDs['id'];
	$bqmultiattachments = "pw=bq_list_table_le.php&pgid=_pb_attachdefinition&parenttable=".$_SESSION['currentpage']['head']['tablename']."&childtablename=_pb_attachdefinition&parentpgid=".$_SESSION['currentpage']['head']['pgid']."&action=list_le&parentid=".$recid."&hid=".$recid;
	echo "<div id='multiattach' class='bq-setup p-2''>
		        <div class='bq-setup-radius' hx-get='do_bq.php?bqkey=".pw_enc($bqmultiattachments)."' id='hkf' hx-target='#editPanel' hx-swap='innerHTML''>
		           <i class='bi bi-paperclip' style='color:#5c5c5c'></i>
		         </div>
	           <span style='font-weight:bold;text-align:center'>Attachments Defination</span>
	      </div>";
	      //$bqformlinkchecklists ='';
	//added check List anjali on 29-01-2026.  
	$dsPagechecklist=getValueForPS("selrec id,tablename,caption,pgid,role from _pb_pagehead where pgid=?","s","_pb_checklist1");
	$bqformlinkchecklists=pw_enc("pw=bq_list_table_le.php&pgid=_pb_checklist1&parenttable=".$_SESSION['currentpage']['head']['tablename']."&parentid=".$recid."&parentpgid=".$_SESSION['currentpage']['head']['pgid']."&src=pagesetup&action=list_le&childtablename=".$dsPagechecklist['tablename']."&linkid=".$recid);
		echo "<div id='checklist' class='bq-setup p-2''>
		        <div class='bq-setup-radius' id='temflow' hx-get='do_bq.php?bqkey=".$bqformlinkchecklists."' hx-target='#editPanel'
		             hx-swap='innerHTML'  onclick=\"document.getElementById('pageList').style.display = 'none';setSideWidth('30%');\">
				<i class='bi bi-card-checklist' style='color:#5c5c5c'></i>
				</div>
				        <span style='font-weight:bold;text-align:center'>Check Lists</span>
			    </div>";
	    echo "</div>";
?>

<!-- HTMX -->
<!--<script src="https://unpkg.com/htmx.org@2.0"></script>-->
<script>
document.body.addEventListener('htmx:configRequest', (evt) => {
  if (evt.detail.parameters.rty === 'replicate_save') {
    const newname = document.getElementById('newname');
    if (newname) evt.detail.parameters.newname = newname.value;
  }
});
  document.getElementById('hkutils').focus(); // set focus to take focus from list search
	  openHKPanel('hkutils')
</script>
