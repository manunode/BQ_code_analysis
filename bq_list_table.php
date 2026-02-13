<?php
	/* Prevent caching (page + HTMX responses) */
	$dovar="plumbee";
	include_once("bq_indi_engine.php");
	// if actions link clicked
	// printr($_GET);
	//printr($_POST);
	//define('ROWSPERPAGE',11);
	define('PHPPAGE','bq_list_table.php');
	$pgid=$_GET['pgid']??null;
	$pgno=$_GET['pgno']??null;
	$tip=$_GET;
	$_SESSION['listPagePgid']=$pgid;  // for safety
	// setSession("rksastry");
	// start of topcombo sql
	if(file_exists("segment/".$pgid.".php")){
		include_once("segment/".$pgid.".php");
	}
	// top combo change
	if(isset($_GET['action']) && $_GET['action']=='bqtopcombo'){
		if(!empty($_SESSION['currentpage']['head']['topcombosql']) || !empty($_SESSION['currentpage']['head']['topcombosql2'])){
			// topcombo 1
			$filter = "";
			if(!empty($_SESSION['currentpage']['head']['topcombosql'])){
				$topCombo = "";
				$topCombo =$_SESSION['currentpage']['head']['topcombosql'];
				$topCombo = replaceDS2message("",$topCombo);
				$tcArray   = explode("//",$topCombo);
				$reqString = explode("::",$_POST[$tcArray[1]]);
				//$reqString = replaceDS2message("",$reqString);
				
				$_SESSION['tc'] = $reqString[0];
				$tcFilter = "";
				if($topCombo!='')$tcFilter = cont_TopComboFilter($topCombo,$_POST,"",$tcArray[3]??'');
				if($tcFilter!='')$filter .= $tcFilter;
			}
			// topcombo 2
			if(!empty($_SESSION['currentpage']['head']['topcombosql2'])){
				$topCombo2  = "";
				$topCombo2  = $_SESSION['currentpage']['head']['topcombosql2'];
				$topCombo2 = replaceDS2message("",$topCombo2);
				$tcArray2   = explode("//",$topCombo2);
				$reqString2 = explode("::",$_POST[$tcArray2[1]]??'');
				$_SESSION['tc2'] = $reqString2[0];
				$tcFilter2 ="";
				if($topCombo2!='')$tcFilter2 = cont_TopComboFilter($topCombo2,$_POST,"",$tcArray2[3]??'');
				if($tcFilter2!='')$filter .= $tcFilter2;
			}
		}
		$_SESSION['currentpage']['meta']['usertopcombo'] = $filter;
	}
	// end of topcombosql	
	// list search / filter end
	if ($pgno==""){
		$_SESSION['currentpage']['meta']['pgno']=0;	
	} 
	$_SESSION['currentpage']['meta']['pgno']=intval($_SESSION['currentpage']['meta']['pgno']); // safety
	if($pgno==="n"){	// next page
		//if($_POST['txt_s']!='')$_POST['txt_s']=$_POST['txt_s'];
		$_SESSION['currentpage']['meta']['pgno']=$_SESSION['currentpage']['meta']['pgno']+1;
		$totalPages = "";
		$totalPages = ceil($_SESSION['currentpage']['meta']['totalRecords'] / $_SESSION['PW_CONSTANTS']['ROWSPERPAGE']);
			// prevent exceeding total pages
		if ($_SESSION['currentpage']['meta']['pgno'] >= $totalPages) {
		    $_SESSION['currentpage']['meta']['pgno'] = $totalPages - 1;
		}		
	}
	if($pgno==="p"){	// Prev page
		$_SESSION['currentpage']['meta']['pgno']=$_SESSION['currentpage']['meta']['pgno']-1;
	}
	if($pgno==="f"){	// First page
		$_SESSION['currentpage']['meta']['pgno']=0;
	}
	if($pgno==="l"){	// Last  page
		//$_SESSION['currentpage']['meta']['pgno']=0;
		$totalRecords = $_SESSION['currentpage']['meta']['totalRecords'];
		$perPage = $_SESSION['PW_CONSTANTS']['ROWSPERPAGE'];
		$_SESSION['currentpage']['meta']['pgno'] = ceil($totalRecords / $perPage) - 1;		
	}
	// Handle direct page number jump
	if (isset($_GET['goto_pgno']) && is_numeric($_GET['goto_pgno'])) {
		$userPage = intval($_GET['goto_pgno']);
		$userPage = max($userPage, 1); // minimum 1
		$totalPages = ceil($_SESSION['currentpage']['meta']['totalRecords'] / $_SESSION['PW_CONSTANTS']['ROWSPERPAGE']);
		if ($userPage > $totalPages) {
			$userPage = $totalPages;
		}
		// Convert to 0-based index
		$_SESSION['currentpage']['meta']['pgno'] = $userPage - 1;
	}	
	if($_SESSION['currentpage']['meta']['pgno']=="") $_SESSION['currentpage']['meta']['pgno']=0;
	$_SESSION['currentpage']['meta']['pgno']=max($_SESSION['currentpage']['meta']['pgno'],0);
	define('PAGENO',$_SESSION['currentpage']['meta']['pgno']);	
	// list search / filter
	//printr($_GET);
	if (isset($_REQUEST['txt_s'])) {
		$raw = trim($_REQUEST['txt_s']);
		if ($raw === '') {
			// CLEAR SEARCH COMPLETELY
			$_SESSION['currentpage']['meta']['usersearch'] = '';
			$_SESSION['currentpage']['meta']['start'] = 0; // reset pagination
		}
	}	
	$searchString=""; $searchWhere="";
	// for retaining the search text when clicked on pagination
	if(isset($_GET['action']) && $_GET['action']=="search" && (isset($_GET['txt_s']) && trim($_GET['txt_s'])!="")){
		$s = trim($_GET['txt_s']);
		$s = str_replace(["'", '"'], "", $s);
		$searchWhere = "(";
		foreach($_SESSION['currentpage']['meta']['searchfields'] as $k=>$v){
			$searchWhere .= " $v LIKE '%$s%' OR ";
		}
		$searchWhere = removelastnchars($searchWhere, 3);
		$searchWhere .= ")";
		$_SESSION['currentpage']['meta']['usersearch']=$searchWhere;
	}
	// for regular search
	if(isset($_GET['action']) && $_GET['action']=="search" && (isset($_POST['txt_s']) && trim($_POST['txt_s'])!="")){
		//printr($_POST);
		$s = trim($_POST['txt_s']);
		//$s = detectDateOnly($s); // date style searcj
		$s = str_replace(["'", '"'], "", $s);
		$searchWhere = "(";
		// Keep original input for text search
		$sText = $s;
		// Normalize only for date processing
		$sDate = str_replace(['/', '.'], '-', $s);
		// -------------------------------
		//  DATE RANGE
		// -------------------------------
		if (preg_match('/^(\d{2}-\d{2}-\d{4})\s*to\s*(\d{2}-\d{2}-\d{4})$/i', $sDate, $m)) {
			$d1 = implode("-", array_reverse(explode("-", $m[1])));
			$d2 = implode("-", array_reverse(explode("-", $m[2])));
			foreach($_SESSION['currentpage']['meta']['searchfields'] as $k=>$v){
				$searchWhere .= " ($v BETWEEN '$d1' AND '$d2') OR ";
			}
		}
		// -------------------------------
		// SINGLE DATE
		// -------------------------------
		else if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $sDate)) {
			$d = implode("-", array_reverse(explode("-", $sDate)));
			foreach($_SESSION['currentpage']['meta']['searchfields'] as $k=>$v){
				$searchWhere .= " $v = '$d' OR ";
			}
		}
		// -------------------------------
		//  NORMAL TEXT SEARCH (use original input)
		// -------------------------------
		else {
			foreach($_SESSION['currentpage']['meta']['searchfields'] as $k=>$v){
				$searchWhere .= " $v LIKE '%$sText%' OR ";
			}
		}
		// Remove last OR
	//	printr($_POST);
		//printr($searchWhere);
		$searchWhere = removelastnchars($searchWhere, 3);
		$searchWhere .= ")";
		$_SESSION['currentpage']['meta']['usersearch']=$searchWhere;
		
	}
	
	if(isset($_GET['action'])  and $_GET['action']=="actionlinks"){
		echo list_actions();
		exit;
	}
	if(isset($_GET['action']) and $_GET['action']=="edit"){
		list_editform();
		exit;  
	}
	if(isset($_GET['action']) and $_GET['action']=="addrec"){
		$pgid	 = $_POST['pgid']??null;
		getAddForm($pgid);
		exit;  
	}	
	// default sorting
	if(isset($_GET['action']) and $_GET['action']=="sort"){	// col head storing
		$sortColumn=$_SESSION['currentpage']['meta']['colids'][$_GET['colid']];
		if($_SESSION['currentpage']['meta']['sortfield']==$sortColumn){
			$_SESSION['currentpage']['meta']['sortfield'] =$sortColumn. " desc ";
		}else{
			$_SESSION['currentpage']['meta']['sortfield'] =$sortColumn. "";
		}
	}else{
		$_SESSION['currentpage']['head']['baseorder'] = $_SESSION['currentpage']['head']['baseorder'] ?? '';
		$_SESSION['currentpage']['meta']['sortfield']=$_SESSION['currentpage']['head']['baseorder'];
	}
	if(function_exists('plx_preList')) plx_preList("aaa");
	function render_row($r, $isNew = false) {
		$esc         = array_map('htmlspecialchars', $r);
		$rowClass    = $isNew ? ' class="bq-new-row"' : '';
		$borderCell  = "border-bottom:1px solid #dee2e6;border-right:1px solid #dee2e6;";
		$cellpadding = "0 10px"; // top/bottom 0, left/right 10
		$id          = $r[0];

		return '<tr'.$rowClass.' >
					<td style="'.$borderCell.'padding:'.$cellpadding.';">'.$esc[1].'</td>
					<td style="'.$borderCell.'padding:'.$cellpadding.'">'.$esc[2].'</td>
					<td style="'.$borderCell.'padding:'.$cellpadding.'">'.$esc[3].'</td>
					<td style="'.$borderCell.'padding:'.$cellpadding.'">'.$esc[4].'</td>
					<td style="'.$borderCell.'padding:'.$cellpadding.'">'.$esc[5].'</td>
					<td style="'.$borderCell.'padding:'.$cellpadding.'">'.$esc[6].'</td>
					<td class="sticky-right" style="right:0;z-index:3;background:#fff;border-left:1px solid #dee2e6;min-width:96px;max-width:96px;'.$borderCell.'text-align:center;">
							<div style="display:inline-flex;align-items:center;justify-content:center;gap:6px;width:100%;height:100%;padding:0">
								<button type="button" onclick="bqShowRowMenu(event,'.$rowno.')" aria-label="Row menu"
									style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;margin:0;border:1px solid #dee2e6;background:#fff;border-radius:6px;cursor:pointer">
									<i class="bi bi-caret-down-fill" style="color:#6c757d">`</i>
								</button>
								<button type="button" onclick="alert(\'Edit\')" aria-label="Edit"
									style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:26px;margin:0;border:1px solid #dee2e6;background:#fff;border-radius:6px;cursor:pointer">✎</button>
							</div>
					</td>
				</tr>';
	}
	function cont_TopComboFilter($topCombo,$_POSTDATA=array(),$comboNo="",$like=''){
		
		// filters the data based on topcombo value
		$tcArray   = explode("//",$topCombo);
		$reqString = explode("::",$_POSTDATA[$tcArray[1]]);
		$str 	   = " and ".$tcArray[1]."='".$reqString[0]."' ";
		if($like!='') $str 	   = " and ".$tcArray[1]." like '%".$reqString[0]."%' ";
		if(strtoupper($reqString[0])=='ALL')$str = "";
		//  default listing
		if($_POSTDATA[$tcArray[1]]=='' and $_SESSION['tc'.$comboNo]!=''){
			$str = " and ".$tcArray[1]."='".trim($_SESSION['tc'.$comboNo])."' ";
			if($like!='') $str = " and ".$tcArray[1]." like '%".trim($_SESSION['tc'.$comboNo])."%' ";
		//	if(strtoupper($_SESSION['tc'.$comboNo])=='ALL')$str = "and linkedid='".$_SESSION['currentpage']['head']['id']."' and linkedto='".$_SESSION['currentpage']['head']['pgid']."'";
			if(strtoupper($_SESSION['tc'.$comboNo])=='ALL')$str = "";
		}	
		return $str;
	}
	function list_table_head(){
		$listfields='';
		/* Generate thead */
		$str = '<thead><tr
		data-tour-name="List-header"
		data-tour-order="25"
		data-tour-explanation="The list data columns appear here&#10;You can sort data by these columns">';
	
		$fieldcaptions = array();
		$listfields = $_SESSION['currentpage']['head']['listfields'];
		$listfields = trim(str_replace(" ","",$listfields));
		$fields = explode(",",$listfields);
		foreach($fields as $field) {
			//printr($_SESSION['currentpage']['fields'][$field]);
			$fieldcaptions[] = $_SESSION['currentpage']['fields'][$field]['caption']??null;
			//$fieldcaptions[] = $_SESSION['currentpage']['fields'][$field]['caption']??ucwords(str_replace("_"," ",$field));
		}
		$fieldsCount = count($fieldcaptions);
		for ($k = 0; $k < $fieldsCount; $k++) {
			$sortfieldname = $fields[$k]; // ***
			$sorticon="bi bi-arrow-down-circle-fill";
			if($fieldcaptions[$k]){
				$v = htmlspecialchars($fieldcaptions[$k]);
			}
		    if ($k == 1) {
		        // First column header (sticky-left)
		        if($_SESSION['currentpage']['meta']['sortfield']==$sortfieldname){
					$sorticon="bi bi-caret-down-fill text-primary";
		        }
		        if($_SESSION['currentpage']['meta']['sortfield']==($sortfieldname." desc ")){
		        	$sorticon="bi bi-caret-up-fill text-primary";
		        }
		    	$bqkey=pw_enc("pw=bq_list_table.php&action=sort&colid=".$k);
		        		        // sorting management
				$isSortable=false;
				$_SESSION['currentpage']['fields'][$fields[$k]]['tags'] = $_SESSION['currentpage']['fields'][$fields[$k]]['tags'] ?? '';
				$xtags= $_SESSION['currentpage']['fields'][$fields[$k]]['tags'];
				if(isFoundIn("X".$xtags."x","Sortable")) $isSortable=true;
				$v = $v ?? '';
				$headcaption = ">".$v;
				$bqkey=pw_enc("pw=bq_list_table.php&action=sort&colid=".$k);
				if($isSortable) {
		        	$headcaption='onclick="closeAllPops();"
		                style="
	                	cursor:pointer;
	                    position:sticky; top:0;
	                    z-index: 30;                
	                    background:#f8f9fa;
	                    border-bottom:1px solid #dee2e6; border-right:1px solid #dee2e6; padding:0 10px;"
	                    
	                    hx-get="do_bq.php"
			            hx-swap="innerHTML"
			            hx-target="#bqScroller"
			            hx-vals=\'{"bqkey":"'.$bqkey.'"}\'>'
		               .$v.'<i class="ms-1 '.$sorticon.'" style="color:#ccc; cursor:pointer" ></i>';
		        }
		        $str .= '<th 
		        
		class="bq-ellipse" '.$headcaption.'
		        		</th>';
		    }
		    if($k > 1 && $k < ($fieldsCount)){
		        // Middle headers (sticky-top only)
		        if($_SESSION['currentpage']['meta']['sortfield']==$sortfieldname){
					$sorticon="bi bi-caret-down-fill text-primary";
		        }
		        if($_SESSION['currentpage']['meta']['sortfield']==$sortfieldname." desc "  ){
		        	$sorticon="bi bi-caret-up-fill text-primary";
		        }
		        
		        // sorting management
				$isSortable=false;
				$_SESSION['currentpage']['fields'][$fields[$k]]['tags'] = $_SESSION['currentpage']['fields'][$fields[$k]]['tags'] ?? '';
				$xtags= $_SESSION['currentpage']['fields'][$fields[$k]]['tags'];
				if(isFoundIn("X".$xtags."x","Sortable")) $isSortable=true;
				$headcaption = ">".$v;
				$bqkey=pw_enc("pw=bq_list_table.php&action=sort&colid=".$k);
				if($isSortable) {
		        	$headcaption='onclick="closeAllPops();"
		                style="
	                	cursor:pointer;
	                    position:sticky; top:0;
	                    z-index: 30;                /* header middle */
	                    background:#f8f9fa;
	                    border-bottom:1px solid #dee2e6; border-right:1px solid #dee2e6; padding:0 10px;"
	                    
	                    hx-get="do_bq.php"
			            hx-swap="innerHTML"
			            hx-target="#bqScroller"
			            hx-vals=\'{"bqkey":"'.$bqkey.'"}\'>'
		               .$v.'<i class="ms-1 '.$sorticon.'" style="color:#ccc; cursor:pointer" ></i>';
		        }
		        $str .= '<th class="bq-ellipse" '.$headcaption.'
		        		</th>';
		    }
		}
		// Actions header (sticky-right)
		$str.= '<th class="p-1"
		data-tour-name="List-actions"
		data-tour-order="26"
		data-tour-explanation="The record specific actions can vbe performed here"
	
		          style="position:sticky; top:0; right:0;z-index: 32;/* highest header */background:#f8f9fa;border-left:1px solid #dee2e6; border-bottom:1px solid #dee2e6; text-align:center;">#</th>';
		$str.= '</tr></thead>';
		return $str;
	} 
	function list_table_rows(){
		//printr($_GET);
		$pfid = $_GET['pgid']??'';
		$str='';
		echo debugtip();
		$cfgHead = $_SESSION['currentpage']['head'];
		$cfgMeta = &$_SESSION['currentpage']['meta'];
		$rowsPerPage = $_SESSION['PW_CONSTANTS']['ROWSPERPAGE'];
		
		/* -------------------------------------------------
		 * 0. SAFE DEFAULTS (ADD HERE)
		 * ------------------------------------------------- */
		$cfgMeta['usersearch']       = $cfgMeta['usersearch']       ?? '';
		$cfgMeta['totalRecords']    = $cfgMeta['totalRecords']    ?? 0;
		$cfgMeta['filteredRecords'] = $cfgMeta['filteredRecords'] ?? 0;
		$cfgMeta['totalPages']      = $cfgMeta['totalPages']      ?? 1;		
		
		$basefilter = $cfgHead['basefilter'] ?? '';
		$baseorder  = $cfgMeta['sortfield'] ?? 'id';
		$topcombofilter = $cfgMeta['usertopcombo'] ?? '';
		
		/* normalize basefilter */
		if (strlen(trim($basefilter)) < 5) {
		$basefilter = '1';
		}
		/* pagination */
		$start = max(0, PAGENO * $rowsPerPage);
		
		/* parent condition */
		$parentCond = '';
		//print_r($_GET);
		if (isset($_GET['parentid']) && !empty($_GET['parentid'])) {
			$parentid   = $_GET['parentid'];
			$parenttable= $_GET['parenttable'];
			$parentCond = " AND (linkedid='$parentid' AND linkedto='$parenttable')";
		}
		/* -------------------------------------------------
		* 2. SEARCH STATE (ONLY SESSION BASED)
		* ------------------------------------------------- */
		if (array_key_exists('txt_s', $_GET)) {
			$userSearch = trim($_GET['txt_s']);
			$cfgMeta['usersearch'] = $userSearch;   // save
			$cfgMeta['start'] = 0;                  // reset page
		} else {
			$userSearch = $cfgMeta['usersearch'] ?? '';
		}
		/* -------------------------------------------------
		* 3. BASE WHERE
		* ------------------------------------------------- */
		//$where = "WHERE $basefilter $topcombofilter $parentCond";
		/* -------------------------------------------------
		* 4. TOTAL RECORDS 
		* ------------------------------------------------- */
		$whereBase = "WHERE $basefilter $topcombofilter $parentCond";
		$sqlTotal = "selrec count(id) from {$cfgHead['tablename']} $whereBase group by id";
		$sqlTotal = replaceDS2message("", $sqlTotal);
		$rsTotal  = PW_sql2rsPS($sqlTotal);
		$totalRecords = PW_num_rows($rsTotal);
		$_SESSION['currentpage']['meta']['totalRecords'] = $totalRecords;
		/* -------------------------------------------------
		* 4. TOTAL RECORDS / FILTERED RECORDS
		* ------------------------------------------------- */
		$whereFinal = $whereBase;
		if ($userSearch !== '') {
			$whereFinal = "WHERE ($userSearch) AND $basefilter $topcombofilter $parentCond";
		}
		$countSql = "selrec count(id) from {$cfgHead['tablename']} $whereFinal group by id";
		//echo $countSql;
		$countSql = replaceDS2message("", $countSql);
		$countRs  = PW_sql2rsPS($countSql);
		$totalRows= PW_num_rows($countRs);
		//$_SESSION['currentpage']['meta']['totalRecords'] = $totalRows;
		if ($userSearch !== '') {
			$cfgMeta['filteredRecords'] = $totalRows;
			$_SESSION['currentpage']['meta']['filteredRecords'] = $cfgMeta['filteredRecords'];
		} else {
		//$cfgMeta['totalRecords'] = $totalRows;
			$cfgMeta['filteredRecords'] = 0;
			$_SESSION['currentpage']['meta']['filteredRecords'] = $cfgMeta['filteredRecords'];
		}
		/* total pages */
		$cfgMeta['totalPages'] = ceil($totalRows / $rowsPerPage);
		$_SESSION['currentpage']['meta']['totalPages'] = $cfgMeta['totalPages'];
		
		/* -------------------------------------------------
		* 5. MAIN LIST QUERY
		* ------------------------------------------------- */
		$sql = "selrec {$cfgHead['listfields']} from {$cfgHead['tablename']} $whereFinal order by $baseorder
			   limit $start, $rowsPerPage";
		 
		$sql = replaceDS2message("", $sql);
		$rs = PW_sql2rsPS($sql);
		$_SESSION['listsql']=$sql;
		//echo $sql; // check point
		$rowno=0;
		$str.="<div id='actionPopDiv' style='display:none;z-index:3000;width:200px;position:absolute;padding:0px;xbackground:#2c3e50;'></div>
				<script>
					function showPopAction(btn){
						var popup				= document.getElementById('actionPopDiv');
						var row 				= btn.closest('tr');
						var table				= document.getElementById('table-main-list');
						if(!popup || !row || !table) return;
						
						var container = table.parentElement; // scrollable wrapper
						if (getComputedStyle(container).position === 'static') container.style.position = 'relative';
						
						var rowRect 			= row.getBoundingClientRect();
						var tblRect 			= table.getBoundingClientRect();
						var wrapRect			= container.getBoundingClientRect();
						
						// Measure popup even if hidden
						var prevDisp			= popup.style.display, prevVis = popup.style.visibility;
						popup.style.visibility	= 'hidden';
						popup.style.display 	= 'block';
						var popW				= popup.offsetWidth || 200;
						var popH				= popup.offsetHeight || 80;
						
						// Align to table right (same as before)
						var leftPos 			= (tblRect.right - wrapRect.left) - popW + container.scrollLeft;
						
						// Decide above/below within container viewport
						var spaceBelow			= wrapRect.bottom - rowRect.bottom; // visible space below row
						var belowTop			= (rowRect.bottom - wrapRect.top) + container.scrollTop + 2; // +2px gap
						var aboveTop			= (rowRect.top    - wrapRect.top) + container.scrollTop - popH - 2;
						var topPos				= (spaceBelow >= popH ? belowTop : Math.max(0, aboveTop));
						
						// Apply
						popup.style.right		= '150px';
						popup.style.top 		= topPos  + 'px';
						popup.style.visibility	= 'visible';
						popup.style.display 	= 'block';
						// to make the actions div when scroll is there i have to scroll right
						container = document.getElementById('bqScroller');
					    // container.scrollTo({
					    //     left: container.scrollWidth,
					    //     behavior: 'smooth' // for smooth animation
					    // });
					    //popup.innerHTML			= 'Please wait' 
					}
				</script>";
				
		while ($ds = PW_fetchAssoc($rs)) {
			$_SESSION['currentpage']['meta']['rowids'][$rowno]=$ds['id'];
			$str.= '<tr >';
			$view=[]; $fields=[];
			$j=0;$fldtype="";
			foreach ($ds as $k => $v) {
				$fldtype = PW_field_type($rs,$j);
				if ($k !== 'id'){
				if($fldtype=='DATE') $v = displayDate($v);
				if($fldtype=='REAL') $align = ';text-align:right;';
				if($fldtype=='INT')  $align = ';text-align:right;';
					$view[] = $v; $fields[]=$k; 
				}
				$j++;
			}
			$count = count($view);
			for ($i = 0; $i < $count; $i++) {
				$fldtype ="";
				$align = '';
				$fieldname = $fields[$i];
				$fldtype = PW_field_type($rs,$i+1);
				//echo $fieldname."====".$fldtype."<br>";
				if($fldtype=='REAL') $align = ';text-align:right;';
				if($fldtype=='INT')  $align = ';text-align:right;';		        
				if($fldtype=='DECIMAL')  $align = ';text-align:right;';		        
				//$fieldAttrbs = $_SESSION['currentpage']['fields'][$fieldname]??ucwords(str_replace("_"," ",$fieldname));
				$_SESSION['currentpage']['fields'][$fieldname] = $_SESSION['currentpage']['fields'][$fieldname] ?? '';
				$fieldAttrbs = $_SESSION['currentpage']['fields'][$fieldname];
				if(isset($fieldAttrbs['tags']) && strpos($fieldAttrbs['tags'],"All Decimals")) $view[$i]  = $view[$i];
				if(isset($fieldAttrbs['tags']) && strpos($fieldAttrbs['tags'],"3 Decimals")) $view[$i]  = myMoney($view[$i],3);
				if(isset($fieldAttrbs['tags']) && strpos($fieldAttrbs['tags'],"4 Decimals")) $view[$i]  = myMoney($view[$i],4);
				if(isset($fieldAttrbs['tags']) && strpos($fieldAttrbs['tags'],"Numeric 0 Decimal")) $view[$i] = myMoney($view[$i],0);
				if(isset($fieldAttrbs['tags']) && strpos($fieldAttrbs['tags'],"Numeric 1 Decimal")) $view[$i] = myMoney($view[$i],1);		
				if(isset($fieldAttrbs['tags']) && strpos($fieldAttrbs['tags'],"Numeric 2 Decimal")) $view[$i] = myMoney($view[$i],2);
				if(isset($fieldAttrbs['tags']) && strpos($fieldAttrbs['tags'],"Numeric 3 Decimal")) $view[$i] = myMoney($view[$i],3);
				if(isset($fieldAttrbs['tags']) &&strpos($fieldAttrbs['tags'],"Numeric 4 Decimal")) $view[$i] = myMoney($view[$i],4);
					
					
					
				if ($i === 0) { // first column
				    if (isfoundin(strtoupper($_SESSION['currentpage']['head']['tags']), "Read Only")) {
				        $bqkey = pw_enc("pw=bq_list_edit.php&hid=".$ds['id']."&action=edit&pfid=".$pfid."&rty=readonlytag");
						$str .= '<td class="sticky-left bq-ellipse custom-tooltip"
						data-tooltip="'.htmlspecialchars((string)$view[$i]).'"
						style="'.$align.' cursor: pointer;"
						onclick="waiter(\'editPanel\',\'Please wait..\'); closeAllPops();"
						hx-get="do_bq.php"
						hx-target="#editPanel"
						hx-swap="innerHTML"
						hx-vals=\'{"bqkey":"'.$bqkey.'"}\'> '
						.htmlspecialchars((string)$view[$i]).
						'</td>';
				
				    } else {
				    	$bqkey = pw_enc("pw=bq_list_edit.php&hid=".$ds['id']."&action=edit&pfid=".$pfid);
						$str .= '<td class="sticky-left bq-ellipse custom-tooltip"
						data-tooltip="'.htmlspecialchars((string)$view[$i]).'"
						data-bs-placement="right"
						title="'.htmlspecialchars((string)$view[$i]).'"
						style="'.$align.' cursor: pointer;z-index:998;"
						onclick="waiter(\'editPanel\',\'Please wait..\'); closeAllPops();"
						hx-get="do_bq.php"
						hx-target="#editPanel"
						hx-swap="innerHTML"
						hx-vals=\'{"bqkey":"'.$bqkey.'"}\'>'
						.htmlspecialchars((string)$view[$i]).
						'</td>';
					}
				}else {
					// on mouse over for email and sms
					$mouseclick="";
					if(isset($fieldAttrbs['controltype']) && isfoundin($fieldAttrbs['controltype'],"email")){
						$mouseoverphp="do_bq.php?bqkey=".pw_enc("pw=bq_mess_sendmail.php&email=".$view[$i]);
		
						$mouseclick = ' 
						hx-post = "'.$mouseoverphp.'"
						hx-target = "#allpops"
						hx-swap="innerHTML"
						hx-vals="{\'to\':\''.$view[$i].'\'}"
						onclick="showdiv(\'allpops\');setPopAll(600,130,350,500)" tabindex="0" 
						onmouseover="this.title=\'Click to send mail\';this.style.borderBottom=\'2px solid #00f\'"
						onmouseout="this.style.borderBottom=\'\'"';
					}
					
					if(isset($fieldAttrbs['controltype']) && isfoundin($fieldAttrbs['controltype'],"SMS")){
						$mouseoverphp="do_bq.php?bqkey=".pw_enc("pw=bq_mess_wapp_send.php&action=fromeditform&mobile=".$view[$i]);
		
						$mouseclick = ' 
						hx-post = "'.$mouseoverphp.'"
						hx-target = "#allpops"
						hx-swap="innerHTML"
						hx-vals="{\'to\':\''.$view[$i].'\'}"
						onclick="showdiv(\'allpops\');setPopAll(600,130,350,500)" tabindex="0" 
						onmouseover="this.title=\'Click to send message\';this.style.borderBottom=\'2px solid #00f\'"
						onmouseout="this.style.borderBottom=\'\'"';
					}
					// for is a switch 
					if(isset($fieldAttrbs['tags']) && !empty($fieldAttrbs['tags']) && isFoundIn(strtoupper($fieldAttrbs['tags']),"IS A SWITCH")){
						$bqswitch='do_bq.php?bqkey='.pw_enc("pw=bq_pagesetup_utils.php&action=setstatus&table=".$_SESSION['currentpage']['head']['tablename']."&status=".$view[$i]."&id=".$ds['id']).'"';
						$icon='<i class="primary fs-5 bi bi-toggle-off  text-secondary ms-2"
						hx-get='.$bqswitch.'
						hx-target=""
						hx-swap="outerHTML"
						>';
						if($view[$i]=="Active") $icon='<i class=" fs-5 bi bi-toggle-on text-primary ms-2"
						hx-get='.$bqswitch.'
						hx-target=""
						hx-swap="outerHTML"
						>';
						
						
						$str.= '<td 
						class="bq-ellipse" style="text-align:center" '.$mouseclick.'>'.$icon.'
						</i></td>';
	
					}else{
						if($fldtype=='INT' && isFoundIn(strtoupper($fieldAttrbs['tags']),"PERCENTAGE")){
					
							$str .= "<td>
							<div class='percentage-bar'>
							<div class='bar' style='width: ".(int)$view[$i]."%'></div>
							<span class='percentage-label'>".$view[$i]."%</span>
							</div>
							</td>";
						}else{
							$tour="";
							$_SESSION['currentpage']['fields'][$fieldname]['controltype'] = $_SESSION['currentpage']['fields'][$fieldname]['controltype'] ?? '';
							if($_SESSION['currentpage']['fields'][$fieldname]['controltype']=="Email"){
							$tour='data-tour-name="List-email-message"
							data-tour-order="28"
							data-tour-explanation="Onclick of this email, an email can be sent to the user."';
							}
							if($_SESSION['currentpage']['fields'][$fieldname]['controltype']=="SMS"){
							$tour='data-tour-name="List-mobile-message"
							data-tour-order="27"
							data-tour-explanation="Onclick of this mobile number, a whatsapp can be sent to the user."';
							}
							
							$str.= '<td ' . $tour.' class="bq-ellipse" style="'.$align.'" '.$mouseclick.'>'.htmlspecialchars((string)$view[$i]).'</td>';
						}
					}
				}
					
					
			}
		    // Sticky-right actions column (keep same padding model; borders handled globally)
		    $waiter="Please wait..";//wait <i class=\"bi bi-hourglass-split\"></i>";
		    $lastdiv="<td align=middle>..</td>";
			if(empty($_GET['parentid'])){
				//$bqkeyedit2=pw_enc("pw=do_bq_testlist_edit.php&hid=".$ds['id']."&action=edit");
				$lastdiv= '<td class="sticky-right" hx-preserve style="z-index:3;background:#fff;border-left:1px solid #dee2e6;text-align:center; width:1%">';
				$lastdiv.='<div class="d-flex" style="display:inline-flex;align-items:center;justify-content:xcenter;gap:6px;width:100%;height:100%;padding:0">';		            
		        $lastdiv.='<div aria-label="Row menu"
			                	style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;margin:0;cursor:pointer" title="Record actions"
			                	data-tour-name="Record-actions"
			                	data-tour-order="29"
		data-tour-explanation="The record specific actions can vbe performed here"
			                	onclick		= "closeAllPops();showPopAction(this);"
			                	hx-get		= "do_bq.php"
								hx-target	= "#actionPopDiv"
								hx-swap		= "innerHTML"
								hx-vals	= \'{"bqkey":"'.pw_enc("pw=bq_list_table.php&action=actionlinks&id=".$ds['id']).'"}\'>
								<i class="bi bi-caret-down-fill text-secondary" style="font-size:16px;"></i>
	                	  </div>';
		            // child data show as tree view
		          
		         //printr($_SESSION['currentpage']['head']['tags']);
		        if(isfoundin($_SESSION['currentpage']['head']['tags'],'Tree View')){
	                $lastdiv.='<div id="childForms"
	                data-tour-name="List-child-object"
	                data-tour-order="30"
		data-tour-explanation="The various child actions will appear in single page"
				                	title = "Child data"
				                	hx-get		= "do_bq.php"
									hx-target	= "#mainContent"
									hx-swap		= "innerHTML"
									hx-vals	= \'{"bqkey":"'.pw_enc("pw=bq_list_childforms.php&action=actionlinks&hid=".$ds['id']).'"}\'><i  class="bi bi-list-nested" style="font-size:16px;" ></i>
							   </div>';
		        }
		        //For Easy flowa added anjali on 08-12-2025
		       if ($_SESSION['currentpage']['head']['pgid']=="pb_pagehead") {
  			        	$bqkey_phplink = pw_enc("pw=bq_utl_easyflow.php");
	                $lastdiv.='<div id="easyflow"
                	title = "Easy Flow"
                	hx-get		= "do_bq.php"
					hx-target	= "#mainContent"
					hx-swap		= "innerHTML"
					hx-vals	= \'{"bqkey":"'.pw_enc("pw=mod_utils.php&rq1=wffields&hid=".$ds['id']).'"}\'> <i class="bi bi-diagram-3-fill" style="font-size:18px;"></i></div>';

				}

					// show workflows etc
				if(isfoundin(strtoupper($_SESSION['currentpage']['head']['tags']),"HAS TEMPLATES")){
					$lastdiv.='
					<div id="Workflow"
						data-tour-name="List-wofkflow-templates"
						data-tour-order="32"
						data-tour-explanation="If the form has any workflows, or templates are presen, this link appears to proceed with applicable  workflows and templates"
						onclick="closeAllPops();"
						title = "Workflow"
						hx-get		= "do_bq.php"
						hx-target	= "#editPanel"
						hx-swap		= "innerHTML"
						hx-vals	= \'{"bqkey":"'.pw_enc("pw=bq_utils_wflow_form.php&action=actionlinks&rty=wflowtemplates&rowid=".$rowno."&hid=".$ds['id']."&hpg=".$_SESSION['currentpage']['head']['pgid']).'"}\'><i class="text-secondary bi bi-check-circle" style="font-size:16px;"></i>
					</div>';
				}
				if(isfoundin(strtoupper($_SESSION['currentpage']['head']['tags']),"HAS CHECKLISTS")){
					$lastdiv.='<div id="checklistform"
						onclick="closeAllPops();"
	                	title = "Checklist"
	                	hx-get		= "do_bq.php"
						hx-target	= "#editPanel"
						hx-swap		= "innerHTML"
						hx-vals	= \'{"bqkey":"'.pw_enc("pw=bq_list_checklistentry.php&rty=templists&pgid=".$_SESSION['currentpage']['head']['pgid']."&hid=".$ds['id']).'"}\'><i class="bi bi-card-checklist"></i>
					</div>';
					
				}
				//bq_list_checklistentry.php&rty=templists&pgid=_pb_customer
				/*if(isfoundin(strtoupper($_SESSION['currentpage']['head']['tags']),"HAS TEMPLATES")){
					$lastdiv.='<div id="Templates"
						onclick="closeAllPops();"
	                	title = "Templates"
	                	hx-get		= "do_bq.php"
						hx-target	= "#editPanel"
						hx-swap		= "innerHTML"
						hx-vals	= \'{"bqkey":"'.pw_enc("pw=bq_list_templates.php&action=actionlinks&rty=templatelist&rowid=".$rowno."&hid=".$ds['id']."&hpg=".$_SESSION['currentpage']['head']['pgid']).'"}\'><i class="text-secondary bi bi-check-circle" style="font-size:16px;"></i>
					</div>';
				}*/
				/*if(isfoundin(strtoupper($_SESSION['currentpage']['head']['tags']),"Read Only")){
					$lastdiv.='<div 
	                	title = "Read Only"
	                	hx-get		= "do_bq.php"
						hx-target	= "#editPanel"
						hx-swap		= "innerHTML"
						hx-vals	= \'{"bqkey":"'.pw_enc("pw=bq_list_edit.php&rty=readonlytag&action=edit&rowid=".$rowno."&hid=".$ds['id']).'"}\'><i class="text-secondary bi bi-slash-circle" style="font-size:16px;"></i>
					</div>';
				}*/
				// multi attachment link disabled by laxmikanth 08-12-2025 as the link is enabled in the has attachments link
/*				if(isfoundin(strtoupper($_SESSION['currentpage']['head']['tags']),"PHOTO")){
					$lastdiv.='<div id="multiattachments"
						onclick="closeAllPops();"
	                	title = "Multi Attachments"
	                	hx-get		= "do_bq.php"
						hx-target	= "#editPanel"
						hx-swap		= "innerHTML"
						hx-vals	= \'{"bqkey":"'.pw_enc("pw=bq_multiattachments.php&action=attach&hid=".$ds['id']).'"}\'><i class="bi bi-paperclip"></i>
					</div>';
				}*/
				if(isfoundin(strtoupper($_SESSION['currentpage']['head']['tags']),"HAS ATTACHMENTS")){
					$lastdiv.='<div id="attachmentform"
						onclick="closeAllPops();"
	                	title = "Multi Attachments Form"
	                	hx-get		= "do_bq.php"
						hx-target	= "#editPanel"
						hx-swap		= "innerHTML"
						hx-vals	= \'{"bqkey":"'.pw_enc("pw=bq_list_table_le.php&pgid=_pb_attachments&parenttable=".$_SESSION['currentpage']['head']['tablename']."&childtablename=_pb_attachments&parentpgid=".$_SESSION['currentpage']['head']['pgid']."&action=list_le&parentid=".$ds['id']."&hid=".$ds['id']).'"}\'><i class="bi bi-paperclip"></i>
					</div>';
				}
				

				$lastdiv .= getlinelinksnew($ds['id']);
			  	$lastdiv.='</td>';
			} 
		    $str.= $lastdiv.'</tr>';
		    
		    $rowno++;
		}

		return $str;
	}
	// function to get the pagelinks for line links , link as image, legends
	// added by laxmikanth 29-08-2025
	function getlinelinks($id){
		if(!empty($_SESSION['currentpage']['links'])){ // for page links as
			$pgheadinfo = "";
			$pgheadinfo = $_SESSION['currentpage']['head'];
			$links = "";
			$recDs= array();
			$recDs = getValueForPS("selrec * from ".$_SESSION['currentpage']['head']['tablename']." where id=?","s",$id);
			foreach($_SESSION['currentpage']['links'] as $k=>$v){
				$show = "OK";
				if($v['hideon']!=''){
				 	$condition   = replaceDS2message($recDs,$v['hideon']);
				 	if($condition!="") $show=eval("if(".$condition."){return 'OK';}else{return 'No';}");
				}
				$req = "";
				if($v['requests']){
					$v['requests']   = replaceDS2message($recDs,$v['requests']);
					$req = "&".$v['requests'];
				}				
				if($show=="OK"){
					if($v['linktype']=='Link as Image'){
						$links .= "<div title='".$v['caption']."'> ".$v['caption']."</div>";
					}
				}
			}
		}		
		return 	$links ??"";
	}
	
	// this getlinelinksnew is for line links
	function getlinelinksnew($id){
		//$pageLink_id = getValueForPS("selrec id from _pb_pagelinks where pgid=? and role in (".$_SESSION['user_role_comma'].") and licence in (".$_SESSION['licences_comma'].") and status='Active' and linktype not in ('Line Link','List Edit Link Line','Link as Button','Link as Image','Legend') limit 0,1","s",$dsPage['pgid']);
		//and linktype not in ('Line Link','List Edit Link Line','Link as Button','Link as Image','Legend')
		// drop down links
		//$link_sql = "selrec * from _pb_pagelinks where linktype in ('Drop Down','List Edit Link','Separator') and status='Active' and role in (".$_SESSION['user_role_comma'].") and licence in (".$_SESSION['licences_comma'].") ".$collapseAttach."  and pgid=? order by slno";

		if(!empty($_SESSION['currentpage']['links'])){ // for page links as
			$pgheadinfo = "";
			$pgheadinfo = $_SESSION['currentpage']['head'];
			$links = "";$str="";
			$recDs= array();
			$recDs = getValueForPS("selrec * from ".$_SESSION['currentpage']['head']['tablename']." where id=?","s",$id);
			foreach($_SESSION['currentpage']['links'] as $k=>$v){
				if (in_array($v['linktype'], ['Line Link','List Edit Link Line','Link as Image','Legend'])) {
					$show = "OK";
					if($v['hideon']!=''){
					 	$condition   = replaceDS2message($recDs,$v['hideon']);
					 	if($condition!="") $show=eval("if(".$condition."){return 'OK';}else{return 'No';}");
					}
					$req = "";
					if($v['requests']){
						$v['requests']   = replaceDS2message($recDs,$v['requests']);
						$req = "&".$v['requests'];
					}				
					if($show=="OK"){
						if($v['linktype']=='Link as Image'){
							$str .= " ".$v['caption'];
						}else{
							if(isFoundIn($v['url'],"http://") or isFoundIn($v['url'],"https://")){
								$req = replaceDS2message($recData,$v['requests']);
								$str .= " <a href='".$v['url'].$req."' hx-target='#editPanel'>".$v['caption']."</a>".$linkHelp."";
							}else{				
								if(!isFoundIn($v['url'],".php")){
									$url = "";//&parentid=".$_GET['rowid']." //&childtablename=".$childtablename."
									$childtablename ="";
									$childtablename = getValueForPS("selrec tablename from _pb_pagehead where pgid=?","s",$v['url']);
									// some times if links is not accessed it it giving dummy array
									if(!is_array($childtablename)){
										$tx="pw=bq_list_table_le.php&pgid=".$v['url']."&parenttable=".$_SESSION['currentpage']['head']['tablename']."&parentid=".$id."&parentpgid=".$_SESSION['currentpage']['head']['pgid']."&action=list_le&childtablename=".$childtablename."&linkid=".$v['id'];
										$url = pw_enc($tx);
										$icon="<i class='text-secondary bi bi-arrow-right-circle' style='font-size:16px;'></i>";
										if(isFoundIn($v['icon'],"bi-")) $icon="<i class='fs-6 ".$v['icon']."'></i> ";
										$links .= "<div style='cursor:pointer;' class=' ms-1' hx-get='do_bq.php?bqkey=".$url."' hx-target='#editPanel' onclick='waiter(\"editPanel\",\"Please wait..\"); closeAllPops();' hx-swap='innerHTML' title='xxx".$v['caption']."'>".$icon."</div>"; 
									}
								}	
								if(isFoundIn($v['url'],".php")){
									$icon="<i class='text-secondary bi bi-arrow-right-circle' style='font-size:16px;'></i>";
									if(isFoundIn($v['icon'],"bi-")) $icon="<i class='text-secondary ".$v['icon']."' style='font-size:16px;'></i> ";

									$url = "";
									$url = pw_enc("pw=".$v['url']."&action=actionlink&parentpgid=".$pgheadinfo['pgid']."&hid=".$id."&linkid=".$v['id']);
									$links .= "<div  style='cursor:pointer;' class=' ms-1' hx-get='do_bq.php?bqkey=" . $url . "' hx-target='#editPanel'
									hx-swap='innerHTML' onclick='waiter(\"editPanel\",\"Please wait..\"); closeAllPops();' title='".$v['caption']."'>".$icon."</div>";
								}
							}
						}
					}
				}
			}
		}		
		return 	$links ??"";
	}
	// links page links
	function list_actions(){
		$bqfulledit=pw_enc("pw=bq_list_fulledit.php&rty=fulledit&hid=".$_GET['id']."");
		//$auditThread = anc("pw=Qa.php&rty=auditthread&rq1=".$pgDs['tablename']."&id=".$_REQDATA['hid'],img("audit-icon.svg","Audit Thread","width='16'"),"editf");
		$bqaudit=pw_enc("pw=bq_audittrail.php&rty=auditthread&hid=".$_GET['id']."&rq1=".$_SESSION['currentpage']['head']['tablename']."");
		$bqesignatt=pw_enc("pw=bq_utils_esign.php&rty=esignatt&hid=".$_GET['id']."&rq1=".$_SESSION['currentpage']['head']['tablename']."");
		$str ="";
		$str =  "<div style='box-shadow:0 4px 12px rgba(0, 0, 0, 0.3);'>
				  <div id='actionPopDiv' class='bq-linkaction' style='z-index:7000;position:absolute;width:220px;max-height:200px;'>
					<div class='bq-linkaction_title'>
	 					<div class='float-start'><b>Actions</b></div>
	 					<div class='float-end' style='cursor:pointer;margin-top:15px' onclick=\"document.getElementById('actionPopDiv').style.display='none'\"><i class='bi bi-x-lg' role='button' title='Close'></i>
						</div>
					</div>
					<div class='scroll-box'>
				<div style='max-height:200px;overflow:auto;' class='bq-linkaction'>";
			
				
				// <div id='actionPopDiv' class='bq-linkaction_title d-flex align-items-center justify-content-between' style=''>
				// 	<div class='float-start ms-1'>Actions</div>
				// 	<div class='float-end me-1'>
    // 					<i class='bi bi-x-lg text-dark' role='button' style='font-size:1rem;cursor:pointer;' title='Close' onclick=\"document.getElementById('actionPopDiv').style.display='none'\"></i>
    // 				</div>
				// </div>
 		// full edit on admin
 		$str .= "
			<div xxstyle='padding:0px;background: rgba(255, 255, 255, 0.45);backdrop-filter: blur(5px) saturate(140%);-webkit-backdrop-filter: blur(5px) saturate(140%);'>
				<table border='0' sxxtyle='background: rgba(255, 255, 255, 0.45);backdrop-filter: blur(5px) saturate(140%);-webkit-backdrop-filter: blur(5px) saturate(140%);'class='table bq-moreaction'>
					<tr>
						<td>
							<div hx-get='do_bq.php?bqkey=".$bqfulledit."'  hx-target='#editPanel' hx-swap='innerHTML' style='width:20px!important;float:left;'>
								<i class='bi bi-pencil fs-6' style='color:#5c5c5c;cursor:pointer:border;2px solid #000' title='Full Edit'></i>
							</div>
							<div hx-get='do_bq.php?bqkey=".$bqaudit."' hx-target='#allpops' onclick='showdiv(\"allpops\");setPopAll(100,80,600,515);' style='width:20px!important;float:left;'>
									<i class='bi bi-list-task fs-5' style='color:#5c5c5c;cursor:pointer' title='Audit Thread'></i>
							</div>
							<div hx-get='do_bq.php?bqkey=".$bqesignatt."' hx-target='#editPanel' hx-swap='innerHTML' style='width:20px!important;float:left;'>
									<i class='bi bi-file-earmark-text fs-5 ' style='color:#5c5c5c;cursor:pointer' title='Esign'></i>
							</div>
						</td>
					</tr>
				</table>
			</div>";
		$childtablename = [];$url ="";
		if(!empty($_SESSION['currentpage']['links'])){
			$recDs= array();
			$recDs = getValueForPS("selrec * from ".$_SESSION['currentpage']['head']['tablename']." where id=?","s",$_GET['id']);
			$bqdirectphplink ="";
			foreach($_SESSION['currentpage']['links'] as $k=>$v){
				// only 'Drop Down', 'List Edit Link', 'Separator' link types are displayed
            	if (in_array($v['linktype'], ['Drop Down', 'List Edit Link', 'Separator'])) {
					$url = "pw=".$v['url']."&action=actionlink&parentpgid=".$_SESSION['currentpage']['head']['pgid']."&hid=".$_GET['id']."&linkid=".$v['id'];
					if(!isFoundIn($v['url'],'.php')){ // this link if not php, it is a form
						$childtablename = getValueForPS("selrec tablename from _pb_pagehead where pgid=?","s",$v['url']);
						if(!empty($childtablename)){
							$url="pw=bq_list_table_le.php&pgid=".$v['url']."&parenttable=".$_SESSION['currentpage']['head']['tablename']."&parentid=".$_GET['id']."&parentpgid=".$_SESSION['currentpage']['head']['pgid']."&action=list_le&childtablename=".$childtablename."&linkid=".$v['id'];
						}
					}
					$bqkey2=pw_enc($url);
					$_SESSION['edipanelurl']=$bqkey2;
					$show = "OK";
					$debug = [];
					if($v['hideon']!=''){
						$condition   = replaceDS2message($recDs,$v['hideon']);
						if($condition!=""){
							//$show=eval("if(".$condition."){return 'OK';}else{return 'No';}");
							$result = evaluateCondition($condition, $recDs, $debug);
							$show = $result ? 'OK' : 'No';
					 	} 
					}
					$req = "";
					if($v['requests']){
						$v['requests']   = replaceDS2message($recDs,$v['requests']);
						$req = "&".$v['requests'];
					}	
					if($show=="OK"){
						
						// showing icons
						$linktarget = "";
						if($v['target']=='mc'){
							$linktarget = '#mainContent';
						}
						
						
						$icon="<i class=' fs-7 bi bi-circle-fill text-secondary '></i> ";
						if(isFoundIn($v['icon'],"bi-")) $icon="<i class='fs-6 ".$v['icon']."'></i> ";
						if($v['target']=='popf'){
							$str.="<div class='bq-linkaction_form' hx-get='do_bq.php?bqkey=".rawurlencode($bqkey2)."' hx-target='#allpops'  onclick='showdiv(\"allpops\");'>".$icon.htmlspecialchars($v['caption'])."</div>";
						}else{
							$str .= "<div class='bq-linkaction_form' onclick=\"var da = document.getElementById('editPanel');
						     var baseUrl = 'do_bq.php?bqkey=" . rawurlencode($bqkey2) . "';
						    da.setAttribute('hx-get', baseUrl + '&_ts=' + Date.now());
						    da.setAttribute('hx-trigger', 'refresh');     // <-- add this
						    da.setAttribute('hx-target', '".$linktarget."');     // <-- add this
						    da.setAttribute('hx-swap', 'innerHTML');      // <-- ensure wrapper persists
						    htmx.process(da);
						    htmx.trigger(da, 'refresh');
						  setSideWidth('30%')\">".$icon.htmlspecialchars($v['caption'])."</div>";
						}
					}
				}
			}
		}
		$str.= "</div></div></div>";
		return $str;
	}
	
	function getAddForm($pgid){
		//$str="<div class='row m-sm-1'  >";
		$str="<div class='row g-1'>";
		$tabs = "";
		$tabs=getValueForPS("selrec group_concat(distinct tabname  order by field(tabname,'Info','General') desc) from _pb_pagefields 
			where pgid='".$pgid."' and  tabname<>'XXX' and status='Active' order by slno ");
        $tabs = $tabs ??null;
        $tabsArray = explode(",", $tabs ?? "");
		$str.="<div class='d-flex border-bottom p-1 rounded form-label' style='background-color:#e9eaec'>";
		foreach($tabsArray as $ta){
			$tabid = 'tab_'.$ta;
			$str.="<div id='".$tabid."' onclick='showTab(\"$ta\");' style='min-width:100px; text-align:center'  class='edittabs'>".$ta."</div>";
		}    
		$str.="</div>";			
		// add form 
		// echo PAGEHEADDS['pgid'];
		$result = PW_sql2rsPS("selrec * from _pb_pagefields where pgid=? and status=? order by CASE 
        WHEN tabname IN ('Custom Fields', 't2') THEN 1 ELSE 0 END,  tabname  ASC,slno ","ss",$pgid,'Active');
        $colsize=" col-4 ";$i=0;
		while($ds=PW_fetchAssoc($result)){
			$i++;
			$ctrltype=$ds['controltype'];
			$caption=$ds['caption'];
			$fieldname=$ds['fieldname'];
			$tabname=$ds['tabname'];
			$combosql=$ds['combosql'];
			$tags=$ds['tags'];
			$combosql= str_replace("select ","selrec ",$combosql);
			$combosql= str_replace("#39","'",$combosql);
			$ctrldiv="";
			// echo $ctrltype."/ ";
			$numeric="";$pattern="";$required=false;
			if(isfoundin(strtoupper($tags),"IS REQUIRED")) $required=true;
			if(isfoundin(strtoupper($tags),"NUMBERS ONLY")) $numeric=" numeric-only ";
			if(isfoundin(strtoupper($tags),"NUMBERS ONLY")) $pattern=" pattern='[0-9]+(\.[0-9]+)?' inputmode='decimal' ";

			
			$ctrldivPick="";
			if(strtoupper($ctrltype)=="SQL PICKER"){ 
				$ctrldivPick='<div id="controldiv_'.$tabname.'" class="mb-3 position-relative col-3">
		    		<label for="name" class="form-label">p:'.$caption.'</label>
		    		<input type="text" class="form-control form-control-sm" id="name" name="name"
		        	placeholder="Start typingss..." autocomplete="off"
		        	hx-post="do_pluros_sqlpicker.php?id='.$ds['id'].'"
		        	hx-trigger="keyup changed delay:200ms"
		        	hx-target="#pickershow_'.$fieldname.'"
		        	oninput="document.getElementById("autocomplete-results").style.display = "block">
		    		<div style="background-color:#efefef;z-index:1000;width:400px;max-height:200px; xoverflow:auto" id="pickershow_'.$fieldname.'" class="autocomplete-results list-group position-absolute"></div>
					</div>';
			}
		// readonly
			if(strtoupper($ctrltype)=="READ ONLY"){ 

				$ctrldiv=buildBootstrapControl('readonly', $fieldname, $tabname,$caption,$colsize,"",$pattern, $required, 'Enter '.$caption,"","","","");
			}			 
		// Text control
			if(strtoupper($ctrltype)=="TEXT BOX"){ 
				$ctrldiv=buildBootstrapControl('text', $fieldname, $tabname,$caption,$colsize,"",$pattern, $required, 'Enter '.$caption,"","","","");
			}
		// Text control
			if(strtoupper($ctrltype)=="TEXT AREA"){ 
				$ctrldiv=buildBootstrapControl('textarea', $fieldname, $tabname,$caption,$colsize,"",$pattern, $required, 'Enter '.$caption,"","","","");
			}			
			if(strtoupper($ctrltype)=="EMAIL"){ 
				$ctrldiv=buildBootstrapControl('email', $fieldname, $tabname,$caption,$colsize,$numeric,$pattern, $required, 'Enter '.$caption,"","","","");
			}
		//date control
			if(strtoupper($ctrltype)=="DATE NORMAL"){ 
				//	$type, $name,  $caption, $additionalcss, $controlcss, $inputmask,$mandatory = false, $help = '', $placeholder = '', $options = [], $readonly = false, $defaultValue = '', $minDate = '', $maxDate = '') {
				$ctrldiv=buildBootstrapControl('date', $fieldname, $tabname,$caption,$colsize,$numeric,$pattern, $required, 'Enter '.$caption,"","","","",date("Y-m-d"),"2025-03-01","");
			}
			
			
		// Multi select COMBO CONTOL
			if(strtoupper($ctrltype)=="MULTI SELECT"){
				
		// Multi select COMBO SQL
				if(strtoupper(substr($combosql,0,7))=="SELREC " || strtoupper(substr($combosql,0,7))=="SELECT "){
					$rscombo = PW_sql2rsPS($combosql);
					$ctrlArray=[];
					while($dscombo=PW_fetchArray($rscombo)){
						$ctrlArray[$dscombo[0]]=$dscombo[1];
					}
					$ctrldiv=buildBootstrapControl('multi_select', $fieldname, $tabname,$caption,$colsize,"","", $required,"", 'Select '.$caption,$ctrlArray);
				}else{
		// Multi select fast combo
					$ctrlArray = explode(";",$combosql);
					$ctrldiv=buildBootstrapControl('multi_select', $fieldname, $tabname,$caption,$colsize,"","", false, "",'Select '.$caption, $ctrlArray);
				}
			}
			
		// COMBO CONTOL
			if(strtoupper($ctrltype)=="COMBO"){
				
		//COMBO SQL
		
				if(strtoupper(substr($combosql,0,7))=="SELREC " || strtoupper(substr($combosql,0,7))=="SELECT "){
					$rscombo = PW_sql2rsPS($combosql);
					$ctrlArray=[];
					while($dscombo=PW_fetchArray($rscombo)){
						$ctrlArray[$dscombo[0]]=$dscombo[1];
					}
					$ctrldiv=buildBootstrapControl('select', $fieldname, $tabname,$caption,$colsize,"","", $required,"", 'Select '.$caption,$ctrlArray);
				}else{
				// fast combo
						$ctrlArray = explode(";",$combosql);
					$ctrldiv=buildBootstrapControl('select', $fieldname, $tabname,$caption,$colsize,"","", false, "",'Select '.$caption, $ctrlArray);
				}
			}
			if($ctrldivPick!="")$ctrldiv=$ctrldivPick;

			$str.= $ctrldiv;//.$innerdiv."</div>";
		}
		$str.="</div>";
		//echo $str;
		// $str .="<script>showTab('General');</script>";
		echo  $str;
	}

// function detectDateOnly($input) {
//     $input = trim($input);

//     // Allowed separators
//     $seps = ['-', '/', '.', ' '];

//     // Replace all with hyphen
//     $clean = str_replace($seps, '-', $input);

//     // Must have exactly 3 parts
//     $parts = explode('-', $clean);
//     if (count($parts) !== 3) return false;

//     list($p1, $p2, $p3) = $parts;

//     // All parts must be numeric
//     if (!ctype_digit($p1) || !ctype_digit($p2) || !ctype_digit($p3)) return false;

//     // Year must be 4 digits
//     if (strlen($p1) === 4) {           // YYYY-MM-DD
//         $y = $p1; $m = $p2; $d = $p3;
//     } elseif (strlen($p3) === 4) {     // DD-MM-YYYY or MM-DD-YYYY
//         $y = $p3;
//         // Identify DD vs MM
//         if ((int)$p1 > 12) {
//             // DD-MM-YYYY
//             $d = $p1; $m = $p2;
//         } else {
//             // MM-DD-YYYY
//             $m = $p1; $d = $p2;
//         }
//     } else {
//         return false;
//     }

//     // Validate
//     if (!checkdate((int)$m, (int)$d, (int)$y)) return false;

//     // Return normalized
//     return sprintf("%04d-%02d-%02d", $y, $m, $d);
// }
function detectDateOnly($input) {
    $input = trim($input);

    // Allowed separators
    $seps = ['-', '/', '.', ' '];

    // Count occurrences of each separator
    $sepCount = 0;
    foreach ($seps as $s) {
        $sepCount += substr_count($input, $s);
    }

    // Must have EXACTLY 2 separators, otherwise NOT a date
    if ($sepCount !== 2) {
        return $input;    // return original free text
    }

    // Replace all with hyphen
    $clean = str_replace($seps, '-', $input);

    // Must split into exactly 3 parts
    $parts = explode('-', $clean);
    if (count($parts) !== 3) return $input;

    list($p1, $p2, $p3) = $parts;

    // All parts must be numeric
    if (!ctype_digit($p1) || !ctype_digit($p2) || !ctype_digit($p3)) {
        return $input;
    }

    // Detect formats
    if (strlen($p1) === 4) {           // YYYY-MM-DD
        $y = $p1; $m = $p2; $d = $p3;
    } elseif (strlen($p3) === 4) {     // DD-MM-YYYY or MM-DD-YYYY
        $y = $p3;

        // Identify DD vs MM
        if ((int)$p1 > 12) {
            $d = $p1; $m = $p2;        // DD-MM-YYYY
        } else {
            $m = $p1; $d = $p2;        // MM-DD-YYYY
        }
    } else {
        return $input;
    }

    // Validate date
    if (!checkdate((int)$m, (int)$d, (int)$y)) {
        return $input;
    }

    // Return normalized YYYY-MM-DD
    return sprintf("%04d-%02d-%02d", $y, $m, $d);
}
	/** HTMX endpoint: add row and return only the <tr> (no persistence) */
	if ($_SERVER['REQUEST_METHOD']==='POST'
	&& isset($_GET['action']) && $_GET['action']==='add'
	&& isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST']==='true') {
		$ts = date('H:i:s');
		$new = [
			"NEW @ {$ts}",
			"Credit ".(rand(10,99)*1000),
			"9".rand(100000000,999999999),
			"new{$ts}@example.com",
			"TN",
			"Chennai",
			"Person ".rand(1,9),
		];

		header('Content-Type: text/html; charset=UTF-8');
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header('Vary: HX-Request');

		// scroll to top + remove highlight after 1.5s
		echo "<script>
			var scroller = document.getElementById('bqScroller');
			if (scroller) { scroller.scrollTop = 0; }
			setTimeout(function(){
				var firstRow = document.querySelector('#table-main-list tbody tr');
				if(firstRow){
					firstRow.classList.remove('bq-new-row');
				}
			}, 1500);
		</script>";

		echo render_row($new, true); // New record so true
		exit;
	}
		
	function buildBootstrapControl($type, $name,  $tabname,$caption, $additionalcss, $controlcss, $inputmask,$mandatory = false, $help = '', $placeholder = '', $options = [], $readonly = false, $defaultValue = '', $minDate = '', $maxDate = '') {
	    $required = $mandatory ? 'required' : '';
	    $readonlyAttr = $readonly ? 'readonly' : '';
	    $placeholderText = $placeholder ? 'placeholder="' . htmlspecialchars($placeholder) . '"' : '';
	    $defaultValueAttr = $defaultValue ? 'value="' . htmlspecialchars($defaultValue) . '"' : '';
	    $minDateAttr = $minDate ? 'min="' . htmlspecialchars($minDate) . '"' : '';
	    $maxDateAttr = $maxDate ? 'max="' . htmlspecialchars($maxDate) . '"' : '';
	    $mandatoryClass = $mandatory ? 'mandatory-control' : '';
	
	    // Input Mask Classes
	    $inputMaskClass = '';
	    if ($type === 'mobile') {
	        $inputMaskClass = 'mobile-mask';
	    } elseif ($type === 'email') {
	        $inputMaskClass = 'email-mask';
	    }
	
	    // Control wrapper
	    $html = '<div id="controldiv_'.trim($tabname).'" class="mb-3 form-label'.$additionalcss.'" data-tab="'.trim($tabname).'" >';
	    
	    // Label
	    $html .= '<label for="' . htmlspecialchars($name) . '" class="form-label">' . htmlspecialchars($caption);
	    
	    $html .= '</label>';
	
	    // Input Group (if help is available)
	    $html .= '<div class="input-group form-label">';

	    // Input Field
	    $style="";
	    if($mandatory) $style="border-left:4px solid #f00!important";
	    switch ($type) {
	        case 'text':
	        case 'mobile':
	        case 'email':
	            $inputType = ($type === 'email') ? 'email' : 'text';
	            $html .= '<input style="'.$style.'" type="' . $inputType . '" class="form-control form-control-sm '.$controlcss.' '.$inputMaskClass . ' ' . $mandatoryClass . '" '. $controlcss.'  id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" ' . $defaultValueAttr . ' ' . $placeholderText . ' ' . $required . ' ' . $readonlyAttr . ' onfocus="this.select();">';
	            break;
	
	        case 'readonly':
	            $inputType = ($type === 'email') ? 'email' : 'text';
	            $html .= '<input readonly style="'.$style.'" type="' . $inputType . '" class="form-control form-control-sm '.$controlcss.' '.$inputMaskClass . ' ' . $mandatoryClass . '" '. $controlcss.'  id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" ' . $defaultValueAttr . ' ' . $placeholderText . ' ' . $required . ' ' . $readonlyAttr . ' onfocus="this.select();">';
	            break;
	                
	
	        case 'textarea':
	            $html .= 'xxx<textarea class="form-control expandable-textarea form-control-sm ' . $mandatoryClass . '" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" rows="3" ' . $placeholderText . ' ' . $required . ' ' . $readonlyAttr . '>' . htmlspecialchars($defaultValue) . '</textarea>';
	            break;
	
	        case 'date':
	            $html .= '<input style="'.$style.'" type="date" class="form-control ' . $mandatoryClass . '" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" ' . $defaultValueAttr . ' '.$minDateAttr. ' ' . $required . ' ' . $readonlyAttr . '>';
	            break;
	
	        case 'datetime':
	        	
	            $html .= '<input style="'.$style.'" type="datetime-local" class="form-control form-control-sm ' . $mandatoryClass . '" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" ' . $defaultValueAttr . ' ' . $required . ' ' . $readonlyAttr . '>';
	            break;
	
	        case 'multi_select':
	            $html .= '<div class="form-control form-control-sm ' . $mandatoryClass . '" style="overflow:auto;height:100; padding:5px;">';
	            foreach ($options as $key => $value) {
	                $checked = (is_array($defaultValue) && in_array($key, $defaultValue)) ? 'checked' : '';
	                $html .= '<div class="form-check">
	                            <input class="form-check-input" type="checkbox" name="' . htmlspecialchars($name) . '[] multiple" id="' . htmlspecialchars($name . '_' . $key) . '" value="' . htmlspecialchars($key) . '" ' . $checked . '>
	                            <label class="form-check-label" for="' . htmlspecialchars($name . '_' . $key) . '">' . htmlspecialchars($value) . '</label>
	                          </div>';
	            }
	            $html .= '</div>';
	            break;
			case 'select':
		            $html .= '<select style="'.$style.'" class="form-select form-select-sm ' . $mandatoryClass . '" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '"  ' . $required . '>';
		            foreach ($options as $key => $value) {
		            	// hash for security
		            	$hash=substr(md5($key), 0, 10);
		                $selected = (is_array($defaultValue) && in_array($key, $defaultValue)) ? 'selected' : '';
		                $html .= '<option value="' . $hash."::".htmlspecialchars($key) . '" ' . $selected . '>' . htmlspecialchars($value) . '</option>';
		            }
		            $html .= '</select>';
		            break; 
	        case 'submit':
	            $inputType = 'submit';
	            $html .= '<input type="' . $inputType . '" class="form-control ' . $inputMaskClass . ' ' . $mandatoryClass . '" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" ' . $defaultValueAttr . ' ' . $placeholderText . ' ' . $required . ' ' . $readonlyAttr . '>';
	            break;	            
	    }
	
	    // Help Icon
	    if (!empty($help)) {
	        // suspended $html .= '<span class="input-group-text" onclick="showHelp(\'' . htmlspecialchars($help) . '\')"><i class="fas fa-question-circle"></i></span>';
	    }
	
	    $html .= '</div></div>'; 
    	return $html;
	}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<!--<script src="https://unpkg.com/htmx.org@1.9.12"></script>-->

<style>
  html,body{height:100%}
  #bqFixedTable{height:70vh}

  /* Normalize cell sizing so sticky-left/middle/right share same height */
	  #table-main-list th,
	  #table-main-list td{
	    box-sizing:border-box;
	    white-space:nowrap;
	    padding:0 10px;
	    border-bottom:1px solid #dee2e6;
	    border-right:1px solid #dee2e6;
	    vertical-align:middle;
	  }
	
	  /* Row highlight for newly added rows (no Bootstrap alert spacing) */
	  .bq-new-row td{background:#fff8d6}
	
	  /* Sticky helpers */
	  .sticky-left{background:#f8f9fa;position:sticky;left:0;z-index:3;border-right:1px solid #dee2e6;}
	  .sticky-right{position:sticky;right:0;z-index:3;background:#fff;}
	  thead .sticky-left, thead .sticky-right{top:0;z-index:10;background:#f8f9fa;}
	
	  /* Header styles */
	  #bqScroller thead th{
	    position:sticky;top:0;z-index:8;background:#f8f9fa;white-space:nowrap;
	  }
	
	  /* Optional: steady height to avoid sub-pixel jitter */
	  #table-main-list tbody tr{height:32px;transition: background-color 0.2s ease;}
	  
	  /* Row hover effect */
	  #table-main-list tbody tr:hover td {
	    background-color: #f1f5ff;
	    cursor: pointer;
	  }
	  
	  /* Row active (on click, while mouse down) */
#table-main-list tbody tr:active td {
  background-color: #cce5ff !important;
}

/* Optional: focus style if you make rows focusable (tabindex="0") */
#table-main-list tbody tr:focus td {
  outline: none;
  background-color: #80bdff !important;
}
#table-main-list tbody tr.active-row td {
  background-color: #cce5ff !important;
}


</style>
<script>
	document.querySelectorAll('#table-main-list tbody tr').forEach(row => {
  row.addEventListener('click', () => {
    document.querySelectorAll('#table-main-list tbody tr').forEach(r => r.classList.remove('active-row'));
    row.classList.add('active-row');
  });
});
</script>
</head>
<body class="p-2" >

<!-- Table wrapper --> 
<?php $bqkeyList=pw_enc("pw=bq_list_table.php");
/*		hx-get="do_bq.php?bqkey=<?= $bqkeyList ?>"
    	hx-trigger="load"
    	hx-target="#bqScroller"
    	hx-swap="innerHTML"*/
?>
<div id="bqFixedTable" style="position:relative;height:100%;border:0;border-radius:0;overflow:hidden;background:#fff;margin:0;padding:0">
	<div id="bqScroller" style="position:absolute;inset:0;overflow:auto;-webkit-overflow-scrolling:touch;padding:0;margin:0:top">
		<table id="table-main-list" class="table table-striped table-sm table-xsm">
			<?php echo list_table_head(); ?>
		<tbody id="bqRows">
			<?php echo list_table_rows();  
				$_SESSION['activepage']=$_SESSION['currentpage'];
				
			?>
		</tbody>
		</table>
	</div>
</div>
<?php
	$total_records    = $_SESSION['currentpage']['meta']['totalRecords']??0;
	$filtered_records = $_SESSION['currentpage']['meta']['filteredRecords']??0;
	$total_pages      = $_SESSION['currentpage']['meta']['totalPages'] ?? 1;
	//hx-include="[name='txt_s']";
?>
<div id="bqHeaderBar" hx-swap-oob="true">
	<table border='0' class="table-header">
  <tr style='background:#d9d9d9'>
	<td style="width: 30%;height:39px;padding-left:10px;" nowrap
	data-tour-name="List-data-size" 
		data-tour-explanation="The data size of the specific data appear here. With total pages and current page"
data-tour-order="21"

		>
	<!-- total and filtered records -->
	<span style="font-size:13px;color:#444;">
          <b>Total:</b> <?= $total_records ?> &nbsp; | &nbsp;
          <b>Filtered:</b> <?= $filtered_records ?> &nbsp; | &nbsp;
          <b>Pages:</b> <?= $total_pages ?>
	</span>
	</td> 
	<td style="width: 30%;">..</td>
    <td style="width: 30%;">..</td>
    <td style="width: 10%;padding-right:10px;">
      <div class="d-inline-flex align-items-center justify-content-end"
      data-tour-name="List-pagination"
		data-tour-explanation="The data pages can be navigated from here."
data-tour-order="22"

      	>
        <i class="bi bi-skip-start-fill me-1" 
          title="First page"
          hx-get="do_bq.php"
          hx-vals='{"bqkey":"<?php echo pw_enc("pw=bq_list_table.php&pgno=f");?>","action":"search"}'
          hx-trigger="click"
             
          hx-target="#bqScroller"
          onclick="closeAllPops();"></i>

        <i class="bi bi-caret-left-fill me-1"
          title="Previous page"
          hx-get="do_bq.php"
          hx-vals='{"bqkey":"<?php echo pw_enc("pw=bq_list_table.php&pgno=p");?>","action":"search"}'
          hx-trigger="click"
                    
          hx-target="#bqScroller"
          onclick="closeAllPops();"></i> 

        <form hx-get="do_bq.php" hx-target="#bqScroller" hx-trigger="submit"
              onsubmit="closeAllPops(); return true;" class="m-0 p-0">
          <input type="text" name="goto_pgno" class='form-control form-control-sm' style='width:50px;height:30px;border-radius:5px;border:1px solid #ccc;text-align:center;font-size:14px;font-family:arial' id="goto_pgno" placeholder="1">
          <input type="hidden" name="bqkey"
            value="<?php echo pw_enc('pw=bq_list_table.php&rty=list'); ?>">
        </form>

        <i class="bi bi-caret-right-fill ms-1"
          title="Next page"
          hx-get="do_bq.php"
          hx-vals='{"bqkey":"<?php echo pw_enc("pw=bq_list_table.php&pgno=n");?>","action":"search"}'
          hx-trigger="click"
          
          hx-target="#bqScroller"
          onclick="closeAllPops();"></i>

        <i class="bi bi-skip-end-fill ms-1"
          title="Last page"
          hx-get="do_bq.php"
          hx-vals='{"bqkey":"<?php echo pw_enc("pw=bq_list_table.php&pgno=l");?>","action":"search"}'
          hx-trigger="click"
          hx-target="#bqScroller"
          onclick="closeAllPops();"></i>
      </div>
    </td>
  </tr>
</table>  
</div>


<?php
	// if the list is updated using edit frame, clear edit pane;
	if(function_exists('plx_postList')) plx_postList("aaa");
?>
<script>
// if the text in td is more, automatically the title is set
	document.querySelectorAll('td.bq-ellipse').forEach(td => {
	  if (td.scrollWidth > td.clientWidth) {
	    td.title = td.textContent.trim();
	  }
	});

//document.getElementById("pgno").innerHTML=<?php echo "'".($_SESSION['currentpage']['meta']['pgno']+1)."'";?>;
document.getElementById("goto_pgno").value=<?php echo "'".($_SESSION['currentpage']['meta']['pgno']+1)."'";?>;
function bqShowRowMenu(ev,rowno){
  ev.stopPropagation();
  var menu=document.getElementById('bqRowMenu');
  var scroller=document.getElementById('bqScroller');
  var host=document.getElementById('bqFixedTable');

  menu.style.display='block'; menu.style.visibility='hidden';

  var b=ev.currentTarget.getBoundingClientRect();
  var h=host.getBoundingClientRect();
  var s=scroller.getBoundingClientRect();
  var sy=window.scrollY||document.documentElement.scrollTop;

  // distance from host's left to scroller's visible right
  var hostToScrollerRight = (s.right - h.left);
  // scrollbar compensation
  var scrollbarW = scroller.offsetWidth - scroller.clientWidth;

  var top  = (b.bottom + 4) - h.top - sy - 2;
  var left = hostToScrollerRight - menu.offsetWidth - scrollbarW;

  menu.style.left = left + 'px';
  menu.style.top  = top  + 'px';
  menu.style.visibility = 'visible';

  document.addEventListener('click', bqHideRowMenu, {once:true});
  scroller.addEventListener('scroll', bqHideRowMenu, {once:true});
}
function bqHideRowMenu(){document.getElementById('bqRowMenu').style.display='none';}
function showmodal(){
	var modal = new bootstrap.Modal(document.getElementById('popupModal'));
        modal.show();
}

</script>

<?php

?>
</body>
</html>
